import { Link } from "@tanstack/react-router";
import { ErrorState } from "../components/ErrorState";
import { PageTitle } from "../components/PageTitle";

interface ErrorPageProps {
  error: unknown;
  reset: () => void;
}

/**
 * Global error page (F01-T11): rendered by the root route's errorComponent
 * when a route fails. The message is announced assertively (role=alert) and
 * a retry action is offered. The raw error is never shown to the user.
 * Rendered inside the public layout.
 */
export function ErrorPage({ reset }: ErrorPageProps) {
  return (
    <div className="mx-auto grid w-full max-w-3xl gap-4 px-4 py-8">
      <PageTitle>Une erreur est survenue</PageTitle>
      <ErrorState
        message="Une erreur est survenue. Veuillez réessayer."
        onRetry={reset}
      />
      <p>
        <Link to="/" className="link">
          Retour à l&apos;accueil
        </Link>
      </p>
    </div>
  );
}
