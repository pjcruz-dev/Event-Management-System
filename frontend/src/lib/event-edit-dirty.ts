import type { LandingPageConfigFormValues, ThemeConfigFormValues } from "@/features/events/schemas";
import type { EventRecord } from "@/types/event";

export interface EventEditRsvpSettings {
  allow_plus_ones: boolean;
  max_plus_ones_per_invite: number;
  collect_meal_preferences: boolean;
  meal_options: string[];
  allow_maybe_response: boolean;
  response_deadline: string;
  auto_send_reminders: boolean;
  reminder_days_before_deadline: number;
}

export interface EventEditDirtyInput {
  event: EventRecord | undefined;
  detailsDirty: boolean;
  themeConfig: ThemeConfigFormValues;
  savedThemeJson: string;
  landingConfig: LandingPageConfigFormValues;
  savedLandingJson: string;
  registrationMode: EventRecord["registration_mode"];
  rsvpSettings: EventEditRsvpSettings;
  savedRsvpSettings: EventEditRsvpSettings;
}

function stableJson(value: unknown): string {
  return JSON.stringify(value);
}

export function isEventEditDirty(input: EventEditDirtyInput): boolean {
  if (!input.event) {
    return false;
  }

  if (input.detailsDirty) {
    return true;
  }

  if (stableJson(input.themeConfig) !== input.savedThemeJson) {
    return true;
  }

  if (stableJson(input.landingConfig) !== input.savedLandingJson) {
    return true;
  }

  if (input.registrationMode !== (input.event.registration_mode ?? "open")) {
    return true;
  }

  const savedRsvp = input.savedRsvpSettings;

  if (input.rsvpSettings.allow_plus_ones !== savedRsvp.allow_plus_ones) return true;
  if (input.rsvpSettings.max_plus_ones_per_invite !== savedRsvp.max_plus_ones_per_invite) {
    return true;
  }
  if (input.rsvpSettings.collect_meal_preferences !== savedRsvp.collect_meal_preferences) {
    return true;
  }
  if (stableJson(input.rsvpSettings.meal_options) !== stableJson(savedRsvp.meal_options)) {
    return true;
  }
  if (input.rsvpSettings.allow_maybe_response !== savedRsvp.allow_maybe_response) {
    return true;
  }

  const savedDeadline = savedRsvp.response_deadline;
  if (input.rsvpSettings.response_deadline !== savedDeadline) {
    return true;
  }

  if (input.rsvpSettings.auto_send_reminders !== savedRsvp.auto_send_reminders) {
    return true;
  }
  if (input.rsvpSettings.reminder_days_before_deadline !== savedRsvp.reminder_days_before_deadline) {
    return true;
  }

  return false;
}

export function shouldConfirmDiscardChanges(isDirty: boolean): boolean {
  if (!isDirty) {
    return true;
  }

  return window.confirm("You have unsaved changes. Discard them and continue?");
}
