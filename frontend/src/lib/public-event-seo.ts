import type { EventRecord, LandingBlock, ThemeConfig } from "@/types/event";
import type { DiscoverEventRecord } from "@/types/discover";
import type { PublicEventPayload } from "@/types/ticketing";

export function buildEventMetadata(event: EventRecord) {
  const title = event.meta_title?.trim() || event.name;
  const description =
    event.meta_description?.trim() ||
    event.description?.trim() ||
    `Join ${event.name} — register on Event SaaS.`;

  return {
    title,
    description,
    openGraph: {
      title,
      description,
      type: "website" as const,
      images: event.og_image_url ? [{ url: event.og_image_url }] : undefined,
    },
    twitter: {
      card: "summary_large_image" as const,
      title,
      description,
      images: event.og_image_url ? [event.og_image_url] : undefined,
    },
  };
}

export function buildEventJsonLd(
  event: EventRecord,
  siteUrl: string,
): Record<string, unknown> {
  return {
    "@context": "https://schema.org",
    "@type": "Event",
    name: event.name,
    description: event.description ?? undefined,
    startDate: event.starts_at ?? undefined,
    endDate: event.ends_at ?? undefined,
    eventAttendanceMode: "https://schema.org/OfflineEventAttendanceMode",
    eventStatus: "https://schema.org/EventScheduled",
    location: event.venue
      ? {
          "@type": "Place",
          name: event.venue,
        }
      : undefined,
    image: event.og_image_url ?? event.theme_config?.hero_image_url ?? undefined,
    url: `${siteUrl}/e/${event.slug}`,
  };
}

export function parseFaqItems(
  settings: Record<string, string | boolean>,
): Array<{ question: string; answer: string }> {
  const items: Array<{ question: string; answer: string }> = [];
  const count = Number(settings.item_count ?? "0");

  for (let index = 0; index < count; index += 1) {
    const question = String(settings[`question_${index}`] ?? "").trim();
    const answer = String(settings[`answer_${index}`] ?? "").trim();
    if (question && answer) {
      items.push({ question, answer });
    }
  }

  return items;
}

export type { PublicEventPayload, DiscoverEventRecord, LandingBlock, ThemeConfig };
