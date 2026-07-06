"use client";

import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useMemo, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FormErrorBanner } from "@/components/shared/form-field";
import { ConflictWarnings } from "@/components/conference/conflict-warnings";
import { EventConferenceNav } from "@/components/conference/event-conference-nav";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type {
  EventSessionRecord,
  SessionConflictWarning,
  SessionMutationResponse,
  SpeakerRecord,
  TrackRecord,
} from "@/types/conference";
import { ApiError } from "@/types/api";

export default function EventAgendaPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();
  const [formError, setFormError] = useState<string | null>(null);
  const [warnings, setWarnings] = useState<SessionConflictWarning[]>([]);
  const [trackName, setTrackName] = useState("");
  const [sessionForm, setSessionForm] = useState(emptySessionForm());

  const tracksQuery = useQuery({
    queryKey: ["tracks", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () => apiClient.get<TrackRecord[]>(`/events/${eventId}/tracks`, orgOptions),
  });

  const sessionsQuery = useQuery({
    queryKey: ["sessions", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () =>
      apiClient.get<EventSessionRecord[]>(`/events/${eventId}/sessions`, orgOptions),
  });

  const speakersQuery = useQuery({
    queryKey: ["speakers", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () => apiClient.get<SpeakerRecord[]>(`/events/${eventId}/speakers`, orgOptions),
  });

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: ["tracks", organizationId, eventId] });
    void queryClient.invalidateQueries({ queryKey: ["sessions", organizationId, eventId] });
  };

  const createTrack = useMutation({
    mutationFn: () =>
      apiClient.post<TrackRecord>(
        `/events/${eventId}/tracks`,
        { name: trackName },
        orgOptions,
      ),
    onSuccess: () => {
      setTrackName("");
      setFormError(null);
      invalidate();
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Could not create track."),
  });

  const createSession = useMutation({
    mutationFn: () => {
      const speakers = sessionForm.speakerIds.map((id) => ({ id }));
      return apiClient.post<SessionMutationResponse>(
        `/events/${eventId}/sessions`,
        {
          track_id: Number(sessionForm.trackId),
          title: sessionForm.title,
          description: sessionForm.description || null,
          room: sessionForm.room || null,
          starts_at: toIsoFromLocal(sessionForm.startsAt),
          ends_at: toIsoFromLocal(sessionForm.endsAt),
          capacity: sessionForm.capacity ? Number(sessionForm.capacity) : null,
          is_published: sessionForm.isPublished,
          speakers: speakers.length > 0 ? speakers : undefined,
        },
        orgOptions,
      );
    },
    onSuccess: (data) => {
      setWarnings(data.warnings);
      setSessionForm(emptySessionForm(data.session.track_id));
      setFormError(null);
      invalidate();
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Could not create session."),
  });

  const deleteSession = useMutation({
    mutationFn: (sessionId: number) =>
      apiClient.delete(`/events/${eventId}/sessions/${sessionId}`, orgOptions),
    onSuccess: () => invalidate(),
  });

  const sessionsByTrack = useMemo(() => {
    const map = new Map<number, EventSessionRecord[]>();
    for (const session of sessionsQuery.data ?? []) {
      const list = map.get(session.track_id) ?? [];
      list.push(session);
      map.set(session.track_id, list);
    }
    return map;
  }, [sessionsQuery.data]);

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  const tracks = tracksQuery.data ?? [];
  const speakers = speakersQuery.data ?? [];

  return (
    <div className="space-y-6">
      <div className="space-y-3">
        <h1 className="text-2xl font-semibold">Conference agenda</h1>
        <p className="text-sm text-muted-foreground">
          Build tracks and sessions. Scheduling conflicts are surfaced as warnings.
        </p>
        <EventConferenceNav eventId={eventId} />
      </div>

      {formError ? <FormErrorBanner message={formError} /> : null}
      <ConflictWarnings warnings={warnings} />

      <Card>
        <CardHeader>
          <CardTitle>Tracks</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-wrap gap-2">
            <Input
              placeholder="Track name"
              value={trackName}
              onChange={(e) => setTrackName(e.target.value)}
              className="max-w-xs"
            />
            <Button
              type="button"
              disabled={!trackName.trim() || createTrack.isPending}
              onClick={() => createTrack.mutate()}
            >
              Add track
            </Button>
          </div>
          <div className="flex flex-wrap gap-2">
            {tracks.map((track) => (
              <span
                key={track.id}
                className="rounded-full border border-border px-3 py-1 text-sm"
              >
                {track.name}
              </span>
            ))}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Add session</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-2">
          <select
            className="flex h-10 rounded-md border border-input bg-background px-3 text-sm"
            value={sessionForm.trackId}
            onChange={(e) => setSessionForm((s) => ({ ...s, trackId: e.target.value }))}
          >
            <option value="">Select track</option>
            {tracks.map((track) => (
              <option key={track.id} value={track.id}>
                {track.name}
              </option>
            ))}
          </select>
          <Input
            placeholder="Session title"
            value={sessionForm.title}
            onChange={(e) => setSessionForm((s) => ({ ...s, title: e.target.value }))}
          />
          <Input
            placeholder="Room"
            value={sessionForm.room}
            onChange={(e) => setSessionForm((s) => ({ ...s, room: e.target.value }))}
          />
          <Input
            type="number"
            min={1}
            placeholder="Capacity (optional)"
            value={sessionForm.capacity}
            onChange={(e) => setSessionForm((s) => ({ ...s, capacity: e.target.value }))}
          />
          <Input
            type="datetime-local"
            value={sessionForm.startsAt}
            onChange={(e) => setSessionForm((s) => ({ ...s, startsAt: e.target.value }))}
          />
          <Input
            type="datetime-local"
            value={sessionForm.endsAt}
            onChange={(e) => setSessionForm((s) => ({ ...s, endsAt: e.target.value }))}
          />
          <Textarea
            className="md:col-span-2"
            placeholder="Description"
            value={sessionForm.description}
            onChange={(e) => setSessionForm((s) => ({ ...s, description: e.target.value }))}
          />
          <div className="md:col-span-2">
            <p className="mb-2 text-sm font-medium">Speakers</p>
            <div className="flex flex-wrap gap-2">
              {speakers.map((speaker) => {
                const selected = sessionForm.speakerIds.includes(speaker.id);
                return (
                  <button
                    key={speaker.id}
                    type="button"
                    className={`rounded-md border px-3 py-1 text-sm ${
                      selected
                        ? "border-primary bg-primary/10"
                        : "border-border text-muted-foreground"
                    }`}
                    onClick={() =>
                      setSessionForm((s) => ({
                        ...s,
                        speakerIds: selected
                          ? s.speakerIds.filter((id) => id !== speaker.id)
                          : [...s.speakerIds, speaker.id],
                      }))
                    }
                  >
                    {speaker.name}
                  </button>
                );
              })}
            </div>
          </div>
          <label className="flex items-center gap-2 text-sm md:col-span-2">
            <input
              type="checkbox"
              checked={sessionForm.isPublished}
              onChange={(e) =>
                setSessionForm((s) => ({ ...s, isPublished: e.target.checked }))
              }
            />
            Published on public agenda
          </label>
          <Button
            type="button"
            className="md:col-span-2"
            disabled={
              !sessionForm.trackId ||
              !sessionForm.title ||
              !sessionForm.startsAt ||
              !sessionForm.endsAt ||
              createSession.isPending
            }
            onClick={() => createSession.mutate()}
          >
            Add session
          </Button>
        </CardContent>
      </Card>

      <div className="space-y-4">
        {tracks.map((track) => (
          <Card key={track.id}>
            <CardHeader>
              <CardTitle>{track.name}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {(sessionsByTrack.get(track.id) ?? []).length === 0 ? (
                <p className="text-sm text-muted-foreground">No sessions yet.</p>
              ) : (
                (sessionsByTrack.get(track.id) ?? []).map((session) => (
                  <div
                    key={session.id}
                    className="flex flex-wrap items-start justify-between gap-3 rounded-md border border-border p-3"
                  >
                    <div>
                      <p className="font-medium">{session.title}</p>
                      <p className="text-sm text-muted-foreground">
                        {session.starts_at
                          ? new Date(session.starts_at).toLocaleString()
                          : "—"}
                        {session.ends_at
                          ? ` – ${new Date(session.ends_at).toLocaleString()}`
                          : ""}
                      </p>
                      {session.room ? (
                        <p className="text-sm text-muted-foreground">Room: {session.room}</p>
                      ) : null}
                      {session.speakers && session.speakers.length > 0 ? (
                        <p className="text-sm text-muted-foreground">
                          Speakers: {session.speakers.map((s) => s.name).join(", ")}
                        </p>
                      ) : null}
                      <p className="text-xs text-muted-foreground">
                        {session.is_published ? "Published" : "Draft"}
                        {session.capacity !== null
                          ? ` · ${session.registered_count ?? 0}/${session.capacity} registered`
                          : ""}
                      </p>
                    </div>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      disabled={deleteSession.isPending}
                      onClick={() => deleteSession.mutate(session.id)}
                    >
                      Delete
                    </Button>
                  </div>
                ))
              )}
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}

function emptySessionForm(trackId?: number) {
  return {
    trackId: trackId ? String(trackId) : "",
    title: "",
    description: "",
    room: "",
    startsAt: "",
    endsAt: "",
    capacity: "",
    isPublished: true,
    speakerIds: [] as number[],
  };
}

function toIsoFromLocal(local: string): string {
  return new Date(local).toISOString();
}
