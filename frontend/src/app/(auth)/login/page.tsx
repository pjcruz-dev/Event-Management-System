"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
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
import { loginSchema, type LoginFormValues } from "@/features/auth/schemas";
import { apiClient } from "@/lib/api-client";
import { useAuthStore } from "@/stores/auth-store";
import type { AuthTokenResponse } from "@/types/auth";
import { ApiError } from "@/types/api";

export default function LoginPage() {
  const router = useRouter();
  const setAuth = useAuthStore((s) => s.setAuth);
  const fetchMe = useAuthStore((s) => s.fetchMe);
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: "", password: "" },
  });

  const onSubmit = async (values: LoginFormValues) => {
    setFormError(null);
    try {
      const data = await apiClient.post<AuthTokenResponse>("/auth/login", {
        ...values,
        device_name: "web",
      });
      setAuth(data);
      await fetchMe();
      router.push("/dashboard");
    } catch (error) {
      setFormError(
        error instanceof ApiError ? error.message : "Unable to sign in.",
      );
    }
  };

  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle>Sign in</CardTitle>
        <CardDescription>
          Access your organizer dashboard and events.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {formError ? <FormErrorBanner message={formError} /> : null}
          <FormField label="Email" htmlFor="email" error={errors.email?.message}>
            <Input
              id="email"
              type="email"
              autoComplete="email"
              aria-invalid={Boolean(errors.email)}
              {...register("email")}
            />
          </FormField>
          <FormField
            label="Password"
            htmlFor="password"
            error={errors.password?.message}
          >
            <Input
              id="password"
              type="password"
              autoComplete="current-password"
              aria-invalid={Boolean(errors.password)}
              {...register("password")}
            />
          </FormField>
          <Button type="submit" className="w-full" disabled={isSubmitting}>
            {isSubmitting ? "Signing in…" : "Sign in"}
          </Button>
        </form>
        <div className="mt-4 space-y-2 text-center text-sm text-muted-foreground">
          <p>
            <Link href="/forgot-password" className="text-foreground underline">
              Forgot password?
            </Link>
          </p>
          <p>
            No account?{" "}
            <Link href="/register" className="text-foreground underline">
              Create one
            </Link>
          </p>
        </div>
      </CardContent>
    </Card>
  );
}
