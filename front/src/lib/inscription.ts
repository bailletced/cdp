import { z } from "zod";
import type { PlanningSlot } from "./planning";

/**
 * Inscription (F09-T15/T16): shared helpers for the /inscription pages.
 */

/**
 * Parses the ?creneaux=… search parameter into slot ids (F09-T15). The
 * planning grid (F08-T10) builds the parameter as a comma-separated string;
 * the router may also hand over an already-parsed array. Empty segments,
 * whitespace and duplicates are dropped.
 */
export function parseCreneauxParam(raw: unknown): string[] {
  const parts = Array.isArray(raw)
    ? raw
    : typeof raw === "string"
      ? raw.split(",")
      : [];

  const ids: string[] = [];

  for (const part of parts) {
    if (typeof part !== "string") {
      continue;
    }

    const id = part.trim();

    if (id.length > 0 && !ids.includes(id)) {
      ids.push(id);
    }
  }

  return ids;
}

/**
 * Selects the slots matching the given ids, in chronological (rank) order
 * whatever the order of the parameter (F09-T15).
 */
export function selectSlotsByIds(
  slots: PlanningSlot[],
  ids: string[],
): PlanningSlot[] {
  return slots
    .filter((slot) => ids.includes(slot.id))
    .sort((a, b) => a.rank - b.rank);
}

/** Form values of the inscription form (F09-T16). */
export interface InscriptionFormValues {
  displayName: string;
  email: string;
  consent: boolean;
  website: string;
}

export const DISPLAY_NAME_REQUIRED_MESSAGE = "Le nom affiché est obligatoire.";
export const DISPLAY_NAME_LENGTH_MESSAGE =
  "Le nom affiché doit comporter entre 1 et 80 caractères.";
export const EMAIL_REQUIRED_MESSAGE = "L'adresse e-mail est obligatoire.";
export const EMAIL_LENGTH_MESSAGE =
  "L'adresse e-mail doit comporter entre 3 et 254 caractères.";
export const EMAIL_INVALID_MESSAGE = "L'adresse e-mail n'est pas valide.";
export const CONSENT_REQUIRED_MESSAGE =
  "Le consentement est obligatoire pour s’inscrire.";

/**
 * Validation schema of the inscription form (F09-T16): a Standard Schema
 * (zod) bounded by the OpenAPI contract of POST /api/inscriptions —
 * displayName 1–80, email 3–254 with the email format. The consent is
 * mandatory only when its checkbox is shown: off-session, or in session
 * when the priant never consented (RG-IN-5, RG-IN-7). The honeypot
 * `website` field is never validated: a filled honeypot must reach the API
 * untouched (§6.7).
 */
export function inscriptionFormSchema(consentRequired: boolean) {
  return z.object({
    displayName: z
      .string()
      .trim()
      .min(1, DISPLAY_NAME_REQUIRED_MESSAGE)
      .max(80, DISPLAY_NAME_LENGTH_MESSAGE),
    email: z
      .string()
      .trim()
      .min(1, EMAIL_REQUIRED_MESSAGE)
      .min(3, EMAIL_LENGTH_MESSAGE)
      .max(254, EMAIL_LENGTH_MESSAGE)
      .email(EMAIL_INVALID_MESSAGE),
    consent: consentRequired
      ? z.boolean().refine((value) => value === true, {
          message: CONSENT_REQUIRED_MESSAGE,
        })
      : z.boolean(),
    website: z.string(),
  });
}
