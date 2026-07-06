"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useState, Suspense } from "react";
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
import {
  registerSchema,
  type RegisterFormValues,
} from "@/features/auth/schemas";
import { apiClient } from "@/lib/api-client";
import { useAuthStore } from "@/stores/auth-store";
import type { AuthTokenResponse } from "@/types/auth";
import { ApiError } from "@/types/api";

function RegisterForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const invitationToken = searchParams.get("invitation_token") ?? undefined;
  const setAuth = useAuthStore((s) => s.setAuth);
  const fetchMe = useAuthStore((s) => s.fetchMe);
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<RegisterFormValues>({
    resolver: zodResolver(registerSchema),
    defaultValues: {
      name: "",
      email: "",
      password: "",
      password_confirmation: "",
      invitation_token: invitationToken,
    },
  });

  const onSubmit = async (values: RegisterFormValues) => {
    setFormError(null);
    try {
      const data = await apiClient.post<AuthTokenResponse>("/auth/register", {
        ...values,
        device_name: "web",
      });
      setAuth(data);
      await fetchMe();
      router.push("/dashboard");
    } catch (error) {
      setFormError(
        error instanceof ApiError ? error.message : "Unable to register.",
      );
    }
  };

  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle>Create account</CardTitle>
        <CardDescription>
          {invitationToken
            ? "Complete registration to accept your team invitation."
            : "Start organizing events on the platform."}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {formError ? <FormErrorBanner message={formError} /> : null}
          <input type="hidden" {...register("invitation_token")} />
          <FormField label="Name" htmlFor="name" error={errors.name?.message}>
            <Input id="name" autoComplete="name" {...register("name")} />
          </FormField>
          <FormField label="Email" htmlFor="email" error={errors.email?.message}>
            <Input
              id="email"
              type="email"
              autoComplete="email"
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
              autoComplete="new-password"
              {...register("password")}
            />
          </FormField>
          <FormField
            label="Confirm password"
            htmlFor="password_confirmation"
            error={errors.password_confirmation?.message}
          >
            <Input
              id="password_confirmation"
              type="password"
              autoComplete="new-password"
              {...register("password_confirmation")}
            />
          </FormField>
          <Button type="submit" className="w-full" disabled={isSubmitting}>
            {isSubmitting ? "Creating account…" : "Create account"}
          </Button>
        </form>
        <p className="mt-4 text-center text-sm text-muted-foreground">
          Already have an account?{" "}
          <Link href="/login" className="text-foreground underline">
            Sign in
          </Link>
        </p>
      </CardContent>
    </Card>
  );
}

export default function RegisterPage() {
  return (
    <Suspense fallback={<p className="text-sm text-muted-foreground">Loading…</p>}>
      <RegisterForm />
    </Suspense>
  );
}
