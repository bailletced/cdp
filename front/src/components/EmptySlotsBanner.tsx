import { type PlanningSlot, slotTemporalState } from "../lib/planning";

/** The id of the card of a slot in the grid: the banner scrolls to it. */
export function slotCardId(slotId: string): string {
  return `creneau-${slotId}`;
}

/** The empty slots still to come (RG-PL-2, §5.3), in chronological order. */
export function emptySlotsToCome(
  slots: PlanningSlot[],
  nowMs: number,
): PlanningSlot[] {
  return slots
    .filter(
      (slot) =>
        slot.registrationCount === 0 &&
        slotTemporalState(slot, nowMs) === "future",
    )
    .sort((a, b) => a.rank - b.rank);
}

/**
 * Banner of the empty slots (F13-T05, US-13.1): « Il reste X créneaux vides
 * sur 48 », then their schedules — each one scrolls to its card and moves
 * the focus onto it. During the edition, only the slots still to come count
 * (§5.3); once none is left, « La chaîne est complète. Merci ! ». Nothing is
 * shown once every slot is over.
 *
 * Computed from the grid, so it follows its refresh: a slot freed by a
 * cancellation comes back here (RG-EP-1). The count is a polite live region.
 */
export function EmptySlotsBanner({
  slots,
  nowMs,
}: {
  slots: PlanningSlot[];
  nowMs: number;
}) {
  if (!slots.some((slot) => slotTemporalState(slot, nowMs) !== "past")) {
    return null;
  }

  const empty = emptySlotsToCome(slots, nowMs);

  function goTo(slotId: string) {
    const card = document.getElementById(slotCardId(slotId));
    card?.scrollIntoView({ behavior: "smooth", block: "center" });
    card?.focus({ preventScroll: true });
  }

  return (
    <section
      aria-label="Créneaux à pourvoir"
      className="mb-10 rounded-2xl border border-accent bg-accent-soft p-4 sm:p-5"
    >
      <output className="block font-display text-xl font-bold">
        {empty.length === 0
          ? "La chaîne est complète. Merci !"
          : `Il reste ${empty.length} créneau${empty.length > 1 ? "x" : ""} vide${empty.length > 1 ? "s" : ""} sur ${slots.length}`}
      </output>
      {empty.length > 0 ? (
        <ul aria-label="Créneaux vides" className="mt-3 flex flex-wrap gap-2">
          {empty.map((slot) => (
            <li key={slot.id}>
              <button
                type="button"
                onClick={() => goTo(slot.id)}
                className="min-h-11 rounded-full border border-border bg-surface px-3 font-bold lining-nums tabular-nums hover:border-accent"
              >
                {slot.label}
              </button>
            </li>
          ))}
        </ul>
      ) : null}
    </section>
  );
}
