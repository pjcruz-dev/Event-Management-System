"use client";

import { useQuery } from "@tanstack/react-query";
import type {
  EventSessionRecord,
  ExhibitorRecord,
  SpeakerRecord,
  SponsorRecord,
} from "@/types/conference";
import type { EventRecord, LandingBlockType } from "@/types/event";
import type { TicketTypeRecord } from "@/types/ticketing";
import { apiClient } from "@/lib/api-client";
import type { orgRequestOptions } from "@/hooks/use-org-api";

interface UseEventLandingDataOptions {
  eventId: number;
  blocks: Array<{ type: LandingBlockType }>;
  orgOptions: ReturnType<typeof orgRequestOptions>;
  enabled?: boolean;
}

export function useEventLandingData({
  eventId,
  blocks,
  orgOptions,
  enabled = true,
}: UseEventLandingDataOptions) {
  const needsSpeakers = blocks.some((block) => block.type === "speakers-preview");
  const needsSponsors = blocks.some((block) => block.type === "sponsors");
  const needsExhibitors = blocks.some((block) => block.type === "exhibitors");
  const needsAgenda = blocks.some((block) => block.type === "agenda-preview");

  const speakersQuery = useQuery({
    queryKey: ["event-landing-speakers", eventId],
    enabled: enabled && needsSpeakers,
    queryFn: () => apiClient.get<SpeakerRecord[]>(`/events/${eventId}/speakers`, orgOptions),
  });

  const sponsorsQuery = useQuery({
    queryKey: ["event-landing-sponsors", eventId],
    enabled: enabled && needsSponsors,
    queryFn: () => apiClient.get<SponsorRecord[]>(`/events/${eventId}/sponsors`, orgOptions),
  });

  const exhibitorsQuery = useQuery({
    queryKey: ["event-landing-exhibitors", eventId],
    enabled: enabled && needsExhibitors,
    queryFn: () => apiClient.get<ExhibitorRecord[]>(`/events/${eventId}/exhibitors`, orgOptions),
  });

  const sessionsQuery = useQuery({
    queryKey: ["event-landing-sessions", eventId],
    enabled: enabled && needsAgenda,
    queryFn: () => apiClient.get<EventSessionRecord[]>(`/events/${eventId}/sessions`, orgOptions),
  });

  const ticketsQuery = useQuery({
    queryKey: ["event-landing-tickets", eventId],
    enabled,
    queryFn: () => apiClient.get<TicketTypeRecord[]>(`/events/${eventId}/ticket-types`, orgOptions),
  });

  const publishedSessions = (sessionsQuery.data ?? []).filter((session) => session.is_published);

  return {
    speakers: speakersQuery.data ?? [],
    sponsors: sponsorsQuery.data ?? [],
    exhibitors: exhibitorsQuery.data ?? [],
    agendaSessions: publishedSessions,
    tickets: ticketsQuery.data ?? [],
    isLoading:
      speakersQuery.isLoading ||
      sponsorsQuery.isLoading ||
      exhibitorsQuery.isLoading ||
      sessionsQuery.isLoading ||
      ticketsQuery.isLoading,
  };
}

export function buildEventLandingPayload(
  event: EventRecord,
  tickets: TicketTypeRecord[],
): { event: EventRecord; ticket_types: TicketTypeRecord[] } {
  return { event, ticket_types: tickets };
}
