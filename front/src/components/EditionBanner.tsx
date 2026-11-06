import {
  EDITION_DURATION_MS,
  editionStatusAt,
  formatCountdown,
} from "../lib/editionStatus";
import { formatParisDateTime } from "../lib/editions";
import { useNow } from "../lib/serverClock";

/**
 * « La chaîne » at a glance (F14): where we stand in the current edition.
 *
 * - before it starts: the countdown to the opening, big and golden — the
 *   figure of an expectation (role="timer", deliberately silent for
 *   screen readers: it ticks every second);
 * - during the 24 hours: « La chaîne est en cours », with the countdown
 *   of what is left to pray;
 * - once over: a thank-you, the edition behind us.
 *
 * Mounted with the server-corrected clock (US-08.4): a skewed device
 * cannot mislead the countdown. Without a current edition, nothing.
 */
export function EditionBanner({ startsAt }: { startsAt: string }) {
  const nowMs = useNow();
  const status = editionStatusAt(startsAt, nowMs);

  const startLabel = formatParisDateTime(startsAt);

  if ("upcoming" === status) {
    return (
      <section
        aria-label="État de l'édition"
        className="mb-10 rounded-2xl border border-separator border-l-accent border-l-4 bg-surface p-6 sm:p-7"
      >
        <p className="text-muted">La chaîne commence</p>
        <p className="mt-1 font-display text-2xl font-bold">
          {startLabel} (heure de Paris)
        </p>
        <p
          role="timer"
          aria-label="Temps restant avant le début de la chaîne"
          className="mt-4 font-display text-4xl font-bold lining-nums tabular-nums text-accent sm:text-5xl"
        >
          {formatCountdown(new Date(startsAt).getTime() - nowMs)}
        </p>
      </section>
    );
  }

  if ("live" === status) {
    const endsAt = new Date(startsAt).getTime() + EDITION_DURATION_MS;

    return (
      <section
        aria-label="État de l'édition"
        className="mb-10 rounded-2xl border border-separator border-l-accent border-l-4 bg-surface p-6 sm:p-7"
      >
        <p className="font-display text-2xl font-bold text-accent">
          La chaîne est en cours
        </p>
        <p className="mt-1">
          Elle se termine {formatParisDateTime(new Date(endsAt).toISOString())}{" "}
          (heure de Paris) — encore {formatCountdown(endsAt - nowMs)} de prière.
        </p>
      </section>
    );
  }

  return (
    <section
      aria-label="État de l'édition"
      className="mb-10 rounded-2xl border border-separator bg-surface p-6 sm:p-7"
    >
      <p className="font-display text-2xl font-bold">
        Cette édition est terminée
      </p>
      <p className="mt-1 text-muted">
        Merci d'avoir prié avec nous. Les textes restent à relire depuis vos
        créneaux.
      </p>
    </section>
  );
}
