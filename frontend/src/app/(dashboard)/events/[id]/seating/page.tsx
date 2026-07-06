"use client";

import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FormErrorBanner } from "@/components/shared/form-field";
import { EventGuestNav } from "@/components/rsvp/event-guest-nav";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { EventTableRecord, SeatingPayload } from "@/types/rsvp";
import { ApiError } from "@/types/api";

export default function EventSeatingPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();
  const [formError, setFormError] = useState<string | null>(null);
  const [tableName, setTableName] = useState("");
  const [tableCapacity, setTableCapacity] = useState(8);

  const seatingQuery = useQuery({
    queryKey: ["seating", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () => apiClient.get<SeatingPayload>(`/events/${eventId}/seating`, orgOptions),
  });

  const createTableMutation = useMutation({
    mutationFn: () =>
      apiClient.post<EventTableRecord>(
        `/events/${eventId}/tables`,
        { name: tableName, capacity: tableCapacity },
        orgOptions,
      ),
    onSuccess: () => {
      setTableName("");
      void queryClient.invalidateQueries({ queryKey: ["seating", organizationId, eventId] });
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Could not create table."),
  });

  const assignMutation = useMutation({
    mutationFn: (payload: { guest_invites?: Array<{ id: number; table_id: number | null }>; registrations?: Array<{ id: number; table_id: number | null }> }) =>
      apiClient.put(`/events/${eventId}/seating/assignments`, payload, orgOptions),
    onSuccess: () =>
      void queryClient.invalidateQueries({ queryKey: ["seating", organizationId, eventId] }),
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Assignment failed."),
  });

  const deleteTableMutation = useMutation({
    mutationFn: (tableId: number) =>
      apiClient.delete(`/events/${eventId}/tables/${tableId}`, orgOptions),
    onSuccess: () =>
      void queryClient.invalidateQueries({ queryKey: ["seating", organizationId, eventId] }),
  });

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  const seating = seatingQuery.data;
  const tables = seating?.tables ?? [];

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Seating chart</h1>
          <p className="text-sm text-muted-foreground">Assign guests to tables. Use the list view for accessibility.</p>
        </div>
        <EventGuestNav eventId={eventId} />
      </div>

      {formError ? <FormErrorBanner message={formError} /> : null}

      <Card>
        <CardHeader><CardTitle>Add table</CardTitle></CardHeader>
        <CardContent className="flex flex-wrap gap-3">
          <Input placeholder="Table name" value={tableName} onChange={(e) => setTableName(e.target.value)} />
          <Input
            type="number"
            min={1}
            max={100}
            value={tableCapacity}
            onChange={(e) => setTableCapacity(Number(e.target.value))}
            className="w-28"
          />
          <Button disabled={!tableName || createTableMutation.isPending} onClick={() => createTableMutation.mutate()}>
            Add table
          </Button>
        </CardContent>
      </Card>

      <div className="grid gap-4 lg:grid-cols-2">
        {tables.map((table) => (
          <Card key={table.id}>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="text-base">
                {table.name} ({table.assigned_count}/{table.capacity})
              </CardTitle>
              <Button size="sm" variant="ghost" onClick={() => deleteTableMutation.mutate(table.id)}>
                Delete
              </Button>
            </CardHeader>
            <CardContent className="space-y-2 text-sm">
              {table.assigned_count >= table.capacity ? (
                <p className="text-amber-600 dark:text-amber-400">Table is at capacity.</p>
              ) : null}
            </CardContent>
          </Card>
        ))}
      </div>

      <Card>
        <CardHeader><CardTitle>Unassigned guests</CardTitle></CardHeader>
        <CardContent className="space-y-3">
          {(seating?.unassigned.guest_invites ?? []).map((invite) => (
            <div key={`invite-${invite.id}`} className="flex flex-wrap items-center gap-2">
              <span className="min-w-48 text-sm">{invite.first_name} {invite.last_name} ({invite.email})</span>
              <select
                className="h-9 rounded-md border border-input bg-background px-2 text-sm"
                defaultValue=""
                onChange={(e) => {
                  const tableId = e.target.value ? Number(e.target.value) : null;
                  assignMutation.mutate({ guest_invites: [{ id: invite.id, table_id: tableId }] });
                }}
              >
                <option value="">Unassigned</option>
                {tables.map((table) => (
                  <option key={table.id} value={table.id}>
                    {table.name} ({table.assigned_count}/{table.capacity})
                  </option>
                ))}
              </select>
            </div>
          ))}
          {(seating?.unassigned.registrations ?? []).map((registration) => (
            <div key={`reg-${registration.id}`} className="flex flex-wrap items-center gap-2">
              <span className="min-w-48 text-sm">{registration.attendee_name}</span>
              <select
                className="h-9 rounded-md border border-input bg-background px-2 text-sm"
                defaultValue=""
                onChange={(e) => {
                  const tableId = e.target.value ? Number(e.target.value) : null;
                  assignMutation.mutate({ registrations: [{ id: registration.id, table_id: tableId }] });
                }}
              >
                <option value="">Unassigned</option>
                {tables.map((table) => (
                  <option key={table.id} value={table.id}>
                    {table.name} ({table.assigned_count}/{table.capacity})
                  </option>
                ))}
              </select>
            </div>
          ))}
          {(seating?.unassigned.guest_invites.length ?? 0) === 0 &&
          (seating?.unassigned.registrations.length ?? 0) === 0 ? (
            <p className="text-sm text-muted-foreground">All guests are assigned.</p>
          ) : null}
        </CardContent>
      </Card>
    </div>
  );
}
