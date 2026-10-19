import { Button } from "@heroui/react";

/**
 * Error state (F01-T08): message announced assertively (role=alert) with an
 * optional retry action.
 */
export function ErrorState({
  message,
  onRetry,
}: {
  message: string;
  onRetry?: () => void;
}) {
  return (
    <div role="alert" className="grid justify-items-start gap-4 py-8">
      <p>{message}</p>
      {onRetry ? (
        <Button variant="outline" onClick={onRetry}>
          Réessayer
        </Button>
      ) : null}
    </div>
  );
}
