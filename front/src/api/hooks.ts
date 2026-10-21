import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
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

/**
 * Organizers list (GET /api/admin/organizers, F04-T05).
 * Reserved to organizers: the /organisation route guard rejects other
 * profiles before this query ever runs.
 */
export const organizersQueryOptions = {
  queryKey: ["admin", "organizers"],
  queryFn: async () => {
    const { data, error } = await apiClient.GET("/api/admin/organizers");

    if (error) {
      throw error;
    }

    return data;
  },
} as const;

export function useOrganizers() {
  return useQuery(organizersQueryOptions);
}

/**
 * Adds an organizer by email (POST /api/admin/organizers, F04-T05).
 * The organizers list is invalidated on success.
 */
export function useAddOrganizer() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (email: string) => {
      const { data, error } = await apiClient.POST("/api/admin/organizers", {
        body: { email },
        // API Platform only accepts JSON-LD bodies on this resource
        headers: { "Content-Type": "application/ld+json" },
      });

      if (error) {
        throw error;
      }

      return data;
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({
        queryKey: organizersQueryOptions.queryKey,
      });
    },
  });
}

/**
 * Removes an organizer (DELETE /api/admin/organizers/{id}, F04-T05).
 * The organizers list is invalidated on success.
 */
export function useRemoveOrganizer() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: string) => {
      const { error } = await apiClient.DELETE("/api/admin/organizers/{id}", {
        params: { path: { id } },
      });

      if (error) {
        throw error;
      }
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({
        queryKey: organizersQueryOptions.queryKey,
      });
    },
  });
}
