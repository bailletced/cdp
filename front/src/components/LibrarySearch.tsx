import { Button, Label, SearchField } from "@heroui/react";
import { useState } from "react";
import { useTexts } from "../api/hooks";
import type { SlotText } from "../lib/editions";
import { useDebouncedValue } from "../lib/useDebouncedValue";

/**
 * Search in the text library (title, source), shared by the slot text
 * selector (F07-T07) and « Répartir dans l'ordre » (F07-T08): the typed
 * input, the debounced search and the texts loaded so far.
 */
export function useLibrarySearch() {
  const [input, setInput] = useState("");
  const search = useDebouncedValue(input.trim());
  const textsQuery = useTexts(search);
  const texts: SlotText[] = (
    textsQuery.data?.pages.flatMap((page) => page?.member ?? []) ?? []
  ).flatMap((text) =>
    text.id && text.title && text.source
      ? [{ id: text.id, title: text.title, source: text.source }]
      : [],
  );

  return { input, setInput, search, textsQuery, texts };
}

export function LibrarySearchField({
  value,
  onChange,
  autoFocus = false,
}: {
  value: string;
  onChange: (value: string) => void;
  autoFocus?: boolean;
}) {
  return (
    <SearchField value={value} onChange={onChange} autoFocus={autoFocus}>
      <Label>Rechercher un texte</Label>
      <SearchField.Group>
        <SearchField.SearchIcon />
        <SearchField.Input placeholder="Titre ou source, ex. Psaume" />
        <SearchField.ClearButton />
      </SearchField.Group>
    </SearchField>
  );
}

/** « Afficher plus de textes » while the library has further pages. */
export function MoreTextsButton({
  textsQuery,
}: {
  textsQuery: ReturnType<typeof useLibrarySearch>["textsQuery"];
}) {
  if (!textsQuery.hasNextPage) {
    return null;
  }

  return (
    <Button
      variant="secondary"
      isDisabled={textsQuery.isFetchingNextPage}
      onPress={() => void textsQuery.fetchNextPage()}
    >
      {textsQuery.isFetchingNextPage
        ? "Chargement…"
        : "Afficher plus de textes"}
    </Button>
  );
}
