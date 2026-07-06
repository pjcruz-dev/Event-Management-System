export interface EventPreviewTokenRecord {
  token: string;
  expires_at: string | null;
  last_used_at: string | null;
  created_at: string;
  preview_path: string;
}
