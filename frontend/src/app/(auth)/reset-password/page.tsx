"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Suspense, useState } from "react";
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
  resetPasswordSchema,
  type ResetPasswordFormValues,
} from "@/features/auth/schemas";
import { apiClient } from "@/lib/api-client";
import { ApiError } from "@/types/api";

function ResetPasswordForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [formError, setFormError] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ResetPasswordFormValues>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      email: searchParams.get("email") ?? "",
      token: searchParams.get("token") ?? "",
      password: "",
      password_confirmation: "",
    },
  });

  const onSubmit = async (values: ResetPasswordFormValues) => {
    setFormError(null);
    try {
      await apiClient.post("/auth/reset-password", values);
      router.push("/login");
    } catch (error) {
      setFormError(
        error instanceof ApiError ? error.message : "Unable to reset password.",
      );
    }
  };

  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle>Choose a new password</CardTitle>
        <CardDescription>Enter and confirm your new password.</CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {formError ? <FormErrorBanner message={formError} /> : null}
          <input type="hidden" {...register("token")} />
          <input type="hidden" {...register("email")} />
          <FormField
            label="New password"
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
            {isSubmitting ? "Saving…" : "Reset password"}
          </Button>
        </form>
        <p className="mt-4 text-center text-sm text-muted-foreground">
          <Link href="/login" className="text-foreground underline">
            Back to sign in
          </Link>
        </p>
      </CardContent>
    </Card>
  );
}

export default function ResetPasswordPage() {
  return (
    <Suspense fallback={<p className="text-sm text-muted-foreground">Loading…</p>}>
      <ResetPasswordForm />
    </Suspense>
  );
}
