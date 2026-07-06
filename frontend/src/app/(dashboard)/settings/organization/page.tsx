"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { FormErrorBanner, FormField } from "@/components/shared/form-field";
import {
  inviteSchema,
  organizationSchema,
  type InviteFormValues,
  type OrganizationFormValues,
} from "@/features/auth/schemas";
import { apiClient } from "@/lib/api-client";
import { SUPPORTED_CURRENCIES } from "@/lib/currency";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import { useAuthStore } from "@/stores/auth-store";
import type {
  AuthOrganization,
  OrganizationInvitation,
  OrganizationMember,
  OrganizationRole,
} from "@/types/auth";
import { ApiError } from "@/types/api";

export default function OrganizationSettingsPage() {
  const organizationId = useOrganizationId();
  const organizations = useAuthStore((s) => s.organizations);
  const fetchMe = useAuthStore((s) => s.fetchMe);
  const activeOrg = organizations.find((org) => org.id === organizationId);
  const queryClient = useQueryClient();
  const [formError, setFormError] = useState<string | null>(null);
  const [inviteOpen, setInviteOpen] = useState(false);
  const orgOptions = orgRequestOptions(organizationId);

  const {
    register: registerOrg,
    handleSubmit: handleOrgSubmit,
    formState: { errors: orgErrors, isSubmitting: orgSubmitting },
  } = useForm<OrganizationFormValues>({
    resolver: zodResolver(organizationSchema),
    values: activeOrg
      ? { name: activeOrg.name, slug: activeOrg.slug }
      : { name: "", slug: "" },
  });

  const {
    register: registerInvite,
    handleSubmit: handleInviteSubmit,
    reset: resetInvite,
    formState: { errors: inviteErrors, isSubmitting: inviteSubmitting },
  } = useForm<InviteFormValues>({
    resolver: zodResolver(inviteSchema),
    defaultValues: { email: "", role: "member" },
  });

  const membersQuery = useQuery({
    queryKey: ["organization-members", organizationId],
    enabled: organizationId !== null,
    queryFn: () =>
      apiClient.get<OrganizationMember[]>(
        `/organizations/${organizationId}/members`,
        orgOptions,
      ),
  });

  const invitationsQuery = useQuery({
    queryKey: ["organization-invitations", organizationId],
    enabled: organizationId !== null,
    queryFn: () =>
      apiClient.get<OrganizationInvitation[]>(
        `/organizations/${organizationId}/invitations`,
        orgOptions,
      ),
  });

  const rolesQuery = useQuery({
    queryKey: ["organization-roles", organizationId],
    enabled: organizationId !== null,
    queryFn: () =>
      apiClient.get<OrganizationRole[]>(
        `/organizations/${organizationId}/roles`,
        orgOptions,
      ),
  });

  const updateOrgMutation = useMutation({
    mutationFn: (values: OrganizationFormValues) =>
      apiClient.put<AuthOrganization>(
        `/organizations/${organizationId}`,
        values,
        orgOptions,
      ),
    onSuccess: async () => {
      await fetchMe();
      setFormError(null);
    },
    onError: (error: unknown) => {
      setFormError(
        error instanceof ApiError
          ? error.message
          : "Unable to update organization.",
      );
    },
  });

  const inviteMutation = useMutation({
    mutationFn: (values: InviteFormValues) =>
      apiClient.post(
        `/organizations/${organizationId}/invitations`,
        values,
        orgOptions,
      ),
    onSuccess: async () => {
      resetInvite();
      setInviteOpen(false);
      await queryClient.invalidateQueries({
        queryKey: ["organization-invitations", organizationId],
      });
    },
    onError: (error: unknown) => {
      setFormError(
        error instanceof ApiError ? error.message : "Unable to send invitation.",
      );
    },
  });

  const updateRoleMutation = useMutation({
    mutationFn: ({ userId, role }: { userId: number; role: string }) =>
      apiClient.put(
        `/organizations/${organizationId}/members/${userId}`,
        { role },
        orgOptions,
      ),
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["organization-members", organizationId],
      });
    },
  });

  const revokeInvitationMutation = useMutation({
    mutationFn: (invitationId: number) =>
      apiClient.delete(
        `/organizations/${organizationId}/invitations/${invitationId}`,
        orgOptions,
      ),
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["organization-invitations", organizationId],
      });
    },
  });

  if (!organizationId || !activeOrg) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>No organization selected</CardTitle>
          <CardDescription>
            Create or select an organization from the dashboard first.
          </CardDescription>
        </CardHeader>
      </Card>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Organization</h1>
        <p className="text-muted-foreground">
          Manage {activeOrg.name} settings, members, and invitations.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Details</CardTitle>
        </CardHeader>
        <CardContent>
          <form
            onSubmit={handleOrgSubmit((values) =>
              updateOrgMutation.mutate(values),
            )}
            className="max-w-lg space-y-4"
          >
            {formError ? <FormErrorBanner message={formError} /> : null}
            <FormField
              label="Organization name"
              htmlFor="org-name"
              error={orgErrors.name?.message}
            >
              <Input id="org-name" defaultValue={activeOrg.name} {...registerOrg("name")} />
            </FormField>
            <FormField
              label="Slug"
              htmlFor="org-slug"
              error={orgErrors.slug?.message}
            >
              <Input id="org-slug" defaultValue={activeOrg.slug} {...registerOrg("slug")} />
            </FormField>
            <Button type="submit" disabled={orgSubmitting}>
              {orgSubmitting ? "Saving…" : "Save organization"}
            </Button>
          </form>
        </CardContent>
      </Card>

      <CurrencySettingsCard
        organizationId={organizationId}
        settings={activeOrg.settings}
        orgOptions={orgOptions}
        onSaved={fetchMe}
        onError={setFormError}
      />

      <PublicProfileSettingsCard
        organizationId={organizationId}
        slug={activeOrg.slug}
        settings={activeOrg.settings}
        orgOptions={orgOptions}
        onError={setFormError}
      />

      <Card>
        <CardHeader className="flex flex-row items-center justify-between gap-4">
          <div>
            <CardTitle>Members</CardTitle>
            <CardDescription>Active users in this organization.</CardDescription>
          </div>
          <Button type="button" onClick={() => setInviteOpen((v) => !v)}>
            Invite member
          </Button>
        </CardHeader>
        <CardContent className="space-y-4">
          {inviteOpen ? (
            <form
              onSubmit={handleInviteSubmit((values) =>
                inviteMutation.mutate(values),
              )}
              className="grid gap-4 rounded-md border border-border p-4 md:grid-cols-3"
            >
              <FormField
                label="Email"
                htmlFor="invite-email"
                error={inviteErrors.email?.message}
              >
                <Input id="invite-email" type="email" {...registerInvite("email")} />
              </FormField>
              <FormField
                label="Role"
                htmlFor="invite-role"
                error={inviteErrors.role?.message}
              >
                <select
                  id="invite-role"
                  className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  {...registerInvite("role")}
                >
                  {(rolesQuery.data ?? []).map((role) => (
                    <option key={role.id} value={role.name}>
                      {role.name}
                    </option>
                  ))}
                </select>
              </FormField>
              <div className="flex items-end">
                <Button type="submit" disabled={inviteSubmitting}>
                  {inviteSubmitting ? "Sending…" : "Send invite"}
                </Button>
              </div>
            </form>
          ) : null}

          {membersQuery.isLoading ? (
            <p className="text-sm text-muted-foreground">Loading members…</p>
          ) : membersQuery.isError ? (
            <p className="text-sm text-destructive">Unable to load members.</p>
          ) : (
            <ul className="divide-y divide-border rounded-md border border-border">
              {(membersQuery.data ?? []).map((member) => (
                <li
                  key={member.id}
                  className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div>
                    <p className="font-medium">{member.name}</p>
                    <p className="text-sm text-muted-foreground">{member.email}</p>
                  </div>
                  <select
                    className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                    value={member.role}
                    onChange={(event) =>
                      updateRoleMutation.mutate({
                        userId: member.id,
                        role: event.target.value,
                      })
                    }
                    aria-label={`Role for ${member.name}`}
                  >
                    {(rolesQuery.data ?? []).map((role) => (
                      <option key={role.id} value={role.name}>
                        {role.name}
                      </option>
                    ))}
                  </select>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Pending invitations</CardTitle>
        </CardHeader>
        <CardContent>
          {invitationsQuery.isLoading ? (
            <p className="text-sm text-muted-foreground">Loading invitations…</p>
          ) : (invitationsQuery.data ?? []).length === 0 ? (
            <p className="text-sm text-muted-foreground">No pending invitations.</p>
          ) : (
            <ul className="divide-y divide-border rounded-md border border-border">
              {(invitationsQuery.data ?? []).map((invitation) => (
                <li
                  key={invitation.id}
                  className="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div>
                    <p className="font-medium">{invitation.email}</p>
                    <p className="text-sm text-muted-foreground">
                      Role: {invitation.role} · Expires{" "}
                      {new Date(invitation.expires_at).toLocaleDateString()}
                    </p>
                  </div>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() =>
                      revokeInvitationMutation.mutate(invitation.id)
                    }
                  >
                    Revoke
                  </Button>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

function CurrencySettingsCard({
  organizationId,
  settings,
  orgOptions,
  onSaved,
  onError,
}: {
  organizationId: number;
  settings?: Record<string, unknown>;
  orgOptions: ReturnType<typeof orgRequestOptions>;
  onSaved: () => Promise<void>;
  onError: (message: string | null) => void;
}) {
  const currentCurrency = (settings?.default_currency as string) ?? "USD";
  const [currency, setCurrency] = useState(currentCurrency);
  const [saving, setSaving] = useState(false);

  const save = async () => {
    setSaving(true);
    onError(null);
    try {
      await apiClient.put(
        `/organizations/${organizationId}`,
        {
          settings: {
            ...(settings ?? {}),
            default_currency: currency,
          },
        },
        orgOptions,
      );
      await onSaved();
    } catch (error) {
      onError(error instanceof ApiError ? error.message : "Unable to save currency.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Default currency</CardTitle>
        <CardDescription>
          This currency is used across the dashboard for revenue display.
        </CardDescription>
      </CardHeader>
      <CardContent className="max-w-sm space-y-3">
        <select
          className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          value={currency}
          onChange={(e) => setCurrency(e.target.value)}
        >
          {SUPPORTED_CURRENCIES.map((c) => (
            <option key={c.code} value={c.code}>
              {c.label}
            </option>
          ))}
        </select>
        <Button type="button" disabled={saving || currency === currentCurrency} onClick={() => void save()}>
          {saving ? "Saving…" : "Save currency"}
        </Button>
      </CardContent>
    </Card>
  );
}

function PublicProfileSettingsCard({
  organizationId,
  slug,
  settings,
  orgOptions,
  onError,
}: {
  organizationId: number;
  slug: string;
  settings?: Record<string, unknown>;
  orgOptions: ReturnType<typeof orgRequestOptions>;
  onError: (message: string | null) => void;
}) {
  const profile = (settings?.public_profile ?? {}) as {
    enabled?: boolean;
    description?: string;
    website_url?: string;
  };
  const [enabled, setEnabled] = useState(Boolean(profile.enabled));
  const [description, setDescription] = useState(profile.description ?? "");
  const [websiteUrl, setWebsiteUrl] = useState(profile.website_url ?? "");
  const [saving, setSaving] = useState(false);

  const save = async () => {
    setSaving(true);
    onError(null);
    try {
      await apiClient.put(
        `/organizations/${organizationId}`,
        {
          settings: {
            ...(settings ?? {}),
            public_profile: {
              enabled,
              description: description || null,
              website_url: websiteUrl || null,
            },
          },
        },
        orgOptions,
      );
    } catch (error) {
      onError(error instanceof ApiError ? error.message : "Unable to save public profile.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Public organizer profile</CardTitle>
        <CardDescription>
          When enabled, your public page is available at /organizations/{slug}
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-3">
        <label className="flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={enabled}
            onChange={(e) => setEnabled(e.target.checked)}
          />
          Show public organizer profile
        </label>
        <Textarea
          placeholder="Public description"
          value={description}
          onChange={(e) => setDescription(e.target.value)}
        />
        <Input
          placeholder="Website URL"
          value={websiteUrl}
          onChange={(e) => setWebsiteUrl(e.target.value)}
        />
        <Button type="button" disabled={saving} onClick={() => void save()}>
          {saving ? "Saving…" : "Save public profile"}
        </Button>
      </CardContent>
    </Card>
  );
}
