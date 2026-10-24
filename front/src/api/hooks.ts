import {
  keepPreviousData,
  useInfiniteQuery,
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";
import type { EditionView, SlotText } from "../lib/editions";
import { withSlotText } from "../lib/editions";
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
 * Current public edition (GET /api/editions/current, F08-T02).
 * A 404 means "no edition open yet": a normal state for the home page
 * (arbitrage Q18), mapped to null instead of an error.
 */
export const currentEditionQueryOptions = {
  queryKey: ["public", "editions", "current"],
  queryFn: async () => {
    const { data, error, response } = await apiClient.GET(
      "/api/editions/current",
    );

    if (error) {
      if (response.status === 404) {
        return null;
      }

      throw error;
    }

    return data;
  },
  // A 404 is a normal state: never retry it
  retry: false,
} as const;

export function useCurrentEdition() {
  return useQuery(currentEditionQueryOptions);
}

/**
 * Public planning slots (GET /api/editions/{id}/slots, F08-T04).
 * Refreshed every 60 s and on window focus: the slot states (past / current)
 * follow the server clock (US-08.4).
 */
export const editionSlotsQueryOptions = (editionId: string) =>
  ({
    queryKey: ["public", "editions", editionId, "slots"],
    queryFn: async () => {
      const { data, error } = await apiClient.GET("/api/editions/{id}/slots", {
        params: { path: { id: editionId } },
      });

      if (error) {
        throw error;
      }

      return data;
    },
    refetchInterval: 60_000,
    refetchOnWindowFocus: true,
    retry: false,
  }) as const;

export function useEditionSlots(editionId: string) {
  return useQuery(editionSlotsQueryOptions(editionId));
}

/** Payload of POST /api/inscriptions (F09-T10), bounded by the OpenAPI contract. */
export interface RegisterInscriptionsPayload {
  slotIds: string[];
  displayName: string;
  email: string;
  consent: boolean;
  website: string;
}

/**
 * Registers inscriptions on slots (POST /api/inscriptions, F09-T10).
 * The public planning queries are invalidated on success so the grid shows
 * the updated registration counts (RG-PL-1).
 */
export function useRegisterInscriptions() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: RegisterInscriptionsPayload) => {
      const { data, error } = await apiClient.POST("/api/inscriptions", {
        body: payload,
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
        queryKey: ["public", "editions"],
      });
    },
  });
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

/**
 * Single edition (GET /api/admin/editions/{id}). A null id disables the query
 * (creation form): no request is ever sent for it.
 */
export function useEdition(id: string | null) {
  return useQuery({
    ...editionQueryOptions(id ?? ""),
    enabled: null !== id,
  });
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

/** Root key of the text library queries: every text mutation invalidates it. */
export const textsQueryKey = ["admin", "texts"] as const;

/**
 * Text library (GET /api/admin/texts, F06-T08): 20 texts per page, searched
 * on title and source by the API (accent-insensitive). Pages are loaded on
 * demand while the Hydra view announces a next one.
 */
export function useTexts(search: string) {
  return useInfiniteQuery({
    queryKey: [...textsQueryKey, "list", search],
    queryFn: async ({ pageParam }) => {
      const { data, error } = await apiClient.GET("/api/admin/texts", {
        params: {
          query: { page: pageParam, ...(search ? { q: search } : {}) },
        },
      });

      if (error) {
        throw error;
      }

      return data;
    },
    initialPageParam: 1,
    getNextPageParam: (lastPage, _pages, lastPageParam) =>
      lastPage?.view?.next ? lastPageParam + 1 : undefined,
    // The previous results stay on screen while a new search runs
    placeholderData: keepPreviousData,
  });
}

export const textQueryOptions = (id: string) =>
  ({
    queryKey: [...textsQueryKey, "item", id],
    queryFn: async () => {
      const { data, error } = await apiClient.GET("/api/admin/texts/{id}", {
        params: { path: { id } },
      });

      if (error) {
        throw error;
      }

      return data;
    },
  }) as const;

/**
 * Single text with its body and hints (GET /api/admin/texts/{id}). A null id
 * disables the query (creation form).
 */
export function useText(id: string | null) {
  return useQuery({
    ...textQueryOptions(id ?? ""),
    enabled: null !== id,
  });
}

/** Form values of the text form (F06-T13): the body is HTML from the editor. */
export interface TextFormValues {
  title: string;
  source: string;
  body: string;
  hints: string;
}

/**
 * Creates a text (POST /api/admin/texts, F06-T09). The library is
 * invalidated on success.
 */
export function useCreateText() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (values: TextFormValues) => {
      const { data, error } = await apiClient.POST("/api/admin/texts", {
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
      void queryClient.invalidateQueries({ queryKey: textsQueryKey });
    },
  });
}

/**
 * Updates a text (PATCH /api/admin/texts/{id}, F06-T09): the correction
 * applies to every slot carrying it (RG-TX-3).
 */
export function useUpdateText() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, ...values }: TextFormValues & { id: string }) => {
      const { data, error } = await apiClient.PATCH("/api/admin/texts/{id}", {
        params: { path: { id } },
        body: values,
        headers: { "Content-Type": "application/merge-patch+json" },
      });

      if (error) {
        throw error;
      }

      return data;
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: textsQueryKey });
    },
  });
}

/**
 * Deletes a text (DELETE /api/admin/texts/{id}, F06-T09): refused with 409
 * when the text belongs to a scheduled edition (RG-TX-3).
 */
export function useDeleteText() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: string) => {
      const { error } = await apiClient.DELETE("/api/admin/texts/{id}", {
        params: { path: { id } },
      });

      if (error) {
        throw error;
      }
    },
    onSuccess: (_data, id) => {
      // The deleted text must not be refetched by the form still mounted
      queryClient.removeQueries({ queryKey: textQueryOptions(id).queryKey });
      void queryClient.invalidateQueries({ queryKey: textsQueryKey });
    },
  });
}

/**
 * Puts a text on a slot, or withdraws it with null (PUT
 * /api/admin/slots/{id}/text, F07-T07). The edition is updated at once
 * (optimistic) and put back as it was if the API refuses — a 409 when the
 * edition changed meanwhile.
 */
export function useAssignText(editionId: string) {
  const queryClient = useQueryClient();
  const queryKey = editionQueryOptions(editionId).queryKey;

  return useMutation({
    mutationFn: async ({
      slotId,
      text,
    }: {
      slotId: string;
      text: SlotText | null;
    }) => {
      const { error } = await apiClient.PUT("/api/admin/slots/{id}/text", {
        params: { path: { id: slotId } },
        body: { textId: text?.id ?? null },
        headers: { "Content-Type": "application/ld+json" },
      });

      if (error) {
        throw error;
      }
    },
    onMutate: async ({ slotId, text }) => {
      await queryClient.cancelQueries({ queryKey });
      const previous = queryClient.getQueryData<EditionView>(queryKey);

      if (previous) {
        queryClient.setQueryData<EditionView>(
          queryKey,
          withSlotText(previous, slotId, text),
        );
      }

      return { previous };
    },
    onError: (_error, _variables, context) => {
      if (context?.previous) {
        queryClient.setQueryData<EditionView>(queryKey, context.previous);
      }
    },
    onSettled: () => {
      void queryClient.invalidateQueries({ queryKey });
    },
  });
}

/**
 * Distributes an ordered list of texts from a start slot (POST
 * /api/admin/editions/{id}/distribute-texts, F07-T09): the API truncates the
 * list at slot 48.
 */
export function useDistributeTexts(editionId: string) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: { textIds: string[]; fromRank: number }) => {
      const { error } = await apiClient.POST(
        "/api/admin/editions/{id}/distribute-texts",
        {
          params: { path: { id: editionId } },
          body: payload,
          headers: { "Content-Type": "application/ld+json" },
        },
      );

      if (error) {
        throw error;
      }
    },
    onSuccess: () => {
      void queryClient.invalidateQueries({
        queryKey: editionQueryOptions(editionId).queryKey,
      });
      void queryClient.invalidateQueries({ queryKey: textsQueryKey });
    },
  });
}
