import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { PublicEventLanding } from "@/components/public/public-event-landing";
import { EventPreviewBanner } from "@/components/public/event-preview-banner";
import { fetchPublicPreviewApi } from "@/lib/server-api";
import type {
  EventSessionRecord,
  ExhibitorRecord,
  PublicAgendaPayload,
  SpeakerRecord,
  SponsorRecord,
} from "@/types/conference";
import { isLandingBlockVisible } from "@/lib/landing-block-utils";
import type { PublicEventPayload } from "@/types/ticketing";

export const metadata: Metadata = {
  robots: {
    index: false,
    follow: false,
  },
};

interface PageProps {
  params: Promise<{ slug: string }>;
  searchParams: Promise<{ token?: string }>;
}

export default async function PublicEventPreviewPage({ params, searchParams }: PageProps) {
  const { slug } = await params;
  const { token } = await searchParams;

  if (!token) {
    notFound();
  }

  const payload = await fetchPublicPreviewApi<PublicEventPayload>(
    `/public/events/${slug}/preview`,
    token,
  );

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

  const [speakers, sponsors, exhibitors, agenda] = await Promise.all([
    needsSpeakers
      ? fetchPublicPreviewApi<SpeakerRecord[]>(`/public/events/${slug}/speakers`, token)
      : Promise.resolve(null),
    needsSponsors
      ? fetchPublicPreviewApi<SponsorRecord[]>(`/public/events/${slug}/sponsors`, token)
      : Promise.resolve(null),
    needsExhibitors
      ? fetchPublicPreviewApi<ExhibitorRecord[]>(`/public/events/${slug}/exhibitors`, token)
      : Promise.resolve(null),
    fetchPublicPreviewApi<PublicAgendaPayload>(`/public/events/${slug}/agenda`, token),
  ]);

  return (
    <>
      <EventPreviewBanner />
      <PublicEventLanding
        payload={payload}
        speakers={speakers ?? []}
        sponsors={sponsors ?? []}
        exhibitors={exhibitors ?? []}
        agendaSessions={agenda?.sessions ?? []}
        mode="public"
      />
    </>
  );
}
