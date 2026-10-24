import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Link, useNavigate } from "@tanstack/react-router";
import { useRef, useState } from "react";
import { baseUrl } from "../api/client";
import { getApiErrorDetail } from "../api/errors";
import {
  myInscriptionsQueryOptions,
  useCancelInscription,
  useViewer,
} from "../api/hooks";
import { ConfirmDialog } from "../components/ConfirmDialog";
import { CurrentEditionPlanning } from "../components/CurrentEditionPlanning";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import { ReadTextLink } from "../components/ReadTextLink";
import { RenameDialog } from "../components/RenameDialog";
import { withLocalTime } from "../lib/localTime";

const LINK_CLASS = "text-link underline underline-offset-4";

/** One of my slots, as GET /api/me/inscriptions describes it. */
interface MySlot {
  id: string;
  slotId: string;
  startsAt: string;
  label: string;
  dayLabel: string;
  textTitle: string | null;
  cancellable: boolean;
}

/** The OpenAPI schema types the list as unknown[]: keep the well-formed ones. */
function parseMySlots(inscriptions: unknown): MySlot[] {
  if (!Array.isArray(inscriptions)) {
    return [];
  }

  return inscriptions.flatMap((item: Record<string, unknown>) =>
    typeof item?.id === "string" &&
    typeof item.slotId === "string" &&
    typeof item.startsAt === "string" &&
    typeof item.label === "string" &&
    typeof item.dayLabel === "string"
      ? [
          {
            id: item.id,
            slotId: item.slotId,
            startsAt: item.startsAt,
            label: item.label,
            dayLabel: item.dayLabel,
            textTitle:
              typeof item.textTitle === "string" ? item.textTitle : null,
            // RG-EP-1: the API says whether the slot can still be cancelled
            cancellable:
              Array.isArray(item.actions) && item.actions.includes("cancel"),
          },
        ]
      : [],
  );
}

/** « samedi 28 novembre » → « Samedi 28 novembre » */
function capitalized(value: string): string {
  return value.charAt(0).toUpperCase() + value.slice(1);
}

/**
 * « Mes créneaux » (F14-T07, US-14.1): my slots of the public edition, each
 * with its day, its schedule (and my local time abroad) and its text.
 *
 * « Ajouter des créneaux » leads to the planning below — the inscription
 * form is pre-filled from the session (RG-IN-7) — and a single « Ajouter mes
 * créneaux à mon agenda » downloads the file of all my slots (F14-T10,
 * arbitrage Q15: no button per slot).
 *
 * « Me désinscrire » (F14-T09, US-14.3) until the slot starts: after a
 * confirmation, the outcome is announced politely on the screen — no e-mail
 * is sent — and the focus moves to the list heading, the button being gone.
 */
function MySlots() {
  const query = useQuery(myInscriptionsQueryOptions);
  const cancelInscription = useCancelInscription();
  const [toCancel, setToCancel] = useState<MySlot | null>(null);
  const [outcome, setOutcome] = useState<string | null>(null);
  const [failure, setFailure] = useState<string | null>(null);
  const headingRef = useRef<HTMLHeadingElement>(null);

  async function confirmCancel() {
    const slot = toCancel;
    setToCancel(null);

    if (!slot) {
      return;
    }

    setOutcome(null);
    setFailure(null);

    try {
      await cancelInscription.mutateAsync(slot.id);
      setOutcome(`Vous êtes désinscrit du créneau de ${slot.label}.`);
    } catch (error) {
      setFailure(
        getApiErrorDetail(error) ??
          "La désinscription n'a pas pu être enregistrée. Réessayez dans un instant.",
      );
    }

    headingRef.current?.focus();
  }

  if (query.isPending) {
    return <LoadingState label="Chargement de vos créneaux…" />;
  }

  if (query.isError) {
    return (
      <ErrorState
        message="Vos créneaux n'ont pas pu être chargés."
        onRetry={() => void query.refetch()}
      />
    );
  }

  if (query.data.status === "expired") {
    return (
      <p className="text-lg">
        Votre session a expiré : recevez votre lien personnel pour retrouver vos
        créneaux.{" "}
        <Link to="/receive-my-link" className={LINK_CLASS}>
          Recevoir mon lien personnel
        </Link>
      </p>
    );
  }

  if (query.data.status === "no-edition") {
    return null;
  }

  const { space } = query.data;
  const slots = parseMySlots(space.inscriptions);

  return (
    <>
      <div className="mb-8 flex flex-wrap items-center gap-4">
        <p className="font-display text-3xl font-bold">{space.displayName}</p>
        <RenameDialog currentName={space.displayName ?? ""} />
      </div>
      <section aria-labelledby="mes-creneaux" className="mb-12">
        <h2
          id="mes-creneaux"
          ref={headingRef}
          tabIndex={-1}
          className="mb-4 outline-none"
        >
          Mes créneaux
        </h2>
        <output className={outcome ? "mb-4 block text-lg" : "sr-only"}>
          {outcome}
        </output>
        {failure ? (
          <p role="alert" className="mb-4 text-danger">
            {failure}
          </p>
        ) : null}
        {slots.length === 0 ? (
          <p className="text-lg">
            Vous n'avez pas encore de créneau.{" "}
            <a href="#ajouter-des-creneaux" className={LINK_CLASS}>
              Choisir un créneau
            </a>
          </p>
        ) : (
          <ul className="grid gap-3">
            {slots.map((slot) => (
              <li
                key={slot.id}
                className="rounded-2xl border border-separator bg-surface p-4 sm:p-5"
              >
                <p className="text-muted">{capitalized(slot.dayLabel)}</p>
                <p className="font-display text-2xl font-bold lining-nums tabular-nums">
                  {withLocalTime(slot.label, slot.startsAt)}
                </p>
                {slot.textTitle ? (
                  <p className="mt-2">
                    {slot.textTitle}{" "}
                    <ReadTextLink slotId={slot.slotId} label={slot.label} />
                  </p>
                ) : (
                  <p className="mt-2 text-muted">
                    Le texte de ce créneau sera bientôt disponible
                  </p>
                )}
                {slot.cancellable ? (
                  <button
                    type="button"
                    onClick={() => setToCancel(slot)}
                    className="link mt-3 min-h-11 text-muted"
                  >
                    Me désinscrire
                    <span className="sr-only"> du créneau de {slot.label}</span>
                  </button>
                ) : null}
              </li>
            ))}
          </ul>
        )}
        <div className="mt-6 flex flex-wrap gap-3">
          <a
            href="#ajouter-des-creneaux"
            className="inline-flex min-h-11 items-center rounded-[var(--radius)] border border-border px-5 font-medium"
          >
            Ajouter des créneaux
          </a>
          {slots.length > 0 ? (
            <a
              href={`${baseUrl}/api/me/calendar.ics`}
              download="mes-creneaux.ics"
              className="inline-flex min-h-11 items-center rounded-[var(--radius)] bg-accent px-5 font-medium text-accent-foreground"
            >
              Ajouter mes créneaux à mon agenda
            </a>
          ) : null}
        </div>
      </section>
      <ConfirmDialog
        isOpen={toCancel !== null}
        onClose={() => setToCancel(null)}
        onConfirm={() => void confirmCancel()}
        title="Me désinscrire"
        confirmLabel="Me désinscrire"
        cancelLabel="Garder ce créneau"
        isDanger
      >
        {toCancel ? (
          <p>
            Vous ne serez plus inscrit sur le créneau de {toCancel.label} du{" "}
            {toCancel.dayLabel}. Aucun e-mail ne vous sera envoyé.
          </p>
        ) : null}
      </ConfirmDialog>
    </>
  );
}

/**
 * /my-space (F02-T19, F14-T07): « Mon espace » — my name, my slots of the
 * current edition, then the planning to add slots. The route guard
 * redirects visitors (and a device pass without session) to the landing
 * page; should the session expire while the page is open, it invites to
 * receive the personal link again.
 */
export function MySpacePage() {
  const { viewer, isLoading } = useViewer();
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const [loggingOut, setLoggingOut] = useState(false);

  async function handleLogout() {
    setLoggingOut(true);
    try {
      await fetch(`${baseUrl}/auth/logout`, {
        method: "POST",
        credentials: "same-origin",
      });
    } finally {
      // Drop the cached identity: the next GET /api/me answers 401 (visitor)
      queryClient.removeQueries({ queryKey: ["me"] });
      navigate({ to: "/", replace: true });
    }
  }

  if (isLoading || !viewer) {
    return (
      <div className="mx-auto w-full max-w-3xl px-4 py-8">
        <PageTitle>Mon espace</PageTitle>
        <LoadingState />
      </div>
    );
  }

  return (
    <div className="mx-auto w-full max-w-3xl px-4 py-8">
      <PageTitle>Mon espace</PageTitle>
      <p className="mb-6 text-muted">{viewer.email}</p>
      <MySlots />
      <section aria-labelledby="ajouter-des-creneaux-titre">
        <h2 id="ajouter-des-creneaux-titre" className="mb-4 scroll-mt-24">
          Ajouter des créneaux
        </h2>
        <div id="ajouter-des-creneaux" className="scroll-mt-24">
          <CurrentEditionPlanning />
        </div>
      </section>
      <button
        type="button"
        onClick={handleLogout}
        disabled={loggingOut}
        className="mt-8"
      >
        {loggingOut ? "Déconnexion en cours…" : "Me déconnecter"}
      </button>
    </div>
  );
}
