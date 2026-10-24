import { describe, expect, it } from "vitest";
import {
  type PlanningSlot,
  parsePlanningSlots,
  slotTemporalState,
} from "./planning";

function makeSlot(overrides: Partial<PlanningSlot> = {}): PlanningSlot {
  return {
    id: "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b",
    rank: 1,
    startsAt: "2026-11-28T18:00:00+00:00",
    endsAt: "2026-11-28T18:30:00+00:00",
    label: "19h00–19h30",
    partOfDay: "evening",
    state: "future",
    textTitle: null,
    textSource: null,
    registrationCount: 0,
    ...overrides,
  };
}

describe("slotTemporalState (US-08.4)", () => {
  it("is future before the slot starts", () => {
    expect(
      slotTemporalState(makeSlot(), Date.UTC(2026, 10, 28, 17, 59, 59)),
    ).toBe("future");
  });

  it("is current while the slot is running", () => {
    expect(
      slotTemporalState(makeSlot(), Date.UTC(2026, 10, 28, 18, 0, 0)),
    ).toBe("current");
    expect(
      slotTemporalState(makeSlot(), Date.UTC(2026, 10, 28, 18, 29, 59)),
    ).toBe("current");
  });

  it("is past once the slot has ended", () => {
    expect(
      slotTemporalState(makeSlot(), Date.UTC(2026, 10, 28, 18, 30, 0)),
    ).toBe("past");
    expect(
      slotTemporalState(makeSlot(), Date.UTC(2026, 10, 29, 18, 30, 0)),
    ).toBe("past");
  });
});

describe("parsePlanningSlots", () => {
  it("keeps the registration count of each slot, 0 when missing", () => {
    const [counted, uncounted] = parsePlanningSlots([
      { ...makeSlot(), registrationCount: 3 },
      { ...makeSlot({ rank: 2 }), registrationCount: undefined },
    ]);

    expect(counted.registrationCount).toBe(3);
    expect(uncounted.registrationCount).toBe(0);
  });

  it("keeps well-formed slots", () => {
    const slots = parsePlanningSlots([makeSlot(), makeSlot({ rank: 2 })]);

    expect(slots).toHaveLength(2);
    expect(slots[1].rank).toBe(2);
  });

  it("drops malformed entries instead of failing the whole grid", () => {
    const slots = parsePlanningSlots([
      makeSlot(),
      null,
      { id: "not-a-full-slot" },
      "nope",
      makeSlot({ rank: 3, textTitle: null, textSource: null }),
    ]);

    expect(slots).toHaveLength(2);
    expect(slots.map((slot) => slot.rank)).toEqual([1, 3]);
  });

  it("returns an empty list for a non-array payload", () => {
    expect(parsePlanningSlots(undefined)).toEqual([]);
    expect(parsePlanningSlots(null)).toEqual([]);
    expect(parsePlanningSlots({})).toEqual([]);
  });
});
