"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { ThemeToggle } from "@/components/shared/theme-toggle";
import { Button } from "@/components/ui/button";
import { useExhibitorAuthStore } from "@/stores/exhibitor-auth-store";
import { cn } from "@/lib/utils";

const navItems = [
  { href: "/exhibitor-portal/leads", label: "Leads" },
  { href: "/exhibitor-portal/scanner", label: "Scanner" },
  { href: "/exhibitor-portal/profile", label: "Profile" },
];

export default function ExhibitorPortalLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const pathname = usePathname();
  const contact = useExhibitorAuthStore((s) => s.contact);
  const exhibitor = useExhibitorAuthStore((s) => s.exhibitor);
  const logout = useExhibitorAuthStore((s) => s.logout);
  const isLoginPage = pathname === "/exhibitor-portal/login";

  return (
    <div className="min-h-screen bg-background">
      <header className="border-b border-border">
        <div className="mx-auto flex max-w-5xl flex-col gap-4 p-4 md:flex-row md:items-center md:justify-between">
          <div>
            <p className="text-sm font-semibold">Exhibitor portal</p>
            {!isLoginPage && contact ? (
              <p className="text-sm text-muted-foreground">
                {contact.name} · {exhibitor?.name ?? "Booth"}
              </p>
            ) : null}
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <ThemeToggle />
            {!isLoginPage ? (
              <Button
                type="button"
                variant="outline"
                onClick={() =>
                  void logout().then(() => {
                    window.location.href = "/exhibitor-portal/login";
                  })
                }
              >
                Sign out
              </Button>
            ) : null}
          </div>
        </div>
        {!isLoginPage ? (
          <nav className="mx-auto flex max-w-5xl gap-1 overflow-x-auto px-4 pb-3">
            {navItems.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className={cn(
                  "rounded-md px-3 py-2 text-sm font-medium transition-colors",
                  pathname === item.href
                    ? "bg-accent text-accent-foreground"
                    : "text-muted-foreground hover:bg-accent hover:text-accent-foreground",
                )}
              >
                {item.label}
              </Link>
            ))}
          </nav>
        ) : null}
      </header>
      <main className="mx-auto max-w-5xl p-6">{children}</main>
    </div>
  );
}
