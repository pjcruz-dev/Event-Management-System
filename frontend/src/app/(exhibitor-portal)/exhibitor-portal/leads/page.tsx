"use client";

import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ExhibitorGuard } from "@/components/exhibitor-portal/exhibitor-guard";
import { apiClient } from "@/lib/api-client";
import {
  exhibitorApiOptions,
  useExhibitorAuthStore,
} from "@/stores/exhibitor-auth-store";
import type { ExhibitorLeadRecord } from "@/types/conference";

export default function ExhibitorLeadsPage() {
  const token = useExhibitorAuthStore((s) => s.token);
  const options = exhibitorApiOptions(token);

  const leadsQuery = useQuery({
    queryKey: ["exhibitor-leads", token],
    enabled: Boolean(token),
    queryFn: () => apiClient.get<ExhibitorLeadRecord[]>("/exhibitor-portal/leads", options),
  });

  const exportCsv = () => {
    const leads = leadsQuery.data ?? [];
    const rows = [
      ["Name", "Email", "Registration #", "Scanned at", "Notes"],
      ...leads.map((lead) => [
        lead.attendee_name ?? "",
        lead.attendee_email ?? "",
        lead.registration_number ?? "",
        lead.scanned_at ?? "",
        lead.notes ?? "",
      ]),
    ];
    const csv = rows.map((row) => row.map(escapeCsv).join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = "exhibitor-leads.csv";
    link.click();
    URL.revokeObjectURL(url);
  };

  return (
    <ExhibitorGuard>
      <div className="space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-2xl font-semibold">Captured leads</h1>
            <p className="text-sm text-muted-foreground">
              Attendees scanned at your booth.
            </p>
          </div>
          <Button type="button" variant="outline" onClick={exportCsv}>
            Export CSV
          </Button>
        </div>

        <div className="space-y-3">
          {(leadsQuery.data ?? []).length === 0 ? (
            <p className="text-sm text-muted-foreground">No leads yet.</p>
          ) : (
            leadsQuery.data?.map((lead) => (
              <Card key={lead.id}>
                <CardHeader className="pb-2">
                  <CardTitle className="text-base">
                    {lead.attendee_name ?? "Unknown attendee"}
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-1 text-sm text-muted-foreground">
                  {lead.attendee_email ? <p>{lead.attendee_email}</p> : null}
                  {lead.registration_number ? (
                    <p>Registration #{lead.registration_number}</p>
                  ) : null}
                  {lead.scanned_at ? (
                    <p>Scanned {new Date(lead.scanned_at).toLocaleString()}</p>
                  ) : null}
                  {lead.notes ? <p>Notes: {lead.notes}</p> : null}
                </CardContent>
              </Card>
            ))
          )}
        </div>
      </div>
    </ExhibitorGuard>
  );
}

function escapeCsv(value: string): string {
  if (value.includes(",") || value.includes('"') || value.includes("\n")) {
    return `"${value.replace(/"/g, '""')}"`;
  }
  return value;
}
