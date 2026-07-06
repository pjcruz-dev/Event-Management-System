"use client";

import Link from "next/link";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { useEventLandingContext } from "@/components/public/event-landing-context";

interface EventLandingLinkProps {
  href: string;
  children: React.ReactNode;
  className?: string;
  size?: "default" | "sm" | "lg" | "icon";
  variant?: "default" | "outline" | "ghost" | "secondary";
  style?: React.CSSProperties;
}

export function EventLandingLink({
  href,
  children,
  className,
  size = "default",
  variant = "default",
  style,
}: EventLandingLinkProps) {
  const { mode } = useEventLandingContext();

  if (mode === "preview") {
    return (
      <Button
        type="button"
        size={size}
        variant={variant}
        className={cn(className, "pointer-events-none")}
        style={style}
        disabled
        title="Preview only"
        aria-disabled="true"
      >
        {children}
      </Button>
    );
  }

  return (
    <Button asChild size={size} variant={variant} className={className} style={style}>
      <Link href={href}>{children}</Link>
    </Button>
  );
}

interface EventLandingAnchorProps {
  href: string;
  children: React.ReactNode;
  className?: string;
}

export function EventLandingAnchor({ href, children, className }: EventLandingAnchorProps) {
  const { mode } = useEventLandingContext();

  if (mode === "preview") {
    return <span className={className}>{children}</span>;
  }

  return (
    <a href={href} target="_blank" rel="noopener noreferrer" className={className}>
      {children}
    </a>
  );
}
