import { CalendarDateTime } from "@internationalized/date";
import { afterEach, describe, expect, it, vi } from "vitest";
import { parisCalendarDateTimeFromIso, toParisIso } from "./parisTime";

afterEach(() => {
  vi.unstubAllEnvs();
});

describe("toParisIso", () => {
  it("sends the Paris wall clock with its explicit offset (winter, UTC+1)", () => {
    expect(toParisIso(new CalendarDateTime(2026, 11, 28, 20, 0))).toBe(
      "2026-11-28T20:00:00+01:00",
    );
  });

  it("sends the Paris wall clock with its explicit offset (summer, UTC+2)", () => {
    expect(toParisIso(new CalendarDateTime(2026, 7, 4, 21, 0))).toBe(
      "2026-07-04T21:00:00+02:00",
    );
  });

  it("resolves an ambiguous time to its first occurrence (RG-ED-7)", () => {
    // 2026-10-25 02:30 occurs twice: the first occurrence is summer time
    expect(toParisIso(new CalendarDateTime(2026, 10, 25, 2, 30))).toBe(
      "2026-10-25T02:30:00+02:00",
    );
  });

  it("shifts a nonexistent time forward (RG-ED-7)", () => {
    // 2027-03-28 is Easter Sunday, the spring forward: 02:30 does not exist
    expect(toParisIso(new CalendarDateTime(2027, 3, 28, 2, 30))).toBe(
      "2027-03-28T03:30:00+02:00",
    );
  });

  it.each([
    ["Europe/Paris", -60],
    ["America/Montreal", 300],
    ["Pacific/Kiritimati", -840],
  ])(
    "sends the same value whatever the simulated browser time zone (%s)",
    (timeZone, expectedOffsetMinutes) => {
      vi.stubEnv("TZ", timeZone);

      // Sanity check: the simulated browser zone really applies
      expect(new Date("2026-11-28T19:00:00+00:00").getTimezoneOffset()).toBe(
        expectedOffsetMinutes,
      );

      expect(toParisIso(new CalendarDateTime(2026, 11, 28, 20, 0))).toBe(
        "2026-11-28T20:00:00+01:00",
      );
      expect(toParisIso(new CalendarDateTime(2027, 3, 28, 2, 30))).toBe(
        "2027-03-28T03:30:00+02:00",
      );
    },
  );
});

describe("parisCalendarDateTimeFromIso", () => {
  it("converts a UTC instant to the Paris wall clock", () => {
    expect(
      parisCalendarDateTimeFromIso("2026-11-28T19:00:00+00:00")?.toString(),
    ).toBe("2026-11-28T20:00:00");
  });

  it("converts a UTC instant to the Paris wall clock (summer)", () => {
    expect(
      parisCalendarDateTimeFromIso("2026-07-04T19:00:00+00:00")?.toString(),
    ).toBe("2026-07-04T21:00:00");
  });

  it("round-trips with toParisIso", () => {
    const wallClock = parisCalendarDateTimeFromIso("2026-11-28T19:00:00+00:00");

    expect(wallClock).not.toBeNull();
    expect(toParisIso(wallClock as CalendarDateTime)).toBe(
      "2026-11-28T20:00:00+01:00",
    );
  });

  it("returns null for a missing or invalid date", () => {
    expect(parisCalendarDateTimeFromIso(null)).toBeNull();
    expect(parisCalendarDateTimeFromIso(undefined)).toBeNull();
    expect(parisCalendarDateTimeFromIso("not-a-date")).toBeNull();
  });
});
