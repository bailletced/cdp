import { Checkbox } from "@heroui/react";
import { withLocalTime } from "../lib/localTime";
import { type PlanningSlot, slotTemporalState } from "../lib/planning";
import { now } from "../lib/serverClock";

/**
 * Card of one slot in the public planning grid (F08-T08, US-08.2).
 *
 * Shows the Paris schedule with the visitor's local time when abroad
 * (RG-PL-3), the empty/occupied state carried by a label and an icon — never
 * the color alone (§5.3-1) — the number of priants (RG-PL-1) and the text
 * title/source, or the RG-TX-2 placeholder when no text is attributed yet.
 * A past slot is greyed out and labelled « Créneau passé » (US-08.4): its
 * temporal state is computed with the server-corrected clock, so a skewed
 * device clock cannot mislead.
 *
 * With `selectable`, a not-yet-past slot gets a checkbox (US-08.5, F08-T10):
 * a past slot is never selectable.
 */
export function SlotCard({
  slot,
  registrationCount = 0,
  nowMs = now(),
  selectable = false,
  selected = false,
  onToggle,
}: {
  slot: PlanningSlot;
  registrationCount?: number;
  nowMs?: number;
  selectable?: boolean;
  selected?: boolean;
  onToggle?: (slotId: string) => void;
}) {
  const temporalState = slotTemporalState(slot, nowMs);
  const isPast = temporalState === "past";
  const isEmpty = registrationCount < 1;
  const canSelect = selectable && !isPast;

  // The state is the empty/occupied one (RG-PL-2, §5.2); a past slot is
  // greyed out and labelled, so the information never rides on color alone.
  let stateLabel: string;
  let stateClasses: string;

  if (isPast) {
    stateLabel = "Créneau passé";
    stateClasses = "text-muted";
  } else if (isEmpty) {
    stateLabel = "À pourvoir";
    stateClasses = "text-muted";
  } else {
    stateLabel = "Occupé";
    stateClasses = "text-success";
  }

  return (
    <article
      className={`rounded-xl border border-border bg-surface p-4 ${isPast ? "text-muted" : ""}`}
      data-state={temporalState}
    >
      {canSelect ? (
        <Checkbox
          aria-label={`Sélectionner le créneau de ${slot.label}`}
          className="mb-2"
          isSelected={selected}
          onChange={() => onToggle?.(slot.id)}
        >
          <Checkbox.Content>
            <Checkbox.Control>
              <Checkbox.Indicator />
            </Checkbox.Control>
          </Checkbox.Content>
        </Checkbox>
      ) : null}
      <p className="text-lg font-semibold">
        {withLocalTime(slot.label, slot.startsAt)}
      </p>
      <p className={`mt-1 flex items-center gap-2 font-medium ${stateClasses}`}>
        {isEmpty && !isPast ? <PlusIcon /> : null}
        {stateLabel}
      </p>
      <p className="mt-1">
        {registrationCount} priant{registrationCount > 1 ? "s" : ""}
      </p>
      {slot.textTitle ? (
        <>
          <p className="mt-2 font-medium">{slot.textTitle}</p>
          {slot.textSource ? (
            <p className="text-muted">{slot.textSource}</p>
          ) : null}
        </>
      ) : (
        <p className="mt-2 text-muted">
          Le texte de ce créneau sera bientôt disponible
        </p>
      )}
    </article>
  );
}

/** Plus icon of « À pourvoir »: decorative, the label carries the information. */
function PlusIcon() {
  return (
    <svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16">
      <path
        d="M8 2v12M2 8h12"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
      />
    </svg>
  );
}
