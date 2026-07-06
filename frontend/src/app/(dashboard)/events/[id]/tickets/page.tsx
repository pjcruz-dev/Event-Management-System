"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { formatCurrency, SUPPORTED_CURRENCIES } from "@/lib/currency";
import { useCallback, useEffect, useState } from "react";
import { useAuthStore } from "@/stores/auth-store";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Separator } from "@/components/ui/separator";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { FormErrorBanner } from "@/components/shared/form-field";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { TicketTypeRecord, TicketTypeVisibility } from "@/types/ticketing";
import { ApiError } from "@/types/api";

interface TicketFormData {
  name: string;
  description: string;
  price: string;
  currency: string;
  quantity: string;
  per_order_limit: string;
  visibility: TicketTypeVisibility;
  is_active: boolean;
  sales_starts_at: string;
  sales_ends_at: string;
}

function emptyForm(currency: string): TicketFormData {
  return {
    name: "",
    description: "",
    price: "0",
    currency,
    quantity: "",
    per_order_limit: "10",
    visibility: "public",
    is_active: true,
    sales_starts_at: "",
    sales_ends_at: "",
  };
}

function ticketToForm(ticket: TicketTypeRecord): TicketFormData {
  return {
    name: ticket.name,
    description: ticket.description ?? "",
    price: String(ticket.price),
    currency: ticket.currency,
    quantity: ticket.quantity !== null ? String(ticket.quantity) : "",
    per_order_limit: String(ticket.per_order_limit),
    visibility: ticket.visibility,
    is_active: ticket.is_active,
    sales_starts_at: ticket.sales_starts_at?.slice(0, 16) ?? "",
    sales_ends_at: ticket.sales_ends_at?.slice(0, 16) ?? "",
  };
}

function formToPayload(form: TicketFormData) {
  return {
    name: form.name,
    description: form.description || null,
    price: Number(form.price),
    currency: form.currency,
    quantity: form.quantity ? Number(form.quantity) : null,
    per_order_limit: form.per_order_limit ? Number(form.per_order_limit) : null,
    visibility: form.visibility,
    is_active: form.is_active,
    sales_starts_at: form.sales_starts_at || null,
    sales_ends_at: form.sales_ends_at || null,
  };
}

const CURRENCIES = SUPPORTED_CURRENCIES.map((c) => ({
  value: c.code,
  label: c.code,
}));

function TicketFormFields({
  form,
  onChange,
}: {
  form: TicketFormData;
  onChange: (patch: Partial<TicketFormData>) => void;
}) {
  return (
    <div className="grid gap-4 sm:grid-cols-2">
      <div className="sm:col-span-2">
        <Label htmlFor="ticket-name">Name *</Label>
        <Input
          id="ticket-name"
          placeholder="e.g. General Admission"
          value={form.name}
          onChange={(e) => onChange({ name: e.target.value })}
          className="mt-1"
        />
      </div>

      <div className="sm:col-span-2">
        <Label htmlFor="ticket-desc">Description</Label>
        <Textarea
          id="ticket-desc"
          placeholder="Short description for attendees"
          value={form.description}
          onChange={(e) => onChange({ description: e.target.value })}
          rows={2}
          className="mt-1"
        />
      </div>

      <Separator className="sm:col-span-2" />

      <div>
        <Label htmlFor="ticket-price">Price</Label>
        <div className="mt-1 flex gap-2">
          <Input
            id="ticket-price"
            type="number"
            min={0}
            step="0.01"
            value={form.price}
            onChange={(e) => onChange({ price: e.target.value })}
            className="flex-1"
          />
          <Select
            value={form.currency}
            onValueChange={(val) => onChange({ currency: val })}
          >
            <SelectTrigger className="w-28">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {CURRENCIES.map((c) => (
                <SelectItem key={c.value} value={c.value}>
                  {c.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        {Number(form.price) === 0 && (
          <p className="mt-1 text-xs text-muted-foreground">
            Free ticket — auto-confirmed on registration.
          </p>
        )}
      </div>

      <div>
        <Label htmlFor="ticket-qty">Total quantity</Label>
        <Input
          id="ticket-qty"
          type="number"
          min={1}
          placeholder="Leave empty for unlimited"
          value={form.quantity}
          onChange={(e) => onChange({ quantity: e.target.value })}
          className="mt-1"
        />
      </div>

      <div>
        <Label htmlFor="ticket-limit">Per-order limit</Label>
        <Input
          id="ticket-limit"
          type="number"
          min={1}
          max={100}
          value={form.per_order_limit}
          onChange={(e) => onChange({ per_order_limit: e.target.value })}
          className="mt-1"
        />
      </div>

      <div>
        <Label htmlFor="ticket-visibility">Visibility</Label>
        <Select
          value={form.visibility}
          onValueChange={(val) =>
            onChange({ visibility: val as TicketTypeVisibility })
          }
        >
          <SelectTrigger className="mt-1 w-full">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="public">Public</SelectItem>
            <SelectItem value="hidden">Hidden</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Separator className="sm:col-span-2" />

      <div>
        <Label htmlFor="ticket-starts">Sales start</Label>
        <Input
          id="ticket-starts"
          type="datetime-local"
          value={form.sales_starts_at}
          onChange={(e) => onChange({ sales_starts_at: e.target.value })}
          className="mt-1"
        />
      </div>

      <div>
        <Label htmlFor="ticket-ends">Sales end</Label>
        <Input
          id="ticket-ends"
          type="datetime-local"
          value={form.sales_ends_at}
          onChange={(e) => onChange({ sales_ends_at: e.target.value })}
          className="mt-1"
        />
      </div>

      <Separator className="sm:col-span-2" />

      <div className="flex items-center gap-3 sm:col-span-2">
        <Switch
          id="ticket-active"
          checked={form.is_active}
          onCheckedChange={(checked) => onChange({ is_active: checked })}
        />
        <Label htmlFor="ticket-active" className="cursor-pointer">
          Active (tickets are available for purchase)
        </Label>
      </div>
    </div>
  );
}

function TicketFormDialog({
  open,
  onOpenChange,
  mode,
  initialData,
  eventId,
  ticketId,
  orgOptions,
  onSuccess,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  mode: "create" | "edit";
  initialData: TicketFormData;
  eventId: number;
  ticketId?: number;
  orgOptions: ReturnType<typeof orgRequestOptions>;
  onSuccess: () => void;
}) {
  const [form, setForm] = useState<TicketFormData>(initialData);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (open) {
      setForm(initialData);
      setError(null);
    }
  }, [open, initialData]);

  const onChange = useCallback((patch: Partial<TicketFormData>) => {
    setForm((prev) => ({ ...prev, ...patch }));
  }, []);

  const mutation = useMutation({
    mutationFn: () => {
      if (mode === "create") {
        return apiClient.post<TicketTypeRecord>(
          `/events/${eventId}/ticket-types`,
          formToPayload(form),
          orgOptions,
        );
      }
      return apiClient.put<TicketTypeRecord>(
        `/events/${eventId}/ticket-types/${ticketId}`,
        formToPayload(form),
        orgOptions,
      );
    },
    onSuccess: () => {
      setError(null);
      onOpenChange(false);
      onSuccess();
    },
    onError: (err: Error) =>
      setError(
        err instanceof ApiError
          ? err.message
          : mode === "create"
            ? "Could not create ticket type."
            : "Update failed.",
      ),
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>
            {mode === "create" ? "New ticket type" : "Edit ticket type"}
          </DialogTitle>
          <DialogDescription>
            {mode === "create"
              ? "Create a new ticket type for this event."
              : "Update the ticket type details."}
          </DialogDescription>
        </DialogHeader>
        {error && <FormErrorBanner message={error} />}
        <TicketFormFields form={form} onChange={onChange} />
        <DialogFooter className="gap-2 sm:gap-0">
          <Button
            type="button"
            variant="outline"
            onClick={() => onOpenChange(false)}
          >
            Cancel
          </Button>
          <Button
            type="button"
            disabled={mutation.isPending || !form.name.trim()}
            onClick={() => mutation.mutate()}
          >
            {mutation.isPending
              ? mode === "create"
                ? "Creating..."
                : "Saving..."
              : mode === "create"
                ? "Create ticket type"
                : "Save changes"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function TicketCard({
  ticket,
  onEdit,
  onToggle,
  onDelete,
  isToggling: togglePending,
}: {
  ticket: TicketTypeRecord;
  onEdit: () => void;
  onToggle: () => void;
  onDelete: () => void;
  isToggling: boolean;
}) {
  const isFree = ticket.price === 0;

  return (
    <Card className={!ticket.is_active ? "opacity-60" : undefined}>
      <CardContent className="flex flex-wrap items-start justify-between gap-4 p-4">
        <div className="min-w-0 flex-1 space-y-1.5">
          <div className="flex flex-wrap items-center gap-2">
            <p className="text-base font-semibold">{ticket.name}</p>
            {ticket.is_on_sale ? (
              <Badge variant="published">On sale</Badge>
            ) : (
              <Badge variant="archived">Off sale</Badge>
            )}
            {ticket.visibility === "hidden" && (
              <Badge variant="draft">Hidden</Badge>
            )}
            {isFree && <Badge>Free</Badge>}
          </div>

          {ticket.description && (
            <p className="text-sm text-muted-foreground line-clamp-2">
              {ticket.description}
            </p>
          )}

          <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
            <span className="font-medium text-foreground">
              {isFree
                ? "Free"
                : formatCurrency(ticket.price, ticket.currency)}
            </span>
            {ticket.quantity !== null ? (
              <span>
                {ticket.quantity_sold} / {ticket.quantity} sold
                {ticket.remaining_quantity !== null &&
                  ` · ${ticket.remaining_quantity} remaining`}
              </span>
            ) : (
              <span>{ticket.quantity_sold} sold · Unlimited</span>
            )}
            <span>Limit: {ticket.per_order_limit}/order</span>
          </div>

          {(ticket.sales_starts_at || ticket.sales_ends_at) && (
            <p className="text-xs text-muted-foreground">
              {ticket.sales_starts_at &&
                `Opens: ${new Date(ticket.sales_starts_at).toLocaleDateString()} `}
              {ticket.sales_ends_at &&
                `Closes: ${new Date(ticket.sales_ends_at).toLocaleDateString()}`}
            </p>
          )}
        </div>

        <div className="flex flex-shrink-0 gap-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={onToggle}
            disabled={togglePending}
          >
            {ticket.is_active ? "Deactivate" : "Activate"}
          </Button>
          <Button type="button" variant="outline" size="sm" onClick={onEdit}>
            Edit
          </Button>
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="border-destructive/30 text-destructive hover:bg-destructive/10 hover:text-destructive"
            onClick={onDelete}
          >
            Delete
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}

export default function EventTicketsPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();
  const organizations = useAuthStore((s) => s.organizations);
  const activeOrg = organizations.find((o) => o.id === organizationId);
  const orgCurrency = activeOrg?.default_currency ?? "USD";

  const [dialogOpen, setDialogOpen] = useState(false);
  const [dialogMode, setDialogMode] = useState<"create" | "edit">("create");
  const [editingTicket, setEditingTicket] = useState<TicketTypeRecord | null>(
    null,
  );
  const [cardError, setCardError] = useState<string | null>(null);

  const ticketsQuery = useQuery({
    queryKey: ["ticket-types", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () =>
      apiClient.get<TicketTypeRecord[]>(
        `/events/${eventId}/ticket-types`,
        orgOptions,
      ),
  });

  const tickets = ticketsQuery.data ?? [];
  const onSaleCount = tickets.filter((t) => t.is_on_sale).length;
  const totalSold = tickets.reduce((sum, t) => sum + t.quantity_sold, 0);

  const invalidate = useCallback(() => {
    void queryClient.invalidateQueries({
      queryKey: ["ticket-types", organizationId, eventId],
    });
  }, [queryClient, organizationId, eventId]);

  const toggleMutation = useMutation({
    mutationFn: (ticket: TicketTypeRecord) =>
      apiClient.put<TicketTypeRecord>(
        `/events/${eventId}/ticket-types/${ticket.id}`,
        { is_active: !ticket.is_active },
        orgOptions,
      ),
    onSuccess: () => invalidate(),
  });

  const deleteMutation = useMutation({
    mutationFn: (ticketId: number) =>
      apiClient.delete(
        `/events/${eventId}/ticket-types/${ticketId}`,
        orgOptions,
      ),
    onSuccess: () => {
      setCardError(null);
      invalidate();
    },
    onError: (err: Error) =>
      setCardError(
        err instanceof ApiError ? err.message : "Delete failed.",
      ),
  });

  const openCreate = () => {
    setEditingTicket(null);
    setDialogMode("create");
    setDialogOpen(true);
  };

  const openEdit = (ticket: TicketTypeRecord) => {
    setEditingTicket(ticket);
    setDialogMode("edit");
    setDialogOpen(true);
  };

  const handleDelete = (ticket: TicketTypeRecord) => {
    if (ticket.quantity_sold > 0) {
      setCardError(
        `Cannot delete "${ticket.name}" because it has sales. Deactivate it instead.`,
      );
      return;
    }
    if (confirm(`Delete "${ticket.name}"? This cannot be undone.`)) {
      deleteMutation.mutate(ticket.id);
    }
  };

  if (organizationId === null) {
    return (
      <p className="text-sm text-muted-foreground">
        Select an organization first.
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Ticket types</h1>
          <p className="text-sm text-muted-foreground">
            {tickets.length} ticket type{tickets.length !== 1 ? "s" : ""}
            {" · "}
            {onSaleCount} on sale
            {" · "}
            {totalSold} total sold
          </p>
        </div>
        <div className="flex gap-2">
          <Button type="button" onClick={openCreate}>
            Add ticket type
          </Button>
          <Button asChild variant="outline">
            <Link href={`/events/${eventId}/edit`}>Back to event</Link>
          </Button>
        </div>
      </div>

      {cardError && (
        <FormErrorBanner
          message={cardError}
          onDismiss={() => setCardError(null)}
        />
      )}

      {ticketsQuery.isLoading ? (
        <p className="text-sm text-muted-foreground">
          Loading ticket types...
        </p>
      ) : tickets.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center">
            <p className="text-muted-foreground">No ticket types yet.</p>
            <p className="mt-1 text-sm text-muted-foreground">
              Create your first ticket type to enable registrations for this
              event.
            </p>
            <Button type="button" className="mt-4" onClick={openCreate}>
              Create ticket type
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-3">
          {tickets.map((ticket) => (
            <TicketCard
              key={ticket.id}
              ticket={ticket}
              onEdit={() => openEdit(ticket)}
              onToggle={() => toggleMutation.mutate(ticket)}
              onDelete={() => handleDelete(ticket)}
              isToggling={
                toggleMutation.isPending &&
                toggleMutation.variables?.id === ticket.id
              }
            />
          ))}
        </div>
      )}

      <TicketFormDialog
        open={dialogOpen}
        onOpenChange={setDialogOpen}
        mode={dialogMode}
        initialData={
          dialogMode === "edit" && editingTicket
            ? ticketToForm(editingTicket)
            : emptyForm(orgCurrency)
        }
        eventId={eventId}
        ticketId={editingTicket?.id}
        orgOptions={orgOptions}
        onSuccess={invalidate}
      />
    </div>
  );
}
