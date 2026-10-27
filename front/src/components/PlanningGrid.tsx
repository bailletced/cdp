import { Link } from "@heroui/react";
import { useState } from "react";
import { useEditionSlots, useMe } from "../api/hooks";
import { groupSlotsByPartOfDay } from "../lib/editions";
import { parsePlanningSlots, slotTemporalState } from "../lib/planning";
import { useNow } from "../lib/serverClock";
import { EmptySlotsBanner } from "./EmptySlotsBanner";
import { EmptyState } from "./EmptyState";
import { ErrorState } from "./ErrorState";
import { LoadingState } from "./LoadingState";
import { QuickInscriptionButton } from "./QuickInscriptionButton";
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
 * carry no checkbox and never enter the URL. A priant in session who already
 * consented skips the form: the button registers at once.
 *
 * The banner of the empty slots heads the grid (F13-T05, US-13.1).
 *
 * To spare the scrolling through 48 cards, a sticky navigation leads to
 * each part of the day still to come, and a part of the day that is over
 * is folded. A whole card toggles its selection; the viewer's own slots are
 * not selectable (RG-IN-3).
 */
export function PlanningGrid({ editionId }: { editionId: string }) {
  const slotsQuery = useEditionSlots(editionId);
  const me = useMe().data;
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

  const sections = groups.map((group, index) => ({
    group,
    titleId: `planning-${group.partOfDay}-${group.slots[0]?.rank ?? index}`,
    isOver: group.slots.every(
      (slot) => slotTemporalState(slot, nowMs) === "past",
    ),
  }));
  const upcomingGroups = sections.filter((section) => !section.isOver);

  // Only slots that are not past nor already the viewer's (RG-IN-3) can be
  // selected — a refresh may turn a ticked slot into either; the URL lists
  // them in chronological (rank) order, whatever the order of the clicks.
  const selectedSlots = slots
    .filter((slot) => selectedIds.includes(slot.id))
    .filter((slot) => slotTemporalState(slot, nowMs) !== "past" && !slot.mine)
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
      <EmptySlotsBanner editionId={editionId} slots={slots} nowMs={nowMs} />
      {upcomingGroups.length > 0 ? (
        // Sticky above the grid: one tap reaches any part of the day,
        // instead of scrolling through 48 cards
        <nav
          aria-label="Moments de la journée"
          className="sticky top-0 z-10 -mx-4 mb-6 bg-background/95 px-4 py-2 backdrop-blur"
        >
          <ul className="flex gap-2 overflow-x-auto sm:flex-wrap">
            {upcomingGroups.map(({ group, titleId }) => (
              <li key={titleId} className="shrink-0">
                <a
                  href={`#${titleId}`}
                  onClick={(event) => {
                    event.preventDefault();
                    goTo(titleId);
                  }}
                  className="inline-flex min-h-11 items-center rounded-full border border-border bg-surface px-4 font-medium whitespace-nowrap no-underline hover:border-accent"
                >
                  {group.label} · {startOf(group.slots[0]?.label ?? "")}
                </a>
              </li>
            ))}
          </ul>
        </nav>
      ) : null}
      <section aria-label="Grille des créneaux" className="grid gap-10">
        {sections.map(({ group, titleId, isOver }) => {
          const cards = (
            <ul className="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
              {group.slots.map((slot) => (
                <li key={slot.id}>
                  <SlotCard
                    nowMs={nowMs}
                    onToggle={toggleSlot}
                    registrationCount={slot.registrationCount}
                    selectable
                    selected={selectedIds.includes(slot.id)}
                    slot={slot}
                  />
                </li>
              ))}
            </ul>
          );

          // A part of the day that is over is folded: during the edition,
          // the slots to come lead the grid
          if (isOver) {
            const count = group.slots.length;

            return (
              <details key={titleId}>
                <summary className="min-h-11 cursor-pointer py-2 text-muted">
                  {group.label} — {count} créneau{count > 1 ? "x" : ""} passé
                  {count > 1 ? "s" : ""}
                </summary>
                <div className="mt-4">{cards}</div>
              </details>
            );
          }

          return (
            <section key={titleId} aria-labelledby={titleId}>
              <div className="mb-4 flex items-center gap-3 border-b border-separator pb-3">
                <PartOfDayIcon partOfDay={group.partOfDay} />
                <h2
                  id={titleId}
                  tabIndex={-1}
                  className="scroll-mt-20 outline-none"
                >
                  {group.label}
                </h2>
              </div>
              {cards}
            </section>
          );
        })}
      </section>
      {/* The selection bar follows the grid in reading order (tick, then
          go on). Once a slot is ticked, it stays pinned to the bottom of the
          screen while the grid scrolls by. On a phone the counter is only
          read out: the link label already carries the count. */}
      <div
        className={`z-10 mt-8 flex items-center justify-between gap-4 rounded-2xl border border-separator bg-surface/95 px-4 py-3 backdrop-blur sm:px-5 ${
          selectedCount > 0
            ? "sticky bottom-4 shadow-[0_12px_40px_rgb(0_0_0/0.45)]"
            : ""
        }`}
      >
        <p className="sr-only text-muted sm:not-sr-only">{counterLabel}</p>
        {me?.consentGiven && me.displayName && me.email ? (
          // A known priant: no form to fill, the slots are taken at once
          <QuickInscriptionButton
            slotIds={selectedSlots.map((slot) => slot.id)}
            displayName={me.displayName}
            email={me.email}
          />
        ) : (
          <>
            {/* isDisabled renders a span (aria-disabled, not focusable, clicks
              blocked by pointer-events-none): the disabled link is not
              navigable. react-aria strips aria-disabled/tabIndex props, so
              isDisabled is the supported way to disable a HeroUI Link. */}
            <Link
              className={`inline-flex min-h-11 w-full items-center justify-center rounded-[var(--radius)] bg-accent px-5 font-bold sm:w-auto text-accent-foreground no-underline ${
                selectedCount === 0 ? "pointer-events-none opacity-60" : ""
              }`}
              href={inscriptionHref}
              isDisabled={selectedCount === 0}
            >
              M&apos;inscrire ({selectedCount})
            </Link>
          </>
        )}
      </div>
    </div>
  );
}

/** « 19h00 » out of « 19h00–19h30 »: when a part of the day starts. */
function startOf(label: string): string {
  return label.split("–")[0] ?? label;
}

/** Scrolls to a part of the day and moves the focus onto its title. */
function goTo(titleId: string) {
  const title = document.getElementById(titleId);
  title?.scrollIntoView({ behavior: "smooth", block: "start" });
  title?.focus({ preventScroll: true });
}

/**
 * Icon of a part of the day (sunset, moon, sunrise, sun): decorative, the
 * section title names the part of the day.
 */
function PartOfDayIcon({ partOfDay }: { partOfDay: string }) {
  return (
    <svg
      aria-hidden="true"
      className="h-7 w-7 shrink-0 text-accent"
      viewBox="0 0 28 28"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      {partOfDay === "night" ? (
        <path d="M19.5 18.5A8.5 8.5 0 0 1 11 5a9 9 0 1 0 12 11.5 8.4 8.4 0 0 1-3.5 2Z" />
      ) : partOfDay === "afternoon" ? (
        <>
          <circle cx="14" cy="14" r="5" />
          <path d="M14 3v2.5M14 22.5V25M3 14h2.5M22.5 14H25M6.2 6.2l1.8 1.8M20 20l1.8 1.8M6.2 21.8 8 20M20 8l1.8-1.8" />
        </>
      ) : (
        // Evening (sunset) and morning (sunrise): the sun on the horizon,
        // the arrow tells which way it goes
        <>
          <path d="M3 21h22M8 21a6 6 0 0 1 12 0" />
          <path
            d={
              partOfDay === "morning"
                ? "M14 4v7M11 7l3-3 3 3"
                : "M14 4v7M11 8l3 3 3-3"
            }
          />
        </>
      )}
    </svg>
  );
}
