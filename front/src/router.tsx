import { createRoute, createRouter } from "@tanstack/react-router";
import { Root } from "./components/Root";
import { LoginPage } from "./pages/LoginPage";
import { ReceiveMyLinkPage } from "./pages/ReceiveMyLinkPage";

const receiveMyLinkRoute = createRoute({
  getParentRoute: () => Root,
  path: "/receive-my-link",
  component: ReceiveMyLinkPage,
});

const loginRoute = createRoute({
  getParentRoute: () => Root,
  path: "/login",
  component: LoginPage,
});

// Placeholder target of the login redirect (F02-T18);
// replaced by the guarded page in F02-T19.
const mySpaceRoute = createRoute({
  getParentRoute: () => Root,
  path: "/my-space",
  component: () => (
    <main>
      <h2>Mon espace</h2>
    </main>
  ),
});

// Define the routes
export const routeTree = Root.addChildren([
  receiveMyLinkRoute,
  loginRoute,
  mySpaceRoute,
]);

export const router = createRouter({
  routeTree,
});

// Declare the types for TypeScript
declare module "@tanstack/react-router" {
  interface Register {
    router: typeof router;
  }
}
