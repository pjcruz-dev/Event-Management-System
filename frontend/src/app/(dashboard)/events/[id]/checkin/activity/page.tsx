"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { CheckInActivityItem } from "@/types/checkin";

interface PaginatedActivity {
  items: CheckInActivityItem[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export default function EventCheckInActivityPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);

  const activityQuery = useQuery({
    queryKey: ["checkin-activity", organizationId, eventId],
    enabled: organizationId !== null,
    refetchInterval: 10000,
    queryFn: () =>
      apiClient.get<PaginatedActivity>(`/events/${eventId}/checkin/activity`, orgOptions),
  });

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">Check-in activity</h1>
        <Button asChild variant="outline" size="sm">
          <Link href={`/events/${eventId}/checkin`}>Back to scanner</Link>
        </Button>
      </div>

      <div className="space-y-3">
        {activityQuery.isLoading ? (
          <p className="text-sm text-muted-foreground">Loading activity…</p>
        ) : null}
        {activityQuery.data?.items.map((item) => (
          <Card key={item.id}>
            <CardHeader className="pb-2">
              <CardTitle className="text-base">{item.action}</CardTitle>
            </CardHeader>
            <CardContent className="text-sm text-muted-foreground">
              <p>{item.actor ?? "System"} · {item.created_at ? new Date(item.created_at).toLocaleString() : ""}</p>
              {item.metadata?.gate ? <p>Gate: {String(item.metadata.gate)}</p> : null}
            </CardContent>
          </Card>
        ))}
        {activityQuery.data?.items.length === 0 && !activityQuery.isLoading ? (
          <p className="text-sm text-muted-foreground">No check-in activity yet.</p>
        ) : null}
      </div>
    </div>
  );
}
