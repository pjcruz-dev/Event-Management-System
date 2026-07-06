import { describe, expect, it } from "vitest";
import { isEventEditDirty, shouldConfirmDiscardChanges } from "@/lib/event-edit-dirty";
import type { EventRecord } from "@/types/event";
import { defaultLandingPageConfig, defaultThemeConfig } from "@/features/events/schemas";

function sampleEvent(overrides: Partial<EventRecord> = {}): EventRecord {
  return {
    id: 1,
    organization_id: 1,
    name: "Sample",
    slug: "sample",
    description: "",
    venue: "Hall",
    timezone: "UTC",
    capacity: null,
    status: "draft",
    visibility: "private",
    registration_mode: "open",
    rsvp_settings: {
      allow_plus_ones: false,
      max_plus_ones_per_invite: 0,
      collect_meal_preferences: false,
      allow_maybe_response: true,
      response_deadline: null,
    },
    category: null,
    theme_config: defaultThemeConfig,
    landing_page_config: defaultLandingPageConfig,
    custom_domain: null,
    custom_domain_verification_status: "unverified",
    meta_title: null,
    meta_description: null,
    og_image_url: null,
    starts_at: "2026-07-06T00:00:00Z",
    ends_at: null,
    published_at: null,
    created_at: null,
    updated_at: null,
    ...overrides,
  };
}


describe("isEventEditDirty", () => {
  it("returns true when theme config changed", () => {
    const event = sampleEvent();
    const dirty = isEventEditDirty({
      event,
      detailsDirty: false,
      themeConfig: { ...defaultThemeConfig, primary_color: "#000000" },
      savedThemeJson: JSON.stringify(defaultThemeConfig),
      landingConfig: defaultLandingPageConfig,
      savedLandingJson: JSON.stringify(defaultLandingPageConfig),
      registrationMode: "open",
      rsvpSettings: {
        allow_plus_ones: false,
        max_plus_ones_per_invite: 0,
        collect_meal_preferences: false,
        allow_maybe_response: true,
        response_deadline: "",
      },
      savedRsvpSettings: {
        allow_plus_ones: false,
        max_plus_ones_per_invite: 0,
        collect_meal_preferences: false,
        allow_maybe_response: true,
        response_deadline: "",
      },
    });

    expect(dirty).toBe(true);
  });

  it("returns false when nothing changed", () => {
    const event = sampleEvent();
    const dirty = isEventEditDirty({
      event,
      detailsDirty: false,
      themeConfig: defaultThemeConfig,
      savedThemeJson: JSON.stringify(defaultThemeConfig),
      landingConfig: defaultLandingPageConfig,
      savedLandingJson: JSON.stringify(defaultLandingPageConfig),
      registrationMode: "open",
      rsvpSettings: {
        allow_plus_ones: false,
        max_plus_ones_per_invite: 0,
        collect_meal_preferences: false,
        allow_maybe_response: true,
        response_deadline: "",
      },
      savedRsvpSettings: {
        allow_plus_ones: false,
        max_plus_ones_per_invite: 0,
        collect_meal_preferences: false,
        allow_maybe_response: true,
        response_deadline: "",
      },
    });

    expect(dirty).toBe(false);
  });
});

describe("shouldConfirmDiscardChanges", () => {
  it("returns true when not dirty", () => {
    expect(shouldConfirmDiscardChanges(false)).toBe(true);
  });
});
