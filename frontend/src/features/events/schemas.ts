import { z } from "zod";

export const themeConfigSchema = z.object({
  primary_color: z.string().regex(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/),
  secondary_color: z.string().regex(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/),
  font: z.enum(["Inter", "Roboto", "Open Sans", "Lato", "Merriweather", "Poppins", "Montserrat", "Playfair Display", "Source Sans 3", "Raleway", "Nunito", "DM Sans"]),
  logo_url: z.string().nullable().optional(),
  hero_image_url: z.string().nullable().optional(),
  hero_video_url: z.string().nullable().optional(),
  hero_background_type: z.enum(["image", "video", "color"]).optional(),
  layout_variant: z.enum(["classic", "minimal", "bold", "modern", "conference"]),
});

const blockSettingValueSchema = z.union([z.string(), z.boolean()]);

const settingsRecordSchema = z.preprocess((value) => {
  if (value === null || value === undefined) {
    return {};
  }

  if (Array.isArray(value)) {
    return {};
  }

  if (typeof value === "object") {
    return Object.fromEntries(
      Object.entries(value as Record<string, unknown>).map(([key, entry]) => {
        if (typeof entry === "boolean") {
          return [key, entry];
        }

        if (entry === null || entry === undefined) {
          return [key, ""];
        }

        return [key, String(entry)];
      }),
    );
  }

  return {};
}, z.record(z.string(), blockSettingValueSchema));

export const landingBlockSchema = z.object({
  type: z.enum([
    "hero",
    "about",
    "image",
    "agenda-preview",
    "speakers-preview",
    "sponsors",
    "exhibitors",
    "faq",
    "cta",
  ]),
  visible: z.boolean().optional(),
  settings: settingsRecordSchema,
});

export const landingTicketsSchema = z.object({
  visible: z.boolean().default(true),
  title: z.string().min(1).max(120).default("Get your tickets"),
  position: z.enum(["after_blocks", "hidden"]).default("after_blocks"),
});

export const landingPageConfigSchema = z.object({
  blocks: z.array(landingBlockSchema).min(1),
  tickets: landingTicketsSchema.optional(),
});

export const eventDetailsSchema = z.object({
  name: z.string().min(1, "Name is required").max(255),
  slug: z
    .string()
    .regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/, "Use lowercase letters, numbers, and hyphens")
    .optional()
    .or(z.literal("")),
  description: z.string().optional(),
  venue: z.string().max(255).optional(),
  timezone: z.string().min(1, "Timezone is required"),
  capacity: z.number().int().min(1).optional(),
  visibility: z.enum(["public", "private"]),
  category: z
    .enum(["conference", "workshop", "concert", "meetup", "webinar", "festival", "other"])
    .optional()
    .or(z.literal("")),
  starts_at: z.string().optional(),
  ends_at: z.string().optional(),
  custom_domain: z.string().max(255).optional(),
  meta_title: z.string().max(255).optional(),
  meta_description: z.string().max(500).optional(),
});

export type EventDetailsFormValues = z.infer<typeof eventDetailsSchema>;
export type ThemeConfigFormValues = z.infer<typeof themeConfigSchema>;
export type LandingPageConfigFormValues = z.infer<typeof landingPageConfigSchema>;

export const defaultThemeConfig: ThemeConfigFormValues = {
  primary_color: "#1E40AF",
  secondary_color: "#F59E0B",
  font: "Inter",
  logo_url: null,
  hero_image_url: null,
  hero_video_url: null,
  hero_background_type: "image",
  layout_variant: "classic",
};

export const defaultLandingPageConfig: LandingPageConfigFormValues = {
  blocks: [
    { type: "hero", visible: true, settings: { headline: "", subheadline: "", show_register_button: true } },
    { type: "about", visible: true, settings: { body: "" } },
    { type: "cta", visible: true, settings: { headline: "", subheadline: "", button_label: "Register now" } },
  ],
  tickets: {
    visible: true,
    title: "Get your tickets",
    position: "after_blocks",
  },
};
