export interface AuthUser {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  timezone: string;
  locale: string;
  avatar_url: string | null;
  email_verified_at: string | null;
  created_at: string | null;
}

export interface AuthOrganization {
  id: number;
  name: string;
  slug: string;
  logo_url: string | null;
  owner_id: number;
  default_currency: string;
  settings: Record<string, unknown>;
  role: string | null;
  membership_status: string | null;
  created_at: string | null;
}

export interface MeResponse {
  user: AuthUser;
  organizations: AuthOrganization[];
}

export interface AuthTokenResponse {
  user: AuthUser;
  token: string;
}

export interface OrganizationMember {
  id: number;
  name: string;
  email: string;
  avatar_url: string | null;
  role: string;
  status: string;
  joined_at: string | null;
}

export interface OrganizationInvitation {
  id: number;
  organization_id: number;
  email: string;
  role: string;
  expires_at: string;
  accepted_at: string | null;
  invited_by: number;
  created_at: string | null;
}

export interface OrganizationRole {
  id: number;
  name: string;
  permissions: string[];
}
