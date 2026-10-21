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

/**
 * Editions list (GET /api/admin/editions, F05-T14).
 * Reserved to organizers: the /organisation route guard rejects other
 * profiles before this query ever runs.
 */
export const editionsQueryOptions = {
  queryKey: ["admin", "editions"],
  queryFn: async () => {
    const { data, error } = await apiClient.GET("/api/admin/editions");

    if (error) {
      throw error;
    }

    return data;
  },
} as const;

export function useEditions() {
  return useQuery(editionsQueryOptions);
}

export const editionQueryOptions = (id: string) =>
  ({
    queryKey: ["admin", "editions", id],
    queryFn: async () => {
      const { data, error } = await apiClient.GET("/api/admin/editions/{id}", {
        params: { path: { id } },
      });

      if (error) {
        throw error;
      }

      return data;
    },
  }) as const;

export function useEdition(id: string) {
  return useQuery(editionQueryOptions(id));
}

/** Form values of the edition form (F05-T20): instants are ISO 8601 with an explicit offset. */
export interface EditionFormValues {
  title: string;
  presentation: string;
  startsAt: string;
  opensAt: string;
}

/**
 * Creates an edition (POST /api/admin/editions, F05-T15).
 * The editions list is invalidated on success.
 */
export function useCreateEdition() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (values: EditionFormValues) => {
      const { data, error } = await apiClient.POST("/api/admin/editions", {
        body: values,
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
        queryKey: editionsQueryOptions.queryKey,
      });
    },
  });
}

/**
 * Updates an edition (PATCH /api/admin/editions/{id}, F05-T15): the version
 * read on the form is sent back — the API answers 409 on optimistic-lock
 * conflict.
 */
export function useUpdateEdition() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({
      id,
      version,
      ...values
    }: EditionFormValues & { id: string; version: number }) => {
      const { data, error } = await apiClient.PATCH(
        "/api/admin/editions/{id}",
        {
          params: { path: { id } },
          body: { ...values, version },
          headers: { "Content-Type": "application/merge-patch+json" },
        },
      );

      if (error) {
        throw error;
      }

      return data;
    },
    onSuccess: (_data, variables) => {
      void queryClient.invalidateQueries({
        queryKey: editionsQueryOptions.queryKey,
      });
      void queryClient.invalidateQueries({
        queryKey: editionQueryOptions(variables.id).queryKey,
      });
    },
  });
}

/**
 * Schedules an edition (POST /api/admin/editions/{id}/schedule, F05-T16):
 * draft → scheduled.
 */
export function useScheduleEdition() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: string) => {
      const { data, error } = await apiClient.POST(
        "/api/admin/editions/{id}/schedule",
        {
          params: { path: { id } },
          body: {},
          headers: { "Content-Type": "application/ld+json" },
        },
      );

      if (error) {
        throw error;
      }

      return data;
    },
    onSuccess: (_data, id) => {
      void queryClient.invalidateQueries({
        queryKey: editionsQueryOptions.queryKey,
      });
      void queryClient.invalidateQueries({
        queryKey: editionQueryOptions(id).queryKey,
      });
    },
  });
}

/**
 * Unschedules an edition (POST /api/admin/editions/{id}/unschedule,
 * F05-T16): back to draft.
 */
export function useUnscheduleEdition() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: string) => {
      const { data, error } = await apiClient.POST(
        "/api/admin/editions/{id}/unschedule",
        {
          params: { path: { id } },
          body: {},
          headers: { "Content-Type": "application/ld+json" },
        },
      );

      if (error) {
        throw error;
      }

      return data;
    },
    onSuccess: (_data, id) => {
      void queryClient.invalidateQueries({
        queryKey: editionsQueryOptions.queryKey,
      });
      void queryClient.invalidateQueries({
        queryKey: editionQueryOptions(id).queryKey,
      });
    },
  });
}
