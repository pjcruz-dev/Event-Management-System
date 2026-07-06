"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent } from "@/components/ui/card";
import { apiClient } from "@/lib/api-client";
import { PublicMarketingHeader } from "@/components/public/public-marketing-header";
import { EVENT_CATEGORIES, type PaginatedDiscoverEvents } from "@/types/discover";

export default function DiscoverEventsPage() {
  const [keyword, setKeyword] = useState("");
  const [category, setCategory] = useState("");
  const [location, setLocation] = useState("");
  const [pricing, setPricing] = useState("any");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const [page, setPage] = useState(1);

  const queryString = useMemo(() => {
    const params = new URLSearchParams({ page: String(page), per_page: "12" });
    if (keyword.trim()) params.set("q", keyword.trim());
    if (category) params.set("category", category);
    if (location.trim()) params.set("location", location.trim());
    if (pricing !== "any") params.set("pricing", pricing);
    if (from) params.set("from", from);
    if (to) params.set("to", to);
    return params.toString();
  }, [keyword, category, location, pricing, from, to, page]);

  const eventsQuery = useQuery({
    queryKey: ["discover-events", queryString],
    queryFn: () => apiClient.get<PaginatedDiscoverEvents>(`/discover/events?${queryString}`),
  });

  const data = eventsQuery.data;

  return (
    <>
      <PublicMarketingHeader />
      <main className="mx-auto flex max-w-6xl flex-col gap-8 p-6 lg:flex-row lg:p-8">
      <aside className="w-full shrink-0 space-y-4 lg:w-72">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Discover events</h1>
          <p className="text-sm text-muted-foreground">
            Search published events across the platform.
          </p>
        </div>

        <Card>
          <CardContent className="space-y-3 p-4">
            <Input
              placeholder="Search keyword"
              value={keyword}
              onChange={(e) => {
                setPage(1);
                setKeyword(e.target.value);
              }}
            />
            <Input
              placeholder="Location / venue"
              value={location}
              onChange={(e) => {
                setPage(1);
                setLocation(e.target.value);
              }}
            />
            <Input
              type="date"
              value={from}
              onChange={(e) => {
                setPage(1);
                setFrom(e.target.value);
              }}
            />
            <Input
              type="date"
              value={to}
              onChange={(e) => {
                setPage(1);
                setTo(e.target.value);
              }}
            />
            <select
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={pricing}
              onChange={(e) => {
                setPage(1);
                setPricing(e.target.value);
              }}
            >
              <option value="any">Any pricing</option>
              <option value="free">Free</option>
              <option value="paid">Paid</option>
            </select>
          </CardContent>
        </Card>

        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            className={`rounded-full border px-3 py-1 text-sm ${category === "" ? "border-primary bg-primary/10" : "border-border"}`}
            onClick={() => {
              setPage(1);
              setCategory("");
            }}
          >
            All
          </button>
          {EVENT_CATEGORIES.map((item) => (
            <button
              key={item.value}
              type="button"
              className={`rounded-full border px-3 py-1 text-sm capitalize ${category === item.value ? "border-primary bg-primary/10" : "border-border"}`}
              onClick={() => {
                setPage(1);
                setCategory(item.value);
              }}
            >
              {item.label}
            </button>
          ))}
        </div>
      </aside>

      <section className="min-w-0 flex-1 space-y-4">
        {eventsQuery.isLoading ? (
          <p className="text-sm text-muted-foreground">Loading events…</p>
        ) : null}

        {data?.items.map((event) => (
          <article key={event.id} className="rounded-lg border border-border p-4">
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div className="space-y-1">
                <div className="flex flex-wrap items-center gap-2">
                  <h2 className="text-lg font-medium">{event.name}</h2>
                  {event.category ? (
                    <span className="rounded-full border border-border px-2 py-0.5 text-xs capitalize">
                      {event.category}
                    </span>
                  ) : null}
                  {event.is_free ? (
                    <span className="rounded-full bg-green-500/10 px-2 py-0.5 text-xs text-green-700 dark:text-green-400">
                      Free
                    </span>
                  ) : null}
                </div>
                <p className="text-sm text-muted-foreground">
                  {event.venue}
                  {event.starts_at
                    ? ` · ${new Date(event.starts_at).toLocaleDateString()}`
                    : ""}
                </p>
                {event.organization ? (
                  <Link
                    href={`/organizations/${event.organization.slug}`}
                    className="text-xs text-muted-foreground hover:underline"
                  >
                    by {event.organization.name}
                  </Link>
                ) : null}
              </div>
              <Button asChild size="sm">
                <Link href={`/e/${event.slug}`}>View event</Link>
              </Button>
            </div>
          </article>
        ))}

        {data && data.items.length === 0 && !eventsQuery.isLoading ? (
          <p className="text-sm text-muted-foreground">No events match your filters.</p>
        ) : null}

        {data && data.meta.last_page > 1 ? (
          <div className="flex items-center gap-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              disabled={page <= 1}
              onClick={() => setPage((p) => p - 1)}
            >
              Previous
            </Button>
            <span className="text-sm text-muted-foreground">
              Page {data.meta.current_page} of {data.meta.last_page}
            </span>
            <Button
              type="button"
              variant="outline"
              size="sm"
              disabled={page >= data.meta.last_page}
              onClick={() => setPage((p) => p + 1)}
            >
              Next
            </Button>
          </div>
        ) : null}
      </section>
    </main>
    </>
  );
}
