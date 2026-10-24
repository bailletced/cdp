import { Link, useParams } from "@tanstack/react-router";
import { useEdition } from "../api/hooks";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import { groupSlotsByPartOfDay, parseEditionSlots } from "../lib/editions";

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
 */
export function AttributionPage() {
  const { editionId } = useParams({
    from: "/organisation/editions/$editionId/texts",
  });
  const editionQuery = useEdition(editionId);
  const edition = editionQuery.data;

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
                  className="grid gap-x-6 gap-y-1 rounded-xl border border-separator bg-surface px-4 py-3 sm:grid-cols-[9rem_1fr]"
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
                </li>
              ))}
            </ul>
          </section>
        );
      })}
    </div>
  );
}
