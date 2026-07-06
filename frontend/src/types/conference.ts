export interface TrackRecord {
  id: number;
  event_id: number;
  name: string;
  description: string | null;
  sort_order: number;
}

export interface SpeakerSocialLinks {
  twitter?: string | null;
  linkedin?: string | null;
  website?: string | null;
  github?: string | null;
}

export interface SpeakerRecord {
  id: number;
  event_id: number;
  name: string;
  title: string | null;
  bio: string | null;
  photo_path: string | null;
  photo_url: string | null;
  social_links: SpeakerSocialLinks;
  sort_order: number;
  event_sessions?: EventSessionRecord[];
}

export type SponsorTier =
  | "platinum"
  | "gold"
  | "silver"
  | "bronze"
  | "partner";

export interface SponsorRecord {
  id: number;
  event_id: number;
  name: string;
  logo_path: string | null;
  logo_url: string | null;
  website_url: string | null;
  tier: SponsorTier;
  description: string | null;
  sort_order: number;
}

export interface BoothRecord {
  id: number;
  event_id: number;
  exhibitor_id: number;
  code: string;
  location: string | null;
}

export interface ExhibitorMaterial {
  name: string;
  url: string;
}

export interface ExhibitorRecord {
  id: number;
  event_id: number;
  name: string;
  description: string | null;
  logo_path: string | null;
  logo_url?: string | null;
  website_url: string | null;
  contact_email: string | null;
  materials: ExhibitorMaterial[];
  booth?: BoothRecord | null;
  leads_count?: number;
}

export interface EventSessionRecord {
  id: number;
  event_id: number;
  track_id: number;
  title: string;
  description: string | null;
  room: string | null;
  starts_at: string | null;
  ends_at: string | null;
  capacity: number | null;
  registered_count?: number;
  sort_order: number;
  is_published: boolean;
  track?: TrackRecord;
  speakers?: SpeakerRecord[];
}

export interface SessionConflictWarning {
  type: "room_conflict" | "speaker_overlap";
  message: string;
  session_id: number;
  speaker_id?: number;
  room?: string;
}

export interface SessionMutationResponse {
  session: EventSessionRecord;
  warnings: SessionConflictWarning[];
}

export interface PublicAgendaPayload {
  event: { id: number; name: string; slug: string };
  tracks: TrackRecord[];
  sessions: EventSessionRecord[];
  sponsors: SponsorRecord[];
}

export interface ExhibitorLeadRecord {
  id: number;
  scanned_at: string | null;
  notes: string | null;
  attendee_name: string | null;
  attendee_email: string | null;
  registration_number: string | null;
}

export interface ExhibitorPortalLoginResponse {
  token: string;
  contact: {
    id: number;
    name: string;
    email: string;
    exhibitor_id: number;
    event_id: number;
  };
}

export interface ExhibitorPortalMeResponse {
  contact: { id: number; name: string; email: string };
  exhibitor: ExhibitorRecord;
}

export interface InviteContactResponse {
  contact_id: number;
  email: string;
  temporary_password: string | null;
}
