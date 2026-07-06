import type { SessionConflictWarning } from "@/types/conference";

interface ConflictWarningsProps {
  warnings: SessionConflictWarning[];
}

export function ConflictWarnings({ warnings }: ConflictWarningsProps) {
  if (warnings.length === 0) return null;

  return (
    <div
      className="rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-amber-900 dark:text-amber-100"
      role="alert"
    >
      <p className="font-medium">Scheduling warnings</p>
      <ul className="mt-2 list-disc space-y-1 pl-5">
        {warnings.map((warning, index) => (
          <li key={`${warning.type}-${warning.session_id}-${index}`}>
            {warning.message}
          </li>
        ))}
      </ul>
    </div>
  );
}
