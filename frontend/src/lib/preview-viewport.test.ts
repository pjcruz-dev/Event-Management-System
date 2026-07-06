import { describe, expect, it } from "vitest";
import { getPreviewViewportFrameClass } from "@/lib/preview-viewport";

describe("getPreviewViewportFrameClass", () => {
  it("applies tablet max width class", () => {
    expect(getPreviewViewportFrameClass("tablet")).toContain("max-w-[768px]");
  });

  it("applies mobile max width class", () => {
    expect(getPreviewViewportFrameClass("mobile")).toContain("max-w-[375px]");
  });

  it("uses full width for desktop", () => {
    expect(getPreviewViewportFrameClass("desktop")).toBe("w-full");
  });
});
