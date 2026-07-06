import { getAuthToken } from "@/lib/api-client";
import type { ApiRequestOptions } from "@/types/api";

const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8001/api/v1";

export async function downloadAuthenticatedFile(
  path: string,
  options: ApiRequestOptions,
  fallbackFilename: string,
): Promise<void> {
  const token = options.token ?? getAuthToken();
  const headers = new Headers(options.headers);
  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }
  if (options.organizationId != null) {
    headers.set("X-Organization-Id", String(options.organizationId));
  }

  const response = await fetch(
    path.startsWith("http") ? path : `${API_BASE_URL}${path}`,
    { headers, method: "GET" },
  );

  if (!response.ok) {
    throw new Error("Export download failed.");
  }

  const blob = await response.blob();
  const disposition = response.headers.get("content-disposition");
  const filename = parseFilename(disposition) ?? fallbackFilename;
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}

function parseFilename(disposition: string | null): string | null {
  if (!disposition) return null;
  const match = /filename="?([^"]+)"?/i.exec(disposition);
  return match?.[1] ?? null;
}

export async function pollExportUntilReady(
  exportId: string,
  options: ApiRequestOptions,
  onStatus?: (status: string) => void,
): Promise<string> {
  const token = options.token ?? getAuthToken();
  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");
  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }
  if (options.organizationId != null) {
    headers.set("X-Organization-Id", String(options.organizationId));
  }

  for (let attempt = 0; attempt < 30; attempt += 1) {
    const response = await fetch(`${API_BASE_URL}/exports/${exportId}`, { headers });
    const envelope = (await response.json()) as {
      success: boolean;
      data?: { status: string; download_url: string | null; error?: string | null };
    };

    const status = envelope.data?.status ?? "processing";
    onStatus?.(status);

    if (status === "ready" && envelope.data?.download_url) {
      return envelope.data.download_url;
    }

    if (status === "failed") {
      throw new Error(envelope.data?.error ?? "Export failed.");
    }

    await new Promise((resolve) => setTimeout(resolve, 2000));
  }

  throw new Error("Export timed out. Try again later.");
}
