"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { CalendarDays } from "lucide-react";
import { UserMenu } from "@/components/layout/user-menu";
import { cn } from "@/lib/utils";

const navItems = [
  { href: "/dashboard", label: "Overview" },
  { href: "/events", label: "Events" },
  { href: "/organization/analytics", label: "Analytics" },
  { href: "/organization/activity", label: "Activity" },
  { href: "/settings/profile", label: "Profile" },
  { href: "/settings/organization", label: "Organization" },
];

function isNavActive(pathname: string, href: string): boolean {
  return pathname === href || pathname.startsWith(`${href}/`);
}

export function DashboardHeader() {
  const pathname = usePathname();

  return (
    <header className="sticky top-0 z-40 border-b border-border bg-background/95 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-background/80">
      <div className="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 lg:gap-4">
        <Link
          href="/dashboard"
          className="flex shrink-0 items-center gap-2 rounded-md text-foreground transition-opacity hover:opacity-80"
        >
          <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
            <CalendarDays className="h-4 w-4" aria-hidden />
          </span>
          <span className="hidden font-semibold tracking-tight sm:inline">EventSaaS</span>
        </Link>

        <nav
          className="hidden flex-1 items-center justify-center gap-1 xl:flex"
          aria-label="Main navigation"
        >
          {navItems.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                "rounded-md px-3 py-2 text-sm font-medium transition-colors",
                isNavActive(pathname, item.href)
                  ? "bg-primary text-primary-foreground shadow-sm"
                  : "text-muted-foreground hover:bg-accent hover:text-accent-foreground",
              )}
            >
              {item.label}
            </Link>
          ))}
        </nav>

        <div className="ml-auto shrink-0">
          <UserMenu />
        </div>
      </div>

      <nav
        className="flex gap-1 overflow-x-auto border-t border-border px-4 py-2 xl:hidden"
        aria-label="Main navigation"
      >
        {navItems.map((item) => (
          <Link
            key={item.href}
            href={item.href}
            className={cn(
              "shrink-0 rounded-md px-3 py-1.5 text-sm font-medium transition-colors",
              isNavActive(pathname, item.href)
                ? "bg-primary text-primary-foreground"
                : "text-muted-foreground hover:bg-accent hover:text-accent-foreground",
            )}
          >
            {item.label}
          </Link>
        ))}
      </nav>
    </header>
  );
}
