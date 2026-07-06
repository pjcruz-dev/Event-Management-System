import { describe, expect, it } from "vitest";
import {
  slugifyFieldKey,
  validateRegistrationFields,
} from "@/lib/registration-form-utils";
import type { RegistrationField } from "@/types/ticketing";

describe("slugifyFieldKey", () => {
  it("converts labels to snake_case keys", () => {
    expect(slugifyFieldKey("T-Shirt Size", 1)).toBe("t_shirt_size");
  });
});

describe("validateRegistrationFields", () => {
  const baseField: RegistrationField = {
    key: "company",
    type: "text",
    label: "Company",
    required: false,
    options: [],
  };

  it("rejects duplicate keys", () => {
    const result = validateRegistrationFields([
      baseField,
      { ...baseField, label: "Company copy" },
    ]);
    expect(result.valid).toBe(false);
  });

  it("requires at least two select options", () => {
    const result = validateRegistrationFields([
      {
        key: "meal",
        type: "select",
        label: "Meal preference",
        required: true,
        options: ["Vegan"],
      },
    ]);
    expect(result.valid).toBe(false);
  });
});
