import {
  createRoute,
  createRouter,
  lazyRouteComponent,
  redirect,
} from "@tanstack/react-router";
import { meQueryOptions } from "./api/hooks";
import { queryClient } from "./api/queryClient";
import { Root } from "./components/Root";
import { ConfidentialitePage } from "./pages/ConfidentialitePage";
import { DemoPage } from "./pages/DemoPage";
import { ErrorPage } from "./pages/ErrorPage";
import { LoginPage } from "./pages/LoginPage";
import { MySpacePage } from "./pages/MySpacePage";
import { ReceiveMyLinkPage } from "./pages/ReceiveMyLinkPage";

const receiveMyLinkRoute = createRoute({
  getParentRoute: () => Root,
  path: "/receive-my-link",
  component: ReceiveMyLinkPage,
});

// Provisional content until F25 (the footer links here, F01-T07)
const confidentialiteRoute = createRoute({
  getParentRoute: () => Root,
  path: "/confidentialite",
  component: ConfidentialitePage,
});

// Component demonstration page (F01-T05)
const demoRoute = createRoute({
  getParentRoute: () => Root,
  path: "/demo",
  component: DemoPage,
});

const loginRoute = createRoute({
  getParentRoute: () => Root,
  path: "/login",
  component: LoginPage,
});

// Route guard (F02-T19): a visitor (401 on /api/me) is redirected
// to /receive-my-link before the page mounts.
const mySpaceRoute = createRoute({
  getParentRoute: () => Root,
  path: "/my-space",
  beforeLoad: async ({ context }) => {
    try {
      await context.queryClient.ensureQueryData(meQueryOptions);
    } catch {
      throw redirect({ to: "/receive-my-link" });
    }
  },
  component: MySpacePage,
});

// Back-office section (F04-T04): lazy-loaded so the back-office bundle is
// never fetched on public pages. Reserved to organizers: visitors (401 on
// /api/me) are redirected to /receive-my-link, authenticated priants to
// /my-space.
const organisationRoute = createRoute({
  getParentRoute: () => Root,
  path: "/organisation",
  beforeLoad: async ({ context }) => {
    const me = await context.queryClient
      .ensureQueryData(meQueryOptions)
      .catch(() => null);

    if (null === me) {
      throw redirect({ to: "/receive-my-link" });
    }

    if (!me.isOrganizer) {
      throw redirect({ to: "/my-space" });
    }
  },
  component: lazyRouteComponent(
    () => import("./components/OrganisationLayout"),
    "OrganisationLayout",
  ),
});

const organisationIndexRoute = createRoute({
  getParentRoute: () => organisationRoute,
  path: "/",
  component: lazyRouteComponent(
    () => import("./pages/OrganisationPage"),
    "OrganisationPage",
  ),
});

// Define the routes
export const routeTree = Root.addChildren([
  receiveMyLinkRoute,
  confidentialiteRoute,
  demoRoute,
  loginRoute,
  mySpaceRoute,
  organisationRoute.addChildren([organisationIndexRoute]),
]);

export const router = createRouter({
  routeTree,
  context: { queryClient },
  // Global error page (F01-T11): as a default error component, it renders
  // inside the public layout when any route fails (a root-route
  // errorComponent would replace the layout).
  defaultErrorComponent: ErrorPage,
});

// Declare the types for TypeScript
declare module "@tanstack/react-router" {
  interface Register {
    router: typeof router;
  }
}
