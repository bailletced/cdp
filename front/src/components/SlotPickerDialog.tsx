import { Button, Modal } from "@heroui/react";
import { useState } from "react";
import { useEditionSlots } from "../api/hooks";
import { withLocalTime } from "../lib/localTime";
import {
  type PlanningSlot,
  parsePlanningSlots,
  slotTemporalState,
} from "../lib/planning";
import { useNow } from "../lib/serverClock";
import { ErrorState } from "./ErrorState";
import { LoadingState } from "./LoadingState";

const PICKER_HEIGHT = "max-h-[24rem] overflow-y-auto";

/** Only the slots still to come can be taken (RG-PL-2, §5.3) — the viewer's
 * own stay: visible, marked, untakeable. */
function slotsToCome(slots: PlanningSlot[], nowMs: number): PlanningSlot[] {
  return slots.filter((slot) => slotTemporalState(slot, nowMs) === "future");
}

/** « N priant(s) » (RG-PL-1: the count accompanies every schedule). */
function priantsLabel(count: number): string {
  return count > 1 ? `${count} priants` : `${count} priant`;
}

/**
 * « Choisir un créneau » (F14-T12): the whole list of the slots still to
 * come, one compact line each — schedule, priants count, and one button
 * to take it. Not the heavy planning grid: the list scrolls on its own
 * inside the dialog, the page never grows, and « Je le prends » leads to
 * the one-click inscription of the already-consented priant.
 *
 * Every future slot is listed, the viewer's own included: they are shown
 * muted, wearing the « Vous y êtes » mark, with no « Je le prends » —
 * impossible to re-register on them (RG-IN-3). Past slots don't appear
 * (RG-PL-2) ; every action stays a single click deep.
 *
 * The dialog lives here but can also be opened from outside — my-space
 * buttons far above this block open it directly (« Ajouter des
 * créneaux ») through the optional `open`/`onOpenChange` pair, so no
 * action ever depends on the reader scrolling to the right place.
 */
export function SlotPickerDialog({
  editionId,
  open,
  onOpenChange,
}: {
  editionId: string;
  /** Controlled mode: omit it and the internal trigger owns the state. */
  open?: boolean;
  onOpenChange?: (isOpen: boolean) => void;
}) {
  const slotsQuery = useEditionSlots(editionId);
  const nowMs = useNow();
  const [internalOpen, setInternalOpen] = useState(false);
  const isOpen = open ?? internalOpen;
  const setDialogOpen = onOpenChange ?? setInternalOpen;

  let content: React.ReactNode = null;

  if (slotsQuery.isPending) {
    content = <LoadingState label="Chargement des créneaux…" />;
  } else if (slotsQuery.isError) {
    content = (
      <ErrorState
        message="Les créneaux n'ont pas pu être chargés."
        onRetry={() => void slotsQuery.refetch()}
      />
    );
  } else {
    const slots = slotsToCome(
      parsePlanningSlots(slotsQuery.data?.slots ?? []),
      nowMs,
    );

    content =
      0 === slots.length ? (
        <p>
          La chaîne est complète. Merci&nbsp;!{" "}
          <Button
            variant="tertiary"
            className="mt-2"
            onPress={() => setDialogOpen(false)}
          >
            Fermer
          </Button>
        </p>
      ) : (
        <ul className={PICKER_HEIGHT} aria-label="Créneaux à venir">
          {slots.map((slot) => (
            <li
              key={slot.id}
              className="border-b border-separator last:border-0"
            >
              <div
                className={`flex items-center justify-between gap-3 py-2 ${
                  slot.mine
                    ? "rounded-[var(--radius)] bg-surface-secondary px-3 text-muted"
                    : ""
                }`}
              >
                <span className="font-display font-bold lining-nums tabular-nums">
                  {withLocalTime(slot.label, slot.startsAt)}
                  <span className="ml-3 font-sans font-normal text-muted">
                    {priantsLabel(slot.registrationCount)}
                  </span>
                </span>
                {slot.mine ? (
                  <span className="rounded-full border border-accent px-3 py-1 font-bold text-accent">
                    Vous y êtes
                  </span>
                ) : (
                  <a
                    href={`/inscription?creneaux=${slot.id}`}
                    className="inline-flex min-h-11 items-center justify-center rounded-[var(--radius)] border border-border px-4 no-underline"
                  >
                    Je le prends
                  </a>
                )}
              </div>
            </li>
          ))}
        </ul>
      );
  }

  return (
    <>
      <Button variant="outline" onPress={() => setDialogOpen(true)}>
        Choisir un créneau
      </Button>
      <Modal isOpen={isOpen} onOpenChange={setDialogOpen}>
        <Modal.Backdrop isDismissable>
          <Modal.Container>
            <Modal.Dialog>
              <Modal.Header>
                <Modal.Heading>Choisir un créneau</Modal.Heading>
              </Modal.Header>
              <Modal.Body>{content}</Modal.Body>
              <Modal.Footer>
                <Button variant="tertiary" onPress={() => setDialogOpen(false)}>
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
