"use client";

import { useEffect, useState, type ChangeEvent } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
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
  profileSchema,
  type ProfileFormValues,
} from "@/features/auth/schemas";
import { apiClient } from "@/lib/api-client";
import { uploadAvatar } from "@/hooks/use-org-api";
import { useAuthStore } from "@/stores/auth-store";
import type { AuthUser } from "@/types/auth";
import { ApiError } from "@/types/api";

export default function ProfileSettingsPage() {
  const user = useAuthStore((s) => s.user);
  const fetchMe = useAuthStore((s) => s.fetchMe);
  const [formError, setFormError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [avatarUploading, setAvatarUploading] = useState(false);

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<ProfileFormValues>({
    resolver: zodResolver(profileSchema),
  });

  useEffect(() => {
    if (user) {
      reset({
        name: user.name,
        email: user.email,
        phone: user.phone ?? "",
        timezone: user.timezone,
        locale: user.locale,
      });
    }
  }, [user, reset]);

  const onSubmit = async (values: ProfileFormValues) => {
    setFormError(null);
    setSuccess(null);
    try {
      await apiClient.put<AuthUser>("/profile", values);
      await fetchMe();
      setSuccess("Profile updated successfully.");
    } catch (error) {
      setFormError(
        error instanceof ApiError ? error.message : "Unable to update profile.",
      );
    }
  };

  const onAvatarChange = async (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setAvatarUploading(true);
    setFormError(null);
    try {
      await uploadAvatar(file);
      await fetchMe();
      setSuccess("Avatar updated successfully.");
    } catch (error) {
      setFormError(
        error instanceof ApiError ? error.message : "Unable to upload avatar.",
      );
    } finally {
      setAvatarUploading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Profile</h1>
        <p className="text-muted-foreground">
          Update your personal details and avatar.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Avatar</CardTitle>
          <CardDescription>JPG, PNG, or WebP up to 2 MB.</CardDescription>
        </CardHeader>
        <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center">
          {user?.avatar_url ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={user.avatar_url}
              alt=""
              className="h-20 w-20 rounded-full object-cover"
            />
          ) : (
            <div className="flex h-20 w-20 items-center justify-center rounded-full bg-muted text-lg font-semibold">
              {user?.name?.charAt(0) ?? "?"}
            </div>
          )}
          <div>
            <Input
              type="file"
              accept="image/jpeg,image/png,image/webp"
              onChange={onAvatarChange}
              disabled={avatarUploading}
              aria-label="Upload avatar"
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Account details</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(onSubmit)} className="max-w-lg space-y-4">
            {formError ? <FormErrorBanner message={formError} /> : null}
            {success ? (
              <p className="text-sm text-muted-foreground">{success}</p>
            ) : null}
            <FormField label="Name" htmlFor="name" error={errors.name?.message}>
              <Input id="name" {...register("name")} />
            </FormField>
            <FormField label="Email" htmlFor="email" error={errors.email?.message}>
              <Input id="email" type="email" {...register("email")} />
            </FormField>
            <FormField label="Phone" htmlFor="phone" error={errors.phone?.message}>
              <Input id="phone" type="tel" {...register("phone")} />
            </FormField>
            <FormField
              label="Timezone"
              htmlFor="timezone"
              error={errors.timezone?.message}
            >
              <Input id="timezone" {...register("timezone")} />
            </FormField>
            <FormField
              label="Locale"
              htmlFor="locale"
              error={errors.locale?.message}
            >
              <Input id="locale" {...register("locale")} />
            </FormField>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Saving…" : "Save changes"}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
