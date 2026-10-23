import { useQuery } from "@tanstack/react-query";
import { Link, useSearch } from "@tanstack/react-router";
import { editionSlotsQueryOptions, useCurrentEdition } from "../api/hooks";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import { parseCreneauxParam, selectSlotsByIds } from "../lib/inscription";
import { withLocalTime } from "../lib/localTime";
import { parsePlanningSlots } from "../lib/planning";

const LINK_CLASS = "text-link underline underline-offset-4";

/**
 * /inscription/confirmation?creneaux=a,b,c (F09-T19, US-09.6): confirmation
 * after a successful registration — récapitulatif des créneaux and « Un
 * e-mail de confirmation vous a été envoyé » (the email itself lands with
 * F11, RG-IN-1).
 *
 * The slot ids ride in the URL, so a reload re-fetches the récapitulatif
 * instead of showing an empty screen. Without the parameter (direct visit),
 * the confirmation message still shows. The placeholders for the link to
 * the text (F12) and the suggestion (F13) are marked as such until those
 * features land.
 */
export function InscriptionConfirmationPage() {
  const { creneaux } = useSearch({ from: "/inscription/confirmation" });
  const ids = parseCreneauxParam(creneaux);
  const editionQuery = useCurrentEdition();

  const edition = editionQuery.data ?? null;
  const slotsQuery = useQuery({
    ...editionSlotsQueryOptions(edition?.id ?? ""),
    enabled: Boolean(edition?.id),
  });

  const slots = parsePlanningSlots(slotsQuery.data?.slots);
  const selected = selectSlotsByIds(slots, ids);

  return (
    <div className="mx-auto w-full max-w-3xl px-4 py-8">
      <PageTitle>Inscription confirmée</PageTitle>
      <p className="mb-8 text-lg">
        Un e-mail de confirmation vous a été envoyé.
      </p>
      {ids.length > 0 ? (
        slotsQuery.isPending ? (
          <LoadingState label="Chargement de vos créneaux…" />
        ) : selected.length > 0 ? (
          <section aria-label="Vos créneaux" className="mb-8">
            <h2 className="mb-4 text-xl font-semibold">Vos créneaux</h2>
            <ul className="grid gap-2">
              {selected.map((slot) => (
                <li
                  key={slot.id}
                  className="rounded-lg border border-border bg-surface p-3"
                >
                  {withLocalTime(slot.label, slot.startsAt)}
                </li>
              ))}
            </ul>
          </section>
        ) : null
      ) : null}
      {/* Placeholders: lien vers le texte (F12) et suggestion (F13) */}
      <section aria-label="Le texte de votre créneau" className="mb-4">
        <p className="rounded border border-dashed p-6 text-center text-default-500">
          Le texte à méditer pendant votre créneau sera bientôt disponible.
        </p>
      </section>
      <section aria-label="Suggérer un texte" className="mb-8">
        <p className="rounded border border-dashed p-6 text-center text-default-500">
          Vous pourrez bientôt suggérer un texte pour votre créneau.
        </p>
      </section>
      <Link to="/" className={LINK_CLASS}>
        Retour à l&apos;accueil
      </Link>
    </div>
  );
}
