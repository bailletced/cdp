import createClient from 'openapi-fetch';
import type { paths } from './schema';

// Crée le client OpenAPI avec configuration par défaut
const apiClient = createClient<paths>({ 
  baseUrl: '/api',
  credentials: 'same-origin',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Hook pour lire X-Server-Time depuis les réponses
export function useServerTime(): string | null {
  // En production, lire l'en-tête X-Server-Time
  // Pour le test, retourner null ou une valeur par défaut
  return null;
}

// Client configuré avec gestion des erreurs
export function createApiClient() {
  return {
    ...apiClient,
    // Surcharger la méthode fetch pour ajouter X-Server-Time
    async fetch<T>(url: string, options?: RequestInit): Promise<T> {
      const response = await apiClient.fetch(url, options);
      
      // Lire l'en-tête X-Server-Time si disponible
      const serverTime = response.headers.get('X-Server-Time');
      if (serverTime) {
        // Stocker ou retourner le timestamp du serveur
        console.debug('X-Server-Time:', serverTime);
      }
      
      return response.json() as Promise<T>;
    },
  };
}

// Exporter le client
export const client = createApiClient();
export default apiClient;
