import type { PaginatedData } from "@/types/api";
import type { EventCategory } from "@/types/event";

export type { EventCategory };

export const EVENT_CATEGORIES: Array<{ value: EventCategory; label: string }> = [
  { value: "conference", label: "Conference" },
  { value: "workshop", label: "Workshop" },
  { value: "concert", label: "Concert" },
  { value: "meetup", label: "Meetup" },
  { value: "webinar", label: "Webinar" },
  { value: "festival", label: "Festival" },
  { value: "other", label: "Other" },
];

export interface DiscoverEventRecord {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  venue: string | null;
  category: EventCategory | null;
  starts_at: string | null;
  ends_at: string | null;
  min_price: number | null;
  max_price: number | null;
  is_free: boolean;
  currency: string | null;
  og_image_url: string | null;
  organization?: {
    id: number;
    name: string;
    slug: string;
  };
}

export type PaginatedDiscoverEvents = PaginatedData<DiscoverEventRecord>;

export interface PublicOrganizationProfile {
  organization: {
    id: number;
    name: string;
    slug: string;
    logo_url: string | null;
    description: string | null;
    website_url: string | null;
  };
  events: DiscoverEventRecord[];
}

export interface ReviewRecord {
  id: number;
  rating: number;
  comment: string | null;
  attendee_name: string | null;
  created_at: string | null;
}

export type PaginatedReviews = PaginatedData<ReviewRecord>;

export interface SitemapPayload {
  events: Array<{ slug: string; updated_at: string | null }>;
}
