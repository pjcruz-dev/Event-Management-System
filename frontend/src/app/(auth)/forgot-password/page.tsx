"use client";

import Link from "next/link";
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
import {
  forgotPasswordSchema,
  type LoginFormValues,
} from "@/features/auth/schemas";
import { apiClient } from "@/lib/api-client";
import { ApiError } from "@/types/api";

export default function ForgotPasswordPage() {
  const [formError, setFormError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<Pick<LoginFormValues, "email">>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: { email: "" },
  });

  const onSubmit = async (values: Pick<LoginFormValues, "email">) => {
    setFormError(null);
    try {
      await apiClient.post("/auth/forgot-password", values);
      setSuccess(true);
    } catch (error) {
      setFormError(
        error instanceof ApiError
          ? error.message
          : "Unable to send reset link.",
      );
    }
  };

  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle>Reset password</CardTitle>
        <CardDescription>
          We will email you a link to choose a new password.
        </CardDescription>
      </CardHeader>
      <CardContent>
        {success ? (
          <p className="text-sm text-muted-foreground">
            If an account exists for that email, a reset link has been sent.
          </p>
        ) : (
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
            {formError ? <FormErrorBanner message={formError} /> : null}
            <FormField label="Email" htmlFor="email" error={errors.email?.message}>
              <Input
                id="email"
                type="email"
                autoComplete="email"
                {...register("email")}
              />
            </FormField>
            <Button type="submit" className="w-full" disabled={isSubmitting}>
              {isSubmitting ? "Sending…" : "Send reset link"}
            </Button>
          </form>
        )}
        <p className="mt-4 text-center text-sm text-muted-foreground">
          <Link href="/login" className="text-foreground underline">
            Back to sign in
          </Link>
        </p>
      </CardContent>
    </Card>
  );
}
