import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { renderHook, waitFor } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import type { ReactNode } from "react";
import { describe, expect, it } from "vitest";
import { server } from "../test/setup";
import {
  editionsQueryOptions,
  useCreateEdition,
  useEditions,
  useMe,
  useViewer,
} from "./hooks";

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

    expect(result.current.data?.email).toBe("thomas@bailletced.org");
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

describe("useViewer", () => {
  it("exposes the viewer for an authenticated account", async () => {
    const { result } = renderHook(() => useViewer(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true);
    });

    expect(result.current.viewer).toEqual({
      email: "thomas@bailletced.org",
      isOrganizer: false,
    });
    expect(result.current.isVisitor).toBe(false);
  });

  it("flags a visitor on 401, without a viewer", async () => {
    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return new HttpResponse(null, { status: 401 });
      }),
    );

    const { result } = renderHook(() => useViewer(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => {
      expect(result.current.isVisitor).toBe(true);
    });

    expect(result.current.viewer).toBeUndefined();
  });
});

describe("useCreateEdition", () => {
  it("refreshes the editions list even when it was loading during the creation", async () => {
    const queryClient = new QueryClient({
      defaultOptions: { queries: { retry: false, staleTime: 5 * 60_000 } },
    });
    let listRequests = 0;
    let releaseFirstList = (): void => undefined;
    server.use(
      http.get("http://localhost:8080/api/admin/editions", async () => {
        listRequests += 1;
        if (listRequests === 1) {
          // The first list is slow: it is still loading when the edition is created
          await new Promise<void>((resolve) => {
            releaseFirstList = resolve;
          });
        }

        return HttpResponse.json({
          member: listRequests === 1 ? [] : [{ id: "new", title: "Nouvelle" }],
          totalItems: listRequests === 1 ? 0 : 1,
        });
      }),
      http.post("http://localhost:8080/api/admin/editions", () =>
        HttpResponse.json({ id: "new", title: "Nouvelle" }, { status: 201 }),
      ),
    );
    const wrapper = ({ children }: { children: ReactNode }) => (
      <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );

    void queryClient.prefetchQuery(editionsQueryOptions);
    await waitFor(() => expect(listRequests).toBe(1));
    const { result } = renderHook(() => useCreateEdition(), { wrapper });
    await result.current.mutateAsync({
      title: "Nouvelle",
      presentation: "",
      startsAt: "2099-01-01T20:00:00+01:00",
      opensAt: "2098-12-25T20:00:00+01:00",
    });
    releaseFirstList();

    // Back on the list page: the late answer of the first load must not
    // hide the new edition
    const list = renderHook(() => useEditions(), { wrapper });
    await waitFor(() =>
      expect(list.result.current.data).toEqual({
        member: [{ id: "new", title: "Nouvelle" }],
        totalItems: 1,
      }),
    );
  });
});
