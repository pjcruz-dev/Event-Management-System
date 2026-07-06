export function formatEventDateRange(
  startsAt: string | null,
  endsAt: string | null,
  timezone?: string,
): string | null {
  if (!startsAt) return null;

  const start = new Date(startsAt);
  const end = endsAt ? new Date(endsAt) : null;
  const dateOpts: Intl.DateTimeFormatOptions = {
    weekday: "short",
    month: "long",
    day: "numeric",
    year: "numeric",
    timeZone: timezone || undefined,
  };
  const timeOpts: Intl.DateTimeFormatOptions = {
    hour: "numeric",
    minute: "2-digit",
    timeZone: timezone || undefined,
  };

  const startDate = start.toLocaleDateString(undefined, dateOpts);
  const startTime = start.toLocaleTimeString(undefined, timeOpts);

  if (!end || end.toDateString() === start.toDateString()) {
    const endTime = end ? end.toLocaleTimeString(undefined, timeOpts) : null;
    return endTime ? `${startDate} · ${startTime} – ${endTime}` : `${startDate} · ${startTime}`;
  }

  const endDate = end.toLocaleDateString(undefined, dateOpts);
  return `${startDate} – ${endDate}`;
}

export function formatTicketPrice(price: number, currency: string): string {
  if (price <= 0) return "Free";
  try {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(price);
  } catch {
    return `${currency} ${price.toFixed(2)}`;
  }
}

export function formatTierLabel(tier: string): string {
  return tier.charAt(0).toUpperCase() + tier.slice(1);
}

export function formatSessionTime(startsAt: string | null, endsAt: string | null): string {
  if (!startsAt) return "Time TBA";
  const start = new Date(startsAt);
  const startStr = start.toLocaleTimeString(undefined, { hour: "numeric", minute: "2-digit" });
  if (!endsAt) return startStr;
  const end = new Date(endsAt);
  const endStr = end.toLocaleTimeString(undefined, { hour: "numeric", minute: "2-digit" });
  return `${startStr} – ${endStr}`;
}
