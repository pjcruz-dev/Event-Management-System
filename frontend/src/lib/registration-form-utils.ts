import type { RegistrationField } from "@/types/ticketing";

export const REGISTRATION_FIELD_TYPES: Array<{
  value: RegistrationField["type"];
  label: string;
  description: string;
}> = [
  { value: "text", label: "Short text", description: "Single-line text input" },
  { value: "email", label: "Email", description: "Validated email address" },
  { value: "phone", label: "Phone", description: "Phone number" },
  { value: "textarea", label: "Long text", description: "Multi-line text area" },
  { value: "select", label: "Dropdown", description: "Choose one option from a list" },
  { value: "checkbox", label: "Checkbox", description: "Yes / no confirmation" },
];

const FIELD_KEY_PATTERN = /^[a-z0-9_]+$/;

export function slugifyFieldKey(label: string, fallbackIndex: number): string {
  const slug = label
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, "_")
    .replace(/^_+|_+$/g, "")
    .replace(/_+/g, "_");

  return slug || `field_${fallbackIndex}`;
}

export function validateRegistrationFields(
  fields: RegistrationField[],
): { valid: true } | { valid: false; message: string } {
  if (fields.length === 0) {
    return { valid: false, message: "Add at least one custom field." };
  }

  const keys = new Set<string>();

  for (const [index, field] of fields.entries()) {
    const label = field.label.trim();
    if (!label) {
      return { valid: false, message: `Field ${index + 1} needs a label.` };
    }

    const key = field.key.trim();
    if (!FIELD_KEY_PATTERN.test(key)) {
      return {
        valid: false,
        message: `"${label}" has an invalid key. Use lowercase letters, numbers, and underscores only.`,
      };
    }

    if (keys.has(key)) {
      return { valid: false, message: `Duplicate field key "${key}". Each field must have a unique key.` };
    }
    keys.add(key);

    if (field.type === "select") {
      const options = field.options.map((option) => option.trim()).filter(Boolean);
      if (options.length < 2) {
        return {
          valid: false,
          message: `"${label}" needs at least two options for a dropdown field.`,
        };
      }
    }
  }

  return { valid: true };
}

export function normalizeRegistrationFields(fields: RegistrationField[]): RegistrationField[] {
  return fields.map((field, index) => ({
    ...field,
    key: field.key.trim() || slugifyFieldKey(field.label, index + 1),
    label: field.label.trim(),
    options:
      field.type === "select"
        ? field.options.map((option) => option.trim()).filter(Boolean)
        : [],
  }));
}
