import { Button, Modal } from "@heroui/react";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { slotSuggestionQueryOptions } from "../api/hooks";
import { withLocalTime } from "../lib/localTime";
import { ErrorState } from "./ErrorState";
import { LoadingState } from "./LoadingState";

/** The drawn slot, mounted only while the dialog is open. */
function Draw({ editionId }: { editionId: string }) {
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
    return <p>La chaîne est complète. Merci !</p>;
  }

  const slotId = slot.slotId;
  // « Un autre » as long as possible: the API offers an excluded slot again
  // only when no other one is left
  const isLast = excluded.includes(slotId);

  return (
    <div className="grid gap-5">
      <div aria-live="polite">
        <p className="font-display text-3xl font-bold lining-nums tabular-nums">
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
          Je le prends
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
 * schedule and the title of its text. « Je le prends » leads to the
 * inscription form, « Un autre » draws again, excluding the slots already
 * offered. React Aria moves the focus into the dialog and gives it back to
 * the button on close.
 */
export function EmptySlotDraw({ editionId }: { editionId: string }) {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <>
      <Button variant="primary" onClick={() => setIsOpen(true)}>
        Prendre un créneau vide
      </Button>
      <Modal isOpen={isOpen} onOpenChange={setIsOpen}>
        <Modal.Backdrop isDismissable>
          <Modal.Container>
            <Modal.Dialog>
              <Modal.Header>
                <Modal.Heading>Un créneau vide pour vous</Modal.Heading>
              </Modal.Header>
              <Modal.Body>
                {isOpen ? <Draw editionId={editionId} /> : null}
              </Modal.Body>
              <Modal.Footer>
                <Button variant="tertiary" onClick={() => setIsOpen(false)}>
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
