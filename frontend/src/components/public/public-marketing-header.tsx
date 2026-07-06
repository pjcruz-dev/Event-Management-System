import Link from "next/link";

export function PublicMarketingHeader() {
  return (
    <header className="sticky top-0 z-50 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
      <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-3">
        <Link href="/discover" className="text-sm font-semibold tracking-tight md:text-base">
          Event SaaS
        </Link>
        <nav className="flex items-center gap-4 text-sm">
          <Link href="/discover" className="text-muted-foreground hover:text-foreground">
            Discover
          </Link>
          <Link href="/login" className="text-muted-foreground hover:text-foreground">
            Organizer sign in
          </Link>
        </nav>
      </div>
    </header>
  );
}
