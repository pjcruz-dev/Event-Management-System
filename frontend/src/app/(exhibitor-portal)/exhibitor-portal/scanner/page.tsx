"use client";

import { useCallback, useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { FormErrorBanner } from "@/components/shared/form-field";
import { LeadScanner } from "@/components/conference/lead-scanner";
import { ExhibitorGuard } from "@/components/exhibitor-portal/exhibitor-guard";
import { apiClient } from "@/lib/api-client";
import {
  exhibitorApiOptions,
  useExhibitorAuthStore,
} from "@/stores/exhibitor-auth-store";
import type { ExhibitorLeadRecord } from "@/types/conference";
import { ApiError } from "@/types/api";

export default function ExhibitorScannerPage() {
  const token = useExhibitorAuthStore((s) => s.token);
  const options = exhibitorApiOptions(token);
  const queryClient = useQueryClient();
  const [notes, setNotes] = useState("");
  const [lastLead, setLastLead] = useState<ExhibitorLeadRecord | null>(null);
  const [formError, setFormError] = useState<string | null>(null);

  const scanMutation = useMutation({
    mutationFn: (scanToken: string) =>
      apiClient.post<ExhibitorLeadRecord>(
        "/exhibitor-portal/leads/scan",
        { token: scanToken, notes: notes || null },
        options,
      ),
    onSuccess: (lead) => {
      setLastLead(lead);
      setFormError(null);
      void queryClient.invalidateQueries({ queryKey: ["exhibitor-leads", token] });
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Scan failed."),
  });

  const handleScan = useCallback(
    (scanToken: string) => {
      scanMutation.mutate(scanToken);
    },
    [scanMutation],
  );

  return (
    <ExhibitorGuard>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-semibold">Lead scanner</h1>
          <p className="text-sm text-muted-foreground">
            Scan attendee badges to capture leads for your booth.
          </p>
        </div>

        {formError ? <FormErrorBanner message={formError} /> : null}

        <Card>
          <CardHeader>
            <CardTitle>Scan badge</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <Input
              placeholder="Optional notes for next scan"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
            />
            <LeadScanner
              disabled={scanMutation.isPending}
              onScan={handleScan}
            />
          </CardContent>
        </Card>

        {lastLead ? (
          <Card>
            <CardHeader>
              <CardTitle>Last capture</CardTitle>
            </CardHeader>
            <CardContent className="text-sm text-muted-foreground">
              <p className="font-medium text-foreground">
                {lastLead.attendee_name ?? "Attendee"}
              </p>
              {lastLead.attendee_email ? <p>{lastLead.attendee_email}</p> : null}
              {lastLead.registration_number ? (
                <p>#{lastLead.registration_number}</p>
              ) : null}
            </CardContent>
          </Card>
        ) : null}
      </div>
    </ExhibitorGuard>
  );
}
