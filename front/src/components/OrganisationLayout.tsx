import { Link, Outlet } from "@tanstack/react-router";

/**
 * Back-office layout (F04-T04): back-office navigation and outlet for the
 * section pages. Rendered inside the public layout's <main> landmark.
 * Loaded lazily with the section: the back-office bundle is never fetched
 * on public pages.
 */
export function OrganisationLayout() {
  return (
    <div className="flex flex-col gap-6 px-4 py-8">
      <nav aria-label="Back-office">
        <ul className="flex flex-wrap gap-x-6 gap-y-2">
          <li>
            <Link
              to="/organisation"
              className="text-link underline underline-offset-4"
            >
              Organisateurs
            </Link>
          </li>
          <li>
            <Link
              to="/organisation/editions"
              className="text-link underline underline-offset-4"
            >
              Éditions
            </Link>
          </li>
          <li>
            <Link
              to="/organisation/texts"
              className="text-link underline underline-offset-4"
            >
              Textes
            </Link>
          </li>
        </ul>
      </nav>
      <Outlet />
    </div>
  );
}
