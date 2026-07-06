import type { ReactNode } from "react";
import { cn } from "@/lib/utils";

interface FormFieldProps {
  label: string;
  htmlFor: string;
  error?: string;
  children: ReactNode;
  className?: string;
}

export function FormField({
  label,
  htmlFor,
  error,
  children,
  className,
}: FormFieldProps) {
  const errorId = error ? `${htmlFor}-error` : undefined;

  return (
    <div className={cn("space-y-2", className)}>
      <label htmlFor={htmlFor} className="text-sm font-medium leading-none">
        {label}
      </label>
      {children}
      {error ? (
        <p id={errorId} className="text-sm text-destructive" role="alert">
          {error}
        </p>
      ) : null}
    </div>
  );
}

interface FormErrorBannerProps {
  message: string;
  onDismiss?: () => void;
}

export function FormErrorBanner({ message, onDismiss }: FormErrorBannerProps) {
  return (
    <div
      className="flex items-center justify-between rounded-md border border-destructive/50 bg-destructive/10 px-4 py-3 text-sm text-destructive"
      role="alert"
    >
      <span>{message}</span>
      {onDismiss && (
        <button
          type="button"
          onClick={onDismiss}
          className="ml-4 shrink-0 text-destructive/70 hover:text-destructive"
          aria-label="Dismiss"
        >
          &times;
        </button>
      )}
    </div>
  );
}
