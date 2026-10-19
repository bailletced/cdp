import { Link } from "@tanstack/react-router";
import { PageTitle } from "../components/PageTitle";

/**
 * 404 page (F01-T11): rendered by the root route's notFoundComponent when
 * no route matches — including `/`, until a home page exists. Rendered
 * inside the public layout.
 */
export function NotFoundPage() {
  return (
    <div className="mx-auto grid w-full max-w-3xl gap-4 px-4 py-8">
      <PageTitle>Page introuvable</PageTitle>
      <p>La page que vous cherchez n&apos;existe pas ou a été déplacée.</p>
      <p>
        <Link to="/" className="link">
          Retour à l&apos;accueil
        </Link>
      </p>
    </div>
  );
}
