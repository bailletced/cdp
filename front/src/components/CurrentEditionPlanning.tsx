import { useCurrentEdition } from "../api/hooks";
import { EditionDates } from "./EditionDates";
import { ErrorState } from "./ErrorState";
import { LoadingState } from "./LoadingState";
import { PlanningGrid } from "./PlanningGrid";

/**
 * Planning of the current public edition, for « Mon espace »: a signed-in
 * priant no longer sees the landing page, so the slots are offered here.
 */
export function CurrentEditionPlanning() {
  const editionQuery = useCurrentEdition();

  if (editionQuery.isPending) {
    return <LoadingState />;
  }

  if (editionQuery.isError) {
    return (
      <ErrorState
        message="L'édition n'a pas pu être chargée."
        onRetry={() => void editionQuery.refetch()}
      />
    );
  }

  const edition = editionQuery.data;

  if (!edition?.id) {
    return <p>La prochaine édition sera bientôt annoncée.</p>;
  }

  return (
    <section aria-labelledby="current-edition-title">
      <h2 id="current-edition-title" className="mb-6">
        {edition.title ?? "24 heures de prière"}
      </h2>
      <div className="mb-10">
        <EditionDates startsAt={edition.startsAt} opensAt={edition.opensAt} />
      </div>
      <PlanningGrid editionId={edition.id} />
    </section>
  );
}
