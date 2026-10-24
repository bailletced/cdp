import { Button } from "@heroui/react";
import { Link, useParams } from "@tanstack/react-router";
import { useState } from "react";
import { getApiErrorDetail } from "../api/errors";
import { useAssignText, useEdition } from "../api/hooks";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import { TextPickerDialog } from "../components/TextPickerDialog";
import {
  type EditionSlot,
  type SlotText,
  groupSlotsByPartOfDay,
  parseEditionSlots,
} from "../lib/editions";

function missingTextsSummary(missing: number, total: number): string {
  if (missing === 0) {
    return "Chaque créneau a son texte.";
  }

  return missing === 1
    ? `1 créneau sur ${total} n’a pas encore de texte.`
    : `${missing} créneaux sur ${total} n’ont pas encore de texte.`;
}

/**
 * /organisation/editions/$editionId/texts (F07-T06, US-07.1): the slots of an
 * edition grouped by part of day, each with its text — title and source — or
 * « Sans texte » (RG-TX-2 on the organizer side).
 *
 * Each slot gets its text from a search in the library, or loses it
 * (F07-T07): the screen changes at once and is put back if the API refuses.
 */
export function AttributionPage() {
  const { editionId } = useParams({
    from: "/organisation/editions/$editionId/texts",
  });
  const editionQuery = useEdition(editionId);
  const assignText = useAssignText(editionId);
  const [picking, setPicking] = useState<EditionSlot | null>(null);
  const [assignError, setAssignError] = useState<string | null>(null);
  const edition = editionQuery.data;

  function assign(slotId: string, text: SlotText | null) {
    setAssignError(null);
    assignText.mutate(
      { slotId, text },
      {
        onError: (error) =>
          setAssignError(
            getApiErrorDetail(error) ??
              "Le texte n'a pas pu être enregistré. Veuillez réessayer.",
          ),
      },
    );
  }

  if (editionQuery.isLoading || editionQuery.isError || !edition) {
    return (
      <div className="mx-auto w-full max-w-3xl">
        <PageTitle>Attribution des textes</PageTitle>
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
  const groups = groupSlotsByPartOfDay(slots);
  const missing =
    edition.slotsWithoutText ??
    slots.filter((slot) => null === slot.textId).length;

  return (
    <div className="mx-auto w-full max-w-3xl">
      <PageTitle>Attribution des textes</PageTitle>
      <p className="mt-2 text-xl">{edition.title}</p>
      <div className="mt-4 flex flex-wrap items-baseline justify-between gap-4">
        <p className={missing > 0 ? "text-warning" : "text-success"}>
          {missingTextsSummary(missing, slots.length)}
        </p>
        <Link
          to="/organisation/editions/$editionId"
          params={{ editionId }}
          className="text-link underline underline-offset-4"
        >
          Retour à l’édition
        </Link>
      </div>
      {assignError ? (
        <p role="alert" className="mt-4 text-danger">
          {assignError}
        </p>
      ) : null}
      {groups.map((group, groupIndex) => {
        const headingId = `part-of-day-${groupIndex}`;

        return (
          <section
            key={`${group.partOfDay}-${groupIndex}`}
            aria-labelledby={headingId}
            className="mt-10"
          >
            <h2 id={headingId} className="mb-4">
              {group.label}
            </h2>
            <ul className="flex flex-col gap-3">
              {group.slots.map((slot) => (
                <li
                  key={slot.rank}
                  className="grid items-center gap-x-6 gap-y-2 rounded-xl border border-separator bg-surface px-4 py-3 sm:grid-cols-[9rem_1fr_auto]"
                >
                  <span className="font-display text-xl font-bold lining-nums tabular-nums">
                    {slot.label}
                  </span>
                  {slot.textTitle ? (
                    <div>
                      <p className="font-medium">{slot.textTitle}</p>
                      <p className="text-muted">{slot.textSource}</p>
                    </div>
                  ) : (
                    <p className="text-muted italic">Sans texte</p>
                  )}
                  {slot.id ? (
                    <SlotActions
                      slot={slot}
                      onPick={() => setPicking(slot)}
                      onWithdraw={() => assign(slot.id as string, null)}
                    />
                  ) : null}
                </li>
              ))}
            </ul>
          </section>
        );
      })}
      <TextPickerDialog
        slotLabel={picking?.label ?? ""}
        isOpen={null !== picking}
        onClose={() => setPicking(null)}
        onSelect={(text) => {
          if (picking?.id) {
            assign(picking.id, text);
          }
          setPicking(null);
        }}
      />
    </div>
  );
}

/**
 * Choose, change or withdraw the text of a slot. The accessible names start
 * with the visible label and add the slot (WCAG 2.5.3).
 */
function SlotActions({
  slot,
  onPick,
  onWithdraw,
}: {
  slot: EditionSlot;
  onPick: () => void;
  onWithdraw: () => void;
}) {
  if (null === slot.textId) {
    return (
      <Button
        variant="secondary"
        onPress={onPick}
        aria-label={`Choisir un texte pour ${slot.label}`}
      >
        Choisir un texte
      </Button>
    );
  }

  return (
    <div className="flex flex-wrap gap-2">
      <Button
        variant="secondary"
        onPress={onPick}
        aria-label={`Changer le texte de ${slot.label}`}
      >
        Changer
      </Button>
      <Button
        variant="ghost"
        onPress={onWithdraw}
        aria-label={`Retirer le texte de ${slot.label}`}
      >
        Retirer
      </Button>
    </div>
  );
}
