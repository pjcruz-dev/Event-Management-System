export interface ApiEnvelope<T> {
  success: boolean;
  data: T | null;
  message: string | null;
  errors: Record<string, string[]> | null;
}

export interface PaginationMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface PaginatedData<T> {
  items: T[];
  meta: PaginationMeta;
}

export type ApiResponse<T> = ApiEnvelope<T>;

export type PaginatedResponse<T> = ApiEnvelope<PaginatedData<T>>;

export class ApiError extends Error {
  readonly status: number;
  readonly errors: Record<string, string[]> | null;
  readonly payload: unknown;

  constructor(
    message: string,
    status: number,
    errors: Record<string, string[]> | null = null,
    payload: unknown = null,
  ) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.errors = errors;
    this.payload = payload;
  }
}

export interface ApiRequestOptions extends Omit<RequestInit, "body"> {
  body?: unknown;
  token?: string | null;
  organizationId?: string | number | null;
}
