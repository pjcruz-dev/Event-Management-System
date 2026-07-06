"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Copy, ExternalLink, Link2, Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { apiClient } from "@/lib/api-client";
import { getSiteUrl } from "@/lib/server-api";
import type { orgRequestOptions } from "@/hooks/use-org-api";
import type { EventPreviewTokenRecord } from "@/types/event-preview";
import { ApiError } from "@/types/api";

interface EventDraftPreviewActionsProps {
  eventId: number;
  slug: string;
  isPubliclyVisible: boolean;
  orgOptions: ReturnType<typeof orgRequestOptions>;
}

function buildPreviewUrl(previewPath: string): string {
  if (typeof window !== "undefined") {
    return `${window.location.origin}${previewPath}`;
  }

  return `${getSiteUrl()}${previewPath}`;
}

export function EventDraftPreviewActions({
  eventId,
  slug,
  isPubliclyVisible,
  orgOptions,
}: EventDraftPreviewActionsProps) {
  const queryClient = useQueryClient();
  const [copyMessage, setCopyMessage] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);

  const tokenQuery = useQuery({
    queryKey: ["event-preview-token", eventId],
    enabled: !isPubliclyVisible,
    queryFn: () =>
      apiClient.get<EventPreviewTokenRecord | null>(`/events/${eventId}/preview-token`, orgOptions),
  });

  const createToken = useMutation({
    mutationFn: () =>
      apiClient.post<EventPreviewTokenRecord>(`/events/${eventId}/preview-token`, undefined, orgOptions),
    onSuccess: () => {
      setActionError(null);
      void queryClient.invalidateQueries({ queryKey: ["event-preview-token", eventId] });
    },
    onError: (error: Error) => {
      setActionError(error instanceof ApiError ? error.message : "Could not create preview link.");
    },
  });

  const activeToken = tokenQuery.data;

  const ensureToken = async (): Promise<EventPreviewTokenRecord> => {
    if (activeToken?.token) {
      return activeToken;
    }

    return createToken.mutateAsync();
  };

  const handleCopy = async () => {
    try {
      const token = await ensureToken();
      const url = buildPreviewUrl(token.preview_path);
      await navigator.clipboard.writeText(url);
      setCopyMessage("Preview link copied.");
      setTimeout(() => setCopyMessage(null), 3000);
    } catch (error) {
      setActionError(error instanceof ApiError ? error.message : "Could not copy preview link.");
    }
  };

  const handleOpen = async () => {
    try {
      const token = await ensureToken();
      const url = buildPreviewUrl(token.preview_path);
      window.open(url, "_blank", "noopener,noreferrer");
    } catch (error) {
      setActionError(error instanceof ApiError ? error.message : "Could not open preview.");
    }
  };

  if (isPubliclyVisible) {
    return null;
  }

  const busy = tokenQuery.isLoading || createToken.isPending;

  return (
    <div className="flex flex-col gap-2">
      <div className="flex flex-wrap gap-2">
        <Button
          type="button"
          variant="outline"
          size="sm"
          disabled={busy}
          onClick={() => void handleCopy()}
        >
          {busy ? (
            <Loader2 className="mr-2 h-4 w-4 animate-spin" aria-hidden />
          ) : (
            <Copy className="mr-2 h-4 w-4" aria-hidden />
          )}
          Copy preview link
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          disabled={busy}
          onClick={() => void handleOpen()}
        >
          <ExternalLink className="mr-2 h-4 w-4" aria-hidden />
          Open preview
        </Button>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          disabled={busy}
          title="Generate a new preview link (invalidates the previous link)"
          onClick={() => createToken.mutate()}
        >
          <Link2 className="mr-2 h-4 w-4" aria-hidden />
          Regenerate link
        </Button>
      </div>
      {copyMessage ? <p className="text-xs text-muted-foreground">{copyMessage}</p> : null}
      {actionError ? (
        <p className="text-xs text-destructive" role="alert">
          {actionError}
        </p>
      ) : null}
      {activeToken?.expires_at ? (
        <p className="text-xs text-muted-foreground">
          Preview link expires {new Date(activeToken.expires_at).toLocaleString()}.
        </p>
      ) : null}
    </div>
  );
}
