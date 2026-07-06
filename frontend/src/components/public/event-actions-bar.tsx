"use client";

import { useState } from "react";
import { CalendarPlus, Check, Copy, ExternalLink, Share2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  downloadIcsFile,
  googleCalendarUrl,
  outlookCalendarUrl,
} from "@/lib/calendar-utils";
import type { EventRecord } from "@/types/event";

interface EventActionsBarProps {
  event: EventRecord;
}

export function EventActionsBar({ event }: EventActionsBarProps) {
  const [calendarOpen, setCalendarOpen] = useState(false);
  const [copied, setCopied] = useState(false);

  const eventUrl = typeof window !== "undefined" ? window.location.href : "";

  const handleCopyLink = async () => {
    try {
      await navigator.clipboard.writeText(eventUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      /* clipboard not available */
    }
  };

  const shareToFacebook = () => {
    window.open(
      `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(eventUrl)}`,
      "_blank",
      "width=600,height=400",
    );
  };

  const shareToTwitter = () => {
    const text = `Check out ${event.name}!`;
    window.open(
      `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(eventUrl)}`,
      "_blank",
      "width=600,height=400",
    );
  };

  const shareToLinkedin = () => {
    window.open(
      `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(eventUrl)}`,
      "_blank",
      "width=600,height=400",
    );
  };

  const hasCalendarData = Boolean(event.starts_at);

  return (
    <div className="flex flex-wrap items-center gap-2">
      {hasCalendarData && (
        <div className="relative">
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => setCalendarOpen(!calendarOpen)}
            className="gap-2"
          >
            <CalendarPlus className="h-4 w-4" aria-hidden />
            Add to calendar
          </Button>
          {calendarOpen && (
            <>
              <div
                className="fixed inset-0 z-40"
                onClick={() => setCalendarOpen(false)}
              />
              <div className="absolute left-0 top-full z-50 mt-1 w-52 rounded-lg border border-border bg-popover p-1 shadow-lg">
                <button
                  type="button"
                  className="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-accent"
                  onClick={() => {
                    window.open(googleCalendarUrl(event), "_blank");
                    setCalendarOpen(false);
                  }}
                >
                  Google Calendar
                </button>
                <button
                  type="button"
                  className="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-accent"
                  onClick={() => {
                    window.open(outlookCalendarUrl(event), "_blank");
                    setCalendarOpen(false);
                  }}
                >
                  Outlook Calendar
                </button>
                <button
                  type="button"
                  className="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-accent"
                  onClick={() => {
                    downloadIcsFile(event);
                    setCalendarOpen(false);
                  }}
                >
                  Download .ics file
                </button>
              </div>
            </>
          )}
        </div>
      )}

      <div className="flex items-center gap-1">
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={shareToFacebook}
          title="Share on Facebook"
          className="gap-1.5"
        >
          <Share2 className="h-3.5 w-3.5" aria-hidden />
          Facebook
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={shareToTwitter}
          title="Share on X"
          className="gap-1.5"
        >
          <ExternalLink className="h-3.5 w-3.5" aria-hidden />
          X
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={shareToLinkedin}
          title="Share on LinkedIn"
          className="gap-1.5"
        >
          <ExternalLink className="h-3.5 w-3.5" aria-hidden />
          LinkedIn
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => void handleCopyLink()}
          title="Copy link"
          className="gap-1.5"
        >
          {copied ? (
            <Check className="h-3.5 w-3.5" aria-hidden />
          ) : (
            <Copy className="h-3.5 w-3.5" aria-hidden />
          )}
          {copied ? "Copied!" : "Copy link"}
        </Button>
      </div>
    </div>
  );
}
