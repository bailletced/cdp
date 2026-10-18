import type { QueryClient } from "@tanstack/react-query";

// Context provided to createRouter and available in route hooks (beforeLoad, ...)
export interface AppRouterContext {
  queryClient: QueryClient;
}
