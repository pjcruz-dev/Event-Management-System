import type { EventRecord } from "@/types/event";

function formatIcsDate(date: string): string {
  return new Date(date)
    .toISOString()
    .replace(/[-:]/g, "")
    .replace(/\.\d{3}/, "");
}

export function generateIcsContent(event: EventRecord): string {
  const start = event.starts_at ? formatIcsDate(event.starts_at) : "";
  const end = event.ends_at ? formatIcsDate(event.ends_at) : start;
  const now = formatIcsDate(new Date().toISOString());

  const lines = [
    "BEGIN:VCALENDAR",
    "VERSION:2.0",
    "PRODID:-//EventSaaS//Event//EN",
    "CALSCALE:GREGORIAN",
    "METHOD:PUBLISH",
    "BEGIN:VEVENT",
    `DTSTART:${start}`,
    `DTEND:${end}`,
    `DTSTAMP:${now}`,
    `UID:${event.slug}@eventsaas`,
    `SUMMARY:${escapeIcsText(event.name)}`,
    event.description
      ? `DESCRIPTION:${escapeIcsText(event.description)}`
      : "",
    event.venue ? `LOCATION:${escapeIcsText(event.venue)}` : "",
    "END:VEVENT",
    "END:VCALENDAR",
  ];

  return lines.filter(Boolean).join("\r\n");
}

function escapeIcsText(text: string): string {
  return text
    .replace(/\\/g, "\\\\")
    .replace(/;/g, "\\;")
    .replace(/,/g, "\\,")
    .replace(/\n/g, "\\n");
}

export function downloadIcsFile(event: EventRecord): void {
  const content = generateIcsContent(event);
  const blob = new Blob([content], { type: "text/calendar;charset=utf-8" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = `${event.slug}.ics`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

export function googleCalendarUrl(event: EventRecord): string {
  const params = new URLSearchParams();
  params.set("action", "TEMPLATE");
  params.set("text", event.name);
  if (event.starts_at) {
    const start = formatIcsDate(event.starts_at).replace("Z", "");
    const end = event.ends_at
      ? formatIcsDate(event.ends_at).replace("Z", "")
      : start;
    params.set("dates", `${start}/${end}`);
  }
  if (event.description) {
    params.set("details", event.description.slice(0, 1000));
  }
  if (event.venue) {
    params.set("location", event.venue);
  }
  return `https://calendar.google.com/calendar/render?${params.toString()}`;
}

export function outlookCalendarUrl(event: EventRecord): string {
  const params = new URLSearchParams();
  params.set("path", "/calendar/action/compose");
  params.set("rru", "addevent");
  params.set("subject", event.name);
  if (event.starts_at) params.set("startdt", event.starts_at);
  if (event.ends_at) params.set("enddt", event.ends_at);
  if (event.venue) params.set("location", event.venue);
  if (event.description) params.set("body", event.description.slice(0, 1000));
  return `https://outlook.live.com/calendar/0/deeplink/compose?${params.toString()}`;
}
