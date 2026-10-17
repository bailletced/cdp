import { describe, expect, it, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { server } from '../test/setup';
import { useTestQuery } from './hooks';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from './queryClient';

// Wrapper pour utiliser les hooks avec Testing Library
function wrapper({ children }: { children: React.ReactNode }) {
  return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
}

describe('API Hooks', () => {
  beforeEach(() => {
    // Reset MSW handlers avant chaque test
    server.resetHandlers();
  });

  it('useTestQuery fetches data from API using MSW', async () => {
    const { result } = renderHook(() => useTestQuery(), { wrapper });

    // Attendre que les données soient chargées
    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true);
    });

    expect(result.current.data).toEqual({ message: 'Test successful' });
  });

  it('useTestQuery handles errors', async () => {
    // Configurer MSW pour retourner une erreur
    server.use(
      http.get('http://localhost:8080/api/test', () => {
        return new HttpResponse(null, { status: 500 });
      }),
    );

    const { result } = renderHook(() => useTestQuery(), { wrapper });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });
});

// Import HttpResponse from msw
import { http, HttpResponse } from 'msw';
