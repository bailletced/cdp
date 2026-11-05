import { Button, Input, Label, Link, TextField } from "@heroui/react";
import { type FormEvent, useState } from "react";
import { baseUrl } from "../api/client";
import { useCurrentEdition, useMe } from "../api/hooks";
import { PageTitle } from "../components/PageTitle";
import { PlanningGrid } from "../components/PlanningGrid";
import { SITE_NAME } from "../config/site";
import { POST_LOGIN_TARGET_KEY } from "../lib/postLoginTarget";

const GENERIC_ERROR_MESSAGE = "Une erreur est survenue. Veuillez réessayer.";
const CONFIRMATION_MESSAGE =
  "Un lien de connexion vient de vous être envoyé à cette adresse.";

type SubmissionState = "idle" | "submitting" | "sent" | "error";

/**
 * The event this landing sells, as announced by the community: a 24-hour
 * uninterrupted prayer chain, the Gospel of Mark prayed slot after slot,
 * each slot lasting 30 minutes. (28 Nov 2026 is a Saturday; 24 h later is
 * Sunday 29 Nov at 22:00, Paris time.)
 */
const EVENT = {
  title: "24 heures de prière",
  headline: "Une chaîne de prière ininterrompue",
  fromLabel: "samedi 28 novembre 2026 à 22h00",
  toLabel: "dimanche 29 novembre 2026 à 22h00",
  gospel: "l'Évangile selon saint Marc",
  slotMinutes: 30,
  slotsCount: 48,
} as const;

/**
 * /landing: marketing landing of the prayer-chain event.
 *
 * Typography-led hero (dates + double CTA), the principle in three cards,
 * the three-step journey, then the e-mail form: submitting it creates the
 * account when needed and sends the personal login link (POST /auth/register,
 * F07-T01 — the API always answers 202, never leaking account existence).
 * Before submitting, the page stores "/landing" as post-login target so the
 * link, once clicked, lands the freshly signed-in priant on this page, on
 * the slot grid, ready to pick slots. When an edition is open, its planning
 * grid (US-08.2) closes the page.
 *
 * Public route: a signed-in priant is not redirected away — arriving here
 * after the login link is the intended journey. The form is then replaced
 * by a link to « Mon espace ».
 */
export function EventLandingPage() {
  const editionQuery = useCurrentEdition();
  const me = useMe().data;
  const [state, setState] = useState<SubmissionState>("idle");
  const [message, setMessage] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const formData = new FormData(event.currentTarget);
    const email = String(formData.get("email") ?? "");
    // Honeypot field: legitimate users never see it, bots fill it (F02-T15)
    const website = String(formData.get("website") ?? "");

    // The personal link sent by e-mail must bring the priant back here, on
    // the slot grid — not on /my-space (consumed by the login page)
    localStorage.setItem(POST_LOGIN_TARGET_KEY, "/landing");

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
      {/* Hero — typography-led, the lamp glow of the theme does the rest */}
      <header className="mb-16">
        <p className="mb-3 text-muted">{SITE_NAME}</p>
        <PageTitle>{EVENT.headline}</PageTitle>
        <p className="mt-6 max-w-prose text-xl leading-relaxed">
          {EVENT.title} : du <strong>{EVENT.fromLabel}</strong> au{" "}
          <strong>{EVENT.toLabel}</strong> (heure de Paris), {EVENT.gospel} est
          prié sans interruption, créneau de {EVENT.slotMinutes} minutes après
          créneau.
        </p>
        <div className="mt-8 flex flex-wrap gap-3">
          <a
            href="#inscription"
            className="inline-flex min-h-11 items-center justify-center rounded-[var(--radius)] bg-accent px-5 font-medium text-accent-foreground no-underline hover:bg-accent-hover"
          >
            Je participe
          </a>
          <a
            href="#planning"
            className="inline-flex min-h-11 items-center justify-center rounded-[var(--radius)] border border-border bg-default px-5 font-medium text-default-foreground no-underline hover:bg-surface-secondary"
          >
            Voir les créneaux
          </a>
        </div>
      </header>

      {/* The event in three figures */}
      <ul
        aria-label="L'événement en chiffres"
        className="mb-16 grid gap-4 sm:grid-cols-3"
      >
        {[
          { figure: "24 h", label: "de prière ininterrompue" },
          {
            figure: `${EVENT.slotsCount} × ${EVENT.slotMinutes} min`,
            label: "de créneaux à prendre",
          },
          { figure: "Marc", label: "l'Évangile prié toute la chaîne" },
        ].map((stat) => (
          <li
            key={stat.label}
            className="rounded-2xl border border-separator bg-surface p-6 text-center"
          >
            <p className="font-display text-3xl font-bold text-accent tabular-nums">
              {stat.figure}
            </p>
            <p className="mt-2 text-muted">{stat.label}</p>
          </li>
        ))}
      </ul>

      {/* The principle */}
      <section aria-labelledby="principe" className="mb-16">
        <h2 id="principe" className="mb-8">
          Le principe
        </h2>
        <div className="grid gap-4 sm:grid-cols-3">
          <article className="rounded-2xl border border-separator bg-surface p-6">
            <h3 className="mb-2">Personne ne prie seul</h3>
            <p className="text-muted">
              Pendant ces 24 heures, quelqu'un prie toujours. Votre créneau
              s'enchaîne avec celui du priant suivant : la prière ne s'arrête
              jamais.
            </p>
          </article>
          <article className="rounded-2xl border border-separator bg-surface p-6">
            <h3 className="mb-2">L'Évangile selon saint Marc</h3>
            <p className="text-muted">
              {EVENT.gospel} est lu et prié à travers les créneaux : chaque
              membre qui prend un créneau porte la Parole pendant sa demi-heure.
            </p>
          </article>
          <article className="rounded-2xl border border-separator bg-surface p-6">
            <h3 className="mb-2">30 minutes, un engagement tenable</h3>
            <p className="text-muted">
              Un créneau de {EVENT.slotMinutes} minutes, chez vous, devant
              l'écran. Vous pouvez en prendre plusieurs.
            </p>
          </article>
        </div>
      </section>

      {/* The journey */}
      <section aria-labelledby="participer" className="mb-16">
        <h2 id="participer" className="mb-8">
          Comment participer
        </h2>
        <ol className="grid gap-4 sm:grid-cols-3">
          {[
            {
              step: "1",
              title: "Entrez votre e-mail",
              text: "Vous recevez aussitôt un e-mail de bienvenue avec votre lien personnel.",
            },
            {
              step: "2",
              title: "Cliquez le lien",
              text: "Il vous connecte directement, sans mot de passe.",
            },
            {
              step: "3",
              title: "Choisissez vos créneaux",
              text: "Dans le planning ci-dessous, prenez un ou plusieurs créneaux de 30 minutes.",
            },
          ].map((item) => (
            <li
              key={item.step}
              className="rounded-2xl border border-separator bg-surface p-6"
            >
              <p
                aria-hidden="true"
                className="font-display text-3xl font-bold text-accent"
              >
                {item.step}
              </p>
              <h3 className="mt-2 mb-2">{item.title}</h3>
              <p className="text-muted">{item.text}</p>
            </li>
          ))}
        </ol>
      </section>

      {/* The e-mail form */}
      <section
        aria-labelledby="inscription-titre"
        id="inscription"
        className="mb-16 scroll-mt-8"
      >
        <div className="rounded-2xl border border-separator bg-surface p-6 sm:p-8">
          <h2 id="inscription-titre" className="mb-6">
            Je participe
          </h2>
          {me ? (
            <p>
              Vous êtes connecté·e ({me.email}).{" "}
              <Link href="/my-space">Aller à mon espace</Link> ou choisissez
              directement vos créneaux ci-dessous.
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
                <Input
                  name="email"
                  type="email"
                  required
                  autoComplete="email"
                />
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
                {state === "submitting"
                  ? "Envoi en cours…"
                  : "Recevoir mon lien"}
              </Button>
            </form>
          )}
        </div>
      </section>

      {/* The slot picker */}
      <section
        aria-labelledby="planning-titre"
        id="planning"
        className="scroll-mt-8"
      >
        <h2 id="planning-titre" className="mb-8">
          Les créneaux
        </h2>
        {editionQuery.isPending ? (
          <p className="text-muted">Chargement des créneaux…</p>
        ) : editionQuery.data?.id ? (
          <PlanningGrid editionId={editionQuery.data.id} />
        ) : (
          <div className="rounded-2xl border border-separator bg-surface p-6">
            <p>
              {EVENT.fromLabel} — {EVENT.toLabel} (heure de Paris) :{" "}
              {EVENT.slotsCount} créneaux de {EVENT.slotMinutes} minutes pour
              prier {EVENT.gospel}.
            </p>
            <p className="mt-2 text-muted">
              Le planning s'ouvrira à l'ouverture des inscriptions.
            </p>
          </div>
        )}
      </section>
    </div>
  );
}
