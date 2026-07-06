import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { fetchPublicApi } from "@/lib/server-api";
import { PublicMarketingHeader } from "@/components/public/public-marketing-header";
import type { PublicOrganizationProfile } from "@/types/discover";

export const revalidate = 120;

interface PageProps {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  const data = await fetchPublicApi<PublicOrganizationProfile>(
    `/public/organizations/${slug}`,
  );

  if (!data) {
    return { title: "Organizer not found" };
  }

  return {
    title: `${data.organization.name} — Events`,
    description: data.organization.description ?? `Public events by ${data.organization.name}.`,
  };
}

export default async function PublicOrganizationPage({ params }: PageProps) {
  const { slug } = await params;
  const data = await fetchPublicApi<PublicOrganizationProfile>(
    `/public/organizations/${slug}`,
  );

  if (!data) {
    notFound();
  }

  const { organization, events } = data;

  return (
    <>
      <PublicMarketingHeader />
      <main className="mx-auto max-w-4xl space-y-8 p-8">
      <div className="space-y-2">
        <Link href="/discover" className="text-sm text-muted-foreground hover:underline">
          ← Discover events
        </Link>
        <h1 className="text-3xl font-semibold">{organization.name}</h1>
        {organization.description ? (
          <p className="text-muted-foreground">{organization.description}</p>
        ) : null}
        {organization.website_url ? (
          <a
            href={organization.website_url}
            className="text-sm text-primary hover:underline"
            target="_blank"
            rel="noreferrer"
          >
            {organization.website_url}
          </a>
        ) : null}
      </div>

      <section className="space-y-4">
        <h2 className="text-xl font-semibold">Upcoming public events</h2>
        {events.length === 0 ? (
          <p className="text-sm text-muted-foreground">No public events listed yet.</p>
        ) : (
          events.map((event) => (
            <Card key={event.id}>
              <CardContent className="flex flex-wrap items-center justify-between gap-3 p-4">
                <div>
                  <p className="font-medium">{event.name}</p>
                  <p className="text-sm text-muted-foreground">
                    {event.venue}
                    {event.starts_at
                      ? ` · ${new Date(event.starts_at).toLocaleDateString()}`
                      : ""}
                  </p>
                </div>
                <Button asChild size="sm" variant="outline">
                  <Link href={`/e/${event.slug}`}>View</Link>
                </Button>
              </CardContent>
            </Card>
          ))
        )}
      </section>
    </main>
    </>
  );
}
