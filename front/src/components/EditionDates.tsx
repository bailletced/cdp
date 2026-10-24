import { formatParisDateTime } from "../lib/editions";

/**
 * Start and opening dates of a public edition, in Paris time (US-08.1).
 */
export function EditionDates({
  startsAt,
  opensAt,
}: {
  startsAt: string | null | undefined;
  opensAt: string | null | undefined;
}) {
  return (
    <div className="border-l-2 border-accent pl-5">
      <p className="text-xl">
        Début : {formatParisDateTime(startsAt)} (heure de Paris)
      </p>
      <p className="mt-1 text-muted">
        Ouverture des inscriptions : {formatParisDateTime(opensAt)} (heure de
        Paris)
      </p>
    </div>
  );
}
