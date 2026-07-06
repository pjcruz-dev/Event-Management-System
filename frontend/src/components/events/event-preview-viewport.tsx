"use client";

import { cn } from "@/lib/utils";
import type { PreviewViewport } from "@/lib/preview-viewport";
import { getPreviewViewportFrameClass } from "@/lib/preview-viewport";

const VIEWPORTS: Array<{ id: PreviewViewport; label: string; width: string }> = [
  { id: "desktop", label: "Desktop", width: "100%" },
  { id: "tablet", label: "Tablet", width: "768px" },
  { id: "mobile", label: "Mobile", width: "375px" },
];

interface EventPreviewViewportProps {
  viewport: PreviewViewport;
  onViewportChange: (viewport: PreviewViewport) => void;
  children: React.ReactNode;
}

export function EventPreviewViewport({
  viewport,
  onViewportChange,
  children,
}: EventPreviewViewportProps) {
  const active = VIEWPORTS.find((item) => item.id === viewport) ?? VIEWPORTS[0];

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div
          className="inline-flex rounded-lg border border-border bg-muted/40 p-1"
          role="group"
          aria-label="Preview viewport size"
        >
          {VIEWPORTS.map((item) => (
            <button
              key={item.id}
              type="button"
              className={cn(
                "rounded-md px-3 py-1.5 text-xs font-medium transition-colors",
                viewport === item.id
                  ? "bg-background text-foreground shadow-sm"
                  : "text-muted-foreground hover:text-foreground",
              )}
              aria-pressed={viewport === item.id}
              onClick={() => onViewportChange(item.id)}
            >
              {item.label}
            </button>
          ))}
        </div>
        <p className="text-xs text-muted-foreground">
          Preview width: <span className="font-medium text-foreground">{active.width}</span>
        </p>
      </div>

      <div className="overflow-x-auto rounded-xl border border-border bg-muted/30 p-4">
        <div className={getPreviewViewportFrameClass(viewport)}>
          <div
            className={cn(
              "mx-auto max-h-[70vh] overflow-y-auto overflow-x-hidden rounded-lg border border-border bg-background shadow-md",
              viewport !== "desktop" && "ring-1 ring-border/60",
            )}
            style={viewport !== "desktop" ? { width: active.width, maxWidth: "100%" } : undefined}
          >
            {children}
          </div>
        </div>
      </div>
    </div>
  );
}
