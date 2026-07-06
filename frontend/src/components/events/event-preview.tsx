"use client";

import { PublicEventLanding } from "@/components/public/public-event-landing";
import {
  buildEventLandingPayload,
  useEventLandingData,
} from "@/hooks/use-event-landing-data";
import type { orgRequestOptions } from "@/hooks/use-org-api";
import type { EventRecord } from "@/types/event";

interface EventPreviewProps {
  event: EventRecord;
  eventId: number;
  orgOptions: ReturnType<typeof orgRequestOptions>;
}

export function EventPreview({ event, eventId, orgOptions }: EventPreviewProps) {
  const blocks = event.landing_page_config?.blocks ?? [];
  const { speakers, sponsors, exhibitors, agendaSessions, tickets } = useEventLandingData({
    eventId,
    blocks,
    orgOptions,
  });

  const payload = buildEventLandingPayload(event, tickets);

  return (
    <PublicEventLanding
      mode="preview"
      payload={payload}
      speakers={speakers}
      sponsors={sponsors}
      exhibitors={exhibitors}
      agendaSessions={agendaSessions}
    />
  );
}
