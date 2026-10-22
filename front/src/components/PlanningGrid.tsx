import { useEditionSlots } from "../api/hooks";
import { groupSlotsByPartOfDay } from "../lib/editions";
import { parsePlanningSlots } from "../lib/planning";
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
 */
export function PlanningGrid({ editionId }: { editionId: string }) {
  const slotsQuery = useEditionSlots(editionId);
  const nowMs = useNow();

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

  const groups = groupSlotsByPartOfDay(
    parsePlanningSlots(slotsQuery.data?.slots),
  );

  if (groups.length === 0) {
    return <EmptyState message="Aucun créneau pour le moment." />;
  }

  return (
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
                  <SlotCard slot={slot} nowMs={nowMs} />
                </li>
              ))}
            </ul>
          </section>
        );
      })}
    </section>
  );
}
