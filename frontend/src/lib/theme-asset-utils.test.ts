import { describe, expect, it } from "vitest";
import {
  assetFilenameFromUrl,
  clearThemeAssetUrl,
  hasThemeAsset,
} from "@/lib/theme-asset-utils";

describe("hasThemeAsset", () => {
  it("returns true when url is a non-empty string", () => {
    expect(hasThemeAsset("http://localhost/storage/events/1/logo.png")).toBe(true);
  });

  it("returns false for null or blank values", () => {
    expect(hasThemeAsset(null)).toBe(false);
    expect(hasThemeAsset("")).toBe(false);
    expect(hasThemeAsset("   ")).toBe(false);
  });
});

describe("assetFilenameFromUrl", () => {
  it("extracts filename from storage url", () => {
    expect(assetFilenameFromUrl("http://localhost:8001/storage/events/3/assets/logo.png")).toBe(
      "logo.png",
    );
  });
});

describe("clearThemeAssetUrl", () => {
  it("clears logo_url in local theme config", () => {
    const next = clearThemeAssetUrl(
      { logo_url: "http://localhost/logo.png", hero_image_url: "http://localhost/hero.png" },
      "logo",
    );

    expect(next.logo_url).toBeNull();
    expect(next.hero_image_url).toBe("http://localhost/hero.png");
  });
});
