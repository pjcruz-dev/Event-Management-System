"use client";

import Link from "next/link";
import { useParams, useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { CreditCard, Loader2, AlertCircle } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import type { CheckoutSessionResponse } from "@/types/ticketing";

const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8001/api/v1";

export default function CheckoutPage() {
  const params = useParams<{ slug: string }>();
  const searchParams = useSearchParams();
  const slug = params.slug;
  const orderNumber = searchParams.get("order");

  const [status, setStatus] = useState<"loading" | "redirecting" | "error">("loading");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!orderNumber) {
      setStatus("error");
      setErrorMessage("No order number provided.");
      return;
    }

    let cancelled = false;

    async function initiateCheckout() {
      try {
        const res = await fetch(
          `${API_BASE_URL}/public/orders/${orderNumber}/checkout`,
          { method: "POST", headers: { "Content-Type": "application/json" } },
        );
        const json = await res.json();

        if (cancelled) return;

        if (!res.ok || !json.success) {
          setStatus("error");
          setErrorMessage(json.message ?? "Unable to create checkout session.");
          return;
        }

        const data = json.data as CheckoutSessionResponse;
        setStatus("redirecting");
        window.location.href = data.checkout_url;
      } catch {
        if (!cancelled) {
          setStatus("error");
          setErrorMessage("Something went wrong. Please try again.");
        }
      }
    }

    void initiateCheckout();
    return () => { cancelled = true; };
  }, [orderNumber]);

  if (status === "error") {
    return (
      <main className="mx-auto flex min-h-[60vh] max-w-xl items-center justify-center px-6 py-12">
        <Card className="w-full">
          <CardContent className="flex flex-col items-center space-y-4 pt-8 pb-8 text-center">
            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
              <AlertCircle className="h-8 w-8 text-red-600 dark:text-red-400" />
            </div>
            <h1 className="text-2xl font-semibold">Unable to proceed</h1>
            <p className="text-sm text-muted-foreground max-w-sm">{errorMessage}</p>
            {orderNumber ? (
              <p className="text-xs text-muted-foreground">
                Order: <span className="font-mono">{orderNumber}</span>
              </p>
            ) : null}
            <div className="flex gap-3 mt-4">
              <Button asChild variant="outline">
                <Link href={`/e/${slug}`}>Back to event</Link>
              </Button>
              <Button onClick={() => { setStatus("loading"); window.location.reload(); }}>
                Try again
              </Button>
            </div>
          </CardContent>
        </Card>
      </main>
    );
  }

  return (
    <main className="mx-auto flex min-h-[60vh] max-w-xl items-center justify-center px-6 py-12">
      <Card className="w-full">
        <CardContent className="flex flex-col items-center space-y-4 pt-8 pb-8 text-center">
          <div className="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
            {status === "redirecting" ? (
              <CreditCard className="h-8 w-8 text-primary" />
            ) : (
              <Loader2 className="h-8 w-8 text-primary animate-spin" />
            )}
          </div>
          <h1 className="text-2xl font-semibold">
            {status === "redirecting" ? "Redirecting to payment…" : "Preparing checkout…"}
          </h1>
          <p className="text-sm text-muted-foreground max-w-sm">
            {status === "redirecting"
              ? "You are being redirected to our secure payment provider."
              : "Please wait while we set up your payment session."}
          </p>
          {orderNumber ? (
            <p className="text-xs text-muted-foreground">
              Order: <span className="font-mono">{orderNumber}</span>
            </p>
          ) : null}
        </CardContent>
      </Card>
    </main>
  );
}
