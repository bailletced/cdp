import { createRoute, createRouter, redirect } from "@tanstack/react-router";
import { meQueryOptions } from "./api/hooks";
import { queryClient } from "./api/queryClient";
import { Root } from "./components/Root";
import { DemoPage } from "./pages/DemoPage";
import { LoginPage } from "./pages/LoginPage";
import { MySpacePage } from "./pages/MySpacePage";
import { ReceiveMyLinkPage } from "./pages/ReceiveMyLinkPage";

const receiveMyLinkRoute = createRoute({
  getParentRoute: () => Root,
  path: "/receive-my-link",
  component: ReceiveMyLinkPage,
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

// Define the routes
export const routeTree = Root.addChildren([
  receiveMyLinkRoute,
  demoRoute,
  loginRoute,
  mySpaceRoute,
]);

export const router = createRouter({
  routeTree,
  context: { queryClient },
});

// Declare the types for TypeScript
declare module "@tanstack/react-router" {
  interface Register {
    router: typeof router;
  }
}
