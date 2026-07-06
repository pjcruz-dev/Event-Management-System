"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { formatCurrency } from "@/lib/currency";
import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { MetricCard } from "@/components/dashboard/metric-card";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { OrganizationDashboardMetrics } from "@/types/analytics";

export default function OrganizationAnalyticsPage() {
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const metricsCurrency = metricsQuery.data?.totals.currency ?? "USD";

  const metricsQuery = useQuery({
    queryKey: ["organization-analytics", organizationId],
    enabled: organizationId !== null,
    queryFn: () =>
      apiClient.get<OrganizationDashboardMetrics>("/organization/analytics", orgOptions),
    refetchInterval: 60_000,
  });

  const metrics = metricsQuery.data;

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  if (metricsQuery.isLoading || !metrics) {
    return <p className="text-sm text-muted-foreground">Loading organization analytics…</p>;
  }

  const chartData = metrics.events.map((event) => ({
    name: event.name.length > 18 ? `${event.name.slice(0, 18)}…` : event.name,
    revenue: event.total_revenue,
    registrations: event.confirmed_registrations,
  }));

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Organization analytics</h1>
          <p className="text-sm text-muted-foreground">
            Cross-event revenue and attendance rollup.
          </p>
        </div>
        <Button asChild variant="outline" size="sm">
          <Link href="/organization/activity">Activity log</Link>
        </Button>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <MetricCard
          label="Total revenue"
          value={formatCurrency(metrics.totals.total_revenue, metricsCurrency)}
        />
        <MetricCard
          label="Confirmed registrations"
          value={String(metrics.totals.confirmed_registrations)}
        />
        <MetricCard
          label="Checked in"
          value={String(metrics.totals.checked_in_count)}
        />
        <MetricCard label="Events" value={String(metrics.totals.event_count)} />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Revenue by event</CardTitle>
        </CardHeader>
        <CardContent className="h-[320px]">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={chartData}>
              <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
              <XAxis dataKey="name" tick={{ fontSize: 11 }} />
              <YAxis tick={{ fontSize: 12 }} />
              <Tooltip />
              <Bar dataKey="revenue" fill="hsl(var(--primary))" radius={4} />
            </BarChart>
          </ResponsiveContainer>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Events</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {metrics.events.map((event) => (
            <div
              key={event.event_id}
              className="flex flex-wrap items-center justify-between gap-3 rounded-md border border-border p-3"
            >
              <div>
                <p className="font-medium">{event.name}</p>
                <p className="text-sm text-muted-foreground capitalize">
                  {event.status}
                  {event.starts_at
                    ? ` · ${new Date(event.starts_at).toLocaleDateString()}`
                    : ""}
                </p>
                <p className="text-xs text-muted-foreground">
                  {event.confirmed_registrations} registrations ·{" "}
                  {(event.check_in_rate * 100).toFixed(1)}% check-in
                </p>
              </div>
              <Button asChild variant="outline" size="sm">
                <Link href={`/events/${event.event_id}/analytics`}>View details</Link>
              </Button>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  );
}
