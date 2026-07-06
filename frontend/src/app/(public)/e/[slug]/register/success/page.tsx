"use client";

import Link from "next/link";
import { useParams, useSearchParams } from "next/navigation";
import { CheckCircle } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

export default function PaymentSuccessPage() {
  const params = useParams<{ slug: string }>();
  const searchParams = useSearchParams();
  const slug = params.slug;
  const orderNumber = searchParams.get("order");

  return (
    <main className="mx-auto flex min-h-[60vh] max-w-xl items-center justify-center px-6 py-12">
      <Card className="w-full">
        <CardContent className="flex flex-col items-center space-y-4 pt-8 pb-8 text-center">
          <div className="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
            <CheckCircle className="h-8 w-8 text-green-600 dark:text-green-400" />
          </div>
          <h1 className="text-2xl font-semibold">Payment successful!</h1>
          <p className="text-sm text-muted-foreground max-w-sm">
            Your registration is confirmed. You will receive an email with your ticket(s) and QR code(s) shortly.
          </p>
          {orderNumber ? (
            <p className="text-xs text-muted-foreground">
              Order: <span className="font-mono">{orderNumber}</span>
            </p>
          ) : null}
          <Button asChild className="mt-4">
            <Link href={`/e/${slug}`}>Back to event</Link>
          </Button>
        </CardContent>
      </Card>
    </main>
  );
}
