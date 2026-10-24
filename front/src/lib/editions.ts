import type { components } from "../api/schema";
import { PARIS_TIME_ZONE } from "./parisTime";

/** Edition as returned by the admin API (F05-T14). */
export type EditionView = components["schemas"]["AdminEdition.jsonld"];

/**
 * Slot of an edition, as serialized by the API: instants are UTC ISO 8601
 * strings, the label is the Paris wall-clock range (« 20h00–20h30 », with a
 * disambiguation on DST nights, RG-ED-5). The text fields come with the
 * admin detail (F07-T04): textAlreadyUsed flags a text carried by another
 * slot of the edition too.
 */
export interface EditionSlot {
  id: string | null;
  rank: number;
  startsAt: string;
  endsAt: string;
  label: string;
  partOfDay: string;
  textId: string | null;
  textTitle: string | null;
  textSource: string | null;
  textAlreadyUsed: boolean;
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

type SerializedSlot = Pick<
  EditionSlot,
  "rank" | "startsAt" | "endsAt" | "label" | "partOfDay"
> &
  Record<string, unknown>;

function isEditionSlot(slot: unknown): slot is SerializedSlot {
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

function stringOrNull(value: unknown): string | null {
  return typeof value === "string" ? value : null;
}

/**
 * The OpenAPI schema types slots as unknown[]: narrow them to EditionSlot,
 * dropping malformed entries instead of failing the whole page.
 */
export function parseEditionSlots(slots: EditionView["slots"]): EditionSlot[] {
  if (!Array.isArray(slots)) {
    return [];
  }

  return slots.filter(isEditionSlot).map((slot) => ({
    id: stringOrNull(slot.id),
    rank: slot.rank,
    startsAt: slot.startsAt,
    endsAt: slot.endsAt,
    label: slot.label,
    partOfDay: slot.partOfDay,
    textId: stringOrNull(slot.textId),
    textTitle: stringOrNull(slot.textTitle),
    textSource: stringOrNull(slot.textSource),
    textAlreadyUsed: slot.textAlreadyUsed === true,
  }));
}

/** Part-of-day labels (spec §5.2: soirée, nuit, matin, après-midi). */
export const PART_OF_DAY_LABELS: Record<string, string> = {
  evening: "Soirée",
  night: "Nuit",
  morning: "Matin",
  afternoon: "Après-midi",
};

export interface SlotGroup<T> {
  partOfDay: string;
  label: string;
  slots: T[];
}

/**
 * Groups slots by part of day, in chronological order (spec §5.2). A 24h
 * edition wraps past midnight: two separate « evening » runs stay in two
 * groups rather than merging out of order.
 */
export function groupSlotsByPartOfDay<T extends { partOfDay: string }>(
  slots: T[],
): SlotGroup<T>[] {
  const groups: SlotGroup<T>[] = [];

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

/** The text put on a slot: what the attribution screen shows at once. */
export interface SlotText {
  id: string;
  title: string;
  source: string;
}

/**
 * The edition with one slot carrying a new text (or none): the optimistic
 * state of an attribution (F07-T07), with the warnings recomputed — texts
 * used twice and slots without text — until the server answers back.
 */
export function withSlotText(
  edition: EditionView,
  slotId: string,
  text: SlotText | null,
): EditionView {
  const slots = parseEditionSlots(edition.slots).map((slot) =>
    slot.id === slotId
      ? {
          ...slot,
          textId: text?.id ?? null,
          textTitle: text?.title ?? null,
          textSource: text?.source ?? null,
        }
      : slot,
  );

  const uses = new Map<string, number>();
  for (const slot of slots) {
    if (slot.textId) {
      uses.set(slot.textId, (uses.get(slot.textId) ?? 0) + 1);
    }
  }

  return {
    ...edition,
    slots: slots.map((slot) => ({
      ...slot,
      textAlreadyUsed: null !== slot.textId && (uses.get(slot.textId) ?? 0) > 1,
    })),
    slotsWithoutText: slots.filter((slot) => null === slot.textId).length,
    duplicatedTextIds: [...uses]
      .filter(([, count]) => count > 1)
      .map(([id]) => id),
  };
}
