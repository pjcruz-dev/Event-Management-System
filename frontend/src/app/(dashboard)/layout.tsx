"use client";

import { AuthGuard } from "@/components/shared/auth-guard";
import { DashboardHeader } from "@/components/layout/dashboard-header";

export default function DashboardLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <AuthGuard>
      <div className="min-h-screen bg-background">
        <DashboardHeader />
        <main className="mx-auto max-w-7xl p-4 sm:p-6">{children}</main>
      </div>
    </AuthGuard>
  );
}
