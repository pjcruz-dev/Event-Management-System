"use client";

import Link from "next/link";
import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { PaginatedActivity } from "@/types/analytics";

export default function OrganizationActivityPage() {
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const [action, setAction] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [page, setPage] = useState(1);

  const activityQuery = useQuery({
    queryKey: ["organization-activity", organizationId, action, from, to, page],
    enabled: organizationId !== null,
    queryFn: () => {
      const params = new URLSearchParams({ per_page: "25", page: String(page) });
      if (action.trim()) params.set("action", action.trim());
      if (from) params.set("from", from);
      if (to) params.set("to", to);

      return apiClient.get<PaginatedActivity>(
        `/organization/activity?${params.toString()}`,
        orgOptions,
      );
    },
  });

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  const data = activityQuery.data;

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Activity log</h1>
          <p className="text-sm text-muted-foreground">
            Audit trail across your organization.
          </p>
        </div>
        <Button asChild variant="outline" size="sm">
          <Link href="/organization/analytics">Organization analytics</Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-4">
          <Input
            placeholder="Action (e.g. checkin.scan.success)"
            value={action}
            onChange={(e) => {
              setPage(1);
              setAction(e.target.value);
            }}
          />
          <Input
            type="date"
            value={from}
            onChange={(e) => {
              setPage(1);
              setFrom(e.target.value);
            }}
          />
          <Input
            type="date"
            value={to}
            onChange={(e) => {
              setPage(1);
              setTo(e.target.value);
            }}
          />
        </CardContent>
      </Card>

      <div className="space-y-3">
        {activityQuery.isLoading ? (
          <p className="text-sm text-muted-foreground">Loading activity…</p>
        ) : (data?.items.length ?? 0) === 0 ? (
          <p className="text-sm text-muted-foreground">No activity matches your filters.</p>
        ) : (
          data?.items.map((item) => (
            <Card key={item.id}>
              <CardContent className="space-y-1 p-4 text-sm">
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <p className="font-medium">{item.action}</p>
                  <p className="text-xs text-muted-foreground">
                    {item.created_at ? new Date(item.created_at).toLocaleString() : "—"}
                  </p>
                </div>
                <p className="text-muted-foreground">Actor: {item.actor ?? "System"}</p>
                {item.metadata?.event_id ? (
                  <p className="text-muted-foreground">Event ID: {String(item.metadata.event_id)}</p>
                ) : null}
              </CardContent>
            </Card>
          ))
        )}
      </div>

      {data && data.meta.last_page > 1 ? (
        <div className="flex items-center gap-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={page <= 1}
            onClick={() => setPage((p) => p - 1)}
          >
            Previous
          </Button>
          <span className="text-sm text-muted-foreground">
            Page {data.meta.current_page} of {data.meta.last_page}
          </span>
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={page >= data.meta.last_page}
            onClick={() => setPage((p) => p + 1)}
          >
            Next
          </Button>
        </div>
      ) : null}
    </div>
  );
}
