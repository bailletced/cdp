import { readFileSync } from "node:fs";
import { render, screen } from "@testing-library/react";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { contrastRatio } from "../lib/contrast";
import type { PlanningSlot } from "../lib/planning";
import { SlotCard } from "./SlotCard";

// Read the stylesheet from disk to lock the card's color pairs at level AA
// (spec §8.5: contrastes AA vérifiés) — same approach as theme.test.tsx.
const css = readFileSync("src/index.css", "utf8");

function token(name: string): string {
  const match = css.match(new RegExp(`--${name}:\\s*(#[0-9a-fA-F]{6})`));
  expect(match, `token --${name} not found in index.css`).not.toBeNull();
  return match?.[1] ?? "";
}

beforeEach(() => {
  // Default visitor time zone: Europe/Paris (no local time displayed, RG-PL-3)
  vi.stubEnv("TZ", "Europe/Paris");
});

afterEach(() => {
  vi.unstubAllEnvs();
});

function makeSlot(overrides: Partial<PlanningSlot> = {}): PlanningSlot {
  return {
    id: "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b",
    rank: 1,
    startsAt: "2026-11-28T18:00:00+00:00",
    endsAt: "2026-11-28T18:30:00+00:00",
    label: "19h00–19h30",
    partOfDay: "evening",
    state: "future",
    textTitle: "Oraison de Thérèse",
    textSource: "Thérèse de Lisieux",
    ...overrides,
  };
}

// 2026-11-28T18:10:00Z: inside the slot (18:00–18:30 UTC)
const NOW_INSIDE_MS = Date.UTC(2026, 10, 28, 18, 10, 0);
// 2026-11-28T17:00:00Z: before the slot starts
const NOW_BEFORE_MS = Date.UTC(2026, 10, 28, 17, 0, 0);

describe("SlotCard (US-08.2)", () => {
  it("shows the Paris schedule", () => {
    render(<SlotCard slot={makeSlot()} nowMs={NOW_INSIDE_MS} />);

    expect(screen.getByText("19h00–19h30")).toBeInTheDocument();
  });

  it("adds the local time for a visitor outside Europe/Paris (RG-PL-3)", () => {
    vi.stubEnv("TZ", "America/Montreal");
    // Sanity check: the simulated browser zone really applies (UTC-5)
    expect(new Date("2026-11-28T18:00:00+00:00").getTimezoneOffset()).toBe(300);

    render(<SlotCard slot={makeSlot()} nowMs={NOW_INSIDE_MS} />);

    // 18h00 UTC is 13h00 in Montréal: « 19h00–19h30 (13h00 chez vous) »
    expect(
      screen.getByText("19h00–19h30 (13h00 chez vous)"),
    ).toBeInTheDocument();
  });

  it("marks an empty slot « À pourvoir » with an icon, a label and a color (§5.3-1)", () => {
    const { container } = render(
      <SlotCard
        slot={makeSlot()}
        registrationCount={0}
        nowMs={NOW_BEFORE_MS}
      />,
    );

    const state = screen.getByText("À pourvoir");
    expect(state).toBeInTheDocument();
    // The label is reinforced by an icon (never the color alone)
    expect(state.querySelector("svg")).not.toBeNull();
    expect(state.className).toContain("text-muted");
    expect(screen.getByText("0 priant")).toBeInTheDocument();
    expect(container.querySelector("article")?.dataset.state).toBe("future");
  });

  it("marks an occupied slot « Occupé » with the number of priants (RG-PL-1)", () => {
    render(
      <SlotCard
        slot={makeSlot()}
        registrationCount={2}
        nowMs={NOW_BEFORE_MS}
      />,
    );

    expect(screen.getByText("Occupé")).toBeInTheDocument();
    expect(screen.getByText("2 priants")).toBeInTheDocument();
  });

  it("uses the singular for a single priant", () => {
    render(
      <SlotCard
        slot={makeSlot()}
        registrationCount={1}
        nowMs={NOW_INSIDE_MS}
      />,
    );

    expect(screen.getByText("1 priant")).toBeInTheDocument();
  });

  it("shows the text title and source when a text is attributed (RG-TX-1)", () => {
    render(<SlotCard slot={makeSlot()} nowMs={NOW_INSIDE_MS} />);

    expect(screen.getByText("Oraison de Thérèse")).toBeInTheDocument();
    expect(screen.getByText("Thérèse de Lisieux")).toBeInTheDocument();
  });

  it("shows the RG-TX-2 placeholder when no text is attributed", () => {
    render(
      <SlotCard
        slot={makeSlot({ textTitle: null, textSource: null })}
        nowMs={NOW_INSIDE_MS}
      />,
    );

    expect(
      screen.getByText("Le texte de ce créneau sera bientôt disponible"),
    ).toBeInTheDocument();
  });

  it("greys out a past slot and labels it « Créneau passé » (US-08.4)", () => {
    const { container } = render(
      <SlotCard
        slot={makeSlot()}
        registrationCount={1}
        nowMs={Date.UTC(2026, 10, 28, 18, 30, 0)}
      />,
    );

    expect(screen.getByText("Créneau passé")).toBeInTheDocument();
    const article = container.querySelector("article");
    expect(article?.dataset.state).toBe("past");
    expect(article?.className).toContain("text-muted");
    // The schedule and the count stay visible, greyed out
    expect(screen.getByText("19h00–19h30")).toBeInTheDocument();
    expect(screen.getByText("1 priant")).toBeInTheDocument();
  });

  it("keeps the empty/occupied label while the slot is running (current)", () => {
    const { container } = render(
      <SlotCard
        slot={makeSlot()}
        registrationCount={0}
        nowMs={NOW_INSIDE_MS}
      />,
    );

    // The état stays vide/occupé (§5.2); the temporal state only greys out
    // past slots and drives selection (F08-T10)
    expect(screen.getByText("À pourvoir")).toBeInTheDocument();
    expect(container.querySelector("article")?.dataset.state).toBe("current");
  });
});

describe("SlotCard contrast (AA, spec §8.5)", () => {
  it("keeps every text color of the card at level AA on the card surface", () => {
    const surface = token("surface");

    // horaire, title and count (foreground); « À pourvoir » and
    // « Créneau passé » (muted); « Occupé » (success)
    for (const name of ["foreground", "muted", "success"]) {
      expect(
        contrastRatio(token(name), surface),
        `${name} on surface must reach WCAG AA (≥ 4.5)`,
      ).toBeGreaterThanOrEqual(4.5);
    }
  });

  it("keeps the card border visible (WCAG 1.4.11, ≥ 3:1)", () => {
    expect(
      contrastRatio(token("border"), token("surface")),
    ).toBeGreaterThanOrEqual(3);
  });
});
