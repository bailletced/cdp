import { useQuery } from "@tanstack/react-query";
import { apiClient } from "./client";

/**
 * Current account (GET /api/me, F02-T16).
 * A 401 (visitor) surfaces as an error state, without retry.
 */
export function useMe() {
  return useQuery({
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
  });
}
