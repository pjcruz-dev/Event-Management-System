import type { CSSProperties } from "react";
import type { EventRecord, LandingBlock, ThemeConfig } from "@/types/event";
import { blockSettingString } from "@/lib/landing-block-utils";

export type LayoutVariant = ThemeConfig["layout_variant"];

export function getLayoutVariantDataAttribute(
  variant: LayoutVariant,
): { "data-layout-variant": LayoutVariant } {
  return { "data-layout-variant": variant };
}

export function getLayoutRootClassName(variant: LayoutVariant): string {
  return `event-landing event-landing--${variant}`;
}

export function resolveHeroHeadline(block: LandingBlock, event: EventRecord): string {
  if (block.type !== "hero") {
    return event.name;
  }
  return blockSettingString(block.settings, "headline").trim() || event.name;
}

export function getHeroSectionClassName(variant: LayoutVariant): string {
  switch (variant) {
    case "minimal":
      return "relative overflow-hidden border-b border-border text-white";
    case "modern":
      return "relative overflow-hidden text-white";
    case "conference":
      return "relative overflow-hidden text-white";
    case "bold":
      return "relative overflow-hidden text-white";
    case "classic":
    default:
      return "relative overflow-hidden text-white";
  }
}

export function getHeroSectionStyle(variant: LayoutVariant): CSSProperties {
  switch (variant) {
    case "minimal":
      return { backgroundColor: "var(--event-primary)" };
    case "modern":
      return {
        background:
          "linear-gradient(180deg, var(--event-primary) 0%, color-mix(in srgb, var(--event-primary) 70%, black) 100%)",
      };
    case "conference":
      return {
        background:
          "linear-gradient(135deg, color-mix(in srgb, var(--event-primary) 85%, black) 0%, var(--event-primary) 50%, var(--event-secondary) 100%)",
      };
    case "bold":
      return {
        background:
          "linear-gradient(160deg, var(--event-primary) 0%, var(--event-primary) 42%, var(--event-secondary) 100%)",
      };
    case "classic":
    default:
      return {
        background:
          "linear-gradient(135deg, var(--event-primary) 0%, var(--event-secondary) 100%)",
      };
  }
}

export function showHeroPattern(variant: LayoutVariant): boolean {
  return variant === "classic" || variant === "conference";
}

export function getHeroInnerPaddingClassName(variant: LayoutVariant): string {
  switch (variant) {
    case "minimal":
      return "py-20 md:py-28";
    case "modern":
      return "py-24 md:py-32";
    case "conference":
      return "py-20 md:py-28";
    case "bold":
      return "py-20 md:py-28";
    case "classic":
    default:
      return "py-16 md:py-24";
  }
}

export function getSectionShellClassName(variant: LayoutVariant, muted: boolean): string {
  const base = "px-6";
  switch (variant) {
    case "minimal":
      return `${base} py-20 md:py-24 border-t border-border`;
    case "modern":
      return `${base} py-16 md:py-20 ${muted ? "bg-muted/20" : ""}`;
    case "conference":
      return `${base} py-14 md:py-18 ${muted ? "bg-[color-mix(in_srgb,var(--event-primary)_5%,transparent)]" : ""}`;
    case "bold":
      return `${base} py-16 md:py-20 ${muted ? "bg-[color-mix(in_srgb,var(--event-primary)_8%,transparent)]" : ""}`;
    case "classic":
    default:
      return `${base} py-14 md:py-16 ${muted ? "bg-muted/30" : ""}`;
  }
}

export function getSectionHeadingClassName(variant: LayoutVariant): string {
  switch (variant) {
    case "minimal":
      return "text-xl font-medium tracking-normal md:text-2xl";
    case "modern":
      return "text-2xl font-semibold tracking-tight md:text-3xl";
    case "conference":
      return "text-2xl font-bold uppercase tracking-wider md:text-3xl";
    case "bold":
      return "text-3xl font-extrabold tracking-tight md:text-4xl";
    case "classic":
    default:
      return "text-2xl font-bold tracking-tight md:text-3xl";
  }
}

export function getHeroHeadingClassName(variant: LayoutVariant): string {
  switch (variant) {
    case "minimal":
      return "text-3xl font-medium tracking-tight md:text-4xl lg:text-5xl";
    case "modern":
      return "text-4xl font-semibold tracking-tight md:text-5xl lg:text-6xl";
    case "conference":
      return "text-4xl font-bold uppercase tracking-wide md:text-5xl lg:text-6xl";
    case "bold":
      return "text-5xl font-extrabold tracking-tight md:text-6xl lg:text-7xl";
    case "classic":
    default:
      return "text-4xl font-bold tracking-tight md:text-5xl lg:text-6xl";
  }
}

export function getHeroImageFrameClassName(variant: LayoutVariant): string {
  switch (variant) {
    case "minimal":
      return "relative aspect-[4/3] overflow-hidden rounded-sm border border-white/30";
    case "modern":
      return "relative aspect-[4/3] overflow-hidden rounded-xl shadow-2xl ring-1 ring-white/10";
    case "conference":
      return "relative aspect-[4/3] overflow-hidden rounded-lg shadow-xl border border-white/20";
    case "bold":
      return "relative aspect-[4/3] overflow-hidden rounded-3xl shadow-2xl ring-2 ring-white/30";
    case "classic":
    default:
      return "relative aspect-[4/3] overflow-hidden rounded-2xl shadow-2xl ring-1 ring-white/20";
  }
}

export function getCardClassName(variant: LayoutVariant): string {
  switch (variant) {
    case "minimal":
      return "rounded-sm border border-border bg-background";
    case "modern":
      return "rounded-lg border border-border/50 bg-card shadow-md";
    case "conference":
      return "rounded-lg border border-border bg-card shadow-sm";
    case "bold":
      return "rounded-2xl border-2 border-border bg-card shadow-lg";
    case "classic":
    default:
      return "rounded-xl border border-border bg-card shadow-sm";
  }
}

export function getFaqItemClassName(variant: LayoutVariant): string {
  const base = "group border border-border bg-card px-5 py-4";
  switch (variant) {
    case "minimal":
      return `${base} rounded-sm`;
    case "modern":
      return `${base} rounded-lg shadow-sm`;
    case "conference":
      return `${base} rounded-lg`;
    case "bold":
      return `${base} rounded-2xl shadow-md`;
    case "classic":
    default:
      return `${base} rounded-xl shadow-sm`;
  }
}

export function getCtaSectionClassName(variant: LayoutVariant): string {
  switch (variant) {
    case "minimal":
      return "border-t-4 border-[var(--event-primary)] bg-background px-6 py-16 text-center text-foreground md:py-20";
    case "modern":
      return "px-6 py-20 text-center text-white md:py-24";
    case "conference":
      return "px-6 py-16 text-center text-white md:py-20";
    case "bold":
      return "px-6 py-20 text-center text-white md:py-24";
    case "classic":
    default:
      return "px-6 py-14 text-center text-white md:py-16";
  }
}

export function getCtaSectionStyle(variant: LayoutVariant): CSSProperties | undefined {
  if (variant === "minimal") {
    return undefined;
  }
  if (variant === "modern") {
    return {
      background: "linear-gradient(180deg, var(--event-primary), color-mix(in srgb, var(--event-primary) 70%, black))",
    };
  }
  return {
    background: `linear-gradient(135deg, var(--event-primary), var(--event-secondary))`,
  };
}

export function getCtaHeadingClassName(variant: LayoutVariant): string {
  switch (variant) {
    case "minimal":
      return "text-2xl font-medium md:text-3xl";
    case "modern":
      return "text-2xl font-semibold md:text-3xl";
    case "conference":
      return "text-2xl font-bold uppercase tracking-wide md:text-3xl";
    case "bold":
      return "text-3xl font-extrabold md:text-4xl";
    case "classic":
    default:
      return "text-2xl font-bold md:text-3xl";
  }
}

export function getPrimaryButtonClassName(variant: LayoutVariant): string {
  switch (variant) {
    case "minimal":
      return "bg-[var(--event-primary)] text-white hover:opacity-90";
    case "modern":
      return "bg-white text-[var(--event-primary)] font-medium shadow-lg hover:bg-white/90";
    case "conference":
      return "bg-[var(--event-secondary)] text-black font-bold uppercase tracking-wider hover:opacity-90";
    case "bold":
      return "border-2 border-white bg-transparent text-white hover:bg-white/15";
    case "classic":
    default:
      return "bg-white text-black hover:bg-white/90";
  }
}

export function getHeroPrimaryButtonClassName(variant: LayoutVariant): string {
  switch (variant) {
    case "minimal":
      return "bg-white text-[var(--event-primary)] hover:bg-white/90";
    case "modern":
      return "bg-white text-[var(--event-primary)] font-medium shadow-lg hover:bg-white/90";
    case "conference":
      return "bg-[var(--event-secondary)] text-black font-bold uppercase tracking-wider hover:opacity-90";
    case "bold":
      return "bg-white text-[var(--event-primary)] font-bold hover:bg-white/90";
    case "classic":
    default:
      return "bg-white text-black hover:bg-white/90";
  }
}

export function getDefaultCtaSubtitleClassName(variant: LayoutVariant): string {
  return variant === "minimal" ? "text-muted-foreground" : "text-white/90";
}
