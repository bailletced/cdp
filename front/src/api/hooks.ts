import { useQuery } from '@tanstack/react-query';
import { apiClient } from './client';
import type { paths } from './schema';

// Hook de démonstration pour le endpoint /api/test
export function useTestQuery() {
  return useQuery({
    queryKey: ['test'],
    queryFn: async () => {
      const response = await apiClient.get('/api/test');
      return response as paths['/api/test']['get']['responses']['200']['content']['application/json'];
    },
  });
}

// Hook générique pour GET
export function useApiQuery<T>(url: string, queryKey: string[]) {
  return useQuery<T>({
    queryKey,
    queryFn: async () => {
      const response = await apiClient.get(url);
      return response.json() as T;
    },
  });
}
