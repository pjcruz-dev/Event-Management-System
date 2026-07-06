"use client";

import { Monitor, Moon, Sun } from "lucide-react";
import { useTheme } from "next-themes";
import { useEffect, useState } from "react";
import { cn } from "@/lib/utils";

const OPTIONS = [
  { value: "light", label: "Light", icon: Sun },
  { value: "dark", label: "Dark", icon: Moon },
  { value: "system", label: "System", icon: Monitor },
] as const;

interface ThemeSegmentedControlProps {
  className?: string;
}

export function ThemeSegmentedControl({ className }: ThemeSegmentedControlProps) {
  const { theme, setTheme } = useTheme();
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted) {
    return <div className={cn("h-9 rounded-md bg-muted/50", className)} />;
  }

  return (
    <div
      className={cn("grid grid-cols-3 gap-1 rounded-md border border-border bg-muted/40 p-1", className)}
      role="group"
      aria-label="Appearance"
    >
      {OPTIONS.map((option) => {
        const Icon = option.icon;
        const active = theme === option.value;

        return (
          <button
            key={option.value}
            type="button"
            className={cn(
              "flex items-center justify-center gap-1.5 rounded-sm px-2 py-1.5 text-xs font-medium transition-colors",
              active
                ? "bg-background text-foreground shadow-sm"
                : "text-muted-foreground hover:text-foreground",
            )}
            aria-pressed={active}
            onClick={() => setTheme(option.value)}
          >
            <Icon className="h-3.5 w-3.5" aria-hidden />
            <span>{option.label}</span>
          </button>
        );
      })}
    </div>
  );
}
