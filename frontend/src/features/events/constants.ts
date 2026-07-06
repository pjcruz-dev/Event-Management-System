import type { LandingBlockType } from "@/types/event";

export const LANDING_BLOCK_LABELS: Record<LandingBlockType, string> = {
  hero: "Hero",
  about: "About",
  image: "Image",
  "agenda-preview": "Agenda preview",
  "speakers-preview": "Speakers preview",
  sponsors: "Sponsors",
  exhibitors: "Exhibitors",
  faq: "FAQ",
  cta: "Call to action",
};

export const FONT_OPTIONS = [
  "Inter",
  "Roboto",
  "Open Sans",
  "Lato",
  "Merriweather",
  "Poppins",
  "Montserrat",
  "Playfair Display",
  "Source Sans 3",
  "Raleway",
  "Nunito",
  "DM Sans",
] as const;

export const LAYOUT_OPTIONS = [
  { value: "classic", label: "Classic" },
  { value: "minimal", label: "Minimal" },
  { value: "bold", label: "Bold" },
  { value: "modern", label: "Modern" },
  { value: "conference", label: "Conference" },
] as const;

export const EVENT_STATUS_LABELS = {
  draft: "Draft",
  published: "Published",
  archived: "Archived",
} as const;
