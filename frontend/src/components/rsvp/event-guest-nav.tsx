import Link from "next/link";
import { Button } from "@/components/ui/button";

interface EventGuestNavProps {
  eventId: number;
}

const links = [
  { href: (id: number) => `/events/${id}/guests`, label: "Guest list" },
  { href: (id: number) => `/events/${id}/seating`, label: "Seating" },
] as const;

export function EventGuestNav({ eventId }: EventGuestNavProps) {
  return (
    <div className="flex flex-wrap gap-2">
      {links.map((item) => (
        <Button key={item.label} asChild variant="outline" size="sm">
          <Link href={item.href(eventId)}>{item.label}</Link>
        </Button>
      ))}
      <Button asChild variant="outline" size="sm">
        <Link href={`/events/${eventId}/edit`}>Back to event</Link>
      </Button>
    </div>
  );
}
