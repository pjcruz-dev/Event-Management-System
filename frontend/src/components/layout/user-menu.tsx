"use client";

import Link from "next/link";
import { ChevronDown, LogOut, Settings, User } from "lucide-react";
import { ThemeSegmentedControl } from "@/components/shared/theme-segmented-control";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useAuthStore } from "@/stores/auth-store";
import { cn } from "@/lib/utils";

function userInitials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return "?";
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
  return `${parts[0].charAt(0)}${parts[parts.length - 1].charAt(0)}`.toUpperCase();
}

export function UserMenu({ className }: { className?: string }) {
  const user = useAuthStore((s) => s.user);
  const organizations = useAuthStore((s) => s.organizations);
  const activeOrganizationId = useAuthStore((s) => s.activeOrganizationId);
  const logout = useAuthStore((s) => s.logout);

  const activeOrg = organizations.find((org) => org.id === activeOrganizationId);

  if (!user) {
    return null;
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          type="button"
          variant="outline"
          className={cn("h-10 gap-2 px-2 sm:px-3", className)}
          aria-label="Open account menu"
        >
          <Avatar className="h-7 w-7">
            {user.avatar_url ? <AvatarImage src={user.avatar_url} alt="" /> : null}
            <AvatarFallback className="text-xs">{userInitials(user.name)}</AvatarFallback>
          </Avatar>
          <span className="hidden max-w-[8rem] truncate text-sm font-medium sm:inline">
            {user.name}
          </span>
          <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-72 p-2">
        <div className="px-2 py-2">
          <p className="text-sm font-semibold">{user.name}</p>
          <p className="truncate text-xs text-muted-foreground">{user.email}</p>
          {activeOrg ? (
            <p className="mt-1 truncate text-xs font-medium uppercase tracking-wide text-muted-foreground">
              {activeOrg.name}
            </p>
          ) : null}
        </div>

        <DropdownMenuSeparator />

        <div className="px-2 py-2">
          <DropdownMenuLabel className="px-0 pb-2">Appearance</DropdownMenuLabel>
          <ThemeSegmentedControl />
        </div>

        <DropdownMenuSeparator />

        <DropdownMenuLabel>Account</DropdownMenuLabel>
        <DropdownMenuItem asChild>
          <Link href="/settings/profile">
            <User className="h-4 w-4" aria-hidden />
            Profile
          </Link>
        </DropdownMenuItem>
        <DropdownMenuItem asChild>
          <Link href="/settings/organization">
            <Settings className="h-4 w-4" aria-hidden />
            Organization settings
          </Link>
        </DropdownMenuItem>

        <DropdownMenuSeparator />

        <div className="p-1">
          <Button
            type="button"
            variant="outline"
            className="w-full justify-start gap-2 border-destructive/30 text-destructive hover:bg-destructive/10 hover:text-destructive"
            onClick={() =>
              void logout().then(() => {
                window.location.href = "/login";
              })
            }
          >
            <LogOut className="h-4 w-4" aria-hidden />
            Sign out
          </Button>
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
