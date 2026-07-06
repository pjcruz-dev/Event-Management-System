"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useAuthStore } from "@/stores/auth-store";

interface AuthGuardProps {
  children: React.ReactNode;
}

export function AuthGuard({ children }: AuthGuardProps) {
  const router = useRouter();
  const isHydrated = useAuthStore((s) => s.isHydrated);
  const isLoading = useAuthStore((s) => s.isLoading);
  const token = useAuthStore((s) => s.token);
  const user = useAuthStore((s) => s.user);
  const fetchMe = useAuthStore((s) => s.fetchMe);

  useEffect(() => {
    if (isHydrated && token && !user && !isLoading) {
      void fetchMe();
    }
  }, [isHydrated, token, user, isLoading, fetchMe]);

  useEffect(() => {
    if (isHydrated && !token) {
      router.replace("/login");
    }
  }, [isHydrated, token, router]);

  if (!isHydrated || isLoading || !token || !user) {
    return (
      <div className="flex min-h-[50vh] items-center justify-center">
        <p className="text-sm text-muted-foreground">Loading your workspace…</p>
      </div>
    );
  }

  return <>{children}</>;
}
