import {
  Link,
  Outlet,
  createRootRouteWithContext,
} from "@tanstack/react-router";
import { useMe } from "../api/hooks";
import type { AppRouterContext } from "../routerContext";

export const Root = createRootRouteWithContext<AppRouterContext>()({
  component: () => {
    const { data, isLoading, isError } = useMe();

    return (
      <div className="app">
        <header>
          <h1>Chaîne de prière - CDP</h1>
          <nav>
            {isLoading ? null : !isError && data ? (
              <Link to="/my-space">Mon espace</Link>
            ) : (
              <Link to="/receive-my-link">Recevoir mon lien</Link>
            )}
          </nav>
        </header>
        <Outlet />
      </div>
    );
  },
});
