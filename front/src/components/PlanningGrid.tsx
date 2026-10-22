import { Link } from "@heroui/react";
import { useState } from "react";
import { useEditionSlots } from "../api/hooks";
import { groupSlotsByPartOfDay } from "../lib/editions";
import { parsePlanningSlots, slotTemporalState } from "../lib/planning";
import { useNow } from "../lib/serverClock";
import { EmptyState } from "./EmptyState";
import { ErrorState } from "./ErrorState";
import { LoadingState } from "./LoadingState";
import { SlotCard } from "./SlotCard";

/**
 * Public planning grid (F08-T09, US-08.2): the slots of a public edition,
 * grouped by part of day (soirée, nuit, matin, après-midi) with section
 * titles, in chronological order. The slots are refreshed every 60 s and on
 * window focus, and each card computes its temporal state with the
 * server-corrected clock (US-08.4).
 *
 * A visitor can tick several upcoming slots (US-08.5, F08-T10): a counter
 * and a « M'inscrire (n) » button above the grid lead to the inscription
 * page with the selected slot ids, in chronological (rank) order. Past slots
 * carry no checkbox and never enter the URL.
 */
export function PlanningGrid({ editionId }: { editionId: string }) {
  const slotsQuery = useEditionSlots(editionId);
  const nowMs = useNow();
  const [selectedIds, setSelectedIds] = useState<string[]>([]);

  const toggleSlot = (slotId: string) => {
    setSelectedIds((ids) =>
      ids.includes(slotId)
        ? ids.filter((id) => id !== slotId)
        : [...ids, slotId],
    );
  };

  if (slotsQuery.isPending) {
    return <LoadingState label="Chargement des créneaux…" />;
  }

  if (slotsQuery.isError) {
    return (
      <ErrorState
        message="Les créneaux n'ont pas pu être chargés."
        onRetry={() => void slotsQuery.refetch()}
      />
    );
  }

  const slots = parsePlanningSlots(slotsQuery.data?.slots);
  const groups = groupSlotsByPartOfDay(slots);

  if (groups.length === 0) {
    return <EmptyState message="Aucun créneau pour le moment." />;
  }

  // Only slots that are not past can be selected; the URL lists them in
  // chronological (rank) order, whatever the order of the clicks.
  const selectedSlots = slots
    .filter((slot) => selectedIds.includes(slot.id))
    .filter((slot) => slotTemporalState(slot, nowMs) !== "past")
    .sort((a, b) => a.rank - b.rank);
  const selectedCount = selectedSlots.length;
  const inscriptionHref = `/inscription?creneaux=${selectedSlots
    .map((slot) => slot.id)
    .join(",")}`;
  const counterLabel =
    selectedCount === 0
      ? "Aucun créneau sélectionné."
      : selectedCount === 1
        ? "1 créneau sélectionné."
        : `${selectedCount} créneaux sélectionnés.`;

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p className="text-muted">{counterLabel}</p>
        {/* isDisabled renders a span (aria-disabled, not focusable, clicks
            blocked by pointer-events-none): the disabled link is not
            navigable. react-aria strips aria-disabled/tabIndex props, so
            isDisabled is the supported way to disable a HeroUI Link. */}
        <Link
          className={`inline-flex min-h-11 items-center rounded-lg bg-accent px-4 text-accent-foreground no-underline ${
            selectedCount === 0 ? "pointer-events-none opacity-60" : ""
          }`}
          href={inscriptionHref}
          isDisabled={selectedCount === 0}
        >
          M&apos;inscrire ({selectedCount})
        </Link>
      </div>
      <section aria-label="Grille des créneaux" className="mb-8 grid gap-8">
        {groups.map((group, index) => {
          const titleId = `planning-${group.partOfDay}-${group.slots[0]?.rank ?? index}`;

          return (
            <section key={titleId} aria-labelledby={titleId}>
              <h2 id={titleId} className="mb-4 text-xl font-semibold">
                {group.label}
              </h2>
              <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {group.slots.map((slot) => (
                  <li key={slot.id}>
                    <SlotCard
                      nowMs={nowMs}
                      onToggle={toggleSlot}
                      selectable
                      selected={selectedIds.includes(slot.id)}
                      slot={slot}
                    />
                  </li>
                ))}
              </ul>
            </section>
          );
        })}
      </section>
    </div>
  );
}
