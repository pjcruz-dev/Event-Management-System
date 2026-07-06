"use client";

import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useCallback, useEffect, useMemo, useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { EventDraftPreviewActions } from "@/components/events/event-draft-preview-actions";
import { EventPreview } from "@/components/events/event-preview";
import { EventPreviewViewport } from "@/components/events/event-preview-viewport";
import { EventStatusBadge } from "@/components/events/event-status-badge";
import { LandingPageBuilder } from "@/components/events/landing-page-builder";
import { LifecycleActions } from "@/components/events/lifecycle-actions";
import { ThemeBuilder } from "@/components/events/theme-builder";
import { FormErrorBanner, FormField } from "@/components/shared/form-field";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import type { ThemeConfigFormValues } from "@/features/events/schemas";
import type { ThemeAssetType } from "@/components/events/theme-asset-field";
import {
  defaultLandingPageConfig,
  defaultThemeConfig,
  eventDetailsSchema,
  landingPageConfigSchema,
  themeConfigSchema,
  type EventDetailsFormValues,
  type LandingPageConfigFormValues,
} from "@/features/events/schemas";
import { apiClient, apiUpload } from "@/lib/api-client";
import {
  isEventEditDirty,
  shouldConfirmDiscardChanges,
  type EventEditRsvpSettings,
} from "@/lib/event-edit-dirty";
import type { PreviewViewport } from "@/lib/preview-viewport";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { ConfirmationSettings, EventRecord } from "@/types/event";
import { ApiError } from "@/types/api";

const DEFAULT_CONFIRMATION_SETTINGS: ConfirmationSettings = {
  rsvp_accepted_message: null,
  rsvp_declined_message: null,
  rsvp_maybe_message: null,
  registration_pending_message: null,
  registration_confirmed_message: null,
};

type TabId = "details" | "theme" | "landing" | "settings";

export default function EditEventPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const router = useRouter();
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();
  const [tab, setTab] = useState<TabId>("details");
  const [formError, setFormError] = useState<string | null>(null);
  const [saveMessage, setSaveMessage] = useState<string | null>(null);
  const [themeConfig, setThemeConfig] = useState<ThemeConfigFormValues>(defaultThemeConfig);
  const [landingConfig, setLandingConfig] =
    useState<LandingPageConfigFormValues>(defaultLandingPageConfig);
  const [registrationMode, setRegistrationMode] = useState<"open" | "invite_only" | "rsvp">("open");
  const [rsvpSettings, setRsvpSettings] = useState({
    allow_plus_ones: false,
    max_plus_ones_per_invite: 0,
    collect_meal_preferences: false,
    meal_options: ["Chicken", "Fish", "Vegetarian", "Vegan"] as string[],
    allow_maybe_response: true,
    response_deadline: "",
    auto_send_reminders: false,
    reminder_days_before_deadline: 3,
  });
  const [newMealOption, setNewMealOption] = useState("");
  const [confirmationSettings, setConfirmationSettings] = useState<ConfirmationSettings>(DEFAULT_CONFIRMATION_SETTINGS);
  const [uploadingAssetType, setUploadingAssetType] = useState<ThemeAssetType | null>(null);
  const [removingAssetType, setRemovingAssetType] = useState<ThemeAssetType | null>(null);
  const [themeAssetErrors, setThemeAssetErrors] = useState<Partial<Record<ThemeAssetType, string>>>(
    {},
  );
  const [previewViewport, setPreviewViewport] = useState<PreviewViewport>("desktop");
  const [savedThemeJson, setSavedThemeJson] = useState("");
  const [savedLandingJson, setSavedLandingJson] = useState("");
  const [savedRsvpSettings, setSavedRsvpSettings] = useState<EventEditRsvpSettings>({
    allow_plus_ones: false,
    max_plus_ones_per_invite: 0,
    collect_meal_preferences: false,
    meal_options: ["Chicken", "Fish", "Vegetarian", "Vegan"],
    allow_maybe_response: true,
    response_deadline: "",
    auto_send_reminders: false,
    reminder_days_before_deadline: 3,
  });

  const eventQuery = useQuery({
    queryKey: ["event", organizationId, eventId],
    enabled: organizationId !== null && Number.isFinite(eventId),
    queryFn: () => apiClient.get<EventRecord>(`/events/${eventId}`, orgOptions),
  });

  const event = eventQuery.data;

  const detailsForm = useForm<EventDetailsFormValues>({
    resolver: zodResolver(eventDetailsSchema),
    values: event
      ? {
          name: event.name,
          slug: event.slug,
          description: event.description ?? "",
          venue: event.venue ?? "",
          timezone: event.timezone,
          capacity: event.capacity ?? undefined,
          visibility: event.visibility,
          category: event.category ?? "",
          starts_at: event.starts_at ? toLocalInput(event.starts_at) : "",
          ends_at: event.ends_at ? toLocalInput(event.ends_at) : "",
          custom_domain: event.custom_domain ?? "",
          meta_title: event.meta_title ?? "",
          meta_description: event.meta_description ?? "",
        }
      : undefined,
  });

  useEffect(() => {
    if (!event) return;
    const parsedTheme = themeConfigSchema.parse(event.theme_config);
    const parsedLanding = landingPageConfigSchema.parse(
      event.landing_page_config ?? defaultLandingPageConfig,
    );
    setThemeConfig(parsedTheme);
    setLandingConfig(parsedLanding);
    setSavedThemeJson(JSON.stringify(parsedTheme));
    setSavedLandingJson(JSON.stringify(parsedLanding));
    setRegistrationMode(event.registration_mode ?? "open");
    const nextRsvp: EventEditRsvpSettings = {
      allow_plus_ones: event.rsvp_settings?.allow_plus_ones ?? false,
      max_plus_ones_per_invite: event.rsvp_settings?.max_plus_ones_per_invite ?? 0,
      collect_meal_preferences: event.rsvp_settings?.collect_meal_preferences ?? false,
      meal_options: event.rsvp_settings?.meal_options ?? ["Chicken", "Fish", "Vegetarian", "Vegan"],
      allow_maybe_response: event.rsvp_settings?.allow_maybe_response ?? true,
      response_deadline: event.rsvp_settings?.response_deadline
        ? toLocalInput(event.rsvp_settings.response_deadline)
        : "",
      auto_send_reminders: event.rsvp_settings?.auto_send_reminders ?? false,
      reminder_days_before_deadline: event.rsvp_settings?.reminder_days_before_deadline ?? 3,
    };
    setRsvpSettings(nextRsvp);
    setSavedRsvpSettings(nextRsvp);
    setConfirmationSettings({
      ...DEFAULT_CONFIRMATION_SETTINGS,
      ...(event.confirmation_settings ?? {}),
    });
  }, [event]);

  const invalidate = () => {
    void queryClient.invalidateQueries({ queryKey: ["event", organizationId, eventId] });
    void queryClient.invalidateQueries({ queryKey: ["events", organizationId] });
  };

  const updateDetails = useMutation({
    mutationFn: (values: EventDetailsFormValues) =>
      apiClient.put<EventRecord>(
        `/events/${eventId}`,
        {
          ...values,
          slug: values.slug || undefined,
          capacity: values.capacity,
          category: values.category || null,
        },
        orgOptions,
      ),
    onSuccess: () => {
      setFormError(null);
      invalidate();
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Update failed."),
  });

  const saveSettings = useMutation({
    mutationFn: () =>
      apiClient.put<EventRecord>(
        `/events/${eventId}`,
        {
          visibility: detailsForm.getValues("visibility"),
          capacity: detailsForm.getValues("capacity"),
          timezone: detailsForm.getValues("timezone"),
          category: detailsForm.getValues("category") || null,
          custom_domain: detailsForm.getValues("custom_domain") || null,
          meta_title: detailsForm.getValues("meta_title") || null,
          meta_description: detailsForm.getValues("meta_description") || null,
          registration_mode: registrationMode,
          rsvp_settings: {
            ...rsvpSettings,
            response_deadline: rsvpSettings.response_deadline || null,
          },
          confirmation_settings: confirmationSettings,
        },
        orgOptions,
      ),
    onSuccess: () => {
      setFormError(null);
      setSavedRsvpSettings(rsvpSettings);
      invalidate();
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Update failed."),
  });

  const saveBuilder = useMutation({
    mutationFn: () => {
      const parsedTheme = themeConfigSchema.safeParse(themeConfig);
      if (!parsedTheme.success) {
        throw new ApiError(
          parsedTheme.error.issues[0]?.message ?? "Theme configuration is invalid.",
          422,
        );
      }

      const parsedLanding = landingPageConfigSchema.safeParse(landingConfig);
      if (!parsedLanding.success) {
        throw new ApiError(
          parsedLanding.error.issues[0]?.message ?? "Landing page configuration is invalid.",
          422,
        );
      }

      return apiClient.put<EventRecord>(
        `/events/${eventId}/builder`,
        {
          theme_config: parsedTheme.data,
          landing_page_config: parsedLanding.data,
          meta_title: detailsForm.getValues("meta_title") || null,
          meta_description: detailsForm.getValues("meta_description") || null,
        },
        orgOptions,
      );
    },
    onSuccess: () => {
      setFormError(null);
      setSaveMessage("Landing page and theme saved.");
      setSavedThemeJson(JSON.stringify(themeConfig));
      setSavedLandingJson(JSON.stringify(landingConfig));
      invalidate();
    },
    onError: (error: Error) => {
      setSaveMessage(null);
      setFormError(error instanceof ApiError ? error.message : "Could not save builder.");
    },
  });

  const uploadAsset = useMutation({
    mutationFn: async ({ type, file }: { type: "logo" | "hero" | "hero_video" | "og_image"; file: File }) => {
      const formData = new FormData();
      formData.append("type", type);
      formData.append("file", file);
      return apiUpload<EventRecord>(`/events/${eventId}/assets`, formData, orgOptions);
    },
    onMutate: ({ type }) => {
      if (type === "logo" || type === "hero" || type === "hero_video") {
        setUploadingAssetType(type);
        setThemeAssetErrors((current) => ({ ...current, [type]: undefined }));
      }
    },
    onSuccess: (updated, { type }) => {
      if (type === "logo" || type === "hero" || type === "hero_video") {
        setThemeConfig(themeConfigSchema.parse(updated.theme_config));
      }
      invalidate();
    },
    onError: (error: Error, { type }) => {
      if (type === "logo" || type === "hero" || type === "hero_video") {
        setThemeAssetErrors((current) => ({
          ...current,
          [type]: error instanceof ApiError ? error.message : "Upload failed.",
        }));
      }
    },
    onSettled: (_data, _error, { type }) => {
      if (type === "logo" || type === "hero" || type === "hero_video") {
        setUploadingAssetType(null);
      }
    },
  });

  const deleteThemeAsset = useMutation({
    mutationFn: (type: ThemeAssetType) =>
      apiClient.delete<EventRecord>(`/events/${eventId}/assets/${type}`, orgOptions),
    onMutate: (type) => {
      setRemovingAssetType(type);
      setThemeAssetErrors((current) => ({ ...current, [type]: undefined }));
    },
    onSuccess: (updated) => {
      setThemeConfig(themeConfigSchema.parse(updated.theme_config));
      invalidate();
    },
    onError: (error: Error, type) => {
      setThemeAssetErrors((current) => ({
        ...current,
        [type]: error instanceof ApiError ? error.message : "Could not remove asset.",
      }));
    },
    onSettled: () => {
      setRemovingAssetType(null);
    },
  });

  const publishMutation = useMutation({
    mutationFn: () =>
      apiClient.post<EventRecord>(`/events/${eventId}/publish`, undefined, orgOptions),
    onSuccess: () => invalidate(),
  });

  const archiveMutation = useMutation({
    mutationFn: () =>
      apiClient.post<EventRecord>(`/events/${eventId}/archive`, undefined, orgOptions),
    onSuccess: () => invalidate(),
  });

  const duplicateMutation = useMutation({
    mutationFn: () =>
      apiClient.post<EventRecord>(`/events/${eventId}/duplicate`, undefined, orgOptions),
    onSuccess: (updated) => {
      invalidate();
      router.push(`/events/${updated.id}/edit`);
    },
  });

  const isDirty = useMemo(
    () =>
      isEventEditDirty({
        event,
        detailsDirty: detailsForm.formState.isDirty,
        themeConfig,
        savedThemeJson,
        landingConfig,
        savedLandingJson,
        registrationMode,
        rsvpSettings,
        savedRsvpSettings,
      }),
    [
      event,
      detailsForm.formState.isDirty,
      themeConfig,
      savedThemeJson,
      landingConfig,
      savedLandingJson,
      registrationMode,
      rsvpSettings,
      savedRsvpSettings,
    ],
  );

  const handleTabChange = useCallback(
    (nextTab: TabId) => {
      if (nextTab === tab) return;
      if (!shouldConfirmDiscardChanges(isDirty)) return;
      setTab(nextTab);
    },
    [tab, isDirty],
  );

  useEffect(() => {
    if (!isDirty) return;

    const handleBeforeUnload = (beforeUnloadEvent: BeforeUnloadEvent) => {
      beforeUnloadEvent.preventDefault();
    };

    window.addEventListener("beforeunload", handleBeforeUnload);
    return () => window.removeEventListener("beforeunload", handleBeforeUnload);
  }, [isDirty]);

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  if (eventQuery.isLoading || !event) {
    return <p className="text-sm text-muted-foreground">Loading event…</p>;
  }

  const tabs: { id: TabId; label: string }[] = [
    { id: "details", label: "Details" },
    { id: "theme", label: "Theme" },
    { id: "landing", label: "Landing page" },
    { id: "settings", label: "Settings" },
  ];

  const previewEvent: EventRecord = {
    ...event,
    theme_config: themeConfig,
    landing_page_config: landingConfig,
    name: detailsForm.watch("name") || event.name,
    description: detailsForm.watch("description") || event.description,
    venue: detailsForm.watch("venue") || event.venue,
    starts_at: detailsForm.watch("starts_at")
      ? new Date(detailsForm.watch("starts_at") as string).toISOString()
      : event.starts_at,
  };

  const isPubliclyVisible = event.status === "published" && event.visibility === "public";

  const busy =
    updateDetails.isPending ||
    saveBuilder.isPending ||
    uploadAsset.isPending ||
    deleteThemeAsset.isPending ||
    publishMutation.isPending ||
    archiveMutation.isPending ||
    duplicateMutation.isPending;

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div className="space-y-2">
          <div className="flex items-center gap-2">
            <h1 className="text-2xl font-semibold tracking-tight">{event.name}</h1>
            <EventStatusBadge status={event.status} />
          </div>
          <LifecycleActions
            event={event}
            busy={busy}
            onPublish={() => publishMutation.mutate()}
            onArchive={() => archiveMutation.mutate()}
            onDuplicate={() => duplicateMutation.mutate()}
          />
          <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm">
              <Link href={`/events/${event.id}/tickets`}>Tickets</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href={`/events/${event.id}/form-builder`}>Form builder</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href={`/events/${event.id}/coupons`}>Coupons</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href={`/events/${event.id}/checkin`}>Check-in</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href={`/events/${event.id}/analytics`}>Analytics</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href={`/events/${event.id}/agenda`}>Agenda</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href={`/events/${event.id}/speakers`}>Speakers</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href={`/events/${event.id}/sponsors`}>Sponsors</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href={`/events/${event.id}/exhibitors`}>Exhibitors</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href={`/events/${event.id}/guests`}>Guests</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href={`/events/${event.id}/seating`}>Seating</Link>
            </Button>
            {isPubliclyVisible ? (
              <>
                <Button asChild variant="default" size="sm">
                  <Link href={`/e/${event.slug}`} target="_blank" rel="noopener noreferrer">
                    Public view
                  </Link>
                </Button>
                <Button asChild variant="outline" size="sm">
                  <Link href={`/e/${event.slug}/register`} target="_blank" rel="noopener noreferrer">
                    Public registration
                  </Link>
                </Button>
              </>
            ) : (
              <EventDraftPreviewActions
                eventId={eventId}
                slug={event.slug}
                isPubliclyVisible={isPubliclyVisible}
                orgOptions={orgOptions}
              />
            )}
          </div>
        </div>
      </div>

      <div className="flex flex-wrap gap-2 border-b border-border pb-2">
        {tabs.map((item) => (
          <button
            key={item.id}
            type="button"
            className={`rounded-md px-3 py-2 text-sm font-medium ${
              tab === item.id
                ? "bg-accent text-accent-foreground"
                : "text-muted-foreground hover:bg-accent/50"
            }`}
            onClick={() => handleTabChange(item.id)}
          >
            {item.label}
          </button>
        ))}
      </div>

      {formError ? <FormErrorBanner message={formError} /> : null}
      {saveMessage ? (
        <p className="rounded-md border border-border bg-muted/50 px-4 py-3 text-sm text-foreground">
          {saveMessage}
        </p>
      ) : null}

      <div className="grid gap-6 xl:grid-cols-2">
        <div>
          {tab === "details" ? (
            <Card>
              <CardHeader>
                <CardTitle>Details</CardTitle>
                <CardDescription>Core event information.</CardDescription>
              </CardHeader>
              <CardContent>
                <form
                  className="space-y-4"
                  onSubmit={detailsForm.handleSubmit((values) => updateDetails.mutate(values))}
                >
                  <FormField label="Name" htmlFor="edit-name" error={detailsForm.formState.errors.name?.message}>
                    <Input id="edit-name" {...detailsForm.register("name")} />
                  </FormField>
                  <FormField label="Slug" htmlFor="edit-slug" error={detailsForm.formState.errors.slug?.message}>
                    <Input id="edit-slug" {...detailsForm.register("slug")} />
                  </FormField>
                  <FormField label="Venue" htmlFor="edit-venue">
                    <Input id="edit-venue" {...detailsForm.register("venue")} />
                  </FormField>
                  <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Starts at" htmlFor="edit-starts">
                      <Input id="edit-starts" type="datetime-local" {...detailsForm.register("starts_at")} />
                    </FormField>
                    <FormField label="Ends at" htmlFor="edit-ends">
                      <Input id="edit-ends" type="datetime-local" {...detailsForm.register("ends_at")} />
                    </FormField>
                  </div>
                  <FormField label="Description" htmlFor="edit-description">
                    <Textarea id="edit-description" {...detailsForm.register("description")} />
                  </FormField>
                  <Button type="submit" disabled={busy}>
                    Save details
                  </Button>
                  {isDirty ? <UnsavedChangesBadge /> : null}
                </form>
              </CardContent>
            </Card>
          ) : null}

          {tab === "theme" ? (
            <Card>
              <CardHeader>
                <CardTitle>Theme</CardTitle>
                <CardDescription>Colors, fonts, and imagery.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <ThemeBuilder
                  value={themeConfig}
                  onChange={setThemeConfig}
                  uploadingType={uploadingAssetType}
                  removingType={removingAssetType}
                  assetErrors={themeAssetErrors}
                  onUpload={async (type, file) => {
                    await uploadAsset.mutateAsync({ type, file });
                  }}
                  onRemove={async (type) => {
                    await deleteThemeAsset.mutateAsync(type);
                  }}
                />
                <Button type="button" disabled={busy} onClick={() => saveBuilder.mutate()}>
                  Save theme
                </Button>
                {isDirty ? <UnsavedChangesBadge /> : null}
              </CardContent>
            </Card>
          ) : null}

          {tab === "landing" ? (
            <Card>
              <CardHeader>
                <CardTitle>Landing page</CardTitle>
                <CardDescription>Reorder blocks and edit content.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <LandingPageBuilder value={landingConfig} onChange={setLandingConfig} />
                <Button
                  type="button"
                  disabled={busy}
                  onClick={() => {
                    setSaveMessage(null);
                    saveBuilder.mutate();
                  }}
                >
                  {saveBuilder.isPending ? "Saving…" : "Save landing page"}
                </Button>
                {isDirty ? <UnsavedChangesBadge /> : null}
              </CardContent>
            </Card>
          ) : null}

          {tab === "settings" ? (
            <Card>
              <CardHeader>
                <CardTitle>Settings</CardTitle>
                <CardDescription>Visibility, capacity, SEO, and custom domain.</CardDescription>
              </CardHeader>
              <CardContent>
                <form
                  className="space-y-4"
                  onSubmit={(e) => {
                    e.preventDefault();
                    saveSettings.mutate();
                  }}
                >
                  <FormField label="Category" htmlFor="edit-category">
                    <select
                      id="edit-category"
                      className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                      {...detailsForm.register("category")}
                    >
                      <option value="">Uncategorized</option>
                      <option value="conference">Conference</option>
                      <option value="workshop">Workshop</option>
                      <option value="concert">Concert</option>
                      <option value="meetup">Meetup</option>
                      <option value="webinar">Webinar</option>
                      <option value="festival">Festival</option>
                      <option value="other">Other</option>
                    </select>
                  </FormField>
                  <FormField label="Visibility" htmlFor="edit-visibility">
                    <select
                      id="edit-visibility"
                      className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                      {...detailsForm.register("visibility")}
                    >
                      <option value="private">Private</option>
                      <option value="public">Public</option>
                    </select>
                  </FormField>
                  <FormField label="Registration mode" htmlFor="edit-registration-mode">
                    <select
                      id="edit-registration-mode"
                      className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                      value={registrationMode}
                      onChange={(e) =>
                        setRegistrationMode(e.target.value as "open" | "invite_only" | "rsvp")
                      }
                    >
                      <option value="open">Open registration</option>
                      <option value="invite_only">Invite only</option>
                      <option value="rsvp">RSVP</option>
                    </select>
                  </FormField>
                  {registrationMode !== "open" ? (
                    <div className="space-y-3 rounded-md border border-border p-4">
                      <p className="text-sm font-medium">RSVP settings</p>
                      <label className="flex items-center gap-2 text-sm">
                        <input
                          type="checkbox"
                          checked={rsvpSettings.allow_plus_ones}
                          onChange={(e) =>
                            setRsvpSettings({ ...rsvpSettings, allow_plus_ones: e.target.checked })
                          }
                        />
                        Allow plus ones
                      </label>
                      {rsvpSettings.allow_plus_ones ? (
                        <Input
                          type="number"
                          min={0}
                          max={10}
                          value={rsvpSettings.max_plus_ones_per_invite}
                          onChange={(e) =>
                            setRsvpSettings({
                              ...rsvpSettings,
                              max_plus_ones_per_invite: Number(e.target.value),
                            })
                          }
                          placeholder="Max plus ones per invite"
                        />
                      ) : null}
                      <label className="flex items-center gap-2 text-sm">
                        <input
                          type="checkbox"
                          checked={rsvpSettings.collect_meal_preferences}
                          onChange={(e) =>
                            setRsvpSettings({
                              ...rsvpSettings,
                              collect_meal_preferences: e.target.checked,
                            })
                          }
                        />
                        Collect meal preferences
                      </label>
                      {rsvpSettings.collect_meal_preferences ? (
                        <div className="ml-6 space-y-2 rounded border border-border/60 p-3">
                          <p className="text-xs font-medium text-muted-foreground">Meal options</p>
                          <div className="flex flex-wrap gap-1.5">
                            {rsvpSettings.meal_options.map((opt, i) => (
                              <span
                                key={i}
                                className="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-0.5 text-xs"
                              >
                                {opt}
                                <button
                                  type="button"
                                  className="ml-0.5 text-muted-foreground hover:text-destructive"
                                  onClick={() =>
                                    setRsvpSettings({
                                      ...rsvpSettings,
                                      meal_options: rsvpSettings.meal_options.filter((_, j) => j !== i),
                                    })
                                  }
                                >
                                  &times;
                                </button>
                              </span>
                            ))}
                          </div>
                          <div className="flex gap-2">
                            <Input
                              placeholder="Add option (e.g. Halal)"
                              value={newMealOption}
                              onChange={(e) => setNewMealOption(e.target.value)}
                              onKeyDown={(e) => {
                                if (e.key === "Enter" && newMealOption.trim()) {
                                  e.preventDefault();
                                  setRsvpSettings({
                                    ...rsvpSettings,
                                    meal_options: [...rsvpSettings.meal_options, newMealOption.trim()],
                                  });
                                  setNewMealOption("");
                                }
                              }}
                              className="h-8 text-xs"
                            />
                            <Button
                              type="button"
                              variant="outline"
                              size="sm"
                              className="h-8"
                              disabled={!newMealOption.trim()}
                              onClick={() => {
                                setRsvpSettings({
                                  ...rsvpSettings,
                                  meal_options: [...rsvpSettings.meal_options, newMealOption.trim()],
                                });
                                setNewMealOption("");
                              }}
                            >
                              Add
                            </Button>
                          </div>
                        </div>
                      ) : null}
                      <label className="flex items-center gap-2 text-sm">
                        <input
                          type="checkbox"
                          checked={rsvpSettings.allow_maybe_response}
                          onChange={(e) =>
                            setRsvpSettings({
                              ...rsvpSettings,
                              allow_maybe_response: e.target.checked,
                            })
                          }
                        />
                        Allow Maybe responses
                      </label>
                      <FormField label="Response deadline" htmlFor="rsvp-deadline">
                        <Input
                          id="rsvp-deadline"
                          type="datetime-local"
                          value={rsvpSettings.response_deadline}
                          onChange={(e) =>
                            setRsvpSettings({ ...rsvpSettings, response_deadline: e.target.value })
                          }
                        />
                      </FormField>
                      <label className="flex items-center gap-2 text-sm">
                        <input
                          type="checkbox"
                          checked={rsvpSettings.auto_send_reminders}
                          onChange={(e) =>
                            setRsvpSettings({
                              ...rsvpSettings,
                              auto_send_reminders: e.target.checked,
                            })
                          }
                        />
                        Auto-send reminders before deadline
                      </label>
                      {rsvpSettings.auto_send_reminders ? (
                        <div className="ml-6">
                          <FormField label="Days before deadline" htmlFor="reminder-days">
                            <Input
                              id="reminder-days"
                              type="number"
                              min={1}
                              max={30}
                              value={rsvpSettings.reminder_days_before_deadline}
                              onChange={(e) =>
                                setRsvpSettings({
                                  ...rsvpSettings,
                                  reminder_days_before_deadline: Number(e.target.value),
                                })
                              }
                            />
                          </FormField>
                        </div>
                      ) : null}
                    </div>
                  ) : null}
                  <FormField label="Capacity" htmlFor="edit-capacity">
                    <Input
                      id="edit-capacity"
                      type="number"
                      min={1}
                      {...detailsForm.register("capacity", {
                        setValueAs: (value) =>
                          value === "" || value === undefined ? undefined : Number(value),
                      })}
                    />
                  </FormField>
                  <FormField label="Timezone" htmlFor="edit-timezone">
                    <Input id="edit-timezone" {...detailsForm.register("timezone")} />
                  </FormField>
                  <FormField label="Custom domain" htmlFor="edit-domain">
                    <Input
                      id="edit-domain"
                      placeholder="events.example.com"
                      {...detailsForm.register("custom_domain")}
                    />
                    <p className="mt-1 text-xs text-muted-foreground">
                      DNS verification: {event.custom_domain_verification_status} (full flow in
                      Phase 15)
                    </p>
                  </FormField>
                  <FormField label="Meta title" htmlFor="edit-meta-title">
                    <Input id="edit-meta-title" {...detailsForm.register("meta_title")} />
                  </FormField>
                  <FormField label="Meta description" htmlFor="edit-meta-description">
                    <Textarea id="edit-meta-description" {...detailsForm.register("meta_description")} />
                  </FormField>
                  <FormField label="OG image" htmlFor="edit-og-image">
                    <Input
                      id="edit-og-image"
                      type="file"
                      accept="image/*"
                      disabled={uploadAsset.isPending}
                      onChange={(e) => {
                        const file = e.target.files?.[0];
                        if (file) void uploadAsset.mutateAsync({ type: "og_image", file });
                      }}
                    />
                  </FormField>
                  <div className="space-y-3 rounded-md border border-border p-4">
                    <p className="text-sm font-medium">Confirmation email messages</p>
                    <p className="text-xs text-muted-foreground">
                      Customize the body text for confirmation emails. Leave blank to use defaults.
                    </p>
                    {registrationMode !== "open" ? (
                      <>
                        <FormField label="RSVP Accepted message" htmlFor="confirm-rsvp-accepted">
                          <Textarea
                            id="confirm-rsvp-accepted"
                            rows={2}
                            placeholder="Your attendance has been confirmed."
                            value={confirmationSettings.rsvp_accepted_message ?? ""}
                            onChange={(e) =>
                              setConfirmationSettings({
                                ...confirmationSettings,
                                rsvp_accepted_message: e.target.value || null,
                              })
                            }
                          />
                        </FormField>
                        <FormField label="RSVP Declined message" htmlFor="confirm-rsvp-declined">
                          <Textarea
                            id="confirm-rsvp-declined"
                            rows={2}
                            placeholder="We have recorded that you are unable to attend."
                            value={confirmationSettings.rsvp_declined_message ?? ""}
                            onChange={(e) =>
                              setConfirmationSettings({
                                ...confirmationSettings,
                                rsvp_declined_message: e.target.value || null,
                              })
                            }
                          />
                        </FormField>
                        <FormField label="RSVP Maybe message" htmlFor="confirm-rsvp-maybe">
                          <Textarea
                            id="confirm-rsvp-maybe"
                            rows={2}
                            placeholder="We have recorded your tentative response."
                            value={confirmationSettings.rsvp_maybe_message ?? ""}
                            onChange={(e) =>
                              setConfirmationSettings({
                                ...confirmationSettings,
                                rsvp_maybe_message: e.target.value || null,
                              })
                            }
                          />
                        </FormField>
                      </>
                    ) : null}
                    <FormField label="Registration pending (payment) message" htmlFor="confirm-reg-pending">
                      <Textarea
                        id="confirm-reg-pending"
                        rows={2}
                        placeholder="We have received your registration."
                        value={confirmationSettings.registration_pending_message ?? ""}
                        onChange={(e) =>
                          setConfirmationSettings({
                            ...confirmationSettings,
                            registration_pending_message: e.target.value || null,
                          })
                        }
                      />
                    </FormField>
                    <FormField label="Registration confirmed (ticket issued) message" htmlFor="confirm-reg-confirmed">
                      <Textarea
                        id="confirm-reg-confirmed"
                        rows={2}
                        placeholder="Your registration is confirmed."
                        value={confirmationSettings.registration_confirmed_message ?? ""}
                        onChange={(e) =>
                          setConfirmationSettings({
                            ...confirmationSettings,
                            registration_confirmed_message: e.target.value || null,
                          })
                        }
                      />
                    </FormField>
                  </div>
                  <Button type="submit" disabled={busy || saveSettings.isPending}>
                    Save settings
                  </Button>
                  {isDirty ? <UnsavedChangesBadge /> : null}
                </form>
              </CardContent>
            </Card>
          ) : null}
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Live preview</CardTitle>
            <CardDescription>Updates as you edit theme and landing blocks.</CardDescription>
          </CardHeader>
          <CardContent>
            <EventPreviewViewport viewport={previewViewport} onViewportChange={setPreviewViewport}>
              <EventPreview
                event={previewEvent}
                eventId={eventId}
                orgOptions={orgOptions}
              />
            </EventPreviewViewport>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

function toLocalInput(iso: string): string {
  const date = new Date(iso);
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function UnsavedChangesBadge() {
  return (
    <Badge variant="default" className="ml-2 align-middle border border-border">
      Unsaved changes
    </Badge>
  );
}
