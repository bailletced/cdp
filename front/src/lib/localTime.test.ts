import { afterEach, describe, expect, it, vi } from "vitest";
import {
  isParisTimeZone,
  localHour,
  visitorTimeZone,
  withLocalTime,
} from "./localTime";

afterEach(() => {
  vi.unstubAllEnvs();
});

// 2026-11-29T02:00:00+00:00 is 03h00 in Paris (winter, UTC+1)
const WINTER_INSTANT = "2026-11-29T02:00:00+00:00";
// 2026-07-04T01:00:00+00:00 is 03h00 in Paris (summer, UTC+2)
const SUMMER_INSTANT = "2026-07-04T01:00:00+00:00";

describe("isParisTimeZone", () => {
  it("recognizes Europe/Paris", () => {
    expect(isParisTimeZone("Europe/Paris")).toBe(true);
    expect(isParisTimeZone("America/Montreal")).toBe(false);
  });
});

describe("localHour", () => {
  it("returns null in Europe/Paris (US-08.3: no local time shown)", () => {
    expect(localHour(WINTER_INSTANT, "Europe/Paris")).toBeNull();
  });

  it("formats the local hour in Montréal (winter, UTC-5)", () => {
    // 02h00 UTC is 21h00 the previous day in Montréal
    expect(localHour(WINTER_INSTANT, "America/Montreal")).toBe("21h00");
  });

  it("formats the local hour in Montréal (summer, DST UTC-4)", () => {
    // 01h00 UTC is 21h00 the previous day in Montréal (EDT)
    expect(localHour(SUMMER_INSTANT, "America/Montreal")).toBe("21h00");
  });

  it("formats the local hour in Tokyo (UTC+9, no DST)", () => {
    // 02h00 UTC is 11h00 the same day in Tokyo
    expect(localHour(WINTER_INSTANT, "Asia/Tokyo")).toBe("11h00");
  });

  it("formats midnight as 00h00", () => {
    // 15h00 UTC in Montréal (winter) is 10h00; 05h00 UTC is 00h00
    expect(localHour("2026-11-29T05:00:00+00:00", "America/Montreal")).toBe(
      "00h00",
    );
  });

  it("returns null for an invalid instant", () => {
    expect(localHour("not-a-date", "America/Montreal")).toBeNull();
  });
});

describe("withLocalTime", () => {
  it("keeps the Paris label alone in Europe/Paris", () => {
    expect(withLocalTime("3h00", WINTER_INSTANT, "Europe/Paris")).toBe("3h00");
  });

  it("appends the local hour in Montréal (« 3h00 (21h00 chez vous) »)", () => {
    expect(withLocalTime("3h00", WINTER_INSTANT, "America/Montreal")).toBe(
      "3h00 (21h00 chez vous)",
    );
  });

  it("appends the local hour in Tokyo", () => {
    expect(withLocalTime("3h00", WINTER_INSTANT, "Asia/Tokyo")).toBe(
      "3h00 (11h00 chez vous)",
    );
  });

  it("keeps the Paris label for an invalid instant", () => {
    expect(withLocalTime("3h00", "not-a-date", "America/Montreal")).toBe(
      "3h00",
    );
  });
});

describe("visitorTimeZone (default parameter)", () => {
  it.each([
    ["Europe/Paris", -60, "3h00"],
    ["America/Montreal", 300, "3h00 (21h00 chez vous)"],
    ["Asia/Tokyo", -540, "3h00 (11h00 chez vous)"],
  ])(
    "uses the simulated browser time zone (%s)",
    (timeZone, expectedOffsetMinutes, expectedLabel) => {
      vi.stubEnv("TZ", timeZone);

      // Sanity check: the simulated browser zone really applies
      // (ICU may canonicalize America/Montreal to its alias America/Toronto,
      // so the offset is asserted rather than the zone name)
      expect(new Date(WINTER_INSTANT).getTimezoneOffset()).toBe(
        expectedOffsetMinutes,
      );
      expect(visitorTimeZone()).toBeTruthy();

      expect(withLocalTime("3h00", WINTER_INSTANT)).toBe(expectedLabel);
      expect(localHour(WINTER_INSTANT)).toBe(
        isParisTimeZone(visitorTimeZone())
          ? null
          : (expectedLabel.match(/\((.+) chez vous\)/)?.[1] ?? null),
      );
    },
  );
});
