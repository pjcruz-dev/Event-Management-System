export interface DashboardSummary {
  total_revenue: number;
  currency: string;
  total_registrations: number;
  confirmed_registrations: number;
  checked_in_count: number;
  check_in_rate: number;
  orders_with_coupon: number;
}

export interface TimeSeriesPoint {
  date: string;
  total?: number;
  count?: number;
}

export interface TopTicketType {
  ticket_type_id: number;
  name: string;
  volume: number;
  revenue: number;
}

export interface GeographicPoint {
  country: string;
  city: string;
  count: number;
}

export interface CouponUsagePoint {
  coupon_id: number;
  code: string;
  uses: number;
  discount_total: number;
}

export interface EventDashboardMetrics {
  event_id: number;
  summary: DashboardSummary;
  revenue_over_time: Array<{ date: string; total: number }>;
  registrations_over_time: Array<{ date: string; count: number }>;
  top_ticket_types: TopTicketType[];
  geographic_breakdown: GeographicPoint[];
  coupon_usage: CouponUsagePoint[];
  cached_at: string;
}

export interface OrganizationEventMetric {
  event_id: number;
  name: string;
  status: string;
  starts_at: string | null;
  total_revenue: number;
  currency: string;
  confirmed_registrations: number;
  checked_in_count: number;
  check_in_rate: number;
}

export interface OrganizationDashboardMetrics {
  organization_id: number;
  events: OrganizationEventMetric[];
  totals: {
    total_revenue: number;
    currency: string;
    confirmed_registrations: number;
    checked_in_count: number;
    event_count: number;
  };
  cached_at: string;
}

export interface ActivityLogItem {
  id: number;
  action: string;
  metadata: Record<string, unknown>;
  actor: string | null;
  actor_id: number | null;
  subject_type: string | null;
  subject_id: number | null;
  created_at: string | null;
}

export interface PaginatedActivity {
  items: ActivityLogItem[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface ExportQueuedResponse {
  export_id: string;
  status: string;
  message: string;
}

export interface ExportStatusResponse {
  export_id: string;
  status: "processing" | "ready" | "failed";
  type: string;
  format: string;
  error: string | null;
  download_url: string | null;
}
