"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { apiClient } from "@/lib/api-client";
import {
  downloadAuthenticatedFile,
  pollExportUntilReady,
} from "@/lib/export-download";
import type { ApiRequestOptions } from "@/types/api";
import type { ExportQueuedResponse } from "@/types/analytics";

interface ExportActionsProps {
  eventId: number;
  orgOptions: ApiRequestOptions;
}

const exportTypes = [
  { type: "registrations", label: "Registrations" },
  { type: "orders", label: "Orders" },
  { type: "checkins", label: "Check-ins" },
  { type: "summary", label: "Summary PDF" },
] as const;

export function ExportActions({ eventId, orgOptions }: ExportActionsProps) {
  const [message, setMessage] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const runExport = async (type: string, format: "csv" | "xlsx" | "pdf", asyncMode: boolean) => {
    setBusy(true);
    setMessage(null);
    try {
      if (asyncMode) {
        setMessage("Preparing your export…");
        const queued = await apiClient.post<ExportQueuedResponse>(
          `/events/${eventId}/exports/${type}`,
          { format, async: true },
          orgOptions,
        );
        const downloadUrl = await pollExportUntilReady(queued.export_id, orgOptions, (status) => {
          setMessage(status === "processing" ? "Preparing your export…" : "Export ready. Downloading…");
        });
        await downloadAuthenticatedFile(downloadUrl, orgOptions, `${type}.${format}`);
        setMessage("Export downloaded.");
        return;
      }

      if (format === "pdf") {
        await downloadAuthenticatedFile(
          `/events/${eventId}/exports/${type}?format=pdf`,
          orgOptions,
          `summary-${eventId}.pdf`,
        );
        setMessage("PDF downloaded.");
        return;
      }

      await downloadAuthenticatedFile(
        `/events/${eventId}/exports/${type}?format=${format}`,
        orgOptions,
        `${type}-${eventId}.${format}`,
      );
      setMessage("Export downloaded.");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Export failed.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="space-y-3">
      <p className="text-sm font-medium">Exports</p>
      <div className="flex flex-wrap gap-2">
        {exportTypes.map((item) => (
          <div key={item.type} className="flex flex-wrap gap-1">
            {item.type === "summary" ? (
              <>
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  disabled={busy}
                  onClick={() => void runExport(item.type, "pdf", false)}
                >
                  {item.label}
                </Button>
                <Button
                  type="button"
                  size="sm"
                  variant="ghost"
                  disabled={busy}
                  onClick={() => void runExport(item.type, "pdf", true)}
                >
                  Queue PDF
                </Button>
              </>
            ) : (
              <>
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  disabled={busy}
                  onClick={() => void runExport(item.type, "csv", false)}
                >
                  {item.label} CSV
                </Button>
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  disabled={busy}
                  onClick={() => void runExport(item.type, "xlsx", false)}
                >
                  Excel
                </Button>
                <Button
                  type="button"
                  size="sm"
                  variant="ghost"
                  disabled={busy}
                  onClick={() => void runExport(item.type, "csv", true)}
                >
                  Queue
                </Button>
              </>
            )}
          </div>
        ))}
      </div>
      {message ? <p className="text-sm text-muted-foreground">{message}</p> : null}
    </div>
  );
}
