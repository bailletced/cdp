import { Button, Modal } from "@heroui/react";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { slotSuggestionQueryOptions } from "../api/hooks";
import { parisDayLabel, withLocalTime } from "../lib/localTime";
import { ErrorState } from "./ErrorState";
import { LoadingState } from "./LoadingState";

/** The drawn slot, mounted only while the dialog is open. */
function Draw({
  editionId,
  onFallbackToPicker,
}: {
  editionId: string;
  onFallbackToPicker?: () => void;
}) {
  const [excluded, setExcluded] = useState<string[]>([]);
  const query = useQuery(slotSuggestionQueryOptions(editionId, excluded));

  if (query.isPending) {
    return <LoadingState label="Tirage d'un créneau…" />;
  }

  if (query.isError) {
    return (
      <ErrorState
        message="Le tirage n'a pas pu être fait."
        onRetry={() => void query.refetch()}
      />
    );
  }

  const slot = query.data;

  if (!slot?.slotId) {
    // Full chain: the free-slot draw stops, but slots can always be chosen
    // (several priants relay each other on one slot, RG-PL-1) — the caller
    // may provide its picker as the way out.
    if (!onFallbackToPicker) {
      return <p>La chaîne est complète. Merci&nbsp;!</p>;
    }
    return (
      <div className="grid gap-5 text-center">
        <p>La chaîne est complète. Merci&nbsp;!</p>
        <div>
          <Button variant="outline" onPress={onFallbackToPicker}>
            Choisir un créneau quand même
          </Button>
        </div>
      </div>
    );
  }

  const slotId = slot.slotId;
  // « Un autre » as long as possible: the API offers an excluded slot again
  // only when no other one is left
  const isLast = excluded.includes(slotId);

  return (
    <div className="grid gap-5">
      <div aria-live="polite">
        {slot.startsAt ? (
          <p className="text-muted">{parisDayLabel(slot.startsAt)}</p>
        ) : null}
        <p
          className={`font-display text-3xl font-bold lining-nums tabular-nums ${
            slot.startsAt ? "mt-1" : ""
          }`}
        >
          {withLocalTime(slot.label ?? "", slot.startsAt ?? "")}
        </p>
        {slot.textTitle ? (
          <p className="mt-1 text-lg">{slot.textTitle}</p>
        ) : (
          <p className="mt-1 text-muted">
            Le texte de ce créneau sera bientôt disponible
          </p>
        )}
        {isLast ? (
          <p className="mt-3 text-muted">C'est le dernier créneau vide.</p>
        ) : null}
      </div>
      <div className="flex flex-wrap gap-3">
        <a
          href={`/inscription?creneaux=${slotId}`}
          className="inline-flex min-h-11 items-center justify-center rounded-[var(--radius)] bg-accent px-5 font-medium text-accent-foreground"
        >
          Choisir
        </a>
        <Button
          variant="outline"
          onClick={() => setExcluded((ids) => [...new Set([...ids, slotId])])}
        >
          Un autre
        </Button>
      </div>
    </div>
  );
}

/**
 * « Prendre un créneau vide » (F13-T06, US-13.2): draws an empty slot still
 * to come at random — the chance spreads the priants — and shows its
 * schedule and the title of its text. « Choisir » leads to the
 * inscription form, « Un autre » draws again, excluding the slots already
 * offered. React Aria moves the focus into the dialog and gives it back to
 * the button on close.
 */
export function EmptySlotDraw({
  editionId,
  open,
  onOpenChange,
  onFallbackToPicker,
}: {
  editionId: string;
  /** Controlled mode: omit it and the internal trigger owns the state. */
  open?: boolean;
  onOpenChange?: (isOpen: boolean) => void;
  /** Offered when the chain is complete: a way to choose anyway. */
  onFallbackToPicker?: () => void;
}) {
  const [internalOpen, setInternalOpen] = useState(false);
  const isOpen = open ?? internalOpen;
  const setDialogOpen = onOpenChange ?? setInternalOpen;

  return (
    <>
      <Button variant="primary" onClick={() => setDialogOpen(true)}>
        Prendre un créneau vide
      </Button>
      <Modal isOpen={isOpen} onOpenChange={setDialogOpen}>
        <Modal.Backdrop isDismissable>
          <Modal.Container>
            <Modal.Dialog>
              <Modal.Header>
                <Modal.Heading>Un créneau vide pour vous</Modal.Heading>
              </Modal.Header>
              <Modal.Body>
                {isOpen ? (
                  <Draw
                    editionId={editionId}
                    onFallbackToPicker={onFallbackToPicker}
                  />
                ) : null}
              </Modal.Body>
              <Modal.Footer>
                <Button variant="tertiary" onClick={() => setDialogOpen(false)}>
                  Fermer
                </Button>
              </Modal.Footer>
            </Modal.Dialog>
          </Modal.Container>
        </Modal.Backdrop>
      </Modal>
    </>
  );
}
