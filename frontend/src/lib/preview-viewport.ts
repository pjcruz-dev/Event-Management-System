import type { CSSProperties } from "react";

export type PreviewViewport = "desktop" | "tablet" | "mobile";

export const PREVIEW_VIEWPORT_WIDTH: Record<PreviewViewport, string> = {
  desktop: "100%",
  tablet: "768px",
  mobile: "375px",
};

export function getPreviewViewportFrameClass(viewport: PreviewViewport): string {
  switch (viewport) {
    case "tablet":
      return "mx-auto w-full max-w-[768px]";
    case "mobile":
      return "mx-auto w-full max-w-[375px]";
    case "desktop":
    default:
      return "w-full";
  }
}

export function getPreviewViewportInnerStyle(viewport: PreviewViewport): CSSProperties {
  return {
    maxWidth: PREVIEW_VIEWPORT_WIDTH[viewport],
  };
}
