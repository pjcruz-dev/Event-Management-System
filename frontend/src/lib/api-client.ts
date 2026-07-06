import {
  ApiError,
  type ApiEnvelope,
  type ApiRequestOptions,
} from "@/types/api";

const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8001/api/v1";

let authToken: string | null = null;

export function setAuthToken(token: string | null): void {
  authToken = token;
}

export function getAuthToken(): string | null {
  return authToken;
}

function buildHeaders(options: ApiRequestOptions): Headers {
  const headers = new Headers(options.headers);

  if (!headers.has("Accept")) {
    headers.set("Accept", "application/json");
  }

  const token = options.token ?? authToken;
  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  if (options.organizationId != null) {
    headers.set("X-Organization-Id", String(options.organizationId));
  }

  if (options.body !== undefined && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  return headers;
}

async function parseEnvelope<T>(response: Response): Promise<ApiEnvelope<T>> {
  const contentType = response.headers.get("content-type") ?? "";

  if (!contentType.includes("application/json")) {
    throw new ApiError(
      `Expected JSON response but received ${contentType || "unknown content type"}.`,
      response.status,
    );
  }

  return (await response.json()) as ApiEnvelope<T>;
}

export async function apiRequest<T>(
  path: string,
  options: ApiRequestOptions = {},
): Promise<T> {
  const url = path.startsWith("http") ? path : `${API_BASE_URL}${path}`;
  const { body, ...init } = options;

  const response = await fetch(url, {
    ...init,
    headers: buildHeaders(options),
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  const envelope = await parseEnvelope<T>(response);

  if (!response.ok || !envelope.success) {
    throw new ApiError(
      envelope.message ?? "Request failed.",
      response.status,
      envelope.errors,
      envelope.data,
    );
  }

  return envelope.data as T;
}

export async function apiUpload<T>(
  path: string,
  formData: FormData,
  options: Omit<ApiRequestOptions, "body"> = {},
): Promise<T> {
  const url = path.startsWith("http") ? path : `${API_BASE_URL}${path}`;
  const headers = buildHeaders(options);
  headers.delete("Content-Type");

  const response = await fetch(url, {
    method: "POST",
    headers,
    body: formData,
  });

  const envelope = await parseEnvelope<T>(response);

  if (!response.ok || !envelope.success) {
    throw new ApiError(
      envelope.message ?? "Request failed.",
      response.status,
      envelope.errors,
      envelope.data,
    );
  }

  return envelope.data as T;
}

export const apiClient = {
  get: <T>(path: string, options?: ApiRequestOptions) =>
    apiRequest<T>(path, { ...options, method: "GET" }),
  post: <T>(path: string, body?: unknown, options?: ApiRequestOptions) =>
    apiRequest<T>(path, { ...options, method: "POST", body }),
  put: <T>(path: string, body?: unknown, options?: ApiRequestOptions) =>
    apiRequest<T>(path, { ...options, method: "PUT", body }),
  patch: <T>(path: string, body?: unknown, options?: ApiRequestOptions) =>
    apiRequest<T>(path, { ...options, method: "PATCH", body }),
  delete: <T>(path: string, options?: ApiRequestOptions) =>
    apiRequest<T>(path, { ...options, method: "DELETE" }),
};
