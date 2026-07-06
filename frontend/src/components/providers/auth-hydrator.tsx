"use client";

import { useEffect } from "react";
import { useAuthStore } from "@/stores/auth-store";

export function AuthHydrator() {
  const isHydrated = useAuthStore((s) => s.isHydrated);
  const token = useAuthStore((s) => s.token);
  const fetchMe = useAuthStore((s) => s.fetchMe);

  useEffect(() => {
    if (isHydrated && token) {
      void fetchMe();
    }
  }, [isHydrated, token, fetchMe]);

  return null;
}
