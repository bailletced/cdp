import { QueryClient } from '@tanstack/react-query';
import { createApiClient } from './client';

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

// Export du client API à utiliser avec openapi-react-query
export const apiClient = createApiClient();

// Types pour les hooks générés
export type ApiClientType = ReturnType<typeof createApiClient>;
