import { Button, Modal } from "@heroui/react";
import type { SlotText } from "../lib/editions";
import { EmptyState } from "./EmptyState";
import { ErrorState } from "./ErrorState";
import {
  LibrarySearchField,
  MoreTextsButton,
  useLibrarySearch,
} from "./LibrarySearch";
import { LoadingState } from "./LoadingState";

/** Search results of the library, mounted only while the dialog is open. */
function TextResults({ onSelect }: { onSelect: (text: SlotText) => void }) {
  const { input, setInput, search, textsQuery, texts, isStale } =
    useLibrarySearch();

  return (
    <div className="flex flex-col gap-4">
      <LibrarySearchField value={input} onChange={setInput} autoFocus />
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
        <ul className="flex max-h-96 flex-col gap-2 overflow-y-auto">
          {texts.map((text) => (
            <li key={text.id}>
              <button
                type="button"
                onClick={() => onSelect(text)}
                className="min-h-11 w-full rounded-lg border border-separator px-4 py-2 text-left hover:border-accent focus-visible:border-accent"
              >
                <span className="font-medium">{text.title}</span>
                <span className="text-muted"> — {text.source}</span>
              </button>
            </li>
          ))}
        </ul>
      )}
      <MoreTextsButton textsQuery={textsQuery} isStale={isStale} />
    </div>
  );
}

/**
 * Text selector of one slot (F07-T07, US-07.1): a dialog searching the
 * library on title and source; choosing a text closes it.
 */
export function TextPickerDialog({
  slotLabel,
  isOpen,
  onClose,
  onSelect,
}: {
  slotLabel: string;
  isOpen: boolean;
  onClose: () => void;
  onSelect: (text: SlotText) => void;
}) {
  return (
    <Modal
      isOpen={isOpen}
      onOpenChange={(open) => {
        if (!open) {
          onClose();
        }
      }}
    >
      <Modal.Backdrop isDismissable>
        <Modal.Container>
          <Modal.Dialog>
            <Modal.Header>
              <Modal.Heading>Texte pour {slotLabel}</Modal.Heading>
            </Modal.Header>
            <Modal.Body>
              {isOpen ? <TextResults onSelect={onSelect} /> : null}
            </Modal.Body>
            <Modal.Footer>
              <Button variant="outline" onClick={onClose}>
                Annuler
              </Button>
            </Modal.Footer>
          </Modal.Dialog>
        </Modal.Container>
      </Modal.Backdrop>
    </Modal>
  );
}
