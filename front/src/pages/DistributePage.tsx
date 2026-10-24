import { Button } from "@heroui/react";
import { Link, useNavigate, useParams } from "@tanstack/react-router";
import { useRef, useState } from "react";
import {
  Button as AriaButton,
  GridList,
  GridListItem,
  useDragAndDrop,
} from "react-aria-components";
import { getApiErrorDetail } from "../api/errors";
import { useDistributeTexts, useEdition } from "../api/hooks";
import { EmptyState } from "../components/EmptyState";
import { ErrorState } from "../components/ErrorState";
import {
  LibrarySearchField,
  MoreTextsButton,
  useLibrarySearch,
} from "../components/LibrarySearch";
import { LoadingState } from "../components/LoadingState";
import { notify } from "../components/Notifications";
import { PageTitle } from "../components/PageTitle";
import {
  type EditionSlot,
  type SlotText,
  moveItems,
  parseEditionSlots,
} from "../lib/editions";

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

function previewSummary(fromRank: number, filled: number): string {
  return filled === 1
    ? `Le créneau n° ${fromRank} recevra 1 texte.`
    : `Les créneaux n° ${fromRank} à ${fromRank + filled - 1} recevront ${filled} textes.`;
}

function unusedSummary(unused: number): string {
  return unused === 1
    ? "1 texte ne sera pas utilisé : la répartition s’arrête au dernier créneau."
    : `${unused} textes ne seront pas utilisés : la répartition s’arrête au dernier créneau.`;
}

function submitLabel(count: number): string {
  if (count === 0) {
    return "Répartir les textes";
  }

  return count === 1 ? "Répartir 1 texte" : `Répartir ${count} textes`;
}

/**
 * What the distribution will do before it is sent (F07-T09, US-07.2): the
 * text k goes to the slot fromRank + k - 1, and the list stops at the last
 * slot — the texts beyond it are announced as unused.
 */
function DistributionPreview({
  slots,
  items,
  fromRank,
}: {
  slots: EditionSlot[];
  items: ListedText[];
  fromRank: number;
}) {
  const targets = slots.filter((slot) => slot.rank >= fromRank);
  const filled = Math.min(items.length, targets.length);
  const unused = items.length - filled;

  return (
    <section
      aria-labelledby="preview-title"
      className="mt-6 rounded-2xl border border-separator bg-surface p-6"
    >
      <h3 id="preview-title" className="mb-4">
        Aperçu
      </h3>
      {filled === 0 ? (
        <p className="text-muted">Ajoutez au moins un texte à répartir.</p>
      ) : (
        <>
          <p>{previewSummary(fromRank, filled)}</p>
          {unused > 0 ? (
            <p className="mt-2 text-warning">{unusedSummary(unused)}</p>
          ) : null}
          <ol className="mt-4 flex flex-col gap-1">
            {targets.slice(0, filled).map((slot, index) => (
              <li key={slot.rank} className="flex flex-wrap gap-x-4">
                <span className="w-32 font-medium tabular-nums">
                  {slot.label}
                </span>
                <span>{items[index].text.title}</span>
                {slot.textTitle && slot.textId !== items[index].text.id ? (
                  <span className="text-muted">
                    (remplace « {slot.textTitle} »)
                  </span>
                ) : null}
              </li>
            ))}
          </ol>
        </>
      )}
    </section>
  );
}

/**
 * /organisation/editions/$editionId/distribute (F07-T08, F07-T09, US-07.2):
 * « Répartir dans l'ordre » — pick texts from the library, order them,
 * choose the start slot, check the preview and send.
 */
export function DistributePage() {
  const { editionId } = useParams({
    from: "/organisation/editions/$editionId/distribute",
  });
  const editionQuery = useEdition(editionId);
  const { input, setInput, search, textsQuery, texts, isStale } =
    useLibrarySearch();
  const [items, setItems] = useState<ListedText[]>([]);
  const [fromRank, setFromRank] = useState(1);
  const [sendError, setSendError] = useState<string | null>(null);
  const nextKey = useRef(0);
  const distributeTexts = useDistributeTexts(editionId);
  const navigate = useNavigate();

  async function handleDistribute() {
    setSendError(null);

    try {
      await distributeTexts.mutateAsync({
        textIds: items.map((item) => item.text.id),
        fromRank,
      });
      notify("Textes répartis.", { variant: "success" });
      await navigate({
        to: "/organisation/editions/$editionId/texts",
        params: { editionId },
      });
    } catch (error) {
      setSendError(
        getApiErrorDetail(error) ??
          "Les textes n'ont pas pu être répartis. Veuillez réessayer.",
      );
    }
  }

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

  const slots = parseEditionSlots(edition.slots);

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
              <Button
                variant="secondary"
                // Wait for the results of the current search
                isDisabled={isStale}
                onPress={() => add(texts)}
              >
                {texts.length === 1
                  ? "Ajouter le texte affiché"
                  : `Ajouter les ${texts.length} textes affichés`}
              </Button>
              <MoreTextsButton textsQuery={textsQuery} isStale={isStale} />
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

      <section aria-labelledby="start-title" className="mt-10">
        <h2 id="start-title" className="mb-4">
          Répartition
        </h2>
        <label className="flex flex-col gap-2" htmlFor="from-rank">
          <span className="font-medium">Créneau de départ</span>
          <select
            id="from-rank"
            value={fromRank}
            onChange={(event) => setFromRank(Number(event.target.value))}
            className="min-h-11 w-full max-w-md rounded-lg border border-border bg-field-background px-3"
          >
            {slots.map((slot) => (
              <option key={slot.rank} value={slot.rank}>
                n° {slot.rank} — {slot.label}
              </option>
            ))}
          </select>
        </label>
        <DistributionPreview slots={slots} items={items} fromRank={fromRank} />
        <div className="mt-6 flex flex-wrap items-center gap-4">
          <Button
            variant="primary"
            isDisabled={items.length === 0 || distributeTexts.isPending}
            onPress={() => void handleDistribute()}
          >
            {distributeTexts.isPending
              ? "Répartition…"
              : submitLabel(items.length)}
          </Button>
        </div>
        {sendError ? (
          <p role="alert" className="mt-4 text-danger">
            {sendError}
          </p>
        ) : null}
      </section>
    </div>
  );
}
