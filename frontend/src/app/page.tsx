import { ThemeToggle } from "@/components/shared/theme-toggle";

export default function HomePage() {
  return (
    <main className="flex min-h-screen flex-col items-center justify-center gap-6 p-8">
      <div className="space-y-2 text-center">
        <p className="text-sm font-medium uppercase tracking-wide text-muted-foreground">
          Phase 0
        </p>
        <h1 className="text-3xl font-semibold tracking-tight">
          Event Management SaaS
        </h1>
        <p className="max-w-md text-muted-foreground">
          Architecture foundation — backend API and frontend shell are wired.
          Business features begin in Phase 1.
        </p>
      </div>
      <ThemeToggle />
    </main>
  );
}
