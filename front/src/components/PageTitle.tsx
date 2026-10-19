import { useEffect, useRef } from "react";
import { SITE_NAME } from "../config/site";

/**
 * Page title (F01-T08): renders the page h1, sets the document title, moves
 * focus to the heading and announces the new page on route change (US-01.3).
 */
export function PageTitle({ children }: { children: string }) {
  const headingRef = useRef<HTMLHeadingElement>(null);

  useEffect(() => {
    document.title = `${children} — ${SITE_NAME}`;
    // The heading is the focus target on navigation: tabIndex -1 only, it is
    // never a tab stop. The outline is suppressed — the live region below
    // announces the change to screen readers.
    headingRef.current?.focus();
  }, [children]);

  return (
    <>
      <h1 ref={headingRef} tabIndex={-1} className="outline-none">
        {children}
      </h1>
      <p aria-live="polite" className="sr-only">
        {children}
      </p>
    </>
  );
}
