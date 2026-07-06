"use client";

import Link from "next/link";
import type { DiscoverEventRecord } from "@/types/discover";
import { Card, CardContent } from "@/components/ui/card";

interface SimilarEventsStripProps {
  events: DiscoverEventRecord[];
}

export function SimilarEventsStrip({ events }: SimilarEventsStripProps) {
  if (events.length === 0) return null;

  return (
    <section className="border-t border-border bg-muted/30 px-6 py-12">
      <div className="mx-auto max-w-5xl space-y-4">
        <h2 className="text-xl font-semibold">Similar events</h2>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {events.map((event) => (
            <Card key={event.id}>
              <CardContent className="space-y-2 p-4">
                <p className="font-medium leading-snug">{event.name}</p>
                <p className="text-xs text-muted-foreground">
                  {event.venue}
                  {event.starts_at
                    ? ` · ${new Date(event.starts_at).toLocaleDateString()}`
                    : ""}
                </p>
                <Link href={`/e/${event.slug}`} className="text-sm font-medium text-primary hover:underline">
                  View event
                </Link>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
}
