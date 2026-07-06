"use client";

import { Button } from "@/components/ui/button";
import { canPublish, publishBlockerMessage, type EventRecord } from "@/types/event";

interface LifecycleActionsProps {
  event: EventRecord;
  busy?: boolean;
  onPublish: () => void;
  onArchive: () => void;
  onDuplicate: () => void;
}

export function LifecycleActions({
  event,
  busy = false,
  onPublish,
  onArchive,
  onDuplicate,
}: LifecycleActionsProps) {
  const publishReady = canPublish(event);
  const publishTooltip = publishBlockerMessage(event);

  return (
    <div className="flex flex-wrap gap-2">
      <Button
        type="button"
        disabled={busy || event.status === "published" || !publishReady}
        title={!publishReady ? publishTooltip ?? undefined : undefined}
        onClick={() => {
          if (window.confirm("Publish this event? It will become visible on the public listing.")) {
            onPublish();
          }
        }}
      >
        Publish
      </Button>
      <Button
        type="button"
        variant="outline"
        disabled={busy || event.status === "archived"}
        onClick={() => {
          if (window.confirm("Archive this event?")) onArchive();
        }}
      >
        Archive
      </Button>
      <Button
        type="button"
        variant="outline"
        disabled={busy}
        onClick={() => {
          if (window.confirm("Duplicate this event as a new draft?")) onDuplicate();
        }}
      >
        Duplicate
      </Button>
      {!publishReady && event.status === "draft" ? (
        <p className="w-full text-sm text-muted-foreground">{publishTooltip}</p>
      ) : null}
    </div>
  );
}
