"use client";

import Link from "next/link";
import { useParams, useSearchParams } from "next/navigation";
import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FormErrorBanner, FormField } from "@/components/shared/form-field";
import { apiClient } from "@/lib/api-client";
import { formatCurrency } from "@/lib/currency";
import type {
  CheckoutSessionResponse,
  CouponPreview,
  PublicEventPayload,
  RegistrationCheckoutResponse,
  RegistrationFormRecord,
  TicketTypeRecord,
} from "@/types/ticketing";
import { ApiError } from "@/types/api";

type Step = "tickets" | "form" | "coupon" | "summary";

export default function PublicRegisterPage() {
  const params = useParams<{ slug: string }>();
  const searchParams = useSearchParams();
  const slug = params.slug;
  const ticketParam = searchParams.get("ticket");

  const [step, setStep] = useState<Step>("tickets");
  const [selectedTicket, setSelectedTicket] = useState<TicketTypeRecord | null>(null);
  const [quantity, setQuantity] = useState(1);
  const [couponCode, setCouponCode] = useState("");
  const [couponPreview, setCouponPreview] = useState<CouponPreview | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [checkout, setCheckout] = useState<RegistrationCheckoutResponse | null>(null);
  const [redirectingToStripe, setRedirectingToStripe] = useState(false);

  const [attendee, setAttendee] = useState({
    attendee_first_name: "",
    attendee_last_name: "",
    attendee_email: "",
    attendee_phone: "",
  });
  const [customFields, setCustomFields] = useState<Record<string, string>>({});

  const eventQuery = useQuery({
    queryKey: ["public-event", slug, ticketParam],
    queryFn: () => {
      const qs = ticketParam ? `?ticket=${ticketParam}` : "";
      return apiClient.get<PublicEventPayload>(`/public/events/${slug}${qs}`);
    },
  });

  const formQuery = useQuery({
    queryKey: ["public-registration-form", slug],
    queryFn: () =>
      apiClient.get<RegistrationFormRecord | { fields: [] }>(`/public/events/${slug}/registration-form`),
  });

  const fields = formQuery.data && "fields" in formQuery.data ? formQuery.data.fields : [];

  const subtotal = useMemo(() => {
    if (!selectedTicket) return 0;
    return selectedTicket.price * quantity;
  }, [selectedTicket, quantity]);

  const applyCouponMutation = useMutation({
    mutationFn: () =>
      apiClient.post<CouponPreview>(`/public/events/${slug}/apply-coupon`, {
        code: couponCode,
        ticket_type_id: selectedTicket?.id,
        quantity,
      }),
    onSuccess: (data) => {
      setCouponPreview(data);
      setFormError(null);
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Coupon could not be applied."),
  });

  const registerMutation = useMutation({
    mutationFn: () =>
      apiClient.post<RegistrationCheckoutResponse>(`/public/events/${slug}/register`, {
        ticket_type_id: selectedTicket?.id,
        quantity,
        ...attendee,
        custom_fields: customFields,
        coupon_code: couponCode || undefined,
      }),
    onSuccess: async (data) => {
      setFormError(null);

      if (data.status === "pending_payment" && data.order?.order_number) {
        setRedirectingToStripe(true);
        try {
          const session = await apiClient.post<CheckoutSessionResponse>(
            `/public/orders/${data.order.order_number}/checkout`,
          );
          window.location.href = session.checkout_url;
        } catch {
          setRedirectingToStripe(false);
          setCheckout(data);
          setStep("summary");
        }
        return;
      }

      setCheckout(data);
      setStep("summary");
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Registration failed."),
  });

  const event = eventQuery.data?.event;
  const tickets = eventQuery.data?.ticket_types ?? [];
  const onSaleTickets = tickets.filter((ticket) => ticket.is_on_sale);

  useEffect(() => {
    if (!ticketParam || selectedTicket) return;
    const match = onSaleTickets.find((ticket) => String(ticket.id) === ticketParam);
    if (match) setSelectedTicket(match);
  }, [ticketParam, onSaleTickets, selectedTicket]);

  if (eventQuery.isLoading) {
    return <p className="mx-auto max-w-3xl p-8 text-sm text-muted-foreground">Loading event…</p>;
  }

  if (!event) {
    return <p className="mx-auto max-w-3xl p-8 text-sm text-muted-foreground">Event not found.</p>;
  }

  return (
    <main className="mx-auto max-w-3xl space-y-6 px-6 py-8 md:py-10">
      <div>
        <Link href={`/e/${slug}`} className="text-sm text-muted-foreground hover:underline">
          ← Back to event
        </Link>
        <h1 className="mt-2 text-2xl font-semibold">Register for {event.name}</h1>
        <p className="text-sm text-muted-foreground">{event.venue}</p>
      </div>

      {formError ? <FormErrorBanner message={formError} /> : null}

      {step === "tickets" ? (
        <Card>
          <CardHeader><CardTitle>Select a ticket</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {onSaleTickets.length === 0 ? (
              <div className="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                <p>No tickets are on sale right now.</p>
                <p className="mt-2">
                  The organizer may still be setting up ticket types, or sales may not have opened yet.
                </p>
                <Button asChild className="mt-4" variant="outline">
                  <Link href={`/e/${slug}`}>Return to event page</Link>
                </Button>
              </div>
            ) : (
              onSaleTickets.map((ticket) => (
              <button
                key={ticket.id}
                type="button"
                className={`w-full rounded-md border p-4 text-left ${selectedTicket?.id === ticket.id ? "border-primary" : "border-border"}`}
                onClick={() => setSelectedTicket(ticket)}
              >
                <p className="font-medium">{ticket.name}</p>
                <p className="text-sm text-muted-foreground">
                  {formatCurrency(ticket.price, ticket.currency)}
                  {ticket.remaining_quantity !== null ? ` · ${ticket.remaining_quantity} left` : ""}
                </p>
              </button>
              ))
            )}
            <FormField label="Quantity" htmlFor="quantity">
              <Input
                id="quantity"
                type="number"
                min={1}
                value={quantity}
                onChange={(e) => setQuantity(Number(e.target.value))}
              />
            </FormField>
            {onSaleTickets.length > 0 ? (
              <Button type="button" disabled={!selectedTicket} onClick={() => setStep("form")}>
                Continue
              </Button>
            ) : null}
          </CardContent>
        </Card>
      ) : null}

      {step === "form" ? (
        <Card>
          <CardHeader><CardTitle>Your details</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            <FormField label="First name" htmlFor="first_name">
              <Input id="first_name" value={attendee.attendee_first_name} onChange={(e) => setAttendee({ ...attendee, attendee_first_name: e.target.value })} />
            </FormField>
            <FormField label="Last name" htmlFor="last_name">
              <Input id="last_name" value={attendee.attendee_last_name} onChange={(e) => setAttendee({ ...attendee, attendee_last_name: e.target.value })} />
            </FormField>
            <FormField label="Email" htmlFor="email">
              <Input id="email" type="email" value={attendee.attendee_email} onChange={(e) => setAttendee({ ...attendee, attendee_email: e.target.value })} />
            </FormField>
            {fields.map((field) => (
              <FormField key={field.key} label={field.type === "checkbox" ? "" : field.label} htmlFor={field.key}>
                {field.type === "textarea" ? (
                  <Textarea
                    id={field.key}
                    value={customFields[field.key] ?? ""}
                    onChange={(e) => setCustomFields({ ...customFields, [field.key]: e.target.value })}
                  />
                ) : field.type === "select" ? (
                  <select
                    id={field.key}
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    value={customFields[field.key] ?? ""}
                    onChange={(e) => setCustomFields({ ...customFields, [field.key]: e.target.value })}
                  >
                    <option value="">Select an option</option>
                    {field.options.map((option) => (
                      <option key={option} value={option}>
                        {option}
                      </option>
                    ))}
                  </select>
                ) : field.type === "checkbox" ? (
                  <label className="flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      id={field.key}
                      checked={customFields[field.key] === "true"}
                      onChange={(e) =>
                        setCustomFields({
                          ...customFields,
                          [field.key]: e.target.checked ? "true" : "false",
                        })
                      }
                    />
                    {field.label}
                    {field.required ? <span className="text-destructive"> *</span> : null}
                  </label>
                ) : (
                  <Input
                    id={field.key}
                    type={field.type === "email" ? "email" : field.type === "phone" ? "tel" : "text"}
                    value={customFields[field.key] ?? ""}
                    onChange={(e) => setCustomFields({ ...customFields, [field.key]: e.target.value })}
                  />
                )}
              </FormField>
            ))}
            <div className="flex gap-2">
              <Button type="button" variant="outline" onClick={() => setStep("tickets")}>Back</Button>
              <Button type="button" onClick={() => setStep("coupon")}>Continue</Button>
            </div>
          </CardContent>
        </Card>
      ) : null}

      {step === "coupon" ? (
        <Card>
          <CardHeader><CardTitle>Coupon & summary</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            <p className="text-sm">Subtotal: {formatCurrency(subtotal, selectedTicket?.currency ?? "USD")}</p>
            <div className="flex gap-2">
              <Input placeholder="Coupon code" value={couponCode} onChange={(e) => setCouponCode(e.target.value)} />
              <Button type="button" variant="outline" onClick={() => applyCouponMutation.mutate()}>Apply</Button>
            </div>
            {couponPreview ? (
              <p className="text-sm text-muted-foreground">
                Total after discount: {formatCurrency(couponPreview.total, couponPreview.currency)}
              </p>
            ) : null}
            <div className="flex gap-2">
              <Button type="button" variant="outline" onClick={() => setStep("form")}>Back</Button>
              <Button type="button" disabled={registerMutation.isPending} onClick={() => registerMutation.mutate()}>
                Complete registration
              </Button>
            </div>
          </CardContent>
        </Card>
      ) : null}

      {redirectingToStripe ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12 space-y-3">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
            <p className="text-sm text-muted-foreground">Redirecting to payment…</p>
          </CardContent>
        </Card>
      ) : null}

      {step === "summary" && checkout ? (
        <Card>
          <CardHeader><CardTitle>Registration complete</CardTitle></CardHeader>
          <CardContent className="space-y-2 text-sm">
            {checkout.status === "waitlisted" ? (
              <p>You have been added to the waiting list. We will email you if a spot opens up.</p>
            ) : checkout.status === "confirmed" ? (
              <>
                <p>Registration confirmed. Check your email for your ticket with QR code.</p>
                <p className="text-muted-foreground">No payment was required for this registration.</p>
              </>
            ) : (
              <>
                <p>Order {checkout.order?.order_number} is pending payment.</p>
                <p>
                  Total due: {formatCurrency(checkout.order?.total ?? 0, checkout.order?.currency ?? "USD")}
                </p>
                <Button
                  type="button"
                  className="mt-2"
                  onClick={async () => {
                    if (!checkout.order?.order_number) return;
                    setRedirectingToStripe(true);
                    try {
                      const session = await apiClient.post<CheckoutSessionResponse>(
                        `/public/orders/${checkout.order.order_number}/checkout`,
                      );
                      window.location.href = session.checkout_url;
                    } catch {
                      setRedirectingToStripe(false);
                      setFormError("Unable to create checkout session. Please try again.");
                    }
                  }}
                >
                  Pay now
                </Button>
              </>
            )}
          </CardContent>
        </Card>
      ) : null}
    </main>
  );
}
