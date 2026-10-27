import "@testing-library/jest-dom/vitest";
import { configure } from "@testing-library/react";
import { setupServer } from "msw/node";
import { afterAll, afterEach, beforeAll, vi } from "vitest";
import { handlers } from "./handlers";

// The back-office pages are lazy-loaded routes: under a full parallel run,
// importing one can take longer than the default 1 s of findBy/waitFor
configure({ asyncUtilTimeout: 3000 });

// jsdom does not implement scrollTo (used by TanStack Router on navigation)
window.scrollTo = () => undefined;

// Nor scrollIntoView (the banner of the empty slots scrolls to a card, F13)
Element.prototype.scrollIntoView = () => undefined;

// jsdom does not implement matchMedia (used by HeroUI ToastProvider)
if (!window.matchMedia) {
  window.matchMedia = (query: string): MediaQueryList => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => undefined,
    removeListener: () => undefined,
    addEventListener: () => undefined,
    removeEventListener: () => undefined,
    dispatchEvent: () => false,
  });
}

// jsdom does not implement ResizeObserver (used by HeroUI ToastProvider)
if (!window.ResizeObserver) {
  window.ResizeObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
  } as unknown as typeof ResizeObserver;
}

// The ALTCHA widget (F15) solves its challenge in workers jsdom has not:
// the tests drive the <altcha-widget> element through its events instead
vi.mock("altcha", () => ({}));
vi.mock("altcha/i18n/fr-fr", () => ({}));

// Setup MSW server
export const server = setupServer(...handlers);

beforeAll(() => {
  server.listen({ onUnhandledRequest: "error" });
});

afterEach(() => {
  server.resetHandlers();
});

afterAll(() => {
  server.close();
});
