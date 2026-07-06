export type TicketTypeVisibility = "public" | "hidden";

export interface TicketTypeRecord {
  id: number;
  event_id: number;
  name: string;
  type_tag: string | null;
  description: string | null;
  price: number;
  currency: string;
  quantity: number | null;
  quantity_sold: number;
  remaining_quantity: number | null;
  per_order_limit: number;
  sales_starts_at: string | null;
  sales_ends_at: string | null;
  visibility: TicketTypeVisibility;
  is_active: boolean;
  sort_order: number;
  is_on_sale: boolean;
}

export interface RegistrationField {
  key: string;
  type: "text" | "email" | "phone" | "select" | "checkbox" | "textarea";
  label: string;
  required: boolean;
  options: string[];
}

export interface RegistrationFormRecord {
  id: number;
  event_id: number;
  fields: RegistrationField[];
}

export interface CouponRecord {
  id: number;
  event_id: number;
  code: string;
  discount_type: "percentage" | "fixed";
  discount_value: number;
  applicable_ticket_type_ids: number[] | null;
  max_uses: number | null;
  times_used: number;
  valid_from: string | null;
  valid_until: string | null;
  is_active: boolean;
}

export interface PublicEventPayload {
  event: import("@/types/event").EventRecord;
  ticket_types: TicketTypeRecord[];
}

export interface RegistrationCheckoutResponse {
  status: "pending_payment" | "confirmed" | "waitlisted";
  registration?: {
    id: number;
    registration_number: string;
    attendee_email: string;
  };
  order?: {
    id: number;
    order_number: string;
    status: string;
    subtotal: number;
    discount_total: number;
    total: number;
    currency: string;
    items: Array<{
      description: string;
      quantity: number;
      unit_price: number;
      total_price: number;
    }>;
  };
  waiting_list_entry?: {
    id: number;
    ticket_type_id: number;
    attendee_email: string;
  };
}

export interface CouponPreview {
  subtotal: number;
  discount_total: number;
  total: number;
  currency: string;
}

export interface CheckoutSessionResponse {
  checkout_url: string;
  session_id: string;
}

export interface OrderRegistration {
  id: number;
  registration_number: string;
  attendee_name: string;
  attendee_email: string;
  status: string;
  ticket_type: string | null;
}

export interface OrderItem {
  id: number;
  description: string;
  quantity: number;
  unit_price: number;
  total: number;
}

export interface OrderRecord {
  id: number;
  order_number: string;
  status: string;
  subtotal: number;
  discount_total: number;
  total: number;
  currency: string;
  payment_method: string | null;
  paid_at: string | null;
  refund_amount: number | null;
  refunded_at: string | null;
  refund_reason: string | null;
  registration_id: number | null;
  registrations: OrderRegistration[];
  items: OrderItem[];
  created_at: string;
}

export interface OrderListResponse {
  items: OrderRecord[];
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}
