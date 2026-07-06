"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { formatCurrency } from "@/lib/currency";
import { Html5QrcodeScanner } from "html5-qrcode";
import { useCallback, useEffect, useRef, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { FormErrorBanner } from "@/components/shared/form-field";
import type { TicketTypeRecord } from "@/types/ticketing";
import { apiClient } from "@/lib/api-client";
import {
  listOfflineScans,
  queueOfflineScan,
  removeOfflineScans,
} from "@/lib/checkin-offline-queue";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { CheckInScanResult } from "@/types/checkin";
import { ApiError } from "@/types/api";

interface SearchResult {
  id: number;
  registration_number: string;
  attendee_name: string;
  attendee_email: string;
  ticket_type: string | null;
  checked_in_at: string | null;
  has_qr: boolean;
}

type CheckInTab = "scanner" | "search" | "walkin";

export default function EventCheckInPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();
  const scannerRef = useRef<Html5QrcodeScanner | null>(null);
  const [gate, setGate] = useState("Main entrance");
  const [lastResult, setLastResult] = useState<CheckInScanResult | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [isOnline, setIsOnline] = useState(true);
  const deviceIdRef = useRef(`device-${crypto.randomUUID()}`);
  const [activeTab, setActiveTab] = useState<CheckInTab>("scanner");
  const [searchQuery, setSearchQuery] = useState("");
  const [walkInForm, setWalkInForm] = useState({
    first_name: "",
    last_name: "",
    email: "",
  });
  const [walkInTicketTypeId, setWalkInTicketTypeId] = useState<string>("");
  const [paymentCollected, setPaymentCollected] = useState(false);

  const ticketTypesQuery = useQuery({
    queryKey: ["ticket-types", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () => apiClient.get<TicketTypeRecord[]>(`/events/${eventId}/ticket-types`, orgOptions),
  });
  const ticketTypes = ticketTypesQuery.data ?? [];
  const selectedWalkInTicket = ticketTypes.find((tt) => String(tt.id) === walkInTicketTypeId);
  const walkInTicketIsPaid = selectedWalkInTicket ? selectedWalkInTicket.price > 0 : false;

  const pendingQuery = useQuery({
    queryKey: ["checkin-offline", eventId],
    queryFn: () => listOfflineScans(eventId),
    refetchInterval: 5000,
  });

  const searchResults = useQuery({
    queryKey: ["checkin-search", organizationId, eventId, searchQuery],
    enabled: organizationId !== null && searchQuery.length >= 2,
    queryFn: () =>
      apiClient.get<SearchResult[]>(
        `/events/${eventId}/checkin/search?q=${encodeURIComponent(searchQuery)}`,
        orgOptions,
      ),
  });

  const scanPendingRef = useRef(false);

  const scanMutation = useMutation({
    mutationFn: (token: string) =>
      apiClient.post<CheckInScanResult>(
        `/events/${eventId}/checkin/scan`,
        { token, gate, device_id: deviceIdRef.current },
        orgOptions,
      ),
    onSuccess: (data) => {
      setLastResult(data);
      setFormError(null);
      void queryClient.invalidateQueries({ queryKey: ["checkin-activity", organizationId, eventId] });
    },
    onError: (error: Error) => {
      if (error instanceof ApiError && error.status === 409 && error.payload) {
        setLastResult(error.payload as CheckInScanResult);
        setFormError(null);
        return;
      }
      setFormError(error instanceof ApiError ? error.message : "Scan failed.");
    },
  });

  const manualCheckInMutation = useMutation({
    mutationFn: (registrationId: number) =>
      apiClient.post<CheckInScanResult>(
        `/events/${eventId}/registrations/${registrationId}/manual-checkin`,
        { gate },
        orgOptions,
      ),
    onSuccess: (data) => {
      setLastResult(data);
      setFormError(null);
      void queryClient.invalidateQueries({
        queryKey: ["checkin-search", organizationId, eventId, searchQuery],
      });
      void queryClient.invalidateQueries({
        queryKey: ["checkin-activity", organizationId, eventId],
      });
    },
    onError: (error: Error) => {
      if (error instanceof ApiError && error.status === 409 && error.payload) {
        setLastResult(error.payload as CheckInScanResult);
        setFormError(null);
        return;
      }
      setFormError(error instanceof ApiError ? error.message : "Check-in failed.");
    },
  });

  const walkInMutation = useMutation({
    mutationFn: () =>
      apiClient.post<{ registration: { id: number; registration_number: string }; message: string }>(
        `/events/${eventId}/walk-ins`,
        {
          ...walkInForm,
          gate,
          ticket_type_id: walkInTicketTypeId ? Number(walkInTicketTypeId) : undefined,
          payment_collected: walkInTicketIsPaid ? paymentCollected : undefined,
        },
        orgOptions,
      ),
    onSuccess: (data) => {
      setWalkInForm({ first_name: "", last_name: "", email: "" });
      setWalkInTicketTypeId("");
      setPaymentCollected(false);
      setFormError(null);
      setLastResult({
        status: "success",
        reason: `Walk-in registered: ${data.registration.registration_number}`,
        is_vip: false,
        duplicate_info: null,
        registration: {
          id: data.registration.id,
          registration_number: data.registration.registration_number,
          attendee_name: `${walkInForm.first_name} ${walkInForm.last_name}`.trim(),
          attendee_email: walkInForm.email,
          ticket_type: selectedWalkInTicket?.name ?? "Walk-in",
          table_name: null,
          checked_in_at: new Date().toISOString(),
          checked_in_by: null,
          gate,
        },
      });
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Walk-in registration failed."),
  });

  const syncMutation = useMutation({
    mutationFn: async () => {
      const pending = await listOfflineScans(eventId);
      if (pending.length === 0) return;

      const response = await apiClient.post<{ items: Array<{ idempotency_key: string; status: string }> }>(
        `/events/${eventId}/checkin/sync-batch`,
        { items: pending },
        orgOptions,
      );

      const resolvedKeys = response.items
        .filter((item) => item.status === "success" || item.status === "duplicate" || item.status === "rejected")
        .map((item) => item.idempotency_key);

      await removeOfflineScans(resolvedKeys);
    },
    onSuccess: () => {
      void pendingQuery.refetch();
      void queryClient.invalidateQueries({ queryKey: ["checkin-activity", organizationId, eventId] });
    },
  });

  useEffect(() => {
    const updateOnline = () => setIsOnline(navigator.onLine);
    updateOnline();
    window.addEventListener("online", updateOnline);
    window.addEventListener("offline", updateOnline);
    return () => {
      window.removeEventListener("online", updateOnline);
      window.removeEventListener("offline", updateOnline);
    };
  }, []);

  useEffect(() => {
    if (activeTab !== "scanner" || !isOnline || organizationId === null) return;

    const scanner = new Html5QrcodeScanner(
      "qr-reader",
      { fps: 8, qrbox: { width: 250, height: 250 } },
      false,
    );
    scannerRef.current = scanner;

    scanner.render(
      async (decodedText) => {
        if (scanPendingRef.current) return;
        scanPendingRef.current = true;

        try {
          if (!navigator.onLine) {
            await queueOfflineScan(eventId, {
              idempotency_key: crypto.randomUUID(),
              token: decodedText,
              scanned_at: new Date().toISOString(),
              device_id: deviceIdRef.current,
              gate,
            });
            void pendingQuery.refetch();
            setFormError("Offline — scan queued for sync.");
            return;
          }

          await scanMutation.mutateAsync(decodedText);
        } catch {
          // Handled by mutation onError — suppress re-throw from mutateAsync
        } finally {
          scanPendingRef.current = false;
        }
      },
      () => undefined,
    );

    return () => {
      void scanner.clear().catch(() => undefined);
      scannerRef.current = null;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeTab, eventId, gate, isOnline, organizationId]);

  useEffect(() => {
    if (!isOnline || (pendingQuery.data?.length ?? 0) === 0) return;
    syncMutation.mutate();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isOnline, pendingQuery.data?.length]);

  const handleWalkInChange = useCallback(
    (field: string, value: string) =>
      setWalkInForm((prev) => ({ ...prev, [field]: value })),
    [],
  );

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  const resultColor =
    lastResult?.status === "success"
      ? "border-green-500 bg-green-500/10"
      : lastResult?.status === "duplicate"
        ? "border-amber-500 bg-amber-500/10"
        : lastResult?.status === "rejected"
          ? "border-red-500 bg-red-500/10"
          : "border-border";

  const tabs: { key: CheckInTab; label: string }[] = [
    { key: "scanner", label: "QR Scanner" },
    { key: "search", label: "Name Search" },
    { key: "walkin", label: "Walk-in" },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Check-in</h1>
          <p className="text-sm text-muted-foreground">
            {isOnline ? "Online" : "Offline"} · {(pendingQuery.data?.length ?? 0)} scans pending sync
          </p>
        </div>
        <div className="flex gap-2">
          <Button asChild variant="outline" size="sm">
            <Link href={`/events/${eventId}/checkin/activity`}>Activity feed</Link>
          </Button>
          <Button asChild variant="outline" size="sm">
            <Link href={`/events/${eventId}/edit`}>Back to event</Link>
          </Button>
        </div>
      </div>

      <Card>
        <CardContent className="p-3">
          <Input
            value={gate}
            onChange={(e) => setGate(e.target.value)}
            placeholder="Gate / entrance name"
          />
        </CardContent>
      </Card>

      <div className="flex gap-1 rounded-lg border border-border bg-muted p-1">
        {tabs.map((tab) => (
          <button
            key={tab.key}
            type="button"
            className={`flex-1 rounded-md px-3 py-2 text-sm font-medium transition-colors ${
              activeTab === tab.key
                ? "bg-background text-foreground shadow-sm"
                : "text-muted-foreground hover:text-foreground"
            }`}
            onClick={() => {
              setActiveTab(tab.key);
              setLastResult(null);
              setFormError(null);
            }}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {formError ? <FormErrorBanner message={formError} /> : null}

      {activeTab === "scanner" ? (
        <Card>
          <CardHeader><CardTitle>Camera scanner</CardTitle></CardHeader>
          <CardContent>
            <div id="qr-reader" className="overflow-hidden rounded-md" />
          </CardContent>
        </Card>
      ) : null}

      {activeTab === "search" ? (
        <Card>
          <CardHeader><CardTitle>Search attendees</CardTitle></CardHeader>
          <CardContent className="space-y-4">
            <Input
              placeholder="Search by name, email, or registration number…"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              autoFocus
            />

            {searchResults.isLoading && searchQuery.length >= 2 ? (
              <p className="text-sm text-muted-foreground">Searching…</p>
            ) : null}

            {searchResults.data?.length === 0 && searchQuery.length >= 2 ? (
              <p className="text-sm text-muted-foreground">No matching attendees found.</p>
            ) : null}

            <div className="space-y-2">
              {searchResults.data?.map((result) => (
                <div
                  key={result.id}
                  className="flex flex-wrap items-center justify-between gap-3 rounded-md border border-border p-3"
                >
                  <div className="min-w-0">
                    <p className="font-medium">{result.attendee_name}</p>
                    <p className="text-sm text-muted-foreground">{result.attendee_email}</p>
                    <div className="mt-1 flex flex-wrap gap-1.5">
                      {result.ticket_type ? (
                        <Badge>{result.ticket_type}</Badge>
                      ) : null}
                      <Badge variant="draft">{result.registration_number}</Badge>
                    </div>
                  </div>
                  <div>
                    {result.checked_in_at ? (
                      <Badge variant="published">Checked in</Badge>
                    ) : (
                      <Button
                        type="button"
                        size="sm"
                        disabled={manualCheckInMutation.isPending}
                        onClick={() => manualCheckInMutation.mutate(result.id)}
                      >
                        Check in
                      </Button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      ) : null}

      {activeTab === "walkin" ? (
        <Card>
          <CardHeader>
            <CardTitle>Walk-in registration</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <p className="text-sm text-muted-foreground">
              Register an on-site attendee and check them in immediately.
            </p>

            <div className="grid gap-1.5">
              <Label htmlFor="walkin-ticket">Ticket type</Label>
              <Select value={walkInTicketTypeId} onValueChange={(v) => { setWalkInTicketTypeId(v); setPaymentCollected(false); }}>
                <SelectTrigger id="walkin-ticket">
                  <SelectValue placeholder="Select a ticket type" />
                </SelectTrigger>
                <SelectContent>
                  {ticketTypes.filter((tt) => tt.is_active).map((tt) => (
                    <SelectItem key={tt.id} value={String(tt.id)}>
                      {tt.name} — {tt.price > 0 ? formatCurrency(tt.price, tt.currency) : "Free"}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {walkInTicketIsPaid && selectedWalkInTicket ? (
              <div className="rounded-md border border-orange-300 bg-orange-50 dark:border-orange-700 dark:bg-orange-950/30 p-3 space-y-2">
                <p className="text-sm font-medium text-orange-800 dark:text-orange-300">
                  This ticket costs {formatCurrency(selectedWalkInTicket.price, selectedWalkInTicket.currency)}
                </p>
                <label className="flex items-center gap-2 text-sm cursor-pointer">
                  <input
                    type="checkbox"
                    checked={paymentCollected}
                    onChange={(e) => setPaymentCollected(e.target.checked)}
                    className="rounded border-input"
                  />
                  I confirm payment has been collected
                </label>
              </div>
            ) : null}

            <div className="grid gap-3 sm:grid-cols-2">
              <Input
                placeholder="First name *"
                value={walkInForm.first_name}
                onChange={(e) => handleWalkInChange("first_name", e.target.value)}
              />
              <Input
                placeholder="Last name *"
                value={walkInForm.last_name}
                onChange={(e) => handleWalkInChange("last_name", e.target.value)}
              />
            </div>
            <Input
              placeholder="Email *"
              type="email"
              value={walkInForm.email}
              onChange={(e) => handleWalkInChange("email", e.target.value)}
            />
            <Button
              type="button"
              disabled={
                walkInMutation.isPending ||
                !walkInForm.first_name.trim() ||
                !walkInForm.last_name.trim() ||
                !walkInForm.email.trim() ||
                !walkInTicketTypeId ||
                (walkInTicketIsPaid && !paymentCollected)
              }
              onClick={() => walkInMutation.mutate()}
            >
              {walkInMutation.isPending ? "Registering…" : "Register & check in"}
            </Button>
          </CardContent>
        </Card>
      ) : null}

      {lastResult ? (
        <Card className={resultColor}>
          <CardHeader>
            <CardTitle className="capitalize">
              {lastResult.status}
              {lastResult.is_vip ? " · VIP" : ""}
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            {lastResult.reason ? <p>{lastResult.reason}</p> : null}
            {lastResult.registration ? (
              <>
                <p className="font-medium">{lastResult.registration.attendee_name}</p>
                <p className="text-muted-foreground">{lastResult.registration.ticket_type}</p>
                <p className="text-muted-foreground">{lastResult.registration.registration_number}</p>
                {lastResult.registration.table_name ? (
                  <p className="font-medium text-foreground">
                    Table {lastResult.registration.table_name}
                  </p>
                ) : null}
              </>
            ) : null}
          </CardContent>
        </Card>
      ) : null}
    </div>
  );
}
