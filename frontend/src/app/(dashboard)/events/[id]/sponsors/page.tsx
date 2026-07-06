"use client";

import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FormErrorBanner } from "@/components/shared/form-field";
import { EventConferenceNav } from "@/components/conference/event-conference-nav";
import { apiClient } from "@/lib/api-client";
import { orgRequestOptions, useOrganizationId } from "@/hooks/use-org-api";
import type { SponsorRecord, SponsorTier } from "@/types/conference";
import { ApiError } from "@/types/api";

const tiers: SponsorTier[] = ["platinum", "gold", "silver", "bronze", "partner"];

export default function EventSponsorsPage() {
  const params = useParams<{ id: string }>();
  const eventId = Number(params.id);
  const organizationId = useOrganizationId();
  const orgOptions = orgRequestOptions(organizationId);
  const queryClient = useQueryClient();
  const [formError, setFormError] = useState<string | null>(null);
  const [name, setName] = useState("");
  const [tier, setTier] = useState<SponsorTier>("gold");
  const [website, setWebsite] = useState("");
  const [description, setDescription] = useState("");

  const sponsorsQuery = useQuery({
    queryKey: ["sponsors", organizationId, eventId],
    enabled: organizationId !== null,
    queryFn: () => apiClient.get<SponsorRecord[]>(`/events/${eventId}/sponsors`, orgOptions),
  });

  const createMutation = useMutation({
    mutationFn: () =>
      apiClient.post<SponsorRecord>(
        `/events/${eventId}/sponsors`,
        {
          name,
          tier,
          website_url: website || null,
          description: description || null,
        },
        orgOptions,
      ),
    onSuccess: () => {
      setName("");
      setWebsite("");
      setDescription("");
      setFormError(null);
      void queryClient.invalidateQueries({ queryKey: ["sponsors", organizationId, eventId] });
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Could not create sponsor."),
  });

  const deleteMutation = useMutation({
    mutationFn: (sponsorId: number) =>
      apiClient.delete(`/events/${eventId}/sponsors/${sponsorId}`, orgOptions),
    onSuccess: () =>
      void queryClient.invalidateQueries({ queryKey: ["sponsors", organizationId, eventId] }),
  });

  if (organizationId === null) {
    return <p className="text-sm text-muted-foreground">Select an organization first.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="space-y-3">
        <h1 className="text-2xl font-semibold">Sponsors</h1>
        <EventConferenceNav eventId={eventId} />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Add sponsor</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {formError ? <FormErrorBanner message={formError} /> : null}
          <Input placeholder="Name" value={name} onChange={(e) => setName(e.target.value)} />
          <select
            className="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
            value={tier}
            onChange={(e) => setTier(e.target.value as SponsorTier)}
          >
            {tiers.map((value) => (
              <option key={value} value={value}>
                {value.charAt(0).toUpperCase() + value.slice(1)}
              </option>
            ))}
          </select>
          <Input
            placeholder="Website URL"
            value={website}
            onChange={(e) => setWebsite(e.target.value)}
          />
          <Textarea
            placeholder="Description"
            value={description}
            onChange={(e) => setDescription(e.target.value)}
          />
          <Button
            type="button"
            disabled={!name.trim() || createMutation.isPending}
            onClick={() => createMutation.mutate()}
          >
            Add sponsor
          </Button>
        </CardContent>
      </Card>

      <div className="space-y-3">
        {sponsorsQuery.data?.map((sponsor) => (
          <Card key={sponsor.id}>
            <CardContent className="flex flex-wrap items-start justify-between gap-3 p-4">
              <div>
                <p className="font-medium">{sponsor.name}</p>
                <p className="text-sm capitalize text-muted-foreground">{sponsor.tier}</p>
                {sponsor.website_url ? (
                  <a
                    href={sponsor.website_url}
                    className="text-sm text-primary hover:underline"
                    target="_blank"
                    rel="noreferrer"
                  >
                    {sponsor.website_url}
                  </a>
                ) : null}
              </div>
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={deleteMutation.isPending}
                onClick={() => deleteMutation.mutate(sponsor.id)}
              >
                Delete
              </Button>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
