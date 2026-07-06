"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FormErrorBanner } from "@/components/shared/form-field";
import { EventGuestNav } from "@/components/rsvp/event-guest-nav";
import { apiClient, apiUpload } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { GuestInviteListResponse, GuestInviteRecord } from "@/types/rsvp";
import { ApiError } from "@/types/api";

export default function EventGuestsPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();
  const [formError, setFormError] = useState<string | null>(null);
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [email, setEmail] = useState("");
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [householdName, setHouseholdName] = useState("");
  const [groupLabel, setGroupLabel] = useState("");
  const [householdFilter, setHouseholdFilter] = useState("");

  const guestsQuery = useQuery({
    queryKey: ["guest-invites", organizationId, eventId, search, statusFilter],
    enabled: organizationId !== null,
    queryFn: () => {
      const params = new URLSearchParams();
      if (search) params.set("search", search);
      if (statusFilter) params.set("status", statusFilter);
      const qs = params.toString();
      return apiClient.get<GuestInviteListResponse>(
        `/events/${eventId}/guest-invites${qs ? `?${qs}` : ""}`,
        orgOptions,
      );
    },
  });

  const createMutation = useMutation({
    mutationFn: () =>
      apiClient.post<GuestInviteRecord>(
        `/events/${eventId}/guest-invites`,
        {
          email,
          first_name: firstName,
          last_name: lastName || null,
          household_name: householdName || null,
          group_label: groupLabel || null,
        },
        orgOptions,
      ),
    onSuccess: () => {
      setEmail("");
      setFirstName("");
      setLastName("");
      setHouseholdName("");
      setGroupLabel("");
      setFormError(null);
      void queryClient.invalidateQueries({ queryKey: ["guest-invites", organizationId, eventId] });
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Could not add guest."),
  });

  const sendMutation = useMutation({
    mutationFn: (inviteId: number) =>
      apiClient.post(`/events/${eventId}/guest-invites/${inviteId}/send`, undefined, orgOptions),
    onSuccess: () =>
      void queryClient.invalidateQueries({ queryKey: ["guest-invites", organizationId, eventId] }),
  });

  const bulkSendMutation = useMutation({
    mutationFn: () =>
      apiClient.post(`/events/${eventId}/guest-invites/send-bulk`, undefined, orgOptions),
    onSuccess: () =>
      void queryClient.invalidateQueries({ queryKey: ["guest-invites", organizationId, eventId] }),
  });

  const revokeMutation = useMutation({
    mutationFn: (inviteId: number) =>
      apiClient.delete(`/events/${eventId}/guest-invites/${inviteId}`, orgOptions),
    onSuccess: () =>
      void queryClient.invalidateQueries({ queryKey: ["guest-invites", organizationId, eventId] }),
  });

  const remindMutation = useMutation({
    mutationFn: (inviteId: number) =>
      apiClient.post(`/events/${eventId}/guest-invites/${inviteId}/remind`, undefined, orgOptions),
    onSuccess: () =>
      void queryClient.invalidateQueries({ queryKey: ["guest-invites", organizationId, eventId] }),
  });

  const remindAllMutation = useMutation({
    mutationFn: () =>
      apiClient.post(`/events/${eventId}/guest-invites/remind-all`, undefined, orgOptions),
    onSuccess: () =>
      void queryClient.invalidateQueries({ queryKey: ["guest-invites", organizationId, eventId] }),
  });

  const importMutation = useMutation({
    mutationFn: (file: File) => {
      const formData = new FormData();
      formData.append("file", file);
      return apiUpload(`/events/${eventId}/guest-invites/import`, formData, orgOptions);
    },
    onSuccess: () =>
      void queryClient.invalidateQueries({ queryKey: ["guest-invites", organizationId, eventId] }),
  });

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  const allGuests = guestsQuery.data?.items ?? [];
  const guests = householdFilter
    ? allGuests.filter((g) => g.household_name === householdFilter)
    : allGuests;
  const households = [...new Set(allGuests.map((g) => g.household_name).filter(Boolean))] as string[];

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Guest list</h1>
          <p className="text-sm text-muted-foreground">Manage invitations and RSVP responses.</p>
        </div>
        <EventGuestNav eventId={eventId} />
      </div>

      {formError ? <FormErrorBanner message={formError} /> : null}

      <Card>
        <CardHeader>
          <CardTitle>Add guest</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <div className="grid gap-3 md:grid-cols-3">
            <Input placeholder="Email *" value={email} onChange={(e) => setEmail(e.target.value)} />
            <Input placeholder="First name *" value={firstName} onChange={(e) => setFirstName(e.target.value)} />
            <Input placeholder="Last name" value={lastName} onChange={(e) => setLastName(e.target.value)} />
          </div>
          <div className="grid gap-3 md:grid-cols-3">
            <Input placeholder="Household name" value={householdName} onChange={(e) => setHouseholdName(e.target.value)} />
            <Input placeholder="Group label" value={groupLabel} onChange={(e) => setGroupLabel(e.target.value)} />
            <Button
              disabled={!email || !firstName || createMutation.isPending}
              onClick={() => createMutation.mutate()}
            >
              Add guest
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-3">
          <CardTitle>Guests</CardTitle>
          <div className="flex flex-wrap gap-2">
            <Input
              placeholder="Search name or email"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-48"
            />
            <select
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
            >
              <option value="">All statuses</option>
              <option value="pending">Pending</option>
              <option value="sent">Sent</option>
              <option value="responded">Responded</option>
              <option value="declined">Declined</option>
              <option value="revoked">Revoked</option>
            </select>
            {households.length > 0 ? (
              <select
                className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                value={householdFilter}
                onChange={(e) => setHouseholdFilter(e.target.value)}
              >
                <option value="">All households</option>
                {households.map((h) => (
                  <option key={h} value={h}>{h}</option>
                ))}
              </select>
            ) : null}
            <Button variant="outline" size="sm" onClick={() => bulkSendMutation.mutate()} disabled={bulkSendMutation.isPending}>
              Send all pending
            </Button>
            <Button variant="outline" size="sm" onClick={() => remindAllMutation.mutate()} disabled={remindAllMutation.isPending}>
              Remind non-responders
            </Button>
            <label className="inline-flex cursor-pointer items-center">
              <Button variant="outline" size="sm" asChild>
                <span>Import CSV</span>
              </Button>
              <input
                type="file"
                accept=".csv,text/csv"
                className="hidden"
                onChange={(e) => {
                  const file = e.target.files?.[0];
                  if (file) importMutation.mutate(file);
                }}
              />
            </label>
            <Button asChild variant="outline" size="sm">
              <a href="/templates/guest-import-template.csv" download>
                CSV template
              </a>
            </Button>
          </div>
        </CardHeader>
        <CardContent className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-muted-foreground">
                <th className="py-2 pr-4">Name</th>
                <th className="py-2 pr-4">Email</th>
                <th className="py-2 pr-4">Household</th>
                <th className="py-2 pr-4">Status</th>
                <th className="py-2 pr-4">RSVP</th>
                <th className="py-2 pr-4">Table</th>
                <th className="py-2 pr-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              {guests.map((guest) => {
                const canRemind = guest.rsvp_response === null && ["sent", "opened"].includes(guest.status);
                return (
                  <tr key={guest.id} className="border-b border-border/60">
                    <td className="py-2 pr-4">{guest.first_name} {guest.last_name ?? ""}</td>
                    <td className="py-2 pr-4">{guest.email}</td>
                    <td className="py-2 pr-4 text-xs text-muted-foreground">
                      {guest.household_name ?? "—"}
                      {guest.group_label ? (
                        <span className="ml-1 rounded bg-muted px-1.5 py-0.5 text-[10px]">{guest.group_label}</span>
                      ) : null}
                    </td>
                    <td className="py-2 pr-4 capitalize">{guest.status}</td>
                    <td className="py-2 pr-4 capitalize">{guest.rsvp_response ?? "—"}</td>
                    <td className="py-2 pr-4">{guest.table_name ?? "—"}</td>
                    <td className="py-2 pr-4">
                      <div className="flex gap-2">
                        <Button size="sm" variant="outline" onClick={() => sendMutation.mutate(guest.id)}>
                          Send
                        </Button>
                        {canRemind ? (
                          <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => remindMutation.mutate(guest.id)}
                            disabled={remindMutation.isPending}
                          >
                            Remind{guest.reminder_count > 0 ? ` (${guest.reminder_count})` : ""}
                          </Button>
                        ) : null}
                        <Button size="sm" variant="ghost" onClick={() => revokeMutation.mutate(guest.id)}>
                          Revoke
                        </Button>
                      </div>
                    </td>
                  </tr>
                );
              })}
              {guests.length === 0 ? (
                <tr>
                  <td colSpan={7} className="py-6 text-center text-muted-foreground">
                    No guests yet. Add guests or import a CSV.
                  </td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  );
}
