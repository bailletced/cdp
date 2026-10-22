import { PARIS_TIME_ZONE } from "./parisTime";

/**
 * Local time of a slot for visitors outside Europe/Paris (US-08.3): the
 * planning always shows the Paris wall clock, and adds the visitor's local
 * hour in parentheses when their time zone is not Europe/Paris
 * (« 3h00 (21h00 chez vous) »). Pure Intl with an explicit time zone, so the
 * result never depends on the browser zone unless asked to (default
 * parameter).
 */

/** The visitor's time zone, e.g. "America/Montreal". */
export function visitorTimeZone(): string {
  return Intl.DateTimeFormat().resolvedOptions().timeZone || PARIS_TIME_ZONE;
}

/** Whether a time zone is Europe/Paris (US-08.3: no local time displayed). */
export function isParisTimeZone(timeZone: string): boolean {
  return timeZone === PARIS_TIME_ZONE;
}

const localFormatters = new Map<string, Intl.DateTimeFormat>();

function localPartsFormat(timeZone: string): Intl.DateTimeFormat {
  let formatter = localFormatters.get(timeZone);

  if (!formatter) {
    formatter = new Intl.DateTimeFormat("fr-FR", {
      timeZone,
      hour: "2-digit",
      minute: "2-digit",
      hourCycle: "h23",
    });
    localFormatters.set(timeZone, formatter);
  }

  return formatter;
}

/**
 * Local hour and minute of an instant in a time zone, French 24h format
 * (« 21h00 »). Null when the zone is Europe/Paris or the instant is invalid.
 */
export function localHour(
  isoUtc: string,
  timeZone: string = visitorTimeZone(),
): string | null {
  if (isParisTimeZone(timeZone)) {
    return null;
  }

  const date = new Date(isoUtc);

  if (Number.isNaN(date.getTime())) {
    return null;
  }

  const parts: Record<string, string> = {};

  for (const part of localPartsFormat(timeZone).formatToParts(date)) {
    if (part.type !== "literal") {
      parts[part.type] = part.value;
    }
  }

  return `${parts.hour ?? ""}h${parts.minute ?? ""}`;
}

/**
 * « 3h00 (21h00 chez vous) » — the Paris label, plus the visitor's local
 * hour when they are not in Europe/Paris.
 */
export function withLocalTime(
  parisLabel: string,
  isoUtc: string,
  timeZone: string = visitorTimeZone(),
): string {
  const local = localHour(isoUtc, timeZone);

  return local === null ? parisLabel : `${parisLabel} (${local} chez vous)`;
}
