"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { useMemo, useState } from "react";
import { EventStatusBadge } from "@/components/events/event-status-badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { EventRecord, PaginatedEvents } from "@/types/event";

export default function EventsPage() {
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");

  const queryString = useMemo(() => {
    const params = new URLSearchParams();
    if (search.trim()) params.set("search", search.trim());
    if (status) params.set("status", status);
    const qs = params.toString();
    return qs ? `?${qs}` : "";
  }, [search, status]);

  const eventsQuery = useQuery({
    queryKey: ["events", organizationId, search, status],
    enabled: organizationId !== null,
    queryFn: () =>
      apiClient.get<PaginatedEvents>(`/events${queryString}`, orgOptions),
  });

  if (organizationId === null) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Events</CardTitle>
          <CardDescription>Select an organization to manage events.</CardDescription>
        </CardHeader>
      </Card>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Events</h1>
          <p className="text-sm text-muted-foreground">
            Create, customize, and publish events for your organization.
          </p>
        </div>
        <Button asChild>
          <Link href="/events/new">Create event</Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2">
          <Input
            placeholder="Search by name or venue"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
          <select
            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            value={status}
            onChange={(e) => setStatus(e.target.value)}
          >
            <option value="">All statuses</option>
            <option value="draft">Draft</option>
            <option value="published">Published</option>
            <option value="archived">Archived</option>
          </select>
        </CardContent>
      </Card>

      <div className="space-y-3">
        {eventsQuery.isLoading ? (
          <p className="text-sm text-muted-foreground">Loading events…</p>
        ) : null}
        {eventsQuery.data?.items.map((event: EventRecord) => (
          <Card key={event.id}>
            <CardContent className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
              <div className="space-y-1">
                <div className="flex items-center gap-2">
                  <Link
                    href={`/events/${event.id}/edit`}
                    className="font-medium hover:underline"
                  >
                    {event.name}
                  </Link>
                  <EventStatusBadge status={event.status} />
                </div>
                <p className="text-sm text-muted-foreground">
                  {event.venue ?? "No venue"}
                  {event.starts_at
                    ? ` · ${new Date(event.starts_at).toLocaleString()}`
                    : ""}
                </p>
              </div>
              <Button asChild variant="outline" size="sm">
                <Link href={`/events/${event.id}/edit`}>Edit</Link>
              </Button>
            </CardContent>
          </Card>
        ))}
        {eventsQuery.data?.items.length === 0 && !eventsQuery.isLoading ? (
          <p className="text-sm text-muted-foreground">No events found.</p>
        ) : null}
      </div>
    </div>
  );
}
