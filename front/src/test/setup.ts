import "@testing-library/jest-dom/vitest";
import { configure } from "@testing-library/react";
import { setupServer } from "msw/node";
import { afterAll, afterEach, beforeAll } from "vitest";
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
