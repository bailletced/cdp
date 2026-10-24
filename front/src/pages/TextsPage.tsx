import { Button, Label, SearchField } from "@heroui/react";
import { useEffect, useState } from "react";
import { useTexts } from "../api/hooks";
import { EmptyState } from "../components/EmptyState";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";

// Wait for a pause in the typing before querying the API
const SEARCH_DELAY_MS = 300;

function attributionLabel(count: number | null | undefined): string {
  if (!count) {
    return "Pas encore attribué";
  }

  return count === 1 ? "Attribué à 1 créneau" : `Attribué à ${count} créneaux`;
}

/**
 * /organisation/texts (F06-T12, US-06.4): the text library — title, source
 * and number of slots carrying each text, searchable on title and source.
 */
export function TextsPage() {
  const [input, setInput] = useState("");
  const [search, setSearch] = useState("");

  useEffect(() => {
    const timeout = setTimeout(() => setSearch(input.trim()), SEARCH_DELAY_MS);

    return () => clearTimeout(timeout);
  }, [input]);

  const textsQuery = useTexts(search);
  const texts = textsQuery.data?.pages.flatMap((page) => page?.member ?? []);

  return (
    <div className="mx-auto w-full max-w-3xl">
      <PageTitle>Textes</PageTitle>
      <SearchField
        value={input}
        onChange={setInput}
        className="mt-6 w-full max-w-md"
      >
        <Label>Rechercher</Label>
        <SearchField.Group>
          <SearchField.SearchIcon />
          <SearchField.Input placeholder="Titre ou source, ex. Thérèse" />
          <SearchField.ClearButton />
        </SearchField.Group>
      </SearchField>
      {textsQuery.isPending ? (
        <LoadingState />
      ) : textsQuery.isError ? (
        <ErrorState
          message="La bibliothèque n'a pas pu être chargée."
          onRetry={() => void textsQuery.refetch()}
        />
      ) : !texts || texts.length === 0 ? (
        <EmptyState
          message={
            search
              ? `Aucun texte ne correspond à « ${search} ».`
              : "Aucun texte pour le moment."
          }
        />
      ) : (
        <>
          <ul className="flex flex-col gap-4 py-6">
            {texts.map((text) => (
              <li
                key={text.id ?? undefined}
                className="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 rounded-lg border border-border px-4 py-3"
              >
                <div>
                  <p className="font-medium">{text.title}</p>
                  <p className="text-muted">{text.source}</p>
                </div>
                <p className="text-muted">
                  {attributionLabel(text.attributionCount)}
                </p>
              </li>
            ))}
          </ul>
          {textsQuery.hasNextPage ? (
            <Button
              variant="secondary"
              isDisabled={textsQuery.isFetchingNextPage}
              onPress={() => void textsQuery.fetchNextPage()}
            >
              {textsQuery.isFetchingNextPage
                ? "Chargement…"
                : "Afficher plus de textes"}
            </Button>
          ) : null}
        </>
      )}
    </div>
  );
}
