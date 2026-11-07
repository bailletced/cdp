import { Button, Input, Label, Link, TextField } from "@heroui/react";
import { type FormEvent, useState } from "react";
import { baseUrl } from "../api/client";
import { useMe } from "../api/hooks";
import { PageTitle } from "../components/PageTitle";
import { SITE_NAME } from "../config/site";

const GENERIC_ERROR_MESSAGE = "Une erreur est survenue. Veuillez réessayer.";
// Same wording as the API's own 202 body (RegisterController)
const CONFIRMATION_MESSAGE =
  "Un lien de connexion vient de vous être envoyé à cette adresse.";

/**
 * The event this landing is about: a 24-hour uninterrupted prayer chain, the
 * Gospel of Mark prayed slot after slot, each slot lasting 30 minutes.
 * (28 Nov 2026 is a Saturday; 24 h later is Sunday 29 Nov at 22:00, Paris
 * time.)
 */
const EVENT = {
  headline: "Une chaîne de prière de 24 heures",
  fromLabel: "samedi 28 novembre à 22h",
  toLabel: "dimanche 29 novembre à 22h",
} as const;

const SIGNUP_HINT =
  "Un e-mail suffit : votre lien de connexion arrive aussitôt, pour choisir votre créneau. Sans mot de passe.";

// E-mail clients are quick to misfile an automatic e-mail: say it before the
// user concludes nothing was sent
const SPAM_HINT =
  "L'e-mail peut se glisser parmi vos indésirables — pensez à y regarder, et à nous marquer comme expéditeur sûr.";

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
        <output className="flex flex-col gap-1">
          <span>{message}</span>
          {state === "sent" ? (
            <span className="text-muted">{SPAM_HINT}</span>
          ) : null}
        </output>
      ) : null}
    </form>
  );
}

/**
 * /landing: the event landing, written for Catholics who need no convincing
 * — the wording speaks of a vigil, of half an hour offered to the Lord, not
 * of accounts and forms.
 *
 * A light, airful page (the `event-landing` body class swaps the dark
 * vigil theme for a warm ivory treatment, scoped to this route) whose one
 * job is collecting e-mail addresses: the form sits in the hero, next to
 * Christ the King and the Queen Virgin — Hans von Kulmbach, Pławno
 * triptych — and again after the pitch. Choosing a slot happens on
 * /my-space once the welcome e-mail's link has signed the visitor in.
 */
export function EventLandingPage() {
  const me = useMe().data;

  return (
    <>
      {/* Hero: the event, Our Lady, and the form straight away */}
      <header className="mx-auto w-full max-w-5xl px-4 pt-12 pb-4 sm:pt-16">
        <div className="grid items-center gap-10 md:grid-cols-[3fr_2fr]">
          <div>
            <p className="text-muted">{SITE_NAME}</p>
            <div className="mt-6">
              <PageTitle>{EVENT.headline}</PageTitle>
            </div>
            <p className="mt-6 max-w-prose text-xl leading-relaxed">
              Du <strong>{EVENT.fromLabel}</strong> au{" "}
              <strong>{EVENT.toLabel}</strong> (heure de Paris), la Communion
              veille sans interruption : l'Évangile selon saint Marc est prié
              trente minutes après trente minutes, chacun offrant une demi-heure
              au Seigneur.
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
                  <p className="mt-1 text-muted">{SPAM_HINT}</p>
                </>
              )}
            </div>
          </div>
          <figure className="mx-auto w-full max-w-xs md:max-w-none">
            <img
              src="/img/christ-roi.jpg"
              alt="Le Christ roi et la Vierge reine, couronnés, par Hans von Kulmbach"
              className="w-full rounded-2xl border border-separator object-cover"
              // Do not let the image push the form below the fold
              decoding="async"
            />
            <figcaption className="mt-3 text-center text-muted">
              Le Christ roi et la Vierge reine — Hans von Kulmbach, début du XVI
              <sup>e</sup> siècle (triptyque de Pławno, musée national de
              Varsovie)
            </figcaption>
          </figure>
        </div>
      </header>

      <div className="mx-auto w-full max-w-2xl px-4 pb-12">
        {/* What the vigil is, in three short editorial paragraphs */}
        <section aria-labelledby="levenement" className="mt-16">
          <h2 id="levenement" className="mb-8">
            Une veillée continue
          </h2>
          <div className="max-w-prose space-y-8">
            <div className="border-t border-separator pt-6">
              <h3 className="mb-2">Quelqu'un prie à chaque instant</h3>
              <p>
                Chaque créneau de 30 minutes s'enchaîne au suivant : de la nuit
                du samedi à la nuit du dimanche, la prière ne s'interrompt pas.
                Une veillée que la Communion porte ensemble.
              </p>
            </div>
            <div className="border-t border-separator pt-6">
              <h3 className="mb-2">
                L'Évangile selon saint Marc, d'un bout à l'autre
              </h3>
              <p>
                Toute la chaîne prie l'Évangile selon saint Marc : le passage de
                votre créneau vous est remis à votre espace, avec le texte à
                méditer et ses pistes d'oraison.
              </p>
            </div>
            <div className="border-t border-separator pt-6">
              <h3 className="mb-2">Une demi-heure, offerte</h3>
              <p>
                De chez vous, comme vous priez — oraison, chapelet, simple
                silence. Trente minutes offertes au Seigneur, c'est tout ce que
                la chaîne demande.
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
                text: "Votre lien de connexion arrive aussitôt dans votre boîte.",
              },
              {
                step: "2",
                title: "Ouvrez le lien",
                text: "Il vous ouvre votre espace, sans mot de passe à retenir.",
              },
              {
                step: "3",
                title: "Choisissez votre créneau",
                text: "Dans le planning : votre demi-heure — ou plusieurs, si le Seigneur vous en donne le loisir.",
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
          aria-labelledby="offrir-titre"
          id="inscription-fin"
          className="mt-20 scroll-mt-8"
        >
          <h2 id="offrir-titre" className="mb-4">
            Offrez une demi-heure
          </h2>
          <p className="mb-6 max-w-prose">
            Rien à installer, rien à régler : votre e-mail suffit, la chaîne
            vous attend.
          </p>
          {me ? (
            <p>
              <Link href="/my-space">
                Choisissez vos créneaux dans mon espace
              </Link>
              .
            </p>
          ) : (
            <>
              <EmailCapture submitLabel="Je m'inscris" />
              <p className="mt-3 text-muted">{SPAM_HINT}</p>
            </>
          )}
        </section>
      </div>
    </>
  );
}
