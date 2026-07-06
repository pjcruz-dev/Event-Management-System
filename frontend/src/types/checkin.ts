export type CheckInScanStatus = "success" | "duplicate" | "rejected";

export interface CheckInScanResult {
  status: CheckInScanStatus;
  reason: string | null;
  is_vip: boolean;
  duplicate_info: {
    checked_in_at: string;
    checked_in_by: string | null;
    gate: string | null;
  } | null;
  registration: {
    id: number;
    registration_number: string;
    attendee_name: string;
    attendee_email: string;
    ticket_type: string | null;
    table_name: string | null;
    checked_in_at: string | null;
    checked_in_by: string | null;
    gate: string | null;
  } | null;
}

export interface CheckInActivityItem {
  id: number;
  action: string;
  metadata: Record<string, unknown> | null;
  actor: string | null;
  created_at: string | null;
}

export interface OfflineScanRecord {
  idempotency_key: string;
  token: string;
  scanned_at: string;
  device_id: string;
  gate: string;
}
