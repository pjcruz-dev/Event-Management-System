import type { LandingBlock, LandingBlockType, LandingPageConfig, LandingTicketsConfig } from "@/types/event";

export const DEFAULT_TICKETS_CONFIG: LandingTicketsConfig = {
  visible: true,
  title: "Get your tickets",
  position: "after_blocks",
};

export const DEFAULT_BLOCK_SETTINGS: Record<LandingBlockType, Record<string, string | boolean>> = {
  hero: {
    headline: "",
    subheadline: "",
    show_register_button: true,
  },
  about: {
    body: "",
  },
  image: {
    image_url: "",
    caption: "",
    alt_text: "",
  },
  "agenda-preview": {
    title: "Conference agenda",
    subtitle:
      "Explore sessions, keynotes, and workshops. Reserve your seat for limited-capacity sessions on the full agenda.",
    limit: "4",
    show_view_all_link: true,
  },
  "speakers-preview": {
    title: "Featured speakers",
    subtitle: "Meet the experts leading sessions throughout the event.",
    limit: "6",
    layout: "grid",
  },
  sponsors: {
    title: "Our sponsors",
    subtitle: "Thank you to the partners making this event possible.",
    group_by_tier: true,
  },
  exhibitors: {
    title: "Exhibitors",
    subtitle: "Meet the organizations showcasing products and services at this event.",
    limit: "12",
    layout: "grid",
  },
  faq: {
    title: "Frequently asked questions",
    item_count: "0",
  },
  cta: {
    headline: "",
    subheadline: "",
    button_label: "Register now",
  },
};

export function defaultBlockForType(type: LandingBlockType): LandingBlock {
  return {
    type,
    visible: true,
    settings: { ...DEFAULT_BLOCK_SETTINGS[type] },
  };
}

export function isLandingBlockVisible(block: LandingBlock): boolean {
  return block.visible !== false;
}

export function visibleLandingBlocks(blocks: LandingBlock[]): LandingBlock[] {
  return blocks.filter(isLandingBlockVisible);
}

export function resolveTicketsConfig(config: LandingPageConfig | null | undefined): LandingTicketsConfig {
  return {
    ...DEFAULT_TICKETS_CONFIG,
    ...(config?.tickets ?? {}),
  };
}

export function shouldShowTicketsSection(tickets: LandingTicketsConfig): boolean {
  return tickets.visible !== false && tickets.position !== "hidden";
}

export function blockSettingString(
  settings: Record<string, string | boolean>,
  key: string,
  fallback = "",
): string {
  const value = settings[key];
  if (typeof value === "string") return value;
  if (typeof value === "boolean") return value ? "true" : "false";
  return fallback;
}

export function blockSettingBool(
  settings: Record<string, string | boolean>,
  key: string,
  fallback = true,
): boolean {
  const value = settings[key];
  if (typeof value === "boolean") return value;
  if (value === "true") return true;
  if (value === "false") return false;
  return fallback;
}

export function blockSettingNumber(
  settings: Record<string, string | boolean>,
  key: string,
  fallback: number,
): number {
  const value = settings[key];
  const parsed = typeof value === "number" ? value : Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
}

export function updateBlockSetting(
  settings: Record<string, string | boolean>,
  key: string,
  value: string | boolean,
): Record<string, string | boolean> {
  return { ...settings, [key]: value };
}
