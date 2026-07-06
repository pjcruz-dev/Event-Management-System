"use client";

import { useParams } from "next/navigation";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FormErrorBanner } from "@/components/shared/form-field";
import { apiClient } from "@/lib/api-client";
import { formatEventDateRange } from "@/lib/public-event-format";
import type { PublicRsvpPayload, RsvpResponse } from "@/types/rsvp";
import { ApiError } from "@/types/api";

export default function PublicRsvpPage() {
  const params = useParams<{ token: string }>();
  const token = params.token;
  const [formError, setFormError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [plusOnes, setPlusOnes] = useState([{ first_name: "", last_name: "" }]);
  const [mealPreference, setMealPreference] = useState("");

  const rsvpQuery = useQuery({
    queryKey: ["public-rsvp", token],
    queryFn: () => apiClient.get<PublicRsvpPayload>(`/public/rsvp/${token}`),
  });

  const respondMutation = useMutation({
    mutationFn: (response: RsvpResponse) =>
      apiClient.post(`/public/rsvp/${token}/respond`, {
        response,
        custom_fields: mealPreference ? { meal_preference: mealPreference } : undefined,
        plus_ones:
          response === "accepted" && rsvpQuery.data?.rsvp_settings.allow_plus_ones
            ? plusOnes.filter((p) => p.first_name.trim() !== "")
            : undefined,
        ticket_type_id: rsvpQuery.data?.ticket_types[0]?.id,
      }),
    onSuccess: () => {
      setFormError(null);
      setSuccess("Your response has been recorded. Thank you!");
      void rsvpQuery.refetch();
    },
    onError: (error: Error) =>
      setFormError(error instanceof ApiError ? error.message : "Could not submit RSVP."),
  });

  if (rsvpQuery.isLoading) {
    return <p className="p-8 text-center text-muted-foreground">Loading invitation…</p>;
  }

  if (rsvpQuery.isError || !rsvpQuery.data) {
    return (
      <div className="mx-auto max-w-lg p-8 text-center">
        <h1 className="text-xl font-semibold">Invitation not found</h1>
        <p className="mt-2 text-sm text-muted-foreground">
          This link may have expired or been revoked.
        </p>
      </div>
    );
  }

  const { invite, event, rsvp_settings: settings } = rsvpQuery.data;
  const dateLabel = formatEventDateRange(event.starts_at, event.ends_at, event.timezone);
  const alreadyResponded = invite.rsvp_response !== null;

  if (success || alreadyResponded) {
    return (
      <div className="mx-auto max-w-lg space-y-4 p-8 text-center">
        <h1 className="text-2xl font-semibold">{event.name}</h1>
        <p className="text-muted-foreground">
          {success ?? `You responded: ${invite.rsvp_response}`}
        </p>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-lg space-y-6 p-6">
      <div className="space-y-2 text-center">
        <h1 className="text-2xl font-semibold">{event.name}</h1>
        {dateLabel ? <p className="text-muted-foreground">{dateLabel}</p> : null}
        {event.venue ? <p className="text-muted-foreground">{event.venue}</p> : null}
        <p className="text-sm">Hello, {invite.first_name}!</p>
      </div>

      {formError ? <FormErrorBanner message={formError} /> : null}

      {settings.collect_meal_preferences ? (
        <Card>
          <CardHeader><CardTitle className="text-base">Meal preference</CardTitle></CardHeader>
          <CardContent>
            <select
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={mealPreference}
              onChange={(e) => setMealPreference(e.target.value)}
            >
              <option value="">Select…</option>
              {(settings.meal_options ?? ["Chicken", "Fish", "Vegetarian", "Vegan"]).map((opt) => (
                <option key={opt} value={opt}>{opt}</option>
              ))}
            </select>
          </CardContent>
        </Card>
      ) : null}

      {settings.allow_plus_ones ? (
        <Card>
          <CardHeader><CardTitle className="text-base">Plus ones</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            {plusOnes.map((plusOne, index) => (
              <div key={index} className="flex gap-2">
                <Input
                  placeholder="First name"
                  value={plusOne.first_name}
                  onChange={(e) => {
                    const next = [...plusOnes];
                    next[index] = { ...next[index], first_name: e.target.value };
                    setPlusOnes(next);
                  }}
                />
                <Input
                  placeholder="Last name"
                  value={plusOne.last_name}
                  onChange={(e) => {
                    const next = [...plusOnes];
                    next[index] = { ...next[index], last_name: e.target.value };
                    setPlusOnes(next);
                  }}
                />
              </div>
            ))}
            {plusOnes.length < settings.max_plus_ones_per_invite ? (
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setPlusOnes([...plusOnes, { first_name: "", last_name: "" }])}
              >
                Add plus one
              </Button>
            ) : null}
          </CardContent>
        </Card>
      ) : null}

      <div className="flex flex-wrap justify-center gap-3">
        <Button
          size="lg"
          onClick={() => respondMutation.mutate("accepted")}
          disabled={respondMutation.isPending}
        >
          Accept
        </Button>
        <Button
          size="lg"
          variant="outline"
          onClick={() => respondMutation.mutate("declined")}
          disabled={respondMutation.isPending}
        >
          Decline
        </Button>
        {settings.allow_maybe_response ? (
          <Button
            size="lg"
            variant="secondary"
            onClick={() => respondMutation.mutate("maybe")}
            disabled={respondMutation.isPending}
          >
            Maybe
          </Button>
        ) : null}
      </div>
    </div>
  );
}
