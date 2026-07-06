"use client";

import { Suspense, useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

function VerifyEmailContent() {
  const searchParams = useSearchParams();
  const [status, setStatus] = useState<"loading" | "success" | "error">(
    "loading",
  );
  const [message, setMessage] = useState("Verifying your email…");

  useEffect(() => {
    const verificationUrl = searchParams.get("verification_url");
    if (!verificationUrl) {
      setStatus("error");
      setMessage("Verification link is missing or invalid.");
      return;
    }

    void fetch(verificationUrl, {
      headers: { Accept: "application/json" },
    })
      .then(async (response) => {
        const body = (await response.json()) as { message?: string };
        if (!response.ok) {
          throw new Error(body.message ?? "Verification failed.");
        }
        setStatus("success");
        setMessage(body.message ?? "Email verified successfully.");
      })
      .catch((error: unknown) => {
        setStatus("error");
        setMessage(
          error instanceof Error ? error.message : "Verification failed.",
        );
      });
  }, [searchParams]);

  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle>Email verification</CardTitle>
        <CardDescription>
          {status === "loading"
            ? "Please wait while we confirm your email address."
            : status === "success"
              ? "You can now use all organizer features."
              : "We could not verify your email."}
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <p
          className={
            status === "error" ? "text-sm text-destructive" : "text-sm"
          }
        >
          {message}
        </p>
        <Link href="/login" className="text-sm text-foreground underline">
          Continue to sign in
        </Link>
      </CardContent>
    </Card>
  );
}

export default function VerifyEmailPage() {
  return (
    <Suspense fallback={<p className="text-sm text-muted-foreground">Loading…</p>}>
      <VerifyEmailContent />
    </Suspense>
  );
}
