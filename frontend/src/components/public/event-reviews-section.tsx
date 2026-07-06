"use client";

import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { apiClient } from "@/lib/api-client";
import type { ReviewRecord } from "@/types/discover";
import { ApiError } from "@/types/api";

interface EventReviewsSectionProps {
  slug: string;
  initialReviews: ReviewRecord[];
  eventEnded: boolean;
}

export function EventReviewsSection({
  slug,
  initialReviews,
  eventEnded,
}: EventReviewsSectionProps) {
  const [reviews, setReviews] = useState(initialReviews);
  const [message, setMessage] = useState<string | null>(null);
  const [form, setForm] = useState({
    registration_number: "",
    attendee_email: "",
    rating: "5",
    comment: "",
  });

  const submitReview = useMutation({
    mutationFn: () =>
      apiClient.post<ReviewRecord>(`/public/events/${slug}/reviews`, {
        ...form,
        rating: Number(form.rating),
      }),
    onSuccess: (review) => {
      setReviews((current) => [review, ...current]);
      setMessage("Thanks for sharing your feedback.");
      setForm({ registration_number: "", attendee_email: "", rating: "5", comment: "" });
    },
    onError: (error: Error) =>
      setMessage(error instanceof ApiError ? error.message : "Could not submit review."),
  });

  return (
    <section className="mx-auto max-w-3xl space-y-4 px-6 py-12">
      <h2 className="text-2xl font-semibold">Attendee reviews</h2>

      {reviews.length === 0 ? (
        <p className="text-sm text-muted-foreground">No reviews yet.</p>
      ) : (
        <div className="space-y-3">
          {reviews.map((review) => (
            <Card key={review.id}>
              <CardContent className="space-y-1 p-4">
                <p className="font-medium">
                  {review.attendee_name ?? "Attendee"} · {"★".repeat(review.rating)}
                </p>
                {review.comment ? (
                  <p className="text-sm text-muted-foreground">{review.comment}</p>
                ) : null}
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {eventEnded ? (
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Share your experience</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <Input
              placeholder="Registration number"
              value={form.registration_number}
              onChange={(e) => setForm((f) => ({ ...f, registration_number: e.target.value }))}
            />
            <Input
              placeholder="Email used at registration"
              type="email"
              value={form.attendee_email}
              onChange={(e) => setForm((f) => ({ ...f, attendee_email: e.target.value }))}
            />
            <Input
              type="number"
              min={1}
              max={5}
              value={form.rating}
              onChange={(e) => setForm((f) => ({ ...f, rating: e.target.value }))}
            />
            <Textarea
              placeholder="Your review (optional)"
              value={form.comment}
              onChange={(e) => setForm((f) => ({ ...f, comment: e.target.value }))}
            />
            {message ? <p className="text-sm text-muted-foreground">{message}</p> : null}
            <Button
              type="button"
              disabled={submitReview.isPending}
              onClick={() => submitReview.mutate()}
            >
              Submit review
            </Button>
          </CardContent>
        </Card>
      ) : null}
    </section>
  );
}
