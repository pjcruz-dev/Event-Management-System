import { describe, expect, it } from "vitest";
import {
  getLayoutRootClassName,
  getLayoutVariantDataAttribute,
  resolveHeroHeadline,
} from "@/lib/event-layout-variant";
import type { EventRecord, LandingBlock } from "@/types/event";

function sampleEvent(overrides: Partial<EventRecord> = {}): EventRecord {
  return {
    id: 1,
    organization_id: 1,
    name: "Wedding Event",
    slug: "wedding-event",
    description: "A celebration",
    venue: "Tokyo",
    timezone: "Asia/Tokyo",
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
    theme_config: {
      primary_color: "#961eae",
      secondary_color: "#F59E0B",
      font: "Inter",
      layout_variant: "classic",
    },
    landing_page_config: { blocks: [] },
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

describe("getLayoutVariantDataAttribute", () => {
  it.each(["classic", "minimal", "bold"] as const)("applies data-layout-variant for %s", (variant) => {
    expect(getLayoutVariantDataAttribute(variant)).toEqual({
      "data-layout-variant": variant,
    });
  });
});

describe("getLayoutRootClassName", () => {
  it.each([
    ["classic", "event-landing event-landing--classic"],
    ["minimal", "event-landing event-landing--minimal"],
    ["bold", "event-landing event-landing--bold"],
  ] as const)("returns root class for %s", (variant, expected) => {
    expect(getLayoutRootClassName(variant)).toBe(expected);
  });
});

describe("resolveHeroHeadline", () => {
  it("uses landing block headline when hero block is configured", () => {
    const event = sampleEvent();
    const block: LandingBlock = {
      type: "hero",
      settings: { headline: "Custom headline", subheadline: "Sub" },
    };

    expect(resolveHeroHeadline(block, event)).toBe("Custom headline");
  });

  it("falls back to event name when hero headline is empty", () => {
    const event = sampleEvent({ name: "Fallback Name" });
    const block: LandingBlock = { type: "hero", settings: {} };

    expect(resolveHeroHeadline(block, event)).toBe("Fallback Name");
  });
});
