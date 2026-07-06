const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8001/api/v1";

const SITE_URL =
  process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

interface ApiEnvelope<T> {
  success: boolean;
  data: T;
  message: string | null;
}

export async function fetchPublicApi<T>(
  path: string,
  options: { revalidate?: number } = {},
): Promise<T | null> {
  const url = `${API_BASE_URL}${path}`;
  const response = await fetch(url, {
    headers: { Accept: "application/json" },
    next: { revalidate: options.revalidate ?? 60 },
  });

  if (!response.ok) {
    return null;
  }

  const envelope = (await response.json()) as ApiEnvelope<T>;
  return envelope.success ? envelope.data : null;
}

export async function fetchPublicPreviewApi<T>(
  path: string,
  token: string,
): Promise<T | null> {
  const separator = path.includes("?") ? "&" : "?";
  const url = `${API_BASE_URL}${path}${separator}token=${encodeURIComponent(token)}`;

  const response = await fetch(url, {
    headers: { Accept: "application/json" },
    cache: "no-store",
  });

  if (!response.ok) {
    return null;
  }

  const envelope = (await response.json()) as ApiEnvelope<T>;
  return envelope.success ? envelope.data : null;
}

export function getSiteUrl(): string {
  return SITE_URL.replace(/\/$/, "");
}
