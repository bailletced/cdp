import { Button, Input, Label, Link, TextField } from "@heroui/react";
import { type FormEvent, useEffect, useState } from "react";
import { baseUrl } from "../api/client";
import { useMe } from "../api/hooks";
import { PageTitle } from "../components/PageTitle";
import { SITE_NAME } from "../config/site";

const GENERIC_ERROR_MESSAGE = "Une erreur est survenue. Veuillez réessayer.";
// Same wording as the API's own 202 body (RegisterController)
const CONFIRMATION_MESSAGE =
  "Un lien de connexion vient de vous être envoyé à cette adresse.";

/**
 * The event this landing sells, as announced by the community: a 24-hour
 * uninterrupted prayer chain, the Gospel of Mark prayed slot after slot,
 * each slot lasting 30 minutes. (28 Nov 2026 is a Saturday; 24 h later is
 * Sunday 29 Nov at 22:00, Paris time.)
 */
const EVENT = {
  headline: "Une chaîne de prière de 24 heures",
  fromLabel: "samedi 28 novembre à 22h",
  toLabel: "dimanche 29 novembre à 22h",
} as const;

const SIGNUP_HINT =
  "Un e-mail suffit, sans mot de passe : vous recevez un lien pour choisir vos créneaux.";

type SubmissionState = "idle" | "submitting" | "sent" | "error";

/**
 * The e-mail capture form, the one job of this page: submitting it creates
 * the account when needed and sends the personal login link (POST
 * /auth/register, F07-T01 — the API always answers 202, never leaking
 * account existence). The honeypot field keeps the bots out (F02-T15).
 */
function EmailCapture({ submitLabel }: { submitLabel: string }) {
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
    <form
      onSubmit={handleSubmit}
      className="flex flex-col gap-4 sm:flex-row sm:items-end"
    >
      <TextField className="flex-1">
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
        {state === "submitting" ? "Envoi en cours…" : submitLabel}
      </Button>
      {state === "sent" || state === "error" ? (
        <output>{message}</output>
      ) : null}
    </form>
  );
}

/**
 * /landing: the event landing, written for people who know nothing yet.
 *
 * A light, airful page (the `event-landing` body class swaps the dark
 * vigil theme for a warm ivory treatment, scoped to this route) whose one
 * job is collecting e-mail addresses: the form sits in the hero, above
 * the fold, and again after the pitch. No slot grid, no jargon — choosing
 * a slot happens on /my-space once the welcome e-mail's link has signed
 * the visitor in.
 */
export function EventLandingPage() {
  const me = useMe().data;

  useEffect(() => {
    document.body.classList.add("event-landing");

    return () => {
      document.body.classList.remove("event-landing");
    };
  }, []);

  return (
    <div className="mx-auto w-full max-w-2xl px-4 pt-14 pb-12 sm:pt-20">
      {/* Hero: what it is, when, and the form straight away */}
      <header>
        <p className="text-muted">{SITE_NAME}</p>
        <div className="mt-6">
          <PageTitle>{EVENT.headline}</PageTitle>
        </div>
        <p className="mt-6 max-w-prose text-xl leading-relaxed">
          Du <strong>{EVENT.fromLabel}</strong> au{" "}
          <strong>{EVENT.toLabel}</strong> (heure de Paris), des personnes se
          relaient pour prier sans interruption : l'Évangile selon saint Marc,
          porté par tous, trente minutes après trente minutes.
        </p>
        <div id="inscription" className="mt-8 scroll-mt-8">
          {me ? (
            <p className="text-lg">
              Vous êtes déjà inscrit·e.{" "}
              <Link href="/my-space">
                Choisissez vos créneaux dans mon espace
              </Link>
              .
            </p>
          ) : (
            <>
              <EmailCapture submitLabel="Je participe" />
              <p className="mt-3 text-muted">{SIGNUP_HINT}</p>
            </>
          )}
        </div>
      </header>

      {/* What the event is, in three short editorial paragraphs */}
      <section aria-labelledby="levenement" className="mt-20">
        <h2 id="levenement" className="mb-8">
          Une veillée continue
        </h2>
        <div className="max-w-prose space-y-8">
          <div className="border-t border-separator pt-6">
            <h3 className="mb-2">Pendant 24 heures, quelqu'un prie toujours</h3>
            <p>
              Chaque créneau de 30 minutes s'enchaîne avec le suivant : du
              samedi soir au dimanche soir, la prière ne s'arrête jamais une
              minute.
            </p>
          </div>
          <div className="border-t border-separator pt-6">
            <h3 className="mb-2">
              L'Évangile selon saint Marc, porté par tous
            </h3>
            <p>
              Le récit de l'Évangile selon Marc est prié d'un bout à l'autre de
              la chaîne. Chaque personne qui prend un créneau porte la Parole
              pendant sa demi-heure.
            </p>
          </div>
          <div className="border-t border-separator pt-6">
            <h3 className="mb-2">30 minutes à vous</h3>
            <p>
              Un créneau de 30 minutes, de chez vous, dans votre façon de prier.
              Rien à préparer — le texte du créneau vous est donné sur la page.
            </p>
          </div>
        </div>
      </section>

      {/* How it happens, in three steps */}
      <section aria-labelledby="comment" className="mt-20">
        <h2 id="comment" className="mb-8">
          Comment ça se passe
        </h2>
        <ol className="grid gap-6 sm:grid-cols-3">
          {[
            {
              step: "1",
              title: "Entrez votre e-mail",
              text: "Un e-mail de bienvenue arrive aussitôt, avec votre lien personnel.",
            },
            {
              step: "2",
              title: "Cliquez le lien",
              text: "Il vous connecte, sans mot de passe à retenir.",
            },
            {
              step: "3",
              title: "Choisissez vos créneaux",
              text: "Dans le planning, prenez le créneau de 30 minutes qui vous convient.",
            },
          ].map((item) => (
            <li key={item.step} className="border-t border-separator pt-4">
              <p className="font-display text-2xl font-bold text-accent">
                {item.step}
              </p>
              <h3 className="mt-1 mb-1 text-lg font-medium leading-snug">
                {item.title}
              </h3>
              <p className="text-muted">{item.text}</p>
            </li>
          ))}
        </ol>
      </section>

      {/* Second capture, for the readers who made it this far */}
      <section
        aria-labelledby="inscription-fin-titre"
        id="inscription-fin"
        className="mt-20 scroll-mt-8"
      >
        <h2 id="inscription-fin-titre" className="mb-4">
          Rejoignez la chaîne
        </h2>
        <p className="mb-6 max-w-prose">
          Rien à régler, rien à installer : laissez votre e-mail, la chaîne vous
          attend.
        </p>
        {me ? (
          <p>
            <Link href="/my-space">
              Choisissez vos créneaux dans mon espace
            </Link>
            .
          </p>
        ) : (
          <EmailCapture submitLabel="Je m'inscris" />
        )}
      </section>
    </div>
  );
}
