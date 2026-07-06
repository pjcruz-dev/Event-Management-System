"use client";

import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { FormErrorBanner, FormField } from "@/components/shared/form-field";
import {
  organizationSchema,
  type OrganizationFormValues,
} from "@/features/auth/schemas";
import { apiClient } from "@/lib/api-client";
import { formatCurrency } from "@/lib/currency";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import { useAuthStore } from "@/stores/auth-store";
import type { AuthOrganization } from "@/types/auth";
import type { OrganizationDashboardMetrics } from "@/types/analytics";
import { ApiError } from "@/types/api";
import { MetricCard } from "@/components/dashboard/metric-card";

export default function DashboardPage() {
  const organizations = useAuthStore((s) => s.organizations);
  const fetchMe = useAuthStore((s) => s.fetchMe);
  const setActiveOrganization = useAuthStore((s) => s.setActiveOrganization);
  const user = useAuthStore((s) => s.user);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const [formError, setFormError] = useState<string | null>(null);

  const orgMetricsQuery = useQuery({
    queryKey: ["organization-analytics", organizationId],
    enabled: organizationId !== null,
    queryFn: () =>
      apiClient.get<OrganizationDashboardMetrics>("/organization/analytics", orgOptions),
  });

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<OrganizationFormValues>({
    resolver: zodResolver(organizationSchema),
    defaultValues: { name: "", slug: "" },
  });

  const onCreateOrganization = async (values: OrganizationFormValues) => {
    setFormError(null);
    try {
      const organization = await apiClient.post<AuthOrganization>(
        "/organizations",
        values,
      );
      await fetchMe();
      setActiveOrganization(organization.id);
      reset();
    } catch (error) {
      setFormError(
        error instanceof ApiError
          ? error.message
          : "Unable to create organization.",
      );
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
        <p className="text-muted-foreground">
          Welcome back{user ? `, ${user.name}` : ""}.
        </p>
      </div>

      {organizations.length === 0 ? (
        <Card>
          <CardHeader>
            <CardTitle>Create your organization</CardTitle>
            <CardDescription>
              Every event belongs to an organization. Create one to get started.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form
              onSubmit={handleSubmit(onCreateOrganization)}
              className="max-w-md space-y-4"
            >
              {formError ? <FormErrorBanner message={formError} /> : null}
              <FormField
                label="Organization name"
                htmlFor="name"
                error={errors.name?.message}
              >
                <Input id="name" {...register("name")} />
              </FormField>
              <FormField
                label="Slug (optional)"
                htmlFor="slug"
                error={errors.slug?.message}
              >
                <Input id="slug" placeholder="acme-events" {...register("slug")} />
              </FormField>
              <Button type="submit" disabled={isSubmitting}>
                {isSubmitting ? "Creating…" : "Create organization"}
              </Button>
            </form>
          </CardContent>
        </Card>
      ) : (
        <>
          {orgMetricsQuery.data ? (
            <div className="space-y-4">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-lg font-semibold">Organization snapshot</h2>
                <Link href="/organization/analytics">
                  <Button variant="outline" size="sm">View analytics</Button>
                </Link>
              </div>
              <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <MetricCard
                  label="Total revenue"
                  value={formatCurrency(orgMetricsQuery.data.totals.total_revenue, orgMetricsQuery.data.totals.currency)}
                />
                <MetricCard
                  label="Registrations"
                  value={String(orgMetricsQuery.data.totals.confirmed_registrations)}
                />
                <MetricCard
                  label="Checked in"
                  value={String(orgMetricsQuery.data.totals.checked_in_count)}
                />
                <MetricCard
                  label="Events"
                  value={String(orgMetricsQuery.data.totals.event_count)}
                />
              </div>
            </div>
          ) : null}

          <Card>
          <CardHeader>
            <CardTitle>Your organizations</CardTitle>
            <CardDescription>
              Switch organizations from the header or manage settings.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {organizations.map((org) => (
              <div
                key={org.id}
                className="flex flex-col justify-between gap-2 rounded-md border border-border p-4 sm:flex-row sm:items-center"
              >
                <div>
                  <p className="font-medium">{org.name}</p>
                  <p className="text-sm text-muted-foreground">
                    Role: {org.role ?? "member"}
                  </p>
                </div>
                <Link href="/settings/organization">
                  <Button variant="outline" size="sm">
                    Manage
                  </Button>
                </Link>
              </div>
            ))}
          </CardContent>
        </Card>
        </>
      )}
    </div>
  );
}
