import { useQuery } from "@tanstack/react-query";
import { Link, useSearch } from "@tanstack/react-router";
import { editionSlotsQueryOptions, useCurrentEdition } from "../api/hooks";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import { ReadTextLink } from "../components/ReadTextLink";
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
 * the confirmation message still shows. Each slot links to its text once
 * one is attributed and the device may read it — the pass it just received
 * covers it (F12-T05). The placeholder for the suggestion (F13) is marked
 * as such until that feature lands.
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
                  className="grid gap-1 rounded-lg border border-border bg-surface p-3"
                >
                  <span className="font-bold">
                    {withLocalTime(slot.label, slot.startsAt)}
                  </span>
                  {slot.textTitle ? (
                    <span>
                      {slot.textTitle}
                      {slot.mine ? (
                        <>
                          {" "}
                          <ReadTextLink slotId={slot.id} label={slot.label} />
                        </>
                      ) : null}
                    </span>
                  ) : (
                    <span className="text-muted">
                      Le texte de ce créneau sera bientôt disponible
                    </span>
                  )}
                </li>
              ))}
            </ul>
          </section>
        ) : null
      ) : null}
      {/* Placeholder: suggestion (F13) */}
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
