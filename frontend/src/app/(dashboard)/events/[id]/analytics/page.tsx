"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { formatCurrency } from "@/lib/currency";
import {
  Bar,
  BarChart,
  CartesianGrid,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ExportActions } from "@/components/dashboard/export-actions";
import { MetricCard } from "@/components/dashboard/metric-card";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { EventDashboardMetrics } from "@/types/analytics";

export default function EventAnalyticsPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);

  const metricsQuery = useQuery({
    queryKey: ["event-analytics", organizationId, eventId],
    enabled: organizationId !== null && Number.isFinite(eventId),
    queryFn: () => apiClient.get<EventDashboardMetrics>(`/events/${eventId}/analytics`, orgOptions),
    refetchInterval: 60_000,
  });

  const metrics = metricsQuery.data;

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  if (metricsQuery.isLoading || !metrics) {
    return <p className="text-sm text-muted-foreground">Loading analytics…</p>;
  }

  const summary = metrics.summary;
  const checkInPercent = `${(summary.check_in_rate * 100).toFixed(1)}%`;

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Event analytics</h1>
          <p className="text-sm text-muted-foreground">
            Operational metrics refresh about every minute.
          </p>
        </div>
        <Button asChild variant="outline" size="sm">
          <Link href={`/events/${eventId}/edit`}>Back to event</Link>
        </Button>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <MetricCard
          label="Revenue"
          value={formatCurrency(summary.total_revenue, summary.currency)}
        />
        <MetricCard
          label="Confirmed registrations"
          value={String(summary.confirmed_registrations)}
        />
        <MetricCard
          label="Check-in rate"
          value={checkInPercent}
          hint={`${summary.checked_in_count} checked in`}
        />
        <MetricCard
          label="Coupon orders"
          value={String(summary.orders_with_coupon)}
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <ChartCard title="Revenue trend">
          <ResponsiveContainer width="100%" height={260}>
            <LineChart data={metrics.revenue_over_time}>
              <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
              <XAxis dataKey="date" tick={{ fontSize: 12 }} />
              <YAxis tick={{ fontSize: 12 }} />
              <Tooltip />
              <Line type="monotone" dataKey="total" stroke="hsl(var(--primary))" strokeWidth={2} />
            </LineChart>
          </ResponsiveContainer>
        </ChartCard>

        <ChartCard title="Registrations trend">
          <ResponsiveContainer width="100%" height={260}>
            <LineChart data={metrics.registrations_over_time}>
              <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
              <XAxis dataKey="date" tick={{ fontSize: 12 }} />
              <YAxis tick={{ fontSize: 12 }} />
              <Tooltip />
              <Line type="monotone" dataKey="count" stroke="hsl(var(--chart-2, 142 76% 36%))" strokeWidth={2} />
            </LineChart>
          </ResponsiveContainer>
        </ChartCard>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <ChartCard title="Top ticket types">
          <ResponsiveContainer width="100%" height={260}>
            <BarChart data={metrics.top_ticket_types}>
              <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
              <XAxis dataKey="name" tick={{ fontSize: 11 }} />
              <YAxis tick={{ fontSize: 12 }} />
              <Tooltip />
              <Bar dataKey="volume" fill="hsl(var(--primary))" radius={4} />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>

        <Card>
          <CardHeader>
            <CardTitle>Geographic breakdown</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {metrics.geographic_breakdown.length === 0 ? (
              <p className="text-sm text-muted-foreground">No country/city data collected yet.</p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-border text-left text-muted-foreground">
                      <th className="py-2 pr-3">Country</th>
                      <th className="py-2 pr-3">City</th>
                      <th className="py-2">Count</th>
                    </tr>
                  </thead>
                  <tbody>
                    {metrics.geographic_breakdown.map((row) => (
                      <tr key={`${row.country}-${row.city}`} className="border-b border-border/60">
                        <td className="py-2 pr-3">{row.country}</td>
                        <td className="py-2 pr-3">{row.city}</td>
                        <td className="py-2">{row.count}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardContent className="p-6">
          <ExportActions eventId={eventId} orgOptions={orgOptions} />
        </CardContent>
      </Card>

      <p className="text-xs text-muted-foreground lg:hidden">
        Swipe charts horizontally on small screens or open on desktop for full detail.
      </p>
    </div>
  );
}

function ChartCard({
  title,
  children,
}: {
  title: string;
  children: React.ReactNode;
}) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent className="h-[280px]">{children}</CardContent>
    </Card>
  );
}
