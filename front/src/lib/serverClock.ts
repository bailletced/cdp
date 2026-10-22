import { useEffect, useState } from "react";

/**
 * Server clock (US-08.4, spec §5.1): the API sends X-Server-Time (Unix
 * seconds) on every response. The offset between the server clock and the
 * visitor's device clock is estimated from that header, so the slot states
 * (past / current / future) stay correct even when the device clock is
 * skewed. The latest valid sample wins.
 */

/** Offset (ms) between the server clock and the local clock: server − local. */
let offsetMs = 0;

/** The estimated server offset in milliseconds (0 when unknown). */
export function serverOffsetMs(): number {
  return offsetMs;
}

/** Current time (ms since epoch) corrected by the estimated server offset. */
export function now(): number {
  return Date.now() + offsetMs;
}

/**
 * Estimates the server offset from an X-Server-Time header value (Unix
 * seconds): the response is received "now", so server − local is the
 * skew of the device clock. Missing or invalid headers are ignored.
 */
export function learnFromServerTimeHeader(header: string | null): void {
  if (header === null) {
    return;
  }

  const serverMs = Number(header) * 1000;

  if (!Number.isFinite(serverMs) || serverMs <= 0) {
    return;
  }

  offsetMs = serverMs - Date.now();
}

/** Learns the server offset from a fetch Response (no-op without header). */
export function learnFromResponse(response: Response): void {
  learnFromServerTimeHeader(response.headers.get("X-Server-Time"));
}

/** Resets the estimated offset (tests). */
export function resetServerClock(): void {
  offsetMs = 0;
}

const DEFAULT_INTERVAL_MS = 1000;

/**
 * Current time corrected by the estimated server offset, refreshed every
 * second so the slot states follow the server clock live (US-08.4).
 */
export function useNow(intervalMs: number = DEFAULT_INTERVAL_MS): number {
  const [current, setCurrent] = useState(() => now());

  useEffect(() => {
    setCurrent(now());
    const id = setInterval(() => setCurrent(now()), intervalMs);

    return () => clearInterval(id);
  }, [intervalMs]);

  return current;
}
