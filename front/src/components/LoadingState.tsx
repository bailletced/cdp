import { Spinner } from "@heroui/react";

/**
 * Loading state (F01-T08): spinner + message, announced politely — the
 * <output> element has the implicit role "status" (aria-live polite).
 */
export function LoadingState({ label = "Chargement…" }: { label?: string }) {
  return (
    <output className="flex items-center gap-3 py-8">
      <Spinner aria-hidden="true" size="sm" />
      {label}
    </output>
  );
}
