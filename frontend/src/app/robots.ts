import type { MetadataRoute } from "next";
import { getSiteUrl } from "@/lib/server-api";

export default function robots(): MetadataRoute.Robots {
  const siteUrl = getSiteUrl();

  return {
    rules: {
      userAgent: "*",
      allow: "/",
      disallow: ["/dashboard/", "/events/", "/settings/", "/exhibitor-portal/"],
    },
    sitemap: `${siteUrl}/sitemap.xml`,
  };
}
