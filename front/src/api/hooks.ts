import { useQuery } from "@tanstack/react-query";
import { apiClient } from "./client";

/**
 * Current account (GET /api/me, F02-T16).
 * A 401 (visitor) surfaces as an error state, without retry.
 */
export const meQueryOptions = {
  queryKey: ["me"],
  queryFn: async () => {
    const { data, error } = await apiClient.GET("/api/me");

    if (error) {
      throw error;
    }

    return data;
  },
  // A 401 means "visitor": a normal state, not a transient failure
  retry: false,
} as const;

export function useMe() {
  return useQuery(meQueryOptions);
}

export interface Viewer {
  email: string;
  isOrganizer: boolean;
}

/**
 * Current viewer (F02-T19): wraps useMe (GET /api/me).
 * isVisitor is true on 401: a normal state, not a transient failure.
 */
export function useViewer() {
  const query = useMe();

  return {
    ...query,
    viewer: query.data?.email
      ? {
          email: query.data.email,
          isOrganizer: query.data.isOrganizer ?? false,
        }
      : undefined,
    isVisitor: query.isError,
  };
}
