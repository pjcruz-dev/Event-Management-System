"use client";

import Image from "next/image";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

interface PublicEventNavProps {
  eventName: string;
  slug: string;
  logoUrl?: string | null;
  registrationMode?: string;
}

export function PublicEventNav({ eventName, slug, logoUrl, registrationMode }: PublicEventNavProps) {
  const pathname = usePathname();
  const base = `/e/${slug}`;
  const isOpen = registrationMode === "open";

  const links = [
    {
      href: base,
      label: "Overview",
      active: pathname === base,
    },
    {
      href: `${base}/agenda`,
      label: "Agenda",
      active: pathname.startsWith(`${base}/agenda`),
    },
  ];

  if (isOpen) {
    links.push({
      href: `${base}/register`,
      label: "Register",
      active: pathname.startsWith(`${base}/register`),
    });
  }

  return (
    <nav
      className="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80"
      aria-label="Event navigation"
    >
      <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-6 py-3">
        <Link
          href={base}
          className="flex items-center gap-2.5 hover:opacity-80 transition-opacity"
        >
          {logoUrl ? (
            <Image
              src={logoUrl}
              alt={`${eventName} logo`}
              width={32}
              height={32}
              className="h-8 w-auto shrink-0 object-contain"
            />
          ) : null}
          <span className="max-w-[min(100%,20rem)] truncate text-sm font-semibold md:text-base">
            {eventName}
          </span>
        </Link>
        <div className="flex flex-wrap items-center gap-1 sm:gap-2">
          {links.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              className={cn(
                "rounded-md px-3 py-1.5 text-sm transition-colors",
                link.active
                  ? "bg-accent font-medium text-accent-foreground"
                  : "text-muted-foreground hover:bg-accent/50 hover:text-foreground",
              )}
            >
              {link.label}
            </Link>
          ))}
          {isOpen ? (
            <Button asChild size="sm" className="ml-1 hidden sm:inline-flex">
              <Link href={`${base}/register`}>Get tickets</Link>
            </Button>
          ) : (
            <span className="ml-1 hidden rounded-md bg-muted px-3 py-1.5 text-xs font-medium text-muted-foreground sm:inline-flex">
              Invitation only
            </span>
          )}
        </div>
      </div>
    </nav>
  );
}
