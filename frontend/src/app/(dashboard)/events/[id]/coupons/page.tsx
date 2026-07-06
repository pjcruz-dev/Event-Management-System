"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useCallback, useEffect, useMemo, useState } from "react";
import { formatCurrency } from "@/lib/currency";
import { Plus, Pencil, Trash2, Tag, CalendarDays, Users } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { FormErrorBanner } from "@/components/shared/form-field";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import { useAuthStore } from "@/stores/auth-store";
import type { CouponRecord, TicketTypeRecord } from "@/types/ticketing";
import { ApiError } from "@/types/api";

interface CouponFormData {
  code: string;
  discount_type: "percentage" | "fixed";
  discount_value: string;
  max_uses: string;
  valid_from: string;
  valid_until: string;
  applicable_ticket_type_ids: number[];
  is_active: boolean;
}

const emptyCouponForm: CouponFormData = {
  code: "",
  discount_type: "percentage",
  discount_value: "",
  max_uses: "",
  valid_from: "",
  valid_until: "",
  applicable_ticket_type_ids: [],
  is_active: true,
};

function toLocalDatetimeString(iso: string | null | undefined): string {
  if (!iso) return "";
  const d = new Date(iso);
  if (isNaN(d.getTime())) return "";
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export default function EventCouponsPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();
  const organizations = useAuthStore((s) => s.organizations);
  const activeOrg = organizations.find((o) => o.id === organizationId);
  const orgCurrency = (activeOrg?.settings?.default_currency as string) ?? "USD";

  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingCoupon, setEditingCoupon] = useState<CouponRecord | null>(null);
  const [form, setForm] = useState<CouponFormData>(emptyCouponForm);
  const [formError, setFormError] = useState<string | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<CouponRecord | null>(null);

  const couponsQuery = useQuery({
    queryKey: ["coupons", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () => apiClient.get<CouponRecord[]>(`/events/${eventId}/coupons`, orgOptions),
  });

  const ticketTypesQuery = useQuery({
    queryKey: ["ticket-types", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () => apiClient.get<TicketTypeRecord[]>(`/events/${eventId}/ticket-types`, orgOptions),
  });

  const openCreate = useCallback(() => {
    setEditingCoupon(null);
    setForm(emptyCouponForm);
    setFormError(null);
    setDialogOpen(true);
  }, []);

  const openEdit = useCallback((coupon: CouponRecord) => {
    setEditingCoupon(coupon);
    setForm({
      code: coupon.code,
      discount_type: coupon.discount_type,
      discount_value: String(coupon.discount_value),
      max_uses: coupon.max_uses ? String(coupon.max_uses) : "",
      valid_from: toLocalDatetimeString(coupon.valid_from),
      valid_until: toLocalDatetimeString(coupon.valid_until),
      applicable_ticket_type_ids: coupon.applicable_ticket_type_ids ?? [],
      is_active: coupon.is_active,
    });
    setFormError(null);
    setDialogOpen(true);
  }, []);

  const invalidate = useCallback(
    () => queryClient.invalidateQueries({ queryKey: ["coupons", organizationId, eventId] }),
    [queryClient, organizationId, eventId],
  );

  const saveMutation = useMutation({
    mutationFn: () => {
      const payload: Record<string, unknown> = {
        code: form.code,
        discount_type: form.discount_type,
        discount_value: Number(form.discount_value),
        is_active: form.is_active,
        max_uses: form.max_uses ? Number(form.max_uses) : null,
        valid_from: form.valid_from || null,
        valid_until: form.valid_until || null,
        applicable_ticket_type_ids: form.applicable_ticket_type_ids.length > 0
          ? form.applicable_ticket_type_ids
          : null,
      };

      if (editingCoupon) {
        return apiClient.put<CouponRecord>(
          `/events/${eventId}/coupons/${editingCoupon.id}`,
          payload,
          orgOptions,
        );
      }
      return apiClient.post<CouponRecord>(`/events/${eventId}/coupons`, payload, orgOptions);
    },
    onSuccess: () => {
      setDialogOpen(false);
      void invalidate();
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Could not save coupon."),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => apiClient.delete(`/events/${eventId}/coupons/${id}`, orgOptions),
    onSuccess: () => {
      setDeleteTarget(null);
      void invalidate();
    },
  });

  const ticketTypes = ticketTypesQuery.data ?? [];
  const coupons = couponsQuery.data ?? [];

  const ticketNameMap = useMemo(() => {
    const map = new Map<number, string>();
    for (const tt of ticketTypes) map.set(tt.id, tt.name);
    return map;
  }, [ticketTypes]);

  const toggleTicketType = useCallback((id: number) => {
    setForm((prev) => {
      const ids = prev.applicable_ticket_type_ids.includes(id)
        ? prev.applicable_ticket_type_ids.filter((x) => x !== id)
        : [...prev.applicable_ticket_type_ids, id];
      return { ...prev, applicable_ticket_type_ids: ids };
    });
  }, []);

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">Coupons</h1>
        <div className="flex gap-2">
          <Button onClick={openCreate}><Plus className="mr-1 h-4 w-4" />New coupon</Button>
          <Button asChild variant="outline">
            <Link href={`/events/${eventId}/edit`}>Back to event</Link>
          </Button>
        </div>
      </div>

      {coupons.length === 0 ? (
        <div className="rounded-md border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
          No coupons created yet. Create one to offer discounts on this event.
        </div>
      ) : (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {coupons.map((coupon) => (
            <Card key={coupon.id} className="relative">
              <CardContent className="space-y-2 p-4">
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-2">
                    <Tag className="h-4 w-4 text-muted-foreground" />
                    <span className="font-mono text-sm font-semibold">{coupon.code}</span>
                  </div>
                  <div className="flex gap-1">
                    <Button size="icon" variant="ghost" className="h-7 w-7" onClick={() => openEdit(coupon)}>
                      <Pencil className="h-3.5 w-3.5" />
                    </Button>
                    <Button size="icon" variant="ghost" className="h-7 w-7 text-destructive" onClick={() => setDeleteTarget(coupon)}>
                      <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                  </div>
                </div>

                <div className="flex flex-wrap gap-1.5">
                  <Badge variant={coupon.is_active ? "default" : "secondary"}>
                    {coupon.is_active ? "Active" : "Inactive"}
                  </Badge>
                  <Badge variant="outline">
                    {coupon.discount_type === "percentage"
                      ? `${coupon.discount_value}% off`
                      : `${formatCurrency(Number(coupon.discount_value), orgCurrency)} off`}
                  </Badge>
                </div>

                {(coupon.valid_from || coupon.valid_until) ? (
                  <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <CalendarDays className="h-3.5 w-3.5" />
                    {coupon.valid_from ? new Date(coupon.valid_from).toLocaleDateString() : "∞"}
                    {" – "}
                    {coupon.valid_until ? new Date(coupon.valid_until).toLocaleDateString() : "∞"}
                  </div>
                ) : null}

                <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                  <Users className="h-3.5 w-3.5" />
                  {coupon.times_used} used
                  {coupon.max_uses ? ` / ${coupon.max_uses} max` : " (unlimited)"}
                </div>

                {coupon.applicable_ticket_type_ids && coupon.applicable_ticket_type_ids.length > 0 ? (
                  <div className="flex flex-wrap gap-1">
                    {coupon.applicable_ticket_type_ids.map((id) => (
                      <Badge key={id} variant="outline" className="text-xs">
                        {ticketNameMap.get(id) ?? `#${id}`}
                      </Badge>
                    ))}
                  </div>
                ) : (
                  <p className="text-xs text-muted-foreground">Applies to all tickets</p>
                )}
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Create / Edit Dialog */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle>{editingCoupon ? "Edit coupon" : "Create coupon"}</DialogTitle>
            <DialogDescription>
              {editingCoupon ? "Update coupon settings." : "Configure a new discount coupon."}
            </DialogDescription>
          </DialogHeader>

          {formError ? <FormErrorBanner message={formError} onDismiss={() => setFormError(null)} /> : null}

          <div className="grid gap-4">
            <div className="grid gap-1.5">
              <Label htmlFor="coupon-code">Code</Label>
              <Input
                id="coupon-code"
                placeholder="SUMMER2026"
                value={form.code}
                onChange={(e) => setForm({ ...form, code: e.target.value })}
              />
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="grid gap-1.5">
                <Label htmlFor="coupon-discount-type">Discount type</Label>
                <Select
                  value={form.discount_type}
                  onValueChange={(v) => setForm({ ...form, discount_type: v as "percentage" | "fixed" })}
                >
                  <SelectTrigger id="coupon-discount-type">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="percentage">Percentage (%)</SelectItem>
                    <SelectItem value="fixed">Fixed amount ($)</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="grid gap-1.5">
                <Label htmlFor="coupon-discount-value">
                  {form.discount_type === "percentage" ? "Value (%)" : "Value ($)"}
                </Label>
                <Input
                  id="coupon-discount-value"
                  type="number"
                  min={0}
                  step={form.discount_type === "percentage" ? "1" : "0.01"}
                  max={form.discount_type === "percentage" ? 100 : undefined}
                  placeholder={form.discount_type === "percentage" ? "10" : "5.00"}
                  value={form.discount_value}
                  onChange={(e) => setForm({ ...form, discount_value: e.target.value })}
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="grid gap-1.5">
                <Label htmlFor="coupon-valid-from">Valid from</Label>
                <Input
                  id="coupon-valid-from"
                  type="datetime-local"
                  value={form.valid_from}
                  onChange={(e) => setForm({ ...form, valid_from: e.target.value })}
                />
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="coupon-valid-until">Valid until</Label>
                <Input
                  id="coupon-valid-until"
                  type="datetime-local"
                  value={form.valid_until}
                  onChange={(e) => setForm({ ...form, valid_until: e.target.value })}
                />
              </div>
            </div>

            <div className="grid gap-1.5">
              <Label htmlFor="coupon-max-uses">Max uses (leave empty for unlimited)</Label>
              <Input
                id="coupon-max-uses"
                type="number"
                min={1}
                placeholder="Unlimited"
                value={form.max_uses}
                onChange={(e) => setForm({ ...form, max_uses: e.target.value })}
              />
            </div>

            {ticketTypes.length > 0 ? (
              <div className="grid gap-1.5">
                <Label>Applicable ticket types</Label>
                <p className="text-xs text-muted-foreground">Leave all unchecked to apply to all tickets.</p>
                <div className="grid gap-1.5 max-h-40 overflow-y-auto rounded-md border p-2">
                  {ticketTypes.map((tt) => (
                    <label key={tt.id} className="flex items-center gap-2 text-sm cursor-pointer">
                      <input
                        type="checkbox"
                        className="rounded border-input"
                        checked={form.applicable_ticket_type_ids.includes(tt.id)}
                        onChange={() => toggleTicketType(tt.id)}
                      />
                      {tt.name}
                      <span className="text-xs text-muted-foreground ml-auto">
                        {formatCurrency(tt.price, tt.currency)}
                      </span>
                    </label>
                  ))}
                </div>
              </div>
            ) : null}

            <div className="flex items-center gap-2">
              <Switch
                id="coupon-active"
                checked={form.is_active}
                onCheckedChange={(v) => setForm({ ...form, is_active: v })}
              />
              <Label htmlFor="coupon-active">Active</Label>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
            <Button disabled={saveMutation.isPending} onClick={() => saveMutation.mutate()}>
              {editingCoupon ? "Save changes" : "Create coupon"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation */}
      <AlertDialog open={deleteTarget !== null} onOpenChange={(open) => { if (!open) setDeleteTarget(null); }}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete coupon?</AlertDialogTitle>
            <AlertDialogDescription>
              This will permanently delete the coupon <span className="font-mono font-semibold">{deleteTarget?.code}</span>.
              This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              disabled={deleteMutation.isPending}
              onClick={() => deleteTarget && deleteMutation.mutate(deleteTarget.id)}
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
