import { Outlet, createRootRoute } from "@tanstack/react-router";

export const Root = createRootRoute({
  component: () => (
    <div className="app">
      <h1>Chaîne de prière - CDP</h1>
      <Outlet />
    </div>
  ),
});
