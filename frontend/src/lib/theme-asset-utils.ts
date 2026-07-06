export function hasThemeAsset(url: string | null | undefined): boolean {
  return typeof url === "string" && url.trim().length > 0;
}

export function assetFilenameFromUrl(url: string | null | undefined): string | null {
  if (!hasThemeAsset(url)) {
    return null;
  }

  try {
    const pathname = new URL(url as string, "http://localhost").pathname;
    const filename = pathname.split("/").pop();
    return filename && filename.length > 0 ? filename : null;
  } catch {
    const parts = (url as string).split("/");
    return parts[parts.length - 1] || null;
  }
}

export function clearThemeAssetUrl(
  config: { logo_url?: string | null; hero_image_url?: string | null },
  type: "logo" | "hero",
): typeof config {
  const key = type === "logo" ? "logo_url" : "hero_image_url";
  return { ...config, [key]: null };
}
