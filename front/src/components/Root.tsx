import {
  Link,
  Outlet,
  createRootRouteWithContext,
} from "@tanstack/react-router";
import { useMe } from "../api/hooks";
import { SITE_NAME } from "../config/site";
import { NotFoundPage } from "../pages/NotFoundPage";
import type { AppRouterContext } from "../routerContext";

// Standalone link style for TanStack Router links (the .link class of the
// stylesheet only applies to HeroUI Link components). Standalone links get a
// 44 px touch target (F01-T06).
const NAV_LINK =
  "flex min-h-11 items-center px-2 text-link underline decoration-1 underline-offset-4";

/**
 * Public layout (F01-T07): skip link, header (community name from the single
 * site config), main landmark, footer (Confidentialité, Recevoir mon lien).
 * The <main> landmark is provided here: pages render their content inside it.
 */
export const Root = createRootRouteWithContext<AppRouterContext>()({
  // The 404 page renders inside this layout (F01-T11). The global error
  // page is wired as the router's defaultErrorComponent (router.tsx): a
  // route-level errorComponent on the root route would replace this
  // layout instead of rendering inside it.
  notFoundComponent: NotFoundPage,
  component: () => {
    const { data, isLoading, isError } = useMe();

    return (
      <div className="flex min-h-screen flex-col">
        <a
          href="#main"
          className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:flex focus:min-h-11 focus:items-center focus:rounded-lg focus:bg-accent focus:px-4 focus:py-2 focus:text-accent-foreground"
        >
          Aller au contenu
        </a>
        <header className="border-b border-separator">
          <div className="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3 px-4 py-4">
            <Link
              to="/"
              className="flex min-h-11 items-center gap-3 text-foreground no-underline"
            >
              <CarmelMark />
              <span className="max-w-72 font-display text-xl font-medium leading-tight">
                {SITE_NAME}
              </span>
            </Link>
            <nav aria-label="Navigation principale">
              {isLoading ? null : !isError && data ? (
                <ul className="flex flex-wrap gap-x-4">
                  {/* The back-office entry, for organizers only (F04) */}
                  {data.isOrganizer ? (
                    <li>
                      <Link to="/organisation" className={NAV_LINK}>
                        Organisation
                      </Link>
                    </li>
                  ) : null}
                  <li>
                    <Link to="/my-space" className={NAV_LINK}>
                      Mon espace
                    </Link>
                  </li>
                </ul>
              ) : (
                <Link to="/receive-my-link" className={NAV_LINK}>
                  Recevoir mon lien
                </Link>
              )}
            </nav>
          </div>
        </header>
        <main id="main" className="flex-1">
          <Outlet />
        </main>
        <footer className="mt-16 border-t border-separator">
          <div className="mx-auto flex max-w-3xl flex-wrap gap-x-6 gap-y-2 px-4 py-6">
            <Link to="/confidentialite" className={NAV_LINK}>
              Confidentialité
            </Link>
            <Link to="/receive-my-link" className={NAV_LINK}>
              Recevoir mon lien
            </Link>
          </div>
        </footer>
      </div>
    );
  },
});

/**
 * The Carmel mark, drawn from the Order's coat of arms: Mount Carmel crowned
 * with a cross, under two stars. Decorative: the community name next to it
 * names the link.
 */
function CarmelMark() {
  return (
    <svg
      aria-hidden="true"
      className="h-10 w-10 shrink-0 text-accent"
      viewBox="0 0 40 40"
      fill="none"
    >
      <path
        d="M4 35 20 15l16 20Z"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinejoin="round"
      />
      <path
        d="M20 3v12M15.5 7.5h9"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
      />
      <circle cx="20" cy="28" r="2" fill="currentColor" />
      <circle cx="8" cy="9" r="2" fill="currentColor" />
      <circle cx="32" cy="9" r="2" fill="currentColor" />
    </svg>
  );
}
