import { Button, Input, Label, Link, TextField } from "@heroui/react";
import { type FormEvent, useState } from "react";
import { baseUrl } from "../api/client";
import { useCurrentEdition, useMe } from "../api/hooks";
import { ErrorState } from "../components/ErrorState";
import { LoadingState } from "../components/LoadingState";
import { PageTitle } from "../components/PageTitle";
import { PlanningGrid } from "../components/PlanningGrid";
import { SITE_NAME } from "../config/site";
import { formatParisDateTime } from "../lib/editions";

const GENERIC_ERROR_MESSAGE = "Une erreur est survenue. Veuillez réessayer.";
const CONFIRMATION_MESSAGE =
  "Un lien de connexion vient de vous être envoyé à cette adresse.";

type SubmissionState = "idle" | "submitting" | "sent" | "error";

/**
 * / (F08-T07, F07-T01): landing page for the 24-hour prayer chain.
 *
 * The header shows the current public edition — community name, title,
 * dates and presentation (US-08.1) — or, when no edition is open, a sober
 * presentation of the Communion with « La prochaine édition sera bientôt
 * annoncée » and a « Recevoir mon lien » link (arbitrage Q18). Below the
 * header of an open edition, the planning grid of its slots (US-08.2).
 *
 * Below the header, a single-field registration form: submitting an email
 * creates the account (if needed) and sends a personal login link. The API
 * always answers 202 with the same message, whether the account was created
 * or already existed: this page never leaks account existence.
 */
export function LandingPage() {
  const editionQuery = useCurrentEdition();
  // An open session already has its personal link: the email form is only
  // offered to visitors, a signed-in priant is sent to « Mon espace » (§5.2-5)
  const { data: me } = useMe();
  const [state, setState] = useState<SubmissionState>("idle");
  const [message, setMessage] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const email = String(formData.get("email") ?? "");
    // Honeypot field: legitimate users never see it, bots fill it (F02-T15)
    const website = String(formData.get("website") ?? "");

    setState("submitting");

    try {
      const response = await fetch(`${baseUrl}/auth/register`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ email, website }),
      });

      if (!response.ok) {
        throw new Error(`Unexpected status ${response.status}`);
      }

      const body = (await response.json()) as { message?: string };
      setMessage(body.message ?? CONFIRMATION_MESSAGE);
      setState("sent");
    } catch {
      setMessage(GENERIC_ERROR_MESSAGE);
      setState("error");
    }
  }

  return (
    <div className="mx-auto w-full max-w-3xl px-4 pt-10 pb-8 sm:pt-16">
      {editionQuery.isPending ? (
        <>
          <PageTitle>24 heures de prière</PageTitle>
          <LoadingState />
        </>
      ) : editionQuery.isError ? (
        <>
          <PageTitle>24 heures de prière</PageTitle>
          <ErrorState
            message="L'édition n'a pas pu être chargée."
            onRetry={() => void editionQuery.refetch()}
          />
        </>
      ) : editionQuery.data ? (
        <>
          <header className="mb-14">
            <p className="mb-3 text-muted">{SITE_NAME}</p>
            <PageTitle>
              {editionQuery.data.title ?? "24 heures de prière"}
            </PageTitle>
            <div className="mt-8 border-l-2 border-accent pl-5">
              <p className="text-xl">
                Début : {formatParisDateTime(editionQuery.data.startsAt)} (heure
                de Paris)
              </p>
              <p className="mt-1 text-muted">
                Ouverture des inscriptions :{" "}
                {formatParisDateTime(editionQuery.data.opensAt)} (heure de
                Paris)
              </p>
            </div>
            {editionQuery.data.presentation ? (
              <p className="mt-8 max-w-prose text-xl leading-relaxed">
                {editionQuery.data.presentation}
              </p>
            ) : null}
          </header>
          {editionQuery.data.id ? (
            <PlanningGrid editionId={editionQuery.data.id} />
          ) : null}
        </>
      ) : (
        <header className="mb-12">
          <PageTitle>24 heures de prière</PageTitle>
          <p className="mt-6 mb-8 max-w-prose text-xl leading-relaxed">
            Rejoignez la chaîne de prière ininterrompue de 24 heures de la{" "}
            {SITE_NAME}. Chaque priant s'engage sur un créneau de 30 minutes.
          </p>
          <p className="mb-2">La prochaine édition sera bientôt annoncée.</p>
          <p>
            <Link href="/receive-my-link">Recevoir mon lien</Link>
          </p>
        </header>
      )}

      <div className="rounded-2xl border border-separator bg-surface p-6 sm:p-8">
        {me ? (
          <p>
            Retrouvez vos créneaux dans <Link href="/my-space">Mon espace</Link>
            .
          </p>
        ) : state === "sent" || state === "error" ? (
          <output>{message}</output>
        ) : (
          <form
            onSubmit={handleSubmit}
            className="flex flex-col items-start gap-6"
          >
            <TextField className="w-full max-w-md">
              <Label>Adresse e-mail</Label>
              <Input name="email" type="email" required autoComplete="email" />
            </TextField>
            {/* Honeypot: hidden from humans, filled by bots */}
            <input
              type="text"
              name="website"
              tabIndex={-1}
              autoComplete="off"
              aria-hidden="true"
              hidden
            />
            <Button
              type="submit"
              variant="primary"
              isDisabled={state === "submitting"}
            >
              {state === "submitting" ? "Envoi en cours…" : "S'inscrire"}
            </Button>
          </form>
        )}
      </div>
    </div>
  );
}
