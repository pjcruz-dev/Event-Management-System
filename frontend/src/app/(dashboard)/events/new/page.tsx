"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { FormErrorBanner, FormField } from "@/components/shared/form-field";
import {
  eventDetailsSchema,
  type EventDetailsFormValues,
} from "@/features/events/schemas";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { EventRecord } from "@/types/event";
import { ApiError } from "@/types/api";

export default function NewEventPage() {
  const router = useRouter();
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<EventDetailsFormValues>({
    resolver: zodResolver(eventDetailsSchema),
    defaultValues: {
      name: "",
      slug: "",
      description: "",
      venue: "",
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC",
      visibility: "private",
    },
  });

  const onSubmit = handleSubmit(async (values) => {
    if (organizationId === null) return;
    setFormError(null);
    try {
      const event = await apiClient.post<EventRecord>(
        "/events",
        {
          ...values,
          slug: values.slug || undefined,
        },
        orgOptions,
      );
      router.push(`/events/${event.id}/edit`);
    } catch (error) {
      setFormError(error instanceof ApiError ? error.message : "Could not create event.");
    }
  });

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Create event</h1>
        <p className="text-sm text-muted-foreground">
          Start with the basics — theme and landing page come next.
        </p>
      </div>
      <Card>
        <CardHeader>
          <CardTitle>Event details</CardTitle>
          <CardDescription>Required information to get started.</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={onSubmit} className="space-y-4">
            {formError ? <FormErrorBanner message={formError} /> : null}
            <FormField label="Name" htmlFor="name" error={errors.name?.message}>
              <Input id="name" {...register("name")} />
            </FormField>
            <FormField label="Slug (optional)" htmlFor="slug" error={errors.slug?.message}>
              <Input id="slug" placeholder="auto-generated if empty" {...register("slug")} />
            </FormField>
            <FormField label="Venue" htmlFor="venue" error={errors.venue?.message}>
              <Input id="venue" {...register("venue")} />
            </FormField>
            <FormField label="Timezone" htmlFor="timezone" error={errors.timezone?.message}>
              <Input id="timezone" {...register("timezone")} />
            </FormField>
            <div className="grid gap-4 sm:grid-cols-2">
              <FormField label="Starts at" htmlFor="starts_at">
                <Input id="starts_at" type="datetime-local" {...register("starts_at")} />
              </FormField>
              <FormField label="Ends at" htmlFor="ends_at">
                <Input id="ends_at" type="datetime-local" {...register("ends_at")} />
              </FormField>
            </div>
            <FormField label="Description" htmlFor="description">
              <Textarea id="description" {...register("description")} />
            </FormField>
            <FormField label="Visibility" htmlFor="visibility">
              <select
                id="visibility"
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                {...register("visibility")}
              >
                <option value="private">Private</option>
                <option value="public">Public</option>
              </select>
            </FormField>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Creating…" : "Create event"}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
