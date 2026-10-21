import { describe, expect, it } from "vitest";
import {
  type EditionSlot,
  editionStatusLabel,
  formatParisDateTime,
  groupSlotsByPartOfDay,
  parseEditionSlots,
} from "./editions";

describe("editionStatusLabel", () => {
  it("labels each edition status (spec §5.1)", () => {
    expect(editionStatusLabel("draft")).toBe("Brouillon");
    expect(editionStatusLabel("scheduled")).toBe("Programmée");
    expect(editionStatusLabel("open")).toBe("Inscriptions ouvertes");
    expect(editionStatusLabel("running")).toBe("En cours");
    expect(editionStatusLabel("finished")).toBe("Terminée");
  });

  it("falls back to the raw status when it is unknown", () => {
    expect(editionStatusLabel("weird")).toBe("weird");
    expect(editionStatusLabel(null)).toBe("");
    expect(editionStatusLabel(undefined)).toBe("");
  });
});

describe("formatParisDateTime", () => {
  it("formats a UTC instant in Paris time (winter, UTC+1)", () => {
    expect(formatParisDateTime("2026-11-28T19:00:00+00:00")).toBe(
      "samedi 28 novembre 2026 à 20:00",
    );
  });

  it("formats a UTC instant in Paris time (summer, UTC+2)", () => {
    expect(formatParisDateTime("2026-07-04T19:00:00+00:00")).toBe(
      "samedi 4 juillet 2026 à 21:00",
    );
  });

  it("keeps half hours", () => {
    expect(formatParisDateTime("2026-11-28T22:30:00+00:00")).toBe(
      "samedi 28 novembre 2026 à 23:30",
    );
  });

  it("returns an empty string for a missing date", () => {
    expect(formatParisDateTime(null)).toBe("");
    expect(formatParisDateTime(undefined)).toBe("");
  });
});

describe("parseEditionSlots", () => {
  it("keeps well-formed slots and drops malformed ones", () => {
    const slots = parseEditionSlots([
      {
        rank: 0,
        startsAt: "2026-11-28T19:00:00+00:00",
        endsAt: "2026-11-28T19:30:00+00:00",
        label: "20h00–20h30",
        partOfDay: "evening",
        textId: null,
      },
      { rank: "1" },
      null,
    ]);

    expect(slots).toHaveLength(1);
    expect(slots[0]).toMatchObject({
      rank: 0,
      label: "20h00–20h30",
      partOfDay: "evening",
      textId: null,
    });
  });

  it("returns an empty list when the edition has no slots", () => {
    expect(parseEditionSlots(null)).toEqual([]);
    expect(parseEditionSlots(undefined)).toEqual([]);
  });
});

describe("groupSlotsByPartOfDay", () => {
  const slot = (rank: number, partOfDay: string): EditionSlot => ({
    rank,
    startsAt: "2026-11-28T19:00:00+00:00",
    endsAt: "2026-11-28T19:30:00+00:00",
    label: `slot ${rank}`,
    partOfDay,
    textId: null,
  });

  it("groups consecutive slots by part of day, in chronological order", () => {
    const groups = groupSlotsByPartOfDay([
      slot(0, "evening"),
      slot(1, "evening"),
      slot(2, "night"),
      slot(3, "morning"),
      slot(4, "afternoon"),
    ]);

    expect(groups.map((group) => group.label)).toEqual([
      "Soirée",
      "Nuit",
      "Matin",
      "Après-midi",
    ]);
    expect(groups[0].slots).toHaveLength(2);
    expect(groups[1].slots).toHaveLength(1);
  });

  it("keeps a second evening run separate (24h edition wrapping past midnight)", () => {
    const groups = groupSlotsByPartOfDay([
      slot(0, "evening"),
      slot(1, "night"),
      slot(2, "evening"),
    ]);

    expect(groups.map((group) => group.partOfDay)).toEqual([
      "evening",
      "night",
      "evening",
    ]);
  });

  it("labels an unknown part of day with its raw value", () => {
    const groups = groupSlotsByPartOfDay([slot(0, "twilight")]);

    expect(groups[0].label).toBe("twilight");
  });
});
