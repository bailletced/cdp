import { useEffect } from "react";

/**
 * The warm-ivory treatment of the public-facing pages (defined on
 * body.theme-dawn in index.css): the pages that welcome people — the event
 * landing and « Mon espace » — swap the dark vigil theme for a light one.
 * The class is added on mount and removed on unmount, so the rest of the
 * application keeps the dark theme.
 */
export const DAWN_THEME_CLASS = "theme-dawn";

export function useDawnTheme(): void {
  useEffect(() => {
    document.body.classList.add(DAWN_THEME_CLASS);

    return () => {
      document.body.classList.remove(DAWN_THEME_CLASS);
    };
  }, []);
}
