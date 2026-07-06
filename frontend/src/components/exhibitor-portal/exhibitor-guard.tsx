"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useExhibitorAuthStore } from "@/stores/exhibitor-auth-store";

interface ExhibitorGuardProps {
  children: React.ReactNode;
}

export function ExhibitorGuard({ children }: ExhibitorGuardProps) {
  const router = useRouter();
  const isHydrated = useExhibitorAuthStore((s) => s.isHydrated);
  const isLoading = useExhibitorAuthStore((s) => s.isLoading);
  const token = useExhibitorAuthStore((s) => s.token);
  const contact = useExhibitorAuthStore((s) => s.contact);
  const fetchMe = useExhibitorAuthStore((s) => s.fetchMe);

  useEffect(() => {
    if (isHydrated && token && !contact && !isLoading) {
      void fetchMe();
    }
  }, [isHydrated, token, contact, isLoading, fetchMe]);

  useEffect(() => {
    if (isHydrated && !token) {
      router.replace("/exhibitor-portal/login");
    }
  }, [isHydrated, token, router]);

  if (!isHydrated || isLoading || !token || !contact) {
    return (
      <div className="flex min-h-[50vh] items-center justify-center">
        <p className="text-sm text-muted-foreground">Loading exhibitor portal…</p>
      </div>
    );
  }

  return <>{children}</>;
}
