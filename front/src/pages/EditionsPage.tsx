import { Link } from "@tanstack/react-router";
import { useEditions } from "../api/hooks";
import { EmptyState } from "../components/EmptyState";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import { editionStatusLabel, formatParisDateTime } from "../lib/editions";

/**
 * /organisation/editions (F05-T18): back-office editions list — title,
 * dates in Paris time and status label for each edition.
 */
export function EditionsPage() {
  const { data, isLoading, isError, refetch } = useEditions();

  return (
    <div className="mx-auto w-full max-w-3xl">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <PageTitle>Éditions</PageTitle>
        <Link
          to="/organisation/editions/new"
          className="inline-flex min-h-11 items-center rounded-lg bg-accent px-4 text-accent-foreground no-underline"
        >
          Nouvelle édition
        </Link>
      </div>
      {isLoading ? (
        <LoadingState />
      ) : isError ? (
        <ErrorState
          message="La liste des éditions n'a pas pu être chargée."
          onRetry={() => void refetch()}
        />
      ) : !data || data.member.length === 0 ? (
        <EmptyState message="Aucune édition pour le moment." />
      ) : (
        <ul className="flex flex-col gap-4 py-6">
          {data.member.map((edition) => (
            <li
              key={edition.id ?? undefined}
              className="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border px-4 py-3"
            >
              <div>
                <p className="font-medium">{edition.title}</p>
                <p className="text-muted">
                  Début : {formatParisDateTime(edition.startsAt)} (heure de
                  Paris)
                </p>
                <p className="text-muted">
                  Ouverture des inscriptions :{" "}
                  {formatParisDateTime(edition.opensAt)} (heure de Paris)
                </p>
              </div>
              <span className="rounded-full bg-surface px-3 py-1">
                {editionStatusLabel(edition.status)}
              </span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
