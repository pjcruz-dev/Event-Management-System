import type { NextConfig } from "next";

function buildImageRemotePatterns(): NonNullable<
  NextConfig["images"]
>["remotePatterns"] {
  const patterns: NonNullable<NextConfig["images"]>["remotePatterns"] = [
    {
      protocol: "http",
      hostname: "localhost",
      port: "8001",
      pathname: "/storage/**",
    },
    {
      protocol: "http",
      hostname: "127.0.0.1",
      port: "8001",
      pathname: "/storage/**",
    },
  ];

  const apiUrl = process.env.NEXT_PUBLIC_API_URL;

  if (apiUrl) {
    try {
      const parsed = new URL(apiUrl);
      const protocol = parsed.protocol.replace(":", "") as "http" | "https";

      patterns.push({
        protocol,
        hostname: parsed.hostname,
        port: parsed.port || undefined,
        pathname: "/storage/**",
      });
    } catch {
      // Ignore invalid NEXT_PUBLIC_API_URL during config load.
    }
  }

  return patterns;
}

const nextConfig: NextConfig = {
  images: {
    remotePatterns: buildImageRemotePatterns(),
  },
};

export default nextConfig;
