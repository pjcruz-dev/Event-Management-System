import type { PaginatedData } from "@/types/api";

export type EventStatus = "draft" | "published" | "archived";
export type EventVisibility = "public" | "private";
export type EventRegistrationMode = "open" | "invite_only" | "rsvp";
export type CustomDomainVerificationStatus =
  | "unverified"
  | "pending"
  | "verified";

export type LandingBlockType =
  | "hero"
  | "about"
  | "image"
  | "agenda-preview"
  | "speakers-preview"
  | "sponsors"
  | "exhibitors"
  | "faq"
  | "cta";

export interface ThemeConfig {
  primary_color: string;
  secondary_color: string;
  font: string;
  logo_url?: string | null;
  hero_image_url?: string | null;
  hero_video_url?: string | null;
  hero_background_type?: "image" | "video" | "color";
  layout_variant: "classic" | "minimal" | "bold" | "modern" | "conference";
}

export interface LandingBlock {
  type: LandingBlockType;
  visible?: boolean;
  settings: Record<string, string | boolean>;
}

export interface LandingTicketsConfig {
  visible: boolean;
  title: string;
  position: "after_blocks" | "hidden";
}

export interface LandingPageConfig {
  blocks: LandingBlock[];
  tickets?: LandingTicketsConfig;
}

export interface RsvpSettings {
  allow_plus_ones: boolean;
  max_plus_ones_per_invite: number;
  collect_meal_preferences: boolean;
  meal_options?: string[];
  allow_maybe_response: boolean;
  response_deadline: string | null;
  auto_send_reminders?: boolean;
  reminder_days_before_deadline?: number;
}

export interface ConfirmationSettings {
  rsvp_accepted_message: string | null;
  rsvp_declined_message: string | null;
  rsvp_maybe_message: string | null;
  registration_pending_message: string | null;
  registration_confirmed_message: string | null;
}

export type EventCategory =
  | "conference"
  | "workshop"
  | "concert"
  | "meetup"
  | "webinar"
  | "festival"
  | "other";

export interface EventRecord {
  id: number;
  organization_id: number;
  name: string;
  slug: string;
  description: string | null;
  venue: string | null;
  timezone: string;
  capacity: number | null;
  status: EventStatus;
  visibility: EventVisibility;
  registration_mode: EventRegistrationMode;
  rsvp_settings: RsvpSettings;
  confirmation_settings: ConfirmationSettings;
  category: EventCategory | null;
  theme_config: ThemeConfig;
  landing_page_config: LandingPageConfig;
  custom_domain: string | null;
  custom_domain_verification_status: CustomDomainVerificationStatus;
  meta_title: string | null;
  meta_description: string | null;
  og_image_url: string | null;
  starts_at: string | null;
  ends_at: string | null;
  published_at: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export type PaginatedEvents = PaginatedData<EventRecord>;

export interface PublishBlockers {
  name: boolean;
  venue: boolean;
  dates: boolean;
}

export function getPublishBlockers(event: EventRecord): PublishBlockers {
  return {
    name: !event.name?.trim(),
    venue: !event.venue?.trim(),
    dates: !event.starts_at || !event.ends_at,
  };
}

export function canPublish(event: EventRecord): boolean {
  const blockers = getPublishBlockers(event);
  return !blockers.name && !blockers.venue && !blockers.dates;
}

export function publishBlockerMessage(event: EventRecord): string | null {
  const blockers = getPublishBlockers(event);
  const missing: string[] = [];
  if (blockers.name) missing.push("name");
  if (blockers.venue) missing.push("venue");
  if (blockers.dates) missing.push("start and end dates");
  if (missing.length === 0) return null;
  return `Add ${missing.join(", ")} before publishing.`;
}
