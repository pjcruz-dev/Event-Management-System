"use client";

import { createContext, useContext } from "react";
import type { LayoutVariant } from "@/lib/event-layout-variant";

export type EventLandingMode = "public" | "preview";

interface EventLandingContextValue {
  mode: EventLandingMode;
  layoutVariant: LayoutVariant;
}

const EventLandingContext = createContext<EventLandingContextValue>({
  mode: "public",
  layoutVariant: "classic",
});

export function EventLandingProvider({
  mode,
  layoutVariant,
  children,
}: {
  mode: EventLandingMode;
  layoutVariant: LayoutVariant;
  children: React.ReactNode;
}) {
  return (
    <EventLandingContext.Provider value={{ mode, layoutVariant }}>
      {children}
    </EventLandingContext.Provider>
  );
}

export function useEventLandingContext(): EventLandingContextValue {
  return useContext(EventLandingContext);
}
