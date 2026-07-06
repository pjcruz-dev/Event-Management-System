import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { PublicEventLanding } from "@/components/public/public-event-landing";
import { SimilarEventsStrip } from "@/components/public/similar-events-strip";
import { EventReviewsSection } from "@/components/public/event-reviews-section";
import { buildEventJsonLd, buildEventMetadata } from "@/lib/public-event-seo";
import { fetchPublicApi, getSiteUrl } from "@/lib/server-api";
import type { DiscoverEventRecord, ReviewRecord } from "@/types/discover";
import type {
  EventSessionRecord,
  ExhibitorRecord,
  SpeakerRecord,
  SponsorRecord,
  PublicAgendaPayload,
} from "@/types/conference";
import { isLandingBlockVisible } from "@/lib/landing-block-utils";
import type { PublicEventPayload } from "@/types/ticketing";
import type { PaginatedData } from "@/types/api";

export const revalidate = 60;

interface PageProps {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  const payload = await fetchPublicApi<PublicEventPayload>(`/public/events/${slug}`);
  if (!payload?.event) {
    return { title: "Event not found" };
  }
  return buildEventMetadata(payload.event);
}

export default async function PublicEventPage({ params }: PageProps) {
  const { slug } = await params;
  const payload = await fetchPublicApi<PublicEventPayload>(`/public/events/${slug}`);

  if (!payload?.event) {
    notFound();
  }

  const blocks = payload.event.landing_page_config?.blocks ?? [];
  const needsSpeakers = blocks.some(
    (block) => block.type === "speakers-preview" && isLandingBlockVisible(block),
  );
  const needsSponsors = blocks.some(
    (block) => block.type === "sponsors" && isLandingBlockVisible(block),
  );
  const needsExhibitors = blocks.some(
    (block) => block.type === "exhibitors" && isLandingBlockVisible(block),
  );

  const [similar, reviews, speakers, sponsors, exhibitors, agenda] = await Promise.all([
    fetchPublicApi<DiscoverEventRecord[]>(`/public/events/${slug}/similar`),
    fetchPublicApi<PaginatedData<ReviewRecord>>(`/public/events/${slug}/reviews`),
    needsSpeakers
      ? fetchPublicApi<SpeakerRecord[]>(`/public/events/${slug}/speakers`)
      : Promise.resolve(null),
    needsSponsors
      ? fetchPublicApi<SponsorRecord[]>(`/public/events/${slug}/sponsors`)
      : Promise.resolve(null),
    needsExhibitors
      ? fetchPublicApi<ExhibitorRecord[]>(`/public/events/${slug}/exhibitors`)
      : Promise.resolve(null),
    fetchPublicApi<PublicAgendaPayload>(`/public/events/${slug}/agenda`),
  ]);

  const jsonLd = buildEventJsonLd(payload.event, getSiteUrl());
  const eventEnded = payload.event.ends_at
    ? new Date(payload.event.ends_at).getTime() < Date.now()
    : false;

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />
      <PublicEventLanding
        payload={payload}
        speakers={speakers ?? []}
        sponsors={sponsors ?? []}
        exhibitors={exhibitors ?? []}
        agendaSessions={agenda?.sessions ?? []}
      />
      <EventReviewsSection
        slug={slug}
        initialReviews={reviews?.items ?? []}
        eventEnded={eventEnded}
      />
      <SimilarEventsStrip events={similar ?? []} />
    </>
  );
}
