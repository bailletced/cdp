import { Button } from "@heroui/react";

/**
 * Empty state (F01-T08): muted message with an optional action.
 */
export function EmptyState({
  message,
  actionLabel,
  onAction,
}: {
  message: string;
  actionLabel?: string;
  onAction?: () => void;
}) {
  return (
    <div className="grid justify-items-start gap-4 py-8">
      <p className="text-muted">{message}</p>
      {actionLabel && onAction ? (
        <Button variant="outline" onClick={onAction}>
          {actionLabel}
        </Button>
      ) : null}
    </div>
  );
}
