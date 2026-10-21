import type { components } from "../api/schema";

/** Edition as returned by the admin API (F05-T14). */
export type EditionView = components["schemas"]["AdminEdition.jsonld"];

/**
 * Slot of an edition, as serialized by the API: instants are UTC ISO 8601
 * strings, the label is the Paris wall-clock range (« 20h00–20h30 », with a
 * disambiguation on DST nights, RG-ED-5).
 */
export interface EditionSlot {
  rank: number;
  startsAt: string;
  endsAt: string;
  label: string;
  partOfDay: string;
  textId: string | null;
}

/** Status labels (spec §5.1): the API computes the hybrid status (§4.7). */
export const EDITION_STATUS_LABELS: Record<string, string> = {
  draft: "Brouillon",
  scheduled: "Programmée",
  open: "Inscriptions ouvertes",
  running: "En cours",
  finished: "Terminée",
};

export function editionStatusLabel(status: string | null | undefined): string {
  if (status && status in EDITION_STATUS_LABELS) {
    return EDITION_STATUS_LABELS[status];
  }

  return status ?? "";
}

export const PARIS_TIME_ZONE = "Europe/Paris";

const parisDateTimeFormat = new Intl.DateTimeFormat("fr-FR", {
  timeZone: PARIS_TIME_ZONE,
  weekday: "long",
  day: "numeric",
  month: "long",
  year: "numeric",
  hour: "2-digit",
  minute: "2-digit",
});

/**
 * Formats a UTC instant in Paris time (spec §4.6: the API returns UTC, the
 * front only renders it in Europe/Paris — never in the browser time zone).
 */
export function formatParisDateTime(
  isoDate: string | null | undefined,
): string {
  if (!isoDate) {
    return "";
  }

  const date = new Date(isoDate);

  return Number.isNaN(date.getTime())
    ? isoDate
    : parisDateTimeFormat.format(date);
}

function isEditionSlot(slot: unknown): slot is EditionSlot {
  if (typeof slot !== "object" || slot === null) {
    return false;
  }

  const candidate = slot as Record<string, unknown>;

  return (
    typeof candidate.rank === "number" &&
    typeof candidate.startsAt === "string" &&
    typeof candidate.endsAt === "string" &&
    typeof candidate.label === "string" &&
    typeof candidate.partOfDay === "string" &&
    (candidate.textId === null ||
      candidate.textId === undefined ||
      typeof candidate.textId === "string")
  );
}

/**
 * The OpenAPI schema types slots as unknown[]: narrow them to EditionSlot,
 * dropping malformed entries instead of failing the whole page.
 */
export function parseEditionSlots(slots: EditionView["slots"]): EditionSlot[] {
  if (!Array.isArray(slots)) {
    return [];
  }

  return slots.filter(isEditionSlot);
}

/** Part-of-day labels (spec §5.2: soirée, nuit, matin, après-midi). */
export const PART_OF_DAY_LABELS: Record<string, string> = {
  evening: "Soirée",
  night: "Nuit",
  morning: "Matin",
  afternoon: "Après-midi",
};

export interface SlotGroup {
  partOfDay: string;
  label: string;
  slots: EditionSlot[];
}

/**
 * Groups slots by part of day, in chronological order (spec §5.2). A 24h
 * edition wraps past midnight: two separate « evening » runs stay in two
 * groups rather than merging out of order.
 */
export function groupSlotsByPartOfDay(slots: EditionSlot[]): SlotGroup[] {
  const groups: SlotGroup[] = [];

  for (const slot of slots) {
    const last = groups.at(-1);

    if (last && last.partOfDay === slot.partOfDay) {
      last.slots.push(slot);
    } else {
      groups.push({
        partOfDay: slot.partOfDay,
        label: PART_OF_DAY_LABELS[slot.partOfDay] ?? slot.partOfDay,
        slots: [slot],
      });
    }
  }

  return groups;
}
