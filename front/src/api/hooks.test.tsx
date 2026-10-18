import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { renderHook, waitFor } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import type { ReactNode } from "react";
import { describe, expect, it } from "vitest";
import { server } from "../test/setup";
import { useMe } from "./hooks";

// A fresh QueryClient per test so the ['me'] cache never leaks between tests
function createWrapper() {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  });

  return function Wrapper({ children }: { children: ReactNode }) {
    return (
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );
  };
}

describe("useMe", () => {
  it("returns the current account (GET /api/me)", async () => {
    const { result } = renderHook(() => useMe(), { wrapper: createWrapper() });

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true);
    });

    expect(result.current.data?.email).toBe("thomas@example.com");
    expect(result.current.data?.isOrganizer).toBe(false);
  });

  it("surfaces a 401 as an error state (visitor)", async () => {
    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return new HttpResponse(null, { status: 401 });
      }),
    );

    const { result } = renderHook(() => useMe(), { wrapper: createWrapper() });

    await waitFor(() => {
      expect(result.current.isError).toBe(true);
    });
  });
});
