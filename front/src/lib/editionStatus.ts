/**
 * Where we stand in the current public edition: the chain runs exactly 24
 * hours (48 slots of 30 minutes), so its end is its start + 24 h.
 */
export const EDITION_DURATION_MS = 24 * 3600_000;

export type EditionStatus = "upcoming" | "live" | "over";

export function editionStatusAt(
  startsAt: string,
  nowMs: number,
): EditionStatus {
  const start = new Date(startsAt).getTime();

  if (Number.isNaN(start)) {
    return "over";
  }

  if (nowMs < start) {
    return "upcoming";
  }

  return nowMs < start + EDITION_DURATION_MS ? "live" : "over";
}

export interface CountdownParts {
  days: number;
  hours: number;
  minutes: number;
  seconds: number;
}

export function countdownParts(remainingMs: number): CountdownParts {
  const total = Math.max(0, Math.floor(remainingMs / 1000));

  return {
    days: Math.floor(total / 86_400),
    hours: Math.floor((total % 86_400) / 3600),
    minutes: Math.floor((total % 3600) / 60),
    seconds: total % 60,
  };
}

/**
 * « 23 j 04 h 12 m 30 s » — only the units that count: from a day on the
 * days lead, under a day only hours, under an hour only minutes.
 * French spacing, narrow no-break space between number and unit.
 */
export function formatCountdown(remainingMs: number): string {
  const { days, hours, minutes, seconds } = countdownParts(remainingMs);
  const u = "\u202f"; // Narrow no-break space

  if (days > 0) {
    return `${days}${u}j ${String(hours).padStart(2, "0")}${u}h ${String(minutes).padStart(2, "0")}${u}m`;
  }

  if (hours > 0) {
    return `${hours}${u}h ${String(minutes).padStart(2, "0")}${u}m ${String(seconds).padStart(2, "0")}${u}s`;
  }

  if (minutes > 0) {
    return `${minutes}${u}m ${String(seconds).padStart(2, "0")}${u}s`;
  }

  return `${seconds}${u}s`;
}
