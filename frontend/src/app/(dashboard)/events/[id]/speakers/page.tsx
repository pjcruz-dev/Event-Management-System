"use client";

import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FormErrorBanner } from "@/components/shared/form-field";
import { EventConferenceNav } from "@/components/conference/event-conference-nav";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { SpeakerRecord } from "@/types/conference";
import { ApiError } from "@/types/api";

export default function EventSpeakersPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();
  const [formError, setFormError] = useState<string | null>(null);
  const [name, setName] = useState("");
  const [title, setTitle] = useState("");
  const [bio, setBio] = useState("");

  const speakersQuery = useQuery({
    queryKey: ["speakers", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () => apiClient.get<SpeakerRecord[]>(`/events/${eventId}/speakers`, orgOptions),
  });

  const createMutation = useMutation({
    mutationFn: () =>
      apiClient.post<SpeakerRecord>(
        `/events/${eventId}/speakers`,
        { name, title: title || null, bio: bio || null },
        orgOptions,
      ),
    onSuccess: () => {
      setName("");
      setTitle("");
      setBio("");
      setFormError(null);
      void queryClient.invalidateQueries({ queryKey: ["speakers", organizationId, eventId] });
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Could not create speaker."),
  });

  const deleteMutation = useMutation({
    mutationFn: (speakerId: number) =>
      apiClient.delete(`/events/${eventId}/speakers/${speakerId}`, orgOptions),
    onSuccess: () =>
      void queryClient.invalidateQueries({ queryKey: ["speakers", organizationId, eventId] }),
  });

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="space-y-3">
        <h1 className="text-2xl font-semibold">Speakers</h1>
        <EventConferenceNav eventId={eventId} />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Add speaker</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {formError ? <FormErrorBanner message={formError} /> : null}
          <Input placeholder="Name" value={name} onChange={(e) => setName(e.target.value)} />
          <Input placeholder="Title" value={title} onChange={(e) => setTitle(e.target.value)} />
          <Textarea placeholder="Bio" value={bio} onChange={(e) => setBio(e.target.value)} />
          <Button
            type="button"
            disabled={!name.trim() || createMutation.isPending}
            onClick={() => createMutation.mutate()}
          >
            Add speaker
          </Button>
        </CardContent>
      </Card>

      <div className="space-y-3">
        {speakersQuery.data?.map((speaker) => (
          <Card key={speaker.id}>
            <CardContent className="flex flex-wrap items-start justify-between gap-3 p-4">
              <div>
                <p className="font-medium">{speaker.name}</p>
                {speaker.title ? (
                  <p className="text-sm text-muted-foreground">{speaker.title}</p>
                ) : null}
                {speaker.bio ? (
                  <p className="mt-1 text-sm text-muted-foreground">{speaker.bio}</p>
                ) : null}
              </div>
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={deleteMutation.isPending}
                onClick={() => deleteMutation.mutate(speaker.id)}
              >
                Delete
              </Button>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
