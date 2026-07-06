"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { apiClient } from "@/lib/api-client";
import type { EventSessionRecord, PublicAgendaPayload } from "@/types/conference";
import { ApiError } from "@/types/api";

export default function PublicAgendaPage() {
  const params = useParams<{ slug: string }>();
  const slug = params.slug;
  const [trackFilter, setTrackFilter] = useState<string>("all");
  const [dayFilter, setDayFilter] = useState<string>("all");
  const [selectedSession, setSelectedSession] = useState<EventSessionRecord | null>(null);
  const [registrationNumber, setRegistrationNumber] = useState("");
  const [attendeeEmail, setAttendeeEmail] = useState("");
  const [registerMessage, setRegisterMessage] = useState<string | null>(null);
  const [registerError, setRegisterError] = useState<string | null>(null);
  const [isRegistering, setIsRegistering] = useState(false);

  const agendaQuery = useQuery({
    queryKey: ["public-agenda", slug],
    queryFn: () => apiClient.get<PublicAgendaPayload>(`/public/events/${slug}/agenda`),
  });

  const payload = agendaQuery.data;

  const days = useMemo(() => {
    const sessions = payload?.sessions ?? [];
    const set = new Set<string>();
    for (const session of sessions) {
      if (session.starts_at) {
        set.add(session.starts_at.slice(0, 10));
      }
    }
    return [...set].sort();
  }, [payload?.sessions]);

  const filteredSessions = useMemo(() => {
    const sessions = payload?.sessions ?? [];
    return sessions.filter((session) => {
      const trackOk = trackFilter === "all" || String(session.track_id) === trackFilter;
      const dayOk =
        dayFilter === "all" ||
        (session.starts_at ? session.starts_at.slice(0, 10) === dayFilter : false);
      return trackOk && dayOk;
    });
  }, [payload?.sessions, trackFilter, dayFilter]);

  const tracks = payload?.tracks ?? [];

  const registerForSession = async (session: EventSessionRecord) => {
    setRegisterError(null);
    setRegisterMessage(null);
    setIsRegistering(true);
    try {
      await apiClient.post(`/public/events/${slug}/sessions/${session.id}/register`, {
        registration_number: registrationNumber,
        attendee_email: attendeeEmail,
      });
      setRegisterMessage("You are registered for this session.");
      void agendaQuery.refetch();
    } catch (error) {
      setRegisterError(error instanceof ApiError ? error.message : "Registration failed.");
    } finally {
      setIsRegistering(false);
    }
  };

  if (agendaQuery.isLoading) {
    return <p className="mx-auto max-w-4xl p-8 text-sm text-muted-foreground">Loading agenda…</p>;
  }

  if (!payload) {
    return <p className="mx-auto max-w-4xl p-8 text-sm text-muted-foreground">Agenda not found.</p>;
  }

  return (
    <main className="mx-auto max-w-4xl space-y-6 p-8">
      <div>
        <Link href={`/e/${slug}`} className="text-sm text-muted-foreground hover:underline">
          ← {payload.event.name}
        </Link>
        <h1 className="mt-2 text-2xl font-semibold">Conference agenda</h1>
      </div>

      <div className="flex flex-wrap gap-3">
        <select
          className="h-10 rounded-md border border-input bg-background px-3 text-sm"
          value={trackFilter}
          onChange={(e) => setTrackFilter(e.target.value)}
          aria-label="Filter by track"
        >
          <option value="all">All tracks</option>
          {tracks.map((track) => (
            <option key={track.id} value={track.id}>
              {track.name}
            </option>
          ))}
        </select>
        <select
          className="h-10 rounded-md border border-input bg-background px-3 text-sm"
          value={dayFilter}
          onChange={(e) => setDayFilter(e.target.value)}
          aria-label="Filter by day"
        >
          <option value="all">All days</option>
          {days.map((day) => (
            <option key={day} value={day}>
              {day}
            </option>
          ))}
        </select>
      </div>

      <div className="space-y-3">
        {filteredSessions.length === 0 ? (
          <p className="text-sm text-muted-foreground">No sessions match your filters.</p>
        ) : (
          filteredSessions.map((session) => (
            <Card key={session.id}>
              <CardContent className="flex flex-wrap items-start justify-between gap-3 p-4">
                <div>
                  <p className="font-medium">{session.title}</p>
                  <p className="text-sm text-muted-foreground">
                    {session.track?.name ?? "General"}
                    {session.starts_at
                      ? ` · ${new Date(session.starts_at).toLocaleString()}`
                      : ""}
                  </p>
                  {session.room ? (
                    <p className="text-sm text-muted-foreground">Room: {session.room}</p>
                  ) : null}
                  {session.speakers && session.speakers.length > 0 ? (
                    <p className="text-sm text-muted-foreground">
                      {session.speakers.map((s) => s.name).join(", ")}
                    </p>
                  ) : null}
                </div>
                <Button type="button" variant="outline" size="sm" onClick={() => setSelectedSession(session)}>
                  Details
                </Button>
              </CardContent>
            </Card>
          ))
        )}
      </div>

      {payload.sponsors.length > 0 ? (
        <Card>
          <CardHeader>
            <CardTitle>Sponsors</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-wrap gap-2">
            {payload.sponsors.map((sponsor) => (
              <span
                key={sponsor.id}
                className="rounded-md border border-border px-3 py-1 text-sm capitalize"
              >
                {sponsor.name} ({sponsor.tier})
              </span>
            ))}
          </CardContent>
        </Card>
      ) : null}

      {selectedSession ? (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
          role="dialog"
          aria-modal="true"
          aria-labelledby="session-modal-title"
        >
          <Card className="max-h-[90vh] w-full max-w-lg overflow-y-auto">
            <CardHeader>
              <CardTitle id="session-modal-title">{selectedSession.title}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {selectedSession.description ? (
                <p className="text-sm text-muted-foreground">{selectedSession.description}</p>
              ) : null}
              {selectedSession.speakers?.map((speaker) => (
                <div key={speaker.id}>
                  <p className="font-medium">{speaker.name}</p>
                  {speaker.title ? (
                    <p className="text-sm text-muted-foreground">{speaker.title}</p>
                  ) : null}
                  {speaker.bio ? (
                    <p className="text-sm text-muted-foreground">{speaker.bio}</p>
                  ) : null}
                </div>
              ))}

              {selectedSession.capacity !== null ? (
                <div className="space-y-2 border-t border-border pt-4">
                  <p className="text-sm font-medium">Reserve a seat</p>
                  <p className="text-xs text-muted-foreground">
                    {selectedSession.registered_count ?? 0} / {selectedSession.capacity} registered
                  </p>
                  <Input
                    placeholder="Registration number"
                    value={registrationNumber}
                    onChange={(e) => setRegistrationNumber(e.target.value)}
                  />
                  <Input
                    placeholder="Attendee email"
                    type="email"
                    value={attendeeEmail}
                    onChange={(e) => setAttendeeEmail(e.target.value)}
                  />
                  {registerError ? (
                    <p className="text-sm text-destructive">{registerError}</p>
                  ) : null}
                  {registerMessage ? (
                    <p className="text-sm text-green-700 dark:text-green-400">{registerMessage}</p>
                  ) : null}
                  <Button
                    type="button"
                    disabled={
                      isRegistering ||
                      !registrationNumber.trim() ||
                      !attendeeEmail.trim()
                    }
                    onClick={() => void registerForSession(selectedSession)}
                  >
                    Register for session
                  </Button>
                </div>
              ) : null}

              <Button type="button" variant="outline" onClick={() => setSelectedSession(null)}>
                Close
              </Button>
            </CardContent>
          </Card>
        </div>
      ) : null}
    </main>
  );
}
