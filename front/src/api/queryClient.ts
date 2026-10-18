import { QueryClient } from "@tanstack/react-query";
import { apiClient } from "./client";

// Créer le client de requêtes avec configuration par défaut
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      retry: 3,
      refetchOnWindowFocus: false,
    },
    mutations: {
      retry: 1,
    },
  },
});

export { apiClient };
export type ApiClientType = typeof apiClient;
