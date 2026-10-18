import { createRouter } from "@tanstack/react-router";
import { Root } from "./components/Root";

// Define the routes
export const routeTree = Root.addChildren([]);

export const router = createRouter({
  routeTree,
});

// Declare the types for TypeScript
declare module "@tanstack/react-router" {
  interface Register {
    router: typeof router;
  }
}
