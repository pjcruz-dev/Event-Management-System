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
import type { ExhibitorRecord, InviteContactResponse } from "@/types/conference";
import { ApiError } from "@/types/api";

export default function EventExhibitorsPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();
  const [formError, setFormError] = useState<string | null>(null);
  const [inviteMessage, setInviteMessage] = useState<string | null>(null);
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [boothCode, setBoothCode] = useState("");
  const [boothLocation, setBoothLocation] = useState("");
  const [inviteExhibitorId, setInviteExhibitorId] = useState<number | null>(null);
  const [contactName, setContactName] = useState("");
  const [contactEmail, setContactEmail] = useState("");

  const exhibitorsQuery = useQuery({
    queryKey: ["exhibitors", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () =>
      apiClient.get<ExhibitorRecord[]>(`/events/${eventId}/exhibitors`, orgOptions),
  });

  const createMutation = useMutation({
    mutationFn: () =>
      apiClient.post<ExhibitorRecord>(
        `/events/${eventId}/exhibitors`,
        {
          name,
          description: description || null,
          booth_code: boothCode || null,
          booth_location: boothLocation || null,
        },
        orgOptions,
      ),
    onSuccess: () => {
      setName("");
      setDescription("");
      setBoothCode("");
      setBoothLocation("");
      setFormError(null);
      void queryClient.invalidateQueries({ queryKey: ["exhibitors", organizationId, eventId] });
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Could not create exhibitor."),
  });

  const deleteMutation = useMutation({
    mutationFn: (exhibitorId: number) =>
      apiClient.delete(`/events/${eventId}/exhibitors/${exhibitorId}`, orgOptions),
    onSuccess: () =>
      void queryClient.invalidateQueries({ queryKey: ["exhibitors", organizationId, eventId] }),
  });

  const inviteMutation = useMutation({
    mutationFn: (exhibitorId: number) =>
      apiClient.post<InviteContactResponse>(
        `/events/${eventId}/exhibitors/${exhibitorId}/invite-contact`,
        { name: contactName, email: contactEmail },
        orgOptions,
      ),
    onSuccess: (data) => {
      setInviteMessage(
        data.temporary_password
          ? `Portal account created. Temporary password: ${data.temporary_password}`
          : "Portal account created or updated.",
      );
      setContactName("");
      setContactEmail("");
      setInviteExhibitorId(null);
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Could not invite contact."),
  });

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="space-y-3">
        <h1 className="text-2xl font-semibold">Exhibitors</h1>
        <p className="text-sm text-muted-foreground">
          Manage booths and invite exhibitor portal contacts.
        </p>
        <EventConferenceNav eventId={eventId} />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Add exhibitor</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {formError ? <FormErrorBanner message={formError} /> : null}
          {inviteMessage ? (
            <p className="rounded-md border border-border bg-muted/50 p-3 text-sm">{inviteMessage}</p>
          ) : null}
          <Input placeholder="Company name" value={name} onChange={(e) => setName(e.target.value)} />
          <Textarea
            placeholder="Description"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
          />
          <div className="grid gap-3 sm:grid-cols-2">
            <Input
              placeholder="Booth code"
              value={boothCode}
              onChange={(e) => setBoothCode(e.target.value)}
            />
            <Input
              placeholder="Booth location"
              value={boothLocation}
              onChange={(e) => setBoothLocation(e.target.value)}
            />
          </div>
          <Button
            type="button"
            disabled={!name.trim() || createMutation.isPending}
            onClick={() => createMutation.mutate()}
          >
            Add exhibitor
          </Button>
        </CardContent>
      </Card>

      <div className="space-y-3">
        {exhibitorsQuery.data?.map((exhibitor) => (
          <Card key={exhibitor.id}>
            <CardContent className="space-y-3 p-4">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <p className="font-medium">{exhibitor.name}</p>
                  {exhibitor.booth ? (
                    <p className="text-sm text-muted-foreground">
                      Booth {exhibitor.booth.code}
                      {exhibitor.booth.location ? ` · ${exhibitor.booth.location}` : ""}
                    </p>
                  ) : null}
                  <p className="text-xs text-muted-foreground">
                    {exhibitor.leads_count ?? 0} leads captured
                  </p>
                </div>
                <div className="flex gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      setInviteExhibitorId(
                        inviteExhibitorId === exhibitor.id ? null : exhibitor.id,
                      );
                      setInviteMessage(null);
                    }}
                  >
                    Invite contact
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={deleteMutation.isPending}
                    onClick={() => deleteMutation.mutate(exhibitor.id)}
                  >
                    Delete
                  </Button>
                </div>
              </div>

              {inviteExhibitorId === exhibitor.id ? (
                <div className="grid gap-2 border-t border-border pt-3 sm:grid-cols-3">
                  <Input
                    placeholder="Contact name"
                    value={contactName}
                    onChange={(e) => setContactName(e.target.value)}
                  />
                  <Input
                    placeholder="Contact email"
                    type="email"
                    value={contactEmail}
                    onChange={(e) => setContactEmail(e.target.value)}
                  />
                  <Button
                    type="button"
                    disabled={
                      !contactName.trim() ||
                      !contactEmail.trim() ||
                      inviteMutation.isPending
                    }
                    onClick={() => inviteMutation.mutate(exhibitor.id)}
                  >
                    Send invite
                  </Button>
                </div>
              ) : null}
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
