import { describe, expect, it } from "vitest";
import {
  type EditionSlot,
  editionStatusLabel,
  formatParisDateTime,
  groupSlotsByPartOfDay,
  moveItems,
  parseEditionSlots,
  withSlotText,
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
  it("normalizes the text of each slot (F07-T04)", () => {
    const [withText, withoutText] = parseEditionSlots([
      {
        id: "slot-1",
        rank: 1,
        startsAt: "2026-11-28T19:00:00+00:00",
        endsAt: "2026-11-28T19:30:00+00:00",
        label: "20h00–20h30",
        partOfDay: "evening",
        textId: "text-1",
        textTitle: "Que rien ne te trouble",
        textSource: "Thérèse d’Avila",
        textAlreadyUsed: true,
      },
      {
        rank: 2,
        startsAt: "2026-11-28T19:30:00+00:00",
        endsAt: "2026-11-28T20:00:00+00:00",
        label: "20h30–21h00",
        partOfDay: "evening",
        textId: null,
      },
    ]);

    expect(withText).toEqual({
      id: "slot-1",
      rank: 1,
      startsAt: "2026-11-28T19:00:00+00:00",
      endsAt: "2026-11-28T19:30:00+00:00",
      label: "20h00–20h30",
      partOfDay: "evening",
      textId: "text-1",
      textTitle: "Que rien ne te trouble",
      textSource: "Thérèse d’Avila",
      textAlreadyUsed: true,
    });
    expect(withoutText).toMatchObject({
      id: null,
      textId: null,
      textTitle: null,
      textSource: null,
      textAlreadyUsed: false,
    });
  });

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
    id: `slot-${rank}`,
    rank,
    startsAt: "2026-11-28T19:00:00+00:00",
    endsAt: "2026-11-28T19:30:00+00:00",
    label: `slot ${rank}`,
    partOfDay,
    textId: null,
    textTitle: null,
    textSource: null,
    textAlreadyUsed: false,
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

describe("withSlotText (F07-T07)", () => {
  const serialized = (rank: number, textId: string | null) => ({
    id: `slot-${rank}`,
    rank,
    startsAt: "2026-11-28T19:00:00+00:00",
    endsAt: "2026-11-28T19:30:00+00:00",
    label: `slot ${rank}`,
    partOfDay: "evening",
    textId,
    textTitle: textId ? "Titre" : null,
    textSource: textId ? "Source" : null,
    textAlreadyUsed: false,
  });

  it("puts the text on the slot and recomputes the warnings", () => {
    const edition = withSlotText(
      {
        "@id": "/api/admin/editions/1",
        "@type": "AdminEdition",
        slots: [serialized(1, "a"), serialized(2, null), serialized(3, null)],
      },
      "slot-2",
      { id: "a", title: "Psaume 23", source: "Psautier" },
    );
    const slots = parseEditionSlots(edition.slots);

    expect(slots[1]).toMatchObject({
      textId: "a",
      textTitle: "Psaume 23",
      textSource: "Psautier",
      textAlreadyUsed: true,
    });
    expect(slots[0].textAlreadyUsed).toBe(true);
    expect(edition.slotsWithoutText).toBe(1);
    expect(edition.duplicatedTextIds).toEqual(["a"]);
  });

  it("withdraws the text with null", () => {
    const edition = withSlotText(
      {
        "@id": "/api/admin/editions/1",
        "@type": "AdminEdition",
        slots: [serialized(1, "a")],
      },
      "slot-1",
      null,
    );

    expect(parseEditionSlots(edition.slots)[0]).toMatchObject({
      textId: null,
      textTitle: null,
      textAlreadyUsed: false,
    });
    expect(edition.slotsWithoutText).toBe(1);
  });
});

describe("moveItems (F07-T08)", () => {
  const items = ["a", "b", "c", "d"].map((key) => ({ key }));
  const keys = (list: { key: string }[]) => list.map((item) => item.key);

  it("drops the dragged items before or after the target, in their order", () => {
    expect(keys(moveItems(items, new Set(["d"]), "a", "before"))).toEqual([
      "d",
      "a",
      "b",
      "c",
    ]);
    expect(keys(moveItems(items, new Set(["a", "b"]), "c", "after"))).toEqual([
      "c",
      "a",
      "b",
      "d",
    ]);
  });

  it("leaves the list as it is when the target is unknown", () => {
    expect(keys(moveItems(items, new Set(["a"]), "z", "after"))).toEqual([
      "a",
      "b",
      "c",
      "d",
    ]);
  });
});
