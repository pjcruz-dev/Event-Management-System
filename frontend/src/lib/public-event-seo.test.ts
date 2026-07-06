import { describe, expect, it } from "vitest";
import { buildEventJsonLd, buildEventMetadata } from "@/lib/public-event-seo";
import type { EventRecord } from "@/types/event";

function sampleEvent(overrides: Partial<EventRecord> = {}): EventRecord {
  return {
    id: 1,
    organization_id: 1,
    name: "Global Innovation Summit",
    slug: "global-innovation-summit",
    description: "Three days of keynotes and workshops.",
    venue: "Moscone Center",
    timezone: "America/Los_Angeles",
    capacity: 1000,
    status: "published",
    visibility: "public",
    registration_mode: "open",
    rsvp_settings: {
      allow_plus_ones: false,
      max_plus_ones_per_invite: 0,
      collect_meal_preferences: false,
      allow_maybe_response: true,
      response_deadline: null,
    },
    category: "conference",
    theme_config: {
      primary_color: "#1E40AF",
      secondary_color: "#F59E0B",
      font: "Inter",
      layout_variant: "classic",
    },
    landing_page_config: { blocks: [] },
    custom_domain: null,
    custom_domain_verification_status: "unverified",
    meta_title: "Global Innovation Summit 2026",
    meta_description: "Join industry leaders in San Francisco.",
    og_image_url: "http://localhost:8000/storage/events/og.jpg",
    starts_at: "2026-09-01T09:00:00Z",
    ends_at: "2026-09-03T17:00:00Z",
    published_at: "2026-07-01T00:00:00Z",
    created_at: null,
    updated_at: null,
    ...overrides,
  };
}

describe("buildEventMetadata", () => {
  it("uses custom meta fields when present", () => {
    const metadata = buildEventMetadata(sampleEvent());

    expect(metadata.title).toBe("Global Innovation Summit 2026");
    expect(metadata.description).toBe("Join industry leaders in San Francisco.");
    expect(metadata.openGraph?.images?.[0]?.url).toBe(
      "http://localhost:8000/storage/events/og.jpg",
    );
  });

  it("falls back to event name and description", () => {
    const metadata = buildEventMetadata(
      sampleEvent({
        meta_title: null,
        meta_description: null,
      }),
    );

    expect(metadata.title).toBe("Global Innovation Summit");
    expect(metadata.description).toBe("Three days of keynotes and workshops.");
  });
});

describe("buildEventJsonLd", () => {
  it("emits schema.org Event payload with canonical URL", () => {
    const jsonLd = buildEventJsonLd(sampleEvent(), "http://localhost:3000");

    expect(jsonLd["@type"]).toBe("Event");
    expect(jsonLd.name).toBe("Global Innovation Summit");
    expect(jsonLd.url).toBe("http://localhost:3000/e/global-innovation-summit");
    expect(jsonLd.location).toEqual({
      "@type": "Place",
      name: "Moscone Center",
    });
  });
});
