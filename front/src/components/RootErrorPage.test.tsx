import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import {
  RouterProvider,
  createMemoryHistory,
  createRoute,
  createRouter,
} from "@tanstack/react-router";
import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { ErrorPage } from "../pages/ErrorPage";
import { routeTree } from "../router";
import { Root } from "./Root";

// A route that always throws, to exercise the root errorComponent.
// addChildren replaces the children in this test file's module registry
// only (vitest isolates test files): the shared routeTree is untouched
// for the other test files.
const boomRoute = createRoute({
  getParentRoute: () => Root,
  path: "/boom",
  component: () => {
    throw new Error("boom");
  },
});
Root.addChildren([boomRoute]);

describe("global error page (F01-T11)", () => {
  it("renders the error page inside the layout when a route throws", async () => {
    // React logs the caught error: silence it for a clean test output
    const consoleError = vi
      .spyOn(console, "error")
      .mockImplementation(() => {});

    const queryClient = new QueryClient({
      defaultOptions: { queries: { retry: false } },
    });
    const router = createRouter({
      routeTree,
      history: createMemoryHistory({ initialEntries: ["/boom"] }),
      context: { queryClient },
      // Same global error page as the production router (F01-T11)
      defaultErrorComponent: ErrorPage,
    });
    render(
      <QueryClientProvider client={queryClient}>
        <RouterProvider router={router} />
      </QueryClientProvider>,
    );

    // The root errorComponent renders inside the public layout
    expect(
      await screen.findByRole("heading", {
        name: "Une erreur est survenue",
        level: 1,
      }),
    ).toBeInTheDocument();
    expect(screen.getByRole("alert")).toBeInTheDocument();
    expect(screen.getByRole("banner")).toBeInTheDocument();
    expect(screen.getByRole("contentinfo")).toBeInTheDocument();

    consoleError.mockRestore();
  });
});
