/**
 * The last inscription of this tab (F13-T07, US-13.3): the information typed
 * in the form and the slot the API suggests next to it, so « Le prendre
 * aussi » registers in one click, without typing again.
 *
 * Kept in memory only — never in the URL nor in the browser storage: the
 * e-mail address must not outlive the page. A reload forgets it, and the
 * confirmation then offers no suggestion.
 */

/** An empty slot suggested by POST /api/inscriptions (F13-T04). */
export interface SlotSuggestion {
  slotId: string;
  startsAt: string;
  endsAt: string;
  label: string;
  textTitle: string | null;
  textSource: string | null;
}

export interface LastInscription {
  displayName: string;
  email: string;
  consent: boolean;
  suggestion: SlotSuggestion | null;
}

let last: LastInscription | null = null;

export function rememberInscription(inscription: LastInscription): void {
  last = inscription;
}

export function lastInscription(): LastInscription | null {
  return last;
}

/** « Non merci »: the information stays, the suggestion goes. */
export function dismissSuggestion(): void {
  if (last) {
    last = { ...last, suggestion: null };
  }
}

/** Narrows the suggestion of the payload; null when absent or malformed. */
export function parseSlotSuggestion(value: unknown): SlotSuggestion | null {
  if (typeof value !== "object" || value === null) {
    return null;
  }

  const candidate = value as Record<string, unknown>;

  if (
    typeof candidate.slotId !== "string" ||
    typeof candidate.startsAt !== "string" ||
    typeof candidate.endsAt !== "string" ||
    typeof candidate.label !== "string"
  ) {
    return null;
  }

  return {
    slotId: candidate.slotId,
    startsAt: candidate.startsAt,
    endsAt: candidate.endsAt,
    label: candidate.label,
    textTitle:
      typeof candidate.textTitle === "string" ? candidate.textTitle : null,
    textSource:
      typeof candidate.textSource === "string" ? candidate.textSource : null,
  };
}
