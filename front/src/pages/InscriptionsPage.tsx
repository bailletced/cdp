import { Link, useParams } from "@tanstack/react-router";
import { useEdition, useEditionInscriptions } from "../api/hooks";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";

function capitalized(value: string): string {
  return 0 === value.length
    ? value
    : value.charAt(0).toUpperCase() + value.slice(1);
}

/**
 * /organisation/editions/$editionId/inscriptions (F16-T12, US-16.2): the
 * inscriptions of the edition, read slot by slot. Each row of the accessible
 * table carries the Paris day of the slot — an edition spans two days, so a
 * slot label alone (« 0h30–1h00 ») needs its day — then the slot label, the
 * display name and the e-mail address of the priant. A priant registered by
 * phone may have no e-mail (F16): a dash is shown then. The list is only
 * reachable by organizers: the back-office guard rejects other profiles, and
 * the API serves the e-mails under /api/admin only (T17, RG-PL-4).
 */
export function InscriptionsPage() {
  const { editionId } = useParams({
    from: "/organisation/editions/$editionId/inscriptions",
  });
  const { data: edition } = useEdition(editionId);
  const {
    data: inscriptions,
    isLoading,
    isError,
    refetch,
  } = useEditionInscriptions(editionId);

  if (isLoading) {
    return (
      <div className="mx-auto w-full max-w-3xl">
        <PageTitle>Inscriptions</PageTitle>
        <LoadingState />
      </div>
    );
  }

  if (isError) {
    return (
      <div className="mx-auto w-full max-w-3xl">
        <PageTitle>Inscriptions</PageTitle>
        <ErrorState
          message="Les inscriptions n’ont pas pu être chargées."
          onRetry={() => void refetch()}
        />
      </div>
    );
  }

  const rows = inscriptions?.member ?? [];
  const pageTitle = edition?.title
    ? `Inscriptions — ${edition.title}`
    : "Inscriptions";

  return (
    <div className="mx-auto w-full max-w-3xl">
      <PageTitle>{pageTitle}</PageTitle>
      <div className="flex flex-col items-start gap-6 py-6">
        <Link
          to="/organisation/editions/$editionId"
          params={{ editionId }}
          className="text-link underline underline-offset-4"
        >
          Retour à l’édition
        </Link>
        {rows.length === 0 ? (
          <p className="text-muted">Aucune inscription pour le moment.</p>
        ) : (
          // On mobile the wide table scrolls in its own white container
          // (bg-surface) instead of extending the page: an horizontal page
          // scroll would reveal the dark canvas behind the body (html keeps
          // the vigil theme background), where the rows are unreadable.
          <div className="w-full overflow-x-auto rounded-[var(--radius)] bg-surface">
            <table className="w-full text-left">
              <thead>
                <tr>
                  <th scope="col" className="px-2 py-1">
                    Jour
                  </th>
                  <th scope="col" className="px-2 py-1">
                    Créneau
                  </th>
                  <th scope="col" className="px-2 py-1">
                    Nom
                  </th>
                  <th scope="col" className="px-2 py-1">
                    Adresse e-mail
                  </th>
                </tr>
              </thead>
              <tbody>
                {rows.map((inscription) => (
                  <tr key={inscription.id} className="border-t border-border">
                    <td className="px-2 py-1">
                      {capitalized(inscription.dayLabel ?? "")}
                    </td>
                    <td className="px-2 py-1">{inscription.slotLabel}</td>
                    <td className="px-2 py-1">{inscription.displayName}</td>
                    <td className="px-2 py-1">{inscription.email ?? "—"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
