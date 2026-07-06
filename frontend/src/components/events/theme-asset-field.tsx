"use client";

import { useId, useRef } from "react";
import { Loader2, Trash2, Upload } from "lucide-react";
import { assetFilenameFromUrl, hasThemeAsset } from "@/lib/theme-asset-utils";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export type ThemeAssetType = "logo" | "hero" | "hero_video";

interface ThemeAssetFieldProps {
  type: ThemeAssetType;
  label: string;
  url: string | null | undefined;
  uploading?: boolean;
  removing?: boolean;
  error?: string | null;
  onUpload: (file: File) => Promise<void>;
  onRemove: () => Promise<void>;
}

export function ThemeAssetField({
  type,
  label,
  url,
  uploading = false,
  removing = false,
  error,
  onUpload,
  onRemove,
}: ThemeAssetFieldProps) {
  const inputId = useId();
  const inputRef = useRef<HTMLInputElement>(null);
  const busy = uploading || removing;
  const hasAsset = hasThemeAsset(url);
  const filename = assetFilenameFromUrl(url);

  const handleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    try {
      await onUpload(file);
    } finally {
      event.target.value = "";
    }
  };

  return (
    <div className="space-y-2 rounded-lg border border-border p-4">
      <Label htmlFor={inputId}>{label}</Label>

      {hasAsset ? (
        <div className="space-y-3">
          <div
            className={
              type === "logo"
                ? "relative flex h-24 items-center justify-center rounded-md border border-dashed border-border bg-muted/30 p-3"
                : "relative aspect-[4/3] w-full overflow-hidden rounded-md border border-border bg-muted/30"
            }
          >
            {type === "hero_video" ? (
              <video
                src={url as string}
                muted
                loop
                autoPlay
                playsInline
                className="h-full w-full object-cover"
              />
            ) : (
              /* eslint-disable-next-line @next/next/no-img-element */
              <img
                src={url as string}
                alt={`${label} preview`}
                className={
                  type === "logo"
                    ? "max-h-full max-w-full object-contain"
                    : "h-full w-full object-cover"
                }
              />
            )}
          </div>
          {filename ? (
            <p className="truncate text-xs text-muted-foreground" title={filename}>
              {filename}
            </p>
          ) : null}
        </div>
      ) : (
        <div className="flex h-24 items-center justify-center rounded-md border border-dashed border-border bg-muted/20 px-4 text-center text-sm text-muted-foreground">
          No {label.toLowerCase()} uploaded yet
        </div>
      )}

      {error ? (
        <p className="text-sm text-destructive" role="alert">
          {error}
        </p>
      ) : null}

      <div className="flex flex-wrap gap-2">
        <Button
          type="button"
          variant="outline"
          size="sm"
          disabled={busy}
          onClick={() => inputRef.current?.click()}
        >
          {uploading ? (
            <Loader2 className="mr-2 h-4 w-4 animate-spin" aria-hidden />
          ) : (
            <Upload className="mr-2 h-4 w-4" aria-hidden />
          )}
          {hasAsset ? "Replace" : "Upload"}
        </Button>

        {hasAsset ? (
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={busy}
            onClick={() => void onRemove()}
          >
            {removing ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" aria-hidden />
            ) : (
              <Trash2 className="mr-2 h-4 w-4" aria-hidden />
            )}
            Remove
          </Button>
        ) : null}
      </div>

      <Input
        ref={inputRef}
        id={inputId}
        type="file"
        accept={
          type === "hero_video"
            ? "video/mp4,video/webm"
            : "image/jpeg,image/png,image/webp,image/svg+xml"
        }
        className="sr-only"
        disabled={busy}
        onChange={(e) => void handleFileChange(e)}
      />

      {uploading ? (
        <p className="text-xs text-muted-foreground" aria-live="polite">
          Uploading {label.toLowerCase()}…
        </p>
      ) : null}
    </div>
  );
}
