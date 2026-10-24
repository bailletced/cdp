import { Button } from "@heroui/react";
import { Link, useParams } from "@tanstack/react-router";
import { useRef, useState } from "react";
import {
  Button as AriaButton,
  GridList,
  GridListItem,
  useDragAndDrop,
} from "react-aria-components";
import { useEdition } from "../api/hooks";
import { EmptyState } from "../components/EmptyState";
import { ErrorState } from "../components/ErrorState";
import {
  LibrarySearchField,
  MoreTextsButton,
  useLibrarySearch,
} from "../components/LibrarySearch";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import { type SlotText, moveItems } from "../lib/editions";

/** A text of the ordered list; the key tells two copies of a text apart. */
interface ListedText {
  key: string;
  text: SlotText;
}

/**
 * The texts to distribute, in their order (F07-T08, US-07.2): drag and drop
 * (React Aria, keyboard included) and « Monter » / « Descendre » buttons, so
 * the whole list can be ordered without dragging.
 */
function OrderedTexts({
  items,
  onChange,
}: {
  items: ListedText[];
  onChange: (items: ListedText[]) => void;
}) {
  const { dragAndDropHooks } = useDragAndDrop({
    getItems: (keys) =>
      [...keys].map((key) => ({
        "text/plain":
          items.find((item) => item.key === key)?.text.title ?? String(key),
      })),
    onReorder: (event) =>
      onChange(
        moveItems(
          items,
          new Set([...event.keys].map(String)),
          String(event.target.key),
          event.target.dropPosition === "before" ? "before" : "after",
        ),
      ),
  });

  function move(index: number, offset: -1 | 1) {
    const next = [...items];
    [next[index], next[index + offset]] = [next[index + offset], next[index]];
    onChange(next);
  }

  return (
    <GridList
      aria-label="Textes à répartir"
      items={items}
      // The rows show their position: render them again when the order changes
      dependencies={[items]}
      dragAndDropHooks={dragAndDropHooks}
      renderEmptyState={() => (
        <p className="py-4 text-muted">
          Ajoutez des textes depuis la bibliothèque.
        </p>
      )}
      className="flex flex-col gap-2"
    >
      {(item) => {
        const index = items.indexOf(item);
        const { title, source } = item.text;

        return (
          <GridListItem
            id={item.key}
            textValue={title}
            className="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl border border-separator bg-surface px-3 py-2 outline-none data-[dragging]:opacity-50 data-[focus-visible]:border-accent data-[drop-target]:border-accent"
          >
            <AriaButton
              slot="drag"
              aria-label={`Déplacer « ${title} »`}
              className="min-h-11 min-w-11 cursor-grab rounded-lg text-muted"
            >
              ⠿
            </AriaButton>
            <span className="w-8 font-display text-xl font-bold tabular-nums">
              {index + 1}
            </span>
            <div className="min-w-0 flex-1">
              <p data-text-title className="font-medium">
                {title}
              </p>
              <p className="text-muted">{source}</p>
            </div>
            <div className="flex flex-wrap gap-2">
              <Button
                variant="ghost"
                isDisabled={index === 0}
                onPress={() => move(index, -1)}
                aria-label={`Monter « ${title} »`}
              >
                Monter
              </Button>
              <Button
                variant="ghost"
                isDisabled={index === items.length - 1}
                onPress={() => move(index, 1)}
                aria-label={`Descendre « ${title} »`}
              >
                Descendre
              </Button>
              <Button
                variant="ghost"
                onPress={() =>
                  onChange(items.filter((other) => other !== item))
                }
                aria-label={`Retirer « ${title} » de la liste`}
              >
                Retirer
              </Button>
            </div>
          </GridListItem>
        );
      }}
    </GridList>
  );
}

/**
 * /organisation/editions/$editionId/distribute (F07-T08, US-07.2):
 * « Répartir dans l'ordre » — pick texts from the library and order them.
 */
export function DistributePage() {
  const { editionId } = useParams({
    from: "/organisation/editions/$editionId/distribute",
  });
  const editionQuery = useEdition(editionId);
  const { input, setInput, search, textsQuery, texts } = useLibrarySearch();
  const [items, setItems] = useState<ListedText[]>([]);
  const nextKey = useRef(0);

  function add(toAdd: SlotText[]) {
    setItems((current) => [
      ...current,
      ...toAdd.map((text) => {
        nextKey.current += 1;

        return { key: `text-${nextKey.current}`, text };
      }),
    ]);
  }

  const edition = editionQuery.data;

  if (editionQuery.isLoading || editionQuery.isError || !edition) {
    return (
      <div className="mx-auto w-full max-w-3xl">
        <PageTitle>Répartir dans l’ordre</PageTitle>
        {editionQuery.isLoading ? (
          <LoadingState />
        ) : (
          <ErrorState
            message="L'édition n'a pas pu être chargée."
            onRetry={() => void editionQuery.refetch()}
          />
        )}
      </div>
    );
  }

  return (
    <div className="mx-auto w-full max-w-3xl">
      <PageTitle>Répartir dans l’ordre</PageTitle>
      <p className="mt-2 text-xl">{edition.title}</p>
      <Link
        to="/organisation/editions/$editionId/texts"
        params={{ editionId }}
        className="mt-4 inline-block text-link underline underline-offset-4"
      >
        Retour à l’attribution
      </Link>

      <section aria-labelledby="library-title" className="mt-10">
        <h2 id="library-title" className="mb-4">
          Bibliothèque
        </h2>
        <LibrarySearchField value={input} onChange={setInput} />
        {textsQuery.isPending ? (
          <LoadingState />
        ) : textsQuery.isError ? (
          <ErrorState
            message="La bibliothèque n'a pas pu être chargée."
            onRetry={() => void textsQuery.refetch()}
          />
        ) : texts.length === 0 ? (
          <EmptyState
            message={
              search
                ? `Aucun texte ne correspond à « ${search} ».`
                : "La bibliothèque est vide."
            }
          />
        ) : (
          <>
            <ul className="mt-4 flex max-h-96 flex-col gap-2 overflow-y-auto">
              {texts.map((text) => (
                <li
                  key={text.id}
                  className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-separator px-4 py-2"
                >
                  <div>
                    <p className="font-medium">{text.title}</p>
                    <p className="text-muted">{text.source}</p>
                  </div>
                  <Button
                    variant="secondary"
                    onPress={() => add([text])}
                    aria-label={`Ajouter « ${text.title} »`}
                  >
                    Ajouter
                  </Button>
                </li>
              ))}
            </ul>
            <div className="mt-4 flex flex-wrap gap-4">
              <Button variant="secondary" onPress={() => add(texts)}>
                {texts.length === 1
                  ? "Ajouter le texte affiché"
                  : `Ajouter les ${texts.length} textes affichés`}
              </Button>
              <MoreTextsButton textsQuery={textsQuery} />
            </div>
          </>
        )}
      </section>

      <section aria-labelledby="ordered-title" className="mt-10">
        <h2 id="ordered-title" className="mb-4">
          Textes à répartir ({items.length})
        </h2>
        <OrderedTexts items={items} onChange={setItems} />
      </section>
    </div>
  );
}
