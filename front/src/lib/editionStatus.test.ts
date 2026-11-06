import { describe, expect, it } from "vitest";
import {
  countdownParts,
  editionStatusAt,
  formatCountdown,
} from "./editionStatus";

const START = "2026-11-28T21:00:00+00:00"; // 22:00, heure de Paris
const AT = (iso: string) => new Date(iso).getTime();
const HOUR = 3600_000;

describe("editionStatusAt", () => {
  it("says « upcoming » before the start, « live » during the 24 hours, « over » after", () => {
    expect(editionStatusAt(START, AT("2026-11-20T00:00:00+00:00"))).toBe(
      "upcoming",
    );
    expect(editionStatusAt(START, AT(START))).toBe("live");
    expect(editionStatusAt(START, AT("2026-11-29T14:30:00+00:00"))).toBe(
      "live",
    );
    // Exactly 24 h later: over (the last slot ended)
    expect(editionStatusAt(START, AT("2026-11-29T21:00:00+00:00"))).toBe(
      "over",
    );
    expect(editionStatusAt(START, AT("2026-12-01T00:00:00+00:00"))).toBe(
      "over",
    );
  });
});

describe("formatCountdown", () => {
  it("counts down as days / hours / minutes from a day on", () => {
    expect(formatCountdown(23 * 24 * HOUR + 4 * HOUR + 12 * 60_000)).toBe(
      "23\u202fj 04\u202fh 12\u202fm",
    );
  });

  it("counts in hours, minutes and seconds under a day", () => {
    expect(formatCountdown(13 * HOUR + 42 * 60_000 + 7_000)).toBe(
      "13\u202fh 42\u202fm 07\u202fs",
    );
  });

  it("counts in minutes and seconds under an hour, seconds under a minute", () => {
    expect(formatCountdown(12 * 60_000 + 5_000)).toBe("12\u202fm 05\u202fs");
    expect(formatCountdown(42_000)).toBe("42\u202fs");
    expect(formatCountdown(-1234)).toBe("0\u202fs");
  });

  it("splits the parts for the big digits display", () => {
    expect(
      countdownParts(23 * 24 * HOUR + 4 * HOUR + 12 * 60_000 + 30_000),
    ).toEqual({
      days: 23,
      hours: 4,
      minutes: 12,
      seconds: 30,
    });
  });
});
