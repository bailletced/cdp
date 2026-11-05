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

type SubmissionState = "idle" | "submitting" | "sent" | "error";

/**
 * The event this landing sells, as announced by the community: a 24-hour
 * uninterrupted prayer chain, the Gospel of Mark prayed slot after slot,
 * each slot lasting 30 minutes. (28 Nov 2026 is a Saturday; 24 h later is
 * Sunday 29 Nov at 22:00, Paris time.)
 */
const EVENT = {
  headline: "Une chaîne de prière de 24 heures",
  fromLabel: "samedi 28 novembre à 22h00",
  toLabel: "dimanche 29 novembre à 22h00",
  gospel: "l'Évangile selon saint Marc",
  slotMinutes: 30,
  slotsCount: 48,
} as const;

/* ------------------------------------------------------------------ */
/* Inline SVG icons, stroked in the lamp-gold accent — no icon library  */
/* ------------------------------------------------------------------ */

function IconChain() {
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.8"
      strokeLinecap="round"
      className="h-7 w-7 text-accent"
      aria-hidden="true"
    >
      <path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7" />
      <path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7" />
    </svg>
  );
}

function IconBook() {
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.8"
      strokeLinecap="round"
      strokeLinejoin="round"
      className="h-7 w-7 text-accent"
      aria-hidden="true"
    >
      <path d="M12 6c-1.5-1.3-3.5-2-6-2H4v14h2c2.5 0 4.5.7 6 2 1.5-1.3 3.5-2 6-2h2V4h-2c-2.5 0-4.5.7-6 2Z" />
      <path d="M12 6v14" />
    </svg>
  );
}

function IconClock() {
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.8"
      strokeLinecap="round"
      className="h-7 w-7 text-accent"
      aria-hidden="true"
    >
      <circle cx="12" cy="12" r="9" />
      <path d="M12 7v5l3.5 2" />
    </svg>
  );
}

/**
 * The 24-hour chain as a timeline: 48 half-hour segments from Saturday 22:00
 * to Sunday 22:00, dark at both ends (the night) and lit gold around dawn.
 * Purely illustrative (role="img" with a label): the real, interactive
 * slot picker lives on /my-space once signed in.
 */
function ChainTimeline() {
  const segments = Array.from({ length: EVENT.slotsCount }, (_, i) => i);
  // Dawn (about 7:30) is 9.5 h after the 22:00 start: 39.6 % of the chain
  const dawn = 0.396;

  return (
    <svg
      role="img"
      aria-label="Frise des 24 heures : 48 créneaux de 30 minutes du samedi 28 novembre à 22h00 au dimanche 29 novembre à 22h00, heure de Paris"
      viewBox="0 0 480 56"
      className="w-full"
      preserveAspectRatio="none"
    >
      <defs>
        <linearGradient id="chain-night" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stopColor="#211913" />
          <stop
            offset={`${dawn * 100}%`}
            stopColor="#e6b762"
            stopOpacity="0.9"
          />
          <stop offset="60%" stopColor="#3a2e25" />
          <stop offset="100%" stopColor="#211913" />
        </linearGradient>
      </defs>
      {segments.map((i) => (
        <rect
          key={i}
          x={i * 10 + 0.5}
          y="8"
          width="9"
          height="14"
          rx="2"
          fill="url(#chain-night)"
          stroke="#4a3b2f"
          strokeWidth="0.5"
        />
      ))}
      {/* hour labels: 22h, 0h, 6h, 12h, 18h, 22h */}
      {[
        { at: 0, label: "22h" },
        { at: 2 * 10 * 2, label: "0h" }, // 2 h later
        { at: 8 * 10 * 2, label: "6h" },
        { at: 14 * 10 * 2, label: "12h" },
        { at: 20 * 10 * 2, label: "18h" },
        { at: 470, label: "22h" },
      ].map((tick) => (
        <text
          key={tick.label + tick.at}
          x={tick.at}
          y="42"
          fill="#b9a78f"
          fontSize="11"
          textAnchor={
            tick.at === 0 ? "start" : tick.at === 470 ? "end" : "middle"
          }
        >
          {tick.label}
        </text>
      ))}
      <text x="240" y="53" fill="#b9a78f" fontSize="10" textAnchor="middle">
        samedi 28 nov. 22h → dimanche 29 nov. 22h (heure de Paris)
      </text>
    </svg>
  );
}

/**
 * /landing: marketing landing of the prayer-chain event.
 *
 * Typography-led hero with a single CTA, the 24-hour chain drawn as a
 * timeline, the principle in three cards, the three-step journey, then the
 * e-mail form. Submitting it creates the account when needed and sends the
 * personal login link (POST /auth/register, F07-T01 — the API always
 * answers 202, never leaking account existence); the link signs the priant
 * in on /my-space, where the slot picker (« Ajouter des créneaux ») lives.
 * The landing itself never shows the interactive grid: choosing slots makes
 * sense only once signed in.
 *
 * Public route: a signed-in priant is not redirected away.
 */
export function EventLandingPage() {
  const me = useMe().data;
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
    <div className="mx-auto w-full max-w-3xl px-4 pt-14 pb-10 sm:pt-24">
      {/* Hero — typography-led; the lamp glow of the theme does the rest */}
      <header className="mb-14">
        <p className="mb-4 text-muted">{SITE_NAME}</p>
        <PageTitle>{EVENT.headline}</PageTitle>
        <p className="mt-6 max-w-prose text-xl leading-relaxed">
          Du <strong>{EVENT.fromLabel}</strong> au{" "}
          <strong>{EVENT.toLabel}</strong> (heure de Paris), {EVENT.gospel} est
          prié sans interruption — un membre de la Communion toutes les{" "}
          {EVENT.slotMinutes} minutes.
        </p>
        <div className="mt-9 flex flex-wrap items-center gap-4">
          <a
            href="#inscription"
            className="inline-flex min-h-11 items-center justify-center rounded-[var(--radius)] bg-accent px-6 text-lg font-medium text-accent-foreground no-underline hover:bg-accent-hover"
          >
            Je participe
          </a>
          <Link
            href="#deroulement"
            className="underline decoration-1 underline-offset-4"
          >
            Voir le déroulement
          </Link>
        </div>
      </header>

      {/* The signature visual: the 24-hour chain */}
      <div
        id="deroulement"
        className="mb-16 scroll-mt-8 rounded-2xl border border-separator bg-surface p-6 sm:p-8"
      >
        <ChainTimeline />
        <p className="mt-4 text-muted">
          {EVENT.slotsCount} créneaux de {EVENT.slotMinutes} minutes se
          succèdent sans laisser de trou : à chaque instant des 24 heures,
          quelqu'un prie.
        </p>
      </div>

      {/* The principle */}
      <section aria-labelledby="principe" className="mb-16">
        <h2 id="principe" className="mb-8">
          Le principe
        </h2>
        <div className="grid gap-4 sm:grid-cols-3">
          <article className="rounded-2xl border border-separator bg-surface p-6">
            <IconChain />
            <h3 className="mt-4 mb-2">Personne ne prie seul</h3>
            <p className="text-muted">
              Votre créneau s'enchaîne avec celui du priant suivant : la prière
              ne s'arrête jamais pendant ces 24 heures.
            </p>
          </article>
          <article className="rounded-2xl border border-separator bg-surface p-6">
            <IconBook />
            <h3 className="mt-4 mb-2">L'Évangile selon saint Marc</h3>
            <p className="text-muted">
              {EVENT.gospel} est porté créneau après créneau : chaque membre qui
              prend un créneau prie la Parole pendant sa demi-heure.
            </p>
          </article>
          <article className="rounded-2xl border border-separator bg-surface p-6">
            <IconClock />
            <h3 className="mt-4 mb-2">30 minutes, un engagement tenable</h3>
            <p className="text-muted">
              Une demi-heure chez vous, devant l'écran. Vous pouvez prendre
              plusieurs créneaux.
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
              text: "Il vous connecte directement à votre espace, sans mot de passe.",
            },
            {
              step: "3",
              title: "Choisissez vos créneaux",
              text: "Depuis votre espace, prenez un ou plusieurs créneaux de 30 minutes dans le planning.",
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
        className="scroll-mt-8"
      >
        <div className="rounded-2xl border border-accent/40 bg-surface p-6 sm:p-8">
          <h2 id="inscription-titre" className="mb-2">
            Je participe
          </h2>
          <p className="mb-6 text-muted">
            Laissez votre e-mail : un lien de connexion vous est envoyé pour
            choisir vos créneaux.
          </p>
          {me ? (
            <p>
              Vous êtes connecté·e ({me.email}).{" "}
              <Link href="/my-space">Aller à mon espace</Link> pour choisir vos
              créneaux.
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
                {state === "submitting" ? "Envoi en cours…" : "Je participe"}
              </Button>
            </form>
          )}
        </div>
      </section>
    </div>
  );
}
