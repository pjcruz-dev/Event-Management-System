"use client";

import { FONT_OPTIONS, LAYOUT_OPTIONS } from "@/features/events/constants";
import type { ThemeConfigFormValues } from "@/features/events/schemas";
import { ThemeAssetField, type ThemeAssetType } from "@/components/events/theme-asset-field";
import { FormField } from "@/components/shared/form-field";
import { Input } from "@/components/ui/input";

interface ThemeBuilderProps {
  value: ThemeConfigFormValues;
  onChange: (value: ThemeConfigFormValues) => void;
  onUpload: (type: ThemeAssetType, file: File) => Promise<void>;
  onRemove: (type: ThemeAssetType) => Promise<void>;
  uploadingType?: ThemeAssetType | null;
  removingType?: ThemeAssetType | null;
  assetErrors?: Partial<Record<ThemeAssetType, string>>;
}

export function ThemeBuilder({
  value,
  onChange,
  onUpload,
  onRemove,
  uploadingType = null,
  removingType = null,
  assetErrors = {},
}: ThemeBuilderProps) {
  return (
    <div className="space-y-4">
      <p className="text-sm text-muted-foreground">
        SEO and social preview image are configured under Settings.
      </p>

      <div className="grid gap-4 sm:grid-cols-2">
        <FormField label="Primary color" htmlFor="primary_color">
          <div className="flex gap-2">
            <Input
              id="primary_color"
              type="color"
              value={value.primary_color}
              onChange={(e) => onChange({ ...value, primary_color: e.target.value })}
              className="h-10 w-14 cursor-pointer p-1"
            />
            <Input
              value={value.primary_color}
              onChange={(e) => onChange({ ...value, primary_color: e.target.value })}
            />
          </div>
        </FormField>
        <FormField label="Secondary color" htmlFor="secondary_color">
          <div className="flex gap-2">
            <Input
              id="secondary_color"
              type="color"
              value={value.secondary_color}
              onChange={(e) => onChange({ ...value, secondary_color: e.target.value })}
              className="h-10 w-14 cursor-pointer p-1"
            />
            <Input
              value={value.secondary_color}
              onChange={(e) => onChange({ ...value, secondary_color: e.target.value })}
            />
          </div>
        </FormField>
      </div>

      <FormField label="Font" htmlFor="font">
        <select
          id="font"
          className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          value={value.font}
          onChange={(e) =>
            onChange({ ...value, font: e.target.value as ThemeConfigFormValues["font"] })
          }
        >
          {FONT_OPTIONS.map((font) => (
            <option key={font} value={font}>
              {font}
            </option>
          ))}
        </select>
      </FormField>

      <FormField label="Layout variant" htmlFor="layout_variant">
        <select
          id="layout_variant"
          className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          value={value.layout_variant}
          onChange={(e) =>
            onChange({
              ...value,
              layout_variant: e.target.value as ThemeConfigFormValues["layout_variant"],
            })
          }
        >
          {LAYOUT_OPTIONS.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      </FormField>

      <ThemeAssetField
        type="logo"
        label="Logo"
        url={value.logo_url}
        uploading={uploadingType === "logo"}
        removing={removingType === "logo"}
        error={assetErrors.logo}
        onUpload={(file) => onUpload("logo", file)}
        onRemove={() => onRemove("logo")}
      />

      <FormField label="Hero background" htmlFor="hero_background_type">
        <select
          id="hero_background_type"
          className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          value={value.hero_background_type ?? "image"}
          onChange={(e) =>
            onChange({
              ...value,
              hero_background_type: e.target.value as "image" | "video" | "color",
            })
          }
        >
          <option value="image">Image</option>
          <option value="video">Video</option>
          <option value="color">Color only</option>
        </select>
      </FormField>

      {(value.hero_background_type ?? "image") === "image" && (
        <ThemeAssetField
          type="hero"
          label="Hero image"
          url={value.hero_image_url}
          uploading={uploadingType === "hero"}
          removing={removingType === "hero"}
          error={assetErrors.hero}
          onUpload={(file) => onUpload("hero", file)}
          onRemove={() => onRemove("hero")}
        />
      )}

      {value.hero_background_type === "video" && (
        <ThemeAssetField
          type="hero_video"
          label="Hero video (mp4/webm, max 50MB)"
          url={value.hero_video_url}
          uploading={uploadingType === "hero_video"}
          removing={removingType === "hero_video"}
          error={assetErrors.hero_video}
          onUpload={(file) => onUpload("hero_video", file)}
          onRemove={() => onRemove("hero_video")}
        />
      )}
    </div>
  );
}
