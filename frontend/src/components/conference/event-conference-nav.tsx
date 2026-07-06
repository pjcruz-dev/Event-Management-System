import Link from "next/link";
import { Button } from "@/components/ui/button";

interface EventConferenceNavProps {
  eventId: number;
}

const links = [
  { href: (id: number) => `/events/${id}/agenda`, label: "Agenda" },
  { href: (id: number) => `/events/${id}/speakers`, label: "Speakers" },
  { href: (id: number) => `/events/${id}/sponsors`, label: "Sponsors" },
  { href: (id: number) => `/events/${id}/exhibitors`, label: "Exhibitors" },
] as const;

export function EventConferenceNav({ eventId }: EventConferenceNavProps) {
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
