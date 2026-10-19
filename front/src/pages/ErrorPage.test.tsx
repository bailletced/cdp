import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import {
  RouterProvider,
  createMemoryHistory,
  createRoute,
  createRouter,
} from "@tanstack/react-router";
import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { Root } from "../components/Root";
import { ErrorPage } from "./ErrorPage";

// Renders the error page inside the real public layout: a minimal route
// tree mounting ErrorPage at /erreur-test.
function renderErrorPage(reset: () => void) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  const testRoute = createRoute({
    getParentRoute: () => Root,
    path: "/erreur-test",
    component: () => <ErrorPage error={new Error("boom")} reset={reset} />,
  });
  const router = createRouter({
    routeTree: Root.addChildren([testRoute]),
    history: createMemoryHistory({ initialEntries: ["/erreur-test"] }),
    context: { queryClient },
  });
  return render(
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
    </QueryClientProvider>,
  );
}

describe("ErrorPage (F01-T11)", () => {
  it("announces the error (role=alert) inside the public layout", async () => {
    renderErrorPage(() => {});

    const alert = await screen.findByRole("alert");
    expect(alert).toHaveTextContent(
      "Une erreur est survenue. Veuillez réessayer.",
    );
    expect(
      await screen.findByRole("heading", {
        name: "Une erreur est survenue",
        level: 1,
      }),
    ).toBeInTheDocument();
    expect(screen.getByRole("banner")).toBeInTheDocument();
    expect(screen.getByRole("contentinfo")).toBeInTheDocument();
  });

  it("offers a retry action wired to reset", async () => {
    const reset = vi.fn();
    renderErrorPage(reset);

    fireEvent.click(await screen.findByRole("button", { name: "Réessayer" }));

    expect(reset).toHaveBeenCalledTimes(1);
  });
});
