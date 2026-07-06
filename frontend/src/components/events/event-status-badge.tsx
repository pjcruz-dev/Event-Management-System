import { Badge } from "@/components/ui/badge";
import { EVENT_STATUS_LABELS } from "@/features/events/constants";
import type { EventStatus } from "@/types/event";

export function EventStatusBadge({ status }: { status: EventStatus }) {
  return (
    <Badge variant={status === "published" || status === "archived" ? status : "draft"}>
      {EVENT_STATUS_LABELS[status]}
    </Badge>
  );
}
