"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { FormErrorBanner, FormField } from "@/components/shared/form-field";
import { apiClient } from "@/lib/api-client";
import { useExhibitorAuthStore } from "@/stores/exhibitor-auth-store";
import type { ExhibitorPortalLoginResponse } from "@/types/conference";
import { ApiError } from "@/types/api";

export default function ExhibitorPortalLoginPage() {
  const router = useRouter();
  const setAuth = useExhibitorAuthStore((s) => s.setAuth);
  const fetchMe = useExhibitorAuthStore((s) => s.fetchMe);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [formError, setFormError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const onSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setFormError(null);
    setIsSubmitting(true);
    try {
      const data = await apiClient.post<ExhibitorPortalLoginResponse>(
        "/exhibitor-portal/auth/login",
        { email, password },
      );
      setAuth(data);
      await fetchMe();
      router.push("/exhibitor-portal/leads");
    } catch (error) {
      setFormError(error instanceof ApiError ? error.message : "Unable to sign in.");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className="mx-auto max-w-md">
      <CardHeader>
        <CardTitle>Exhibitor sign in</CardTitle>
        <CardDescription>
          Access your booth profile, leads, and badge scanner.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form className="space-y-4" onSubmit={(e) => void onSubmit(e)}>
          {formError ? <FormErrorBanner message={formError} /> : null}
          <FormField label="Email" htmlFor="exhibitor-email">
            <Input
              id="exhibitor-email"
              type="email"
              autoComplete="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </FormField>
          <FormField label="Password" htmlFor="exhibitor-password">
            <Input
              id="exhibitor-password"
              type="password"
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </FormField>
          <Button type="submit" className="w-full" disabled={isSubmitting}>
            Sign in
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
