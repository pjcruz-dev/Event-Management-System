export type EventRegistrationMode = "open" | "invite_only" | "rsvp";

export type GuestInviteStatus =
  | "pending"
  | "sent"
  | "opened"
  | "responded"
  | "declined"
  | "expired"
  | "revoked";

export type RsvpResponse = "accepted" | "declined" | "maybe";

export interface RsvpSettings {
  allow_plus_ones: boolean;
  max_plus_ones_per_invite: number;
  collect_meal_preferences: boolean;
  meal_options: string[];
  allow_maybe_response: boolean;
  response_deadline: string | null;
  auto_send_reminders?: boolean;
  reminder_days_before_deadline?: number;
}

export interface GuestInviteRecord {
  id: number;
  event_id: number;
  email: string;
  first_name: string;
  last_name: string | null;
  phone: string | null;
  status: GuestInviteStatus;
  rsvp_response: RsvpResponse | null;
  household_name: string | null;
  group_label: string | null;
  tags: string[];
  plus_one_limit: number | null;
  table_id: number | null;
  table_name?: string | null;
  registration_id: number | null;
  registration_number?: string | null;
  sent_at: string | null;
  opened_at: string | null;
  responded_at: string | null;
  last_reminder_sent_at: string | null;
  reminder_count: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface GuestInviteListResponse {
  items: GuestInviteRecord[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface GuestInviteImportResult {
  created: number;
  skipped: number;
  errors: Array<{ row: number; message: string }>;
}

export type EventTableShape = "round" | "rectangle" | "head";

export interface EventTableRecord {
  id: number;
  event_id: number;
  name: string;
  capacity: number;
  sort_order: number;
  shape: EventTableShape;
  x: number | null;
  y: number | null;
  rotation: number | null;
  assigned_count: number;
}

export interface SeatingGuestRegistration {
  id: number;
  registration_number: string;
  attendee_name: string;
  attendee_email: string;
  ticket_type: string | null;
}

export interface SeatingPayload {
  tables: EventTableRecord[];
  unassigned: {
    guest_invites: GuestInviteRecord[];
    registrations: SeatingGuestRegistration[];
  };
}

export interface PublicRsvpPayload {
  invite: {
    first_name: string;
    last_name: string | null;
    email: string;
    status: GuestInviteStatus;
    rsvp_response: RsvpResponse | null;
    responded_at: string | null;
    table_name: string | null;
  };
  event: {
    name: string;
    slug: string;
    venue: string | null;
    starts_at: string | null;
    ends_at: string | null;
    timezone: string;
    registration_mode: EventRegistrationMode;
  };
  rsvp_settings: RsvpSettings;
  ticket_types: import("@/types/ticketing").TicketTypeRecord[];
}
