import { CalendarDateTime } from "@internationalized/date";

/**
 * The one and only time zone the organizers work in (spec §4.6): instants are
 * exchanged with the API as ISO 8601 with an explicit offset, wall clocks are
 * always Europe/Paris — never the browser time zone.
 */
export const PARIS_TIME_ZONE = "Europe/Paris";

const DAY_MS = 24 * 60 * 60 * 1000;

const parisPartsFormat = new Intl.DateTimeFormat("en-US", {
  timeZone: PARIS_TIME_ZONE,
  year: "numeric",
  month: "2-digit",
  day: "2-digit",
  hour: "2-digit",
  minute: "2-digit",
  second: "2-digit",
  hourCycle: "h23",
  timeZoneName: "longOffset",
});

interface ParisParts {
  year: string;
  month: string;
  day: string;
  hour: string;
  minute: string;
  second: string;
  offset: string;
}

/** Wall clock and UTC offset of an instant, as seen from Europe/Paris. */
function parisParts(date: Date): ParisParts {
  const values: Record<string, string> = {};

  for (const part of parisPartsFormat.formatToParts(date)) {
    if (part.type !== "literal") {
      values[part.type] = part.value;
    }
  }

  // timeZoneName "longOffset" renders as "GMT+01:00", or bare "GMT" at UTC+0
  const offset = (values.timeZoneName ?? "GMT").replace("GMT", "") || "+00:00";

  return {
    year: values.year ?? "",
    month: values.month ?? "",
    day: values.day ?? "",
    hour: values.hour ?? "",
    minute: values.minute ?? "",
    second: values.second ?? "",
    offset,
  };
}

function toUtcMs(parts: ParisParts): number {
  return Date.UTC(
    Number(parts.year),
    Number(parts.month) - 1,
    Number(parts.day),
    Number(parts.hour),
    Number(parts.minute),
    Number(parts.second),
  );
}

/** The Paris wall clock of an instant, expressed as if it were UTC (ms). */
function parisWallClockMs(instant: number): number {
  return toUtcMs(parisParts(new Date(instant)));
}

/** UTC offset (ms) of Europe/Paris at the given instant. */
function parisOffsetMs(instant: number): number {
  return parisWallClockMs(instant) - instant;
}

/**
 * Instant of a Paris wall clock, with the "compatible" disambiguation
 * (RG-ED-7): an ambiguous time takes its first occurrence, a nonexistent
 * time is shifted forward. Implemented from scratch (Intl with an explicit
 * time zone + Date.UTC only): CalendarDateTime.toDate() of
 * @internationalized/date caches the local time zone and interprets the
 * wall clock in the browser zone when it is not Europe/Paris.
 */
function parisInstantFromWallClock(value: CalendarDateTime): number {
  const wallAsUtc = Date.UTC(
    value.year,
    value.month - 1,
    value.day,
    value.hour,
    value.minute,
    value.second,
    value.millisecond,
  );

  const offsetBefore = parisOffsetMs(wallAsUtc - DAY_MS);
  const offsetAfter = parisOffsetMs(wallAsUtc + DAY_MS);
  const candidates = [wallAsUtc - offsetBefore, wallAsUtc - offsetAfter];
  const valid = candidates.filter(
    (candidate) => parisWallClockMs(candidate) === wallAsUtc,
  );

  if (valid.length === 1) {
    return valid[0];
  }

  if (valid.length > 1) {
    // Ambiguous (fall back): first occurrence
    return Math.min(...valid);
  }

  // Nonexistent (spring forward): shifted forward — the later instant
  return Math.max(...candidates);
}

/**
 * Serializes a Paris wall clock for the API: ISO 8601 with the explicit
 * Europe/Paris offset, e.g. "2026-11-28T20:00:00+01:00". DST edge cases follow
 * RG-ED-7 — an ambiguous time keeps its wall clock with the first-occurrence
 * offset, a nonexistent time is sent shifted forward. Independent of the
 * browser time zone: only Europe/Paris is ever queried.
 */
export function toParisIso(value: CalendarDateTime): string {
  const parts = parisParts(new Date(parisInstantFromWallClock(value)));

  return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}:${parts.second}${parts.offset}`;
}

/**
 * Inverse of toParisIso: the Paris wall clock of an instant (e.g. to pre-fill
 * the edit form from the UTC instant returned by the API).
 */
export function parisCalendarDateTimeFromIso(
  isoDate: string | null | undefined,
): CalendarDateTime | null {
  if (!isoDate) {
    return null;
  }

  const date = new Date(isoDate);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  const parts = parisParts(date);

  return new CalendarDateTime(
    Number(parts.year),
    Number(parts.month),
    Number(parts.day),
    Number(parts.hour),
    Number(parts.minute),
    Number(parts.second),
  );
}
