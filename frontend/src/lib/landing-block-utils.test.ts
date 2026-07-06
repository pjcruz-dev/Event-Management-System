import { describe, expect, it } from "vitest";
import {
  isLandingBlockVisible,
  resolveTicketsConfig,
  shouldShowTicketsSection,
  visibleLandingBlocks,
} from "@/lib/landing-block-utils";
import type { LandingBlock } from "@/types/event";

describe("isLandingBlockVisible", () => {
  it("returns true when visible is omitted", () => {
    const block: LandingBlock = { type: "hero", settings: {} };
    expect(isLandingBlockVisible(block)).toBe(true);
  });

  it("returns false when visible is false", () => {
    const block: LandingBlock = { type: "hero", visible: false, settings: {} };
    expect(isLandingBlockVisible(block)).toBe(false);
  });
});

describe("visibleLandingBlocks", () => {
  it("excludes hidden blocks from preview output", () => {
    const blocks = visibleLandingBlocks([
      { type: "hero", settings: {} },
      { type: "about", visible: false, settings: {} },
      { type: "cta", settings: {} },
    ]);

    expect(blocks.map((block) => block.type)).toEqual(["hero", "cta"]);
  });
});

describe("shouldShowTicketsSection", () => {
  it("hides tickets when visible is false", () => {
    expect(
      shouldShowTicketsSection(
        resolveTicketsConfig({
          blocks: [{ type: "hero", settings: {} }],
          tickets: { visible: false, title: "Tickets", position: "after_blocks" },
        }),
      ),
    ).toBe(false);
  });

  it("hides tickets when position is hidden", () => {
    expect(
      shouldShowTicketsSection(
        resolveTicketsConfig({
          blocks: [{ type: "hero", settings: {} }],
          tickets: { visible: true, title: "Tickets", position: "hidden" },
        }),
      ),
    ).toBe(false);
  });
});
