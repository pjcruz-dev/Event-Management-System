"use client";

import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import { formatCurrency } from "@/lib/currency";
import type { OrderListResponse, OrderRecord } from "@/types/ticketing";
import { ApiError } from "@/types/api";
import Link from "next/link";

const STATUS_VARIANTS: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
  paid: "default",
  pending_payment: "secondary",
  refunded: "destructive",
  cancelled: "outline",
  failed: "destructive",
};

function statusLabel(status: string): string {
  return status.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

export default function EventOrdersPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [refundOrder, setRefundOrder] = useState<OrderRecord | null>(null);
  const [refundAmount, setRefundAmount] = useState("");
  const [refundReason, setRefundReason] = useState("");
  const [refundError, setRefundError] = useState<string | null>(null);
  const [detailOrder, setDetailOrder] = useState<OrderRecord | null>(null);

  const ordersQuery = useQuery({
    queryKey: ["orders", organizationId, eventId, search, statusFilter],
    enabled: organizationId !== null,
    queryFn: () => {
      const params = new URLSearchParams();
      if (search) params.set("search", search);
      if (statusFilter) params.set("status", statusFilter);
      const qs = params.toString();
      return apiClient.get<OrderListResponse>(
        `/events/${eventId}/orders${qs ? `?${qs}` : ""}`,
        orgOptions,
      );
    },
  });

  const refundMutation = useMutation({
    mutationFn: (data: { orderId: number; amount?: number; reason?: string }) =>
      apiClient.post<OrderRecord>(
        `/events/${eventId}/orders/${data.orderId}/refund`,
        {
          amount: data.amount || undefined,
          reason: data.reason || undefined,
        },
        orgOptions,
      ),
    onSuccess: () => {
      setRefundOrder(null);
      setRefundAmount("");
      setRefundReason("");
      setRefundError(null);
      void queryClient.invalidateQueries({ queryKey: ["orders", organizationId, eventId] });
    },
    onError: (error: Error) =>
      setRefundError(error instanceof ApiError ? error.message : "Refund failed."),
  });

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  const orders = ordersQuery.data?.items ?? [];
  const pagination = ordersQuery.data?.pagination;

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Orders</h1>
          <p className="text-sm text-muted-foreground">
            View and manage ticket orders for this event.
          </p>
        </div>
        <Button asChild variant="outline" size="sm">
          <Link href={`/events/${eventId}/edit`}>Back to event</Link>
        </Button>
      </div>

      <Card>
        <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-3">
          <CardTitle>
            All orders{pagination ? ` (${pagination.total})` : ""}
          </CardTitle>
          <div className="flex flex-wrap gap-2">
            <Input
              placeholder="Search order # or attendee"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-56"
            />
            <select
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
            >
              <option value="">All statuses</option>
              <option value="pending_payment">Pending Payment</option>
              <option value="paid">Paid</option>
              <option value="refunded">Refunded</option>
              <option value="cancelled">Cancelled</option>
              <option value="failed">Failed</option>
            </select>
          </div>
        </CardHeader>
        <CardContent className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-muted-foreground">
                <th className="py-2 pr-4">Order #</th>
                <th className="py-2 pr-4">Attendee</th>
                <th className="py-2 pr-4">Status</th>
                <th className="py-2 pr-4">Total</th>
                <th className="py-2 pr-4">Payment</th>
                <th className="py-2 pr-4">Date</th>
                <th className="py-2 pr-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              {orders.map((order) => {
                const primary = order.registrations?.[0];
                return (
                  <tr key={order.id} className="border-b border-border/60">
                    <td className="py-2 pr-4 font-mono text-xs">
                      <button
                        className="text-primary underline-offset-4 hover:underline"
                        onClick={() => setDetailOrder(order)}
                      >
                        {order.order_number}
                      </button>
                    </td>
                    <td className="py-2 pr-4">
                      {primary ? (
                        <div>
                          <div className="font-medium">{primary.attendee_name}</div>
                          <div className="text-xs text-muted-foreground">{primary.attendee_email}</div>
                        </div>
                      ) : (
                        <span className="text-muted-foreground">—</span>
                      )}
                    </td>
                    <td className="py-2 pr-4">
                      <Badge variant={STATUS_VARIANTS[order.status] ?? "outline"}>
                        {statusLabel(order.status)}
                      </Badge>
                      {order.refund_amount !== null ? (
                        <div className="mt-1 text-xs text-destructive">
                          Refunded {formatCurrency(order.refund_amount, order.currency)}
                        </div>
                      ) : null}
                    </td>
                    <td className="py-2 pr-4 font-medium">
                      {formatCurrency(order.total, order.currency)}
                    </td>
                    <td className="py-2 pr-4 capitalize">
                      {order.payment_method ?? "—"}
                    </td>
                    <td className="py-2 pr-4 text-xs text-muted-foreground">
                      {order.created_at
                        ? new Date(order.created_at).toLocaleDateString()
                        : "—"}
                    </td>
                    <td className="py-2 pr-4">
                      {order.status === "paid" ? (
                        <Button
                          size="sm"
                          variant="destructive"
                          onClick={() => {
                            setRefundOrder(order);
                            setRefundAmount(String(order.total));
                            setRefundReason("");
                            setRefundError(null);
                          }}
                        >
                          Refund
                        </Button>
                      ) : null}
                    </td>
                  </tr>
                );
              })}
              {orders.length === 0 ? (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-muted-foreground">
                    {ordersQuery.isLoading ? "Loading orders..." : "No orders found."}
                  </td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </CardContent>
      </Card>

      {/* Refund Dialog */}
      <Dialog open={refundOrder !== null} onOpenChange={(open) => { if (!open) setRefundOrder(null); }}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Refund Order {refundOrder?.order_number}</DialogTitle>
          </DialogHeader>
          {refundOrder ? (
            <div className="space-y-4">
              <div className="rounded-md bg-muted/50 p-3 text-sm">
                <div>Order total: <strong>{formatCurrency(refundOrder.total, refundOrder.currency)}</strong></div>
                <div>Payment: <span className="capitalize">{refundOrder.payment_method ?? "manual"}</span></div>
                {refundOrder.registrations?.length > 0 ? (
                  <div className="mt-1 text-xs text-muted-foreground">
                    {refundOrder.registrations.length} ticket(s) — full refund will cancel all registrations
                  </div>
                ) : null}
              </div>

              {refundError ? (
                <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                  {refundError}
                </div>
              ) : null}

              <div className="space-y-2">
                <Label htmlFor="refund-amount">Refund amount ({refundOrder.currency})</Label>
                <Input
                  id="refund-amount"
                  type="number"
                  step="0.01"
                  min="0.01"
                  max={refundOrder.total}
                  value={refundAmount}
                  onChange={(e) => setRefundAmount(e.target.value)}
                />
                <p className="text-xs text-muted-foreground">
                  {Number(refundAmount) >= refundOrder.total
                    ? "Full refund — registrations will be cancelled"
                    : "Partial refund — registrations remain active"}
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="refund-reason">Reason (optional)</Label>
                <Textarea
                  id="refund-reason"
                  rows={2}
                  value={refundReason}
                  onChange={(e) => setRefundReason(e.target.value)}
                  placeholder="e.g. Customer requested cancellation"
                />
              </div>
            </div>
          ) : null}
          <DialogFooter>
            <Button variant="outline" onClick={() => setRefundOrder(null)}>
              Cancel
            </Button>
            <Button
              variant="destructive"
              disabled={refundMutation.isPending || !refundAmount || Number(refundAmount) <= 0}
              onClick={() => {
                if (!refundOrder) return;
                refundMutation.mutate({
                  orderId: refundOrder.id,
                  amount: Number(refundAmount),
                  reason: refundReason || undefined,
                });
              }}
            >
              {refundMutation.isPending ? "Processing..." : "Process Refund"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Order Detail Dialog */}
      <Dialog open={detailOrder !== null} onOpenChange={(open) => { if (!open) setDetailOrder(null); }}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>Order {detailOrder?.order_number}</DialogTitle>
          </DialogHeader>
          {detailOrder ? (
            <div className="space-y-4 text-sm">
              <div className="grid grid-cols-2 gap-2">
                <div className="text-muted-foreground">Status</div>
                <div>
                  <Badge variant={STATUS_VARIANTS[detailOrder.status] ?? "outline"}>
                    {statusLabel(detailOrder.status)}
                  </Badge>
                </div>
                <div className="text-muted-foreground">Total</div>
                <div className="font-medium">{formatCurrency(detailOrder.total, detailOrder.currency)}</div>
                {detailOrder.discount_total > 0 ? (
                  <>
                    <div className="text-muted-foreground">Discount</div>
                    <div>-{formatCurrency(detailOrder.discount_total, detailOrder.currency)}</div>
                  </>
                ) : null}
                <div className="text-muted-foreground">Payment</div>
                <div className="capitalize">{detailOrder.payment_method ?? "—"}</div>
                {detailOrder.paid_at ? (
                  <>
                    <div className="text-muted-foreground">Paid at</div>
                    <div>{new Date(detailOrder.paid_at).toLocaleString()}</div>
                  </>
                ) : null}
                {detailOrder.refund_amount !== null ? (
                  <>
                    <div className="text-muted-foreground">Refund</div>
                    <div className="text-destructive">{formatCurrency(detailOrder.refund_amount, detailOrder.currency)}</div>
                    {detailOrder.refund_reason ? (
                      <>
                        <div className="text-muted-foreground">Reason</div>
                        <div>{detailOrder.refund_reason}</div>
                      </>
                    ) : null}
                  </>
                ) : null}
              </div>

              {detailOrder.items?.length > 0 ? (
                <div>
                  <p className="mb-2 font-medium">Line items</p>
                  <div className="space-y-1">
                    {detailOrder.items.map((item) => (
                      <div key={item.id} className="flex justify-between rounded bg-muted/50 px-3 py-1.5">
                        <span>{item.description} &times; {item.quantity}</span>
                        <span>{formatCurrency(item.total, detailOrder.currency)}</span>
                      </div>
                    ))}
                  </div>
                </div>
              ) : null}

              {detailOrder.registrations?.length > 0 ? (
                <div>
                  <p className="mb-2 font-medium">Registrations</p>
                  <div className="space-y-1">
                    {detailOrder.registrations.map((reg) => (
                      <div key={reg.id} className="flex items-center justify-between rounded bg-muted/50 px-3 py-1.5">
                        <div>
                          <div className="font-medium">{reg.attendee_name}</div>
                          <div className="text-xs text-muted-foreground">
                            {reg.ticket_type ?? "General"} — {reg.registration_number}
                          </div>
                        </div>
                        <Badge variant={reg.status === "confirmed" ? "default" : "outline"} className="text-xs">
                          {statusLabel(reg.status)}
                        </Badge>
                      </div>
                    ))}
                  </div>
                </div>
              ) : null}
            </div>
          ) : null}
        </DialogContent>
      </Dialog>
    </div>
  );
}
