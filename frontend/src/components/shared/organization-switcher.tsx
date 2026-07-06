"use client";

import { Building2, Check, ChevronsUpDown } from "lucide-react";
import { useAuthStore } from "@/stores/auth-store";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { useState } from "react";

export function OrganizationSwitcher({ compact = false }: { compact?: boolean }) {
  const organizations = useAuthStore((s) => s.organizations);
  const activeOrganizationId = useAuthStore((s) => s.activeOrganizationId);
  const setActiveOrganization = useAuthStore((s) => s.setActiveOrganization);
  const [open, setOpen] = useState(false);

  const activeOrg = organizations.find((org) => org.id === activeOrganizationId);

  if (organizations.length === 0) {
    return null;
  }

  return (
    <div className="relative">
      <Button
        type="button"
        variant="outline"
        className={cn("justify-between", compact ? "h-9 w-full max-w-full" : "w-full md:w-64")}
        onClick={() => setOpen((value) => !value)}
        aria-expanded={open}
        aria-haspopup="listbox"
      >
        <span className="flex items-center gap-2 truncate">
          <Building2 className="h-4 w-4 shrink-0" />
          {activeOrg?.name ?? "Select organization"}
        </span>
        <ChevronsUpDown className="h-4 w-4 shrink-0 opacity-50" />
      </Button>
      {open ? (
        <ul
          className={cn(
            "absolute z-50 mt-2 w-full rounded-md border border-border bg-popover p-1 shadow-md",
            compact ? "min-w-[14rem]" : "md:w-64",
          )}
          role="listbox"
        >
          {organizations.map((org) => (
            <li key={org.id}>
              <button
                type="button"
                role="option"
                aria-selected={org.id === activeOrganizationId}
                className={cn(
                  "flex w-full items-center justify-between rounded-sm px-3 py-2 text-left text-sm hover:bg-accent",
                  org.id === activeOrganizationId && "bg-accent",
                )}
                onClick={() => {
                  setActiveOrganization(org.id);
                  setOpen(false);
                }}
              >
                <span className="truncate">{org.name}</span>
                {org.id === activeOrganizationId ? (
                  <Check className="h-4 w-4 shrink-0" />
                ) : null}
              </button>
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  );
}
