import type { MetadataRoute } from "next";
import { fetchPublicApi, getSiteUrl } from "@/lib/server-api";
import type { SitemapPayload } from "@/types/discover";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const siteUrl = getSiteUrl();
  const data = await fetchPublicApi<SitemapPayload>("/discover/events/sitemap", {
    revalidate: 300,
  });

  const eventEntries =
    data?.events.map((event) => ({
      url: `${siteUrl}/e/${event.slug}`,
      lastModified: event.updated_at ? new Date(event.updated_at) : new Date(),
      changeFrequency: "weekly" as const,
      priority: 0.8,
    })) ?? [];

  return [
    { url: `${siteUrl}/discover`, changeFrequency: "daily", priority: 1 },
    ...eventEntries,
  ];
}
