import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import {
  RouterProvider,
  createMemoryHistory,
  createRouter,
} from "@tanstack/react-router";
import { render } from "@testing-library/react";
import { Notifications } from "../components/Notifications";
import { ErrorPage } from "../pages/ErrorPage";
import { routeTree } from "../router";

// Renders the app at the given path with an in-memory history, with the same
// shell as main.tsx (router and toast region)
export function renderApp(initialPath: string) {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  });
  const testRouter = createRouter({
    routeTree,
    history: createMemoryHistory({ initialEntries: [initialPath] }),
    context: { queryClient },
    // Same global error page as the production router (F01-T11)
    defaultErrorComponent: ErrorPage,
  });

  return render(
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={testRouter} />
      <Notifications />
    </QueryClientProvider>,
  );
}
