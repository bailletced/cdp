import { createRoute, createRouter } from "@tanstack/react-router";
import { Root } from "./components/Root";
import { ReceiveMyLinkPage } from "./pages/ReceiveMyLinkPage";

const receiveMyLinkRoute = createRoute({
  getParentRoute: () => Root,
  path: "/receive-my-link",
  component: ReceiveMyLinkPage,
});

// Define the routes
export const routeTree = Root.addChildren([receiveMyLinkRoute]);

export const router = createRouter({
  routeTree,
});

// Declare the types for TypeScript
declare module "@tanstack/react-router" {
  interface Register {
    router: typeof router;
  }
}
