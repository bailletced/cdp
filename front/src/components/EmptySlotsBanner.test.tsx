import { fireEvent, render, screen, within } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import type { PlanningSlot } from "../lib/planning";
import { EmptySlotsBanner } from "./EmptySlotsBanner";

// 2099-11-28T19:00:00Z is 20h00 in Paris: slot n° 1 starts then
const START_MS = Date.UTC(2099, 10, 28, 19, 0, 0);

function slot(rank: number, registrationCount: number): PlanningSlot {
  const startsAt = START_MS + (rank - 1) * 30 * 60_000;
  const hour = (h: number) =>
    `${(20 + Math.floor(h / 2)) % 24}h${h % 2 ? "30" : "00"}`;

  return {
    id: `slot-${rank}`,
    rank,
    startsAt: new Date(startsAt).toISOString(),
    endsAt: new Date(startsAt + 30 * 60_000).toISOString(),
    label: `${hour(rank - 1)}–${hour(rank)}`,
    partOfDay: "evening",
    state: "future",
    textTitle: null,
    textSource: null,
    registrationCount,
    participants: null,
    mine: false,
  };
}

/** 48 slots, the given ranks empty, the others taken. */
function edition(...emptyRanks: number[]): PlanningSlot[] {
  return Array.from({ length: 48 }, (_, index) =>
    slot(index + 1, emptyRanks.includes(index + 1) ? 0 : 1),
  );
}

describe("EmptySlotsBanner (F13-T05, US-13.1)", () => {
  it("counts the empty slots and lists their schedules", () => {
    render(
      <EmptySlotsBanner
        editionId="edition-1"
        slots={edition(2, 5, 9, 30, 47)}
        nowMs={START_MS - 3600_000}
      />,
    );

    expect(screen.getByRole("status")).toHaveTextContent(
      "Il reste 5 créneaux vides sur 48",
    );
    const list = screen.getByRole("list", { name: "Créneaux vides" });
    expect(
      within(list)
        .getAllByRole("button")
        .map((button) => button.textContent),
    ).toEqual([
      "20h30–21h00",
      "22h00–22h30",
      "0h00–0h30",
      "10h30–11h00",
      "19h00–19h30",
    ]);
  });

  it("uses the singular for the last empty slot", () => {
    render(
      <EmptySlotsBanner
        editionId="edition-1"
        slots={edition(9)}
        nowMs={START_MS - 1}
      />,
    );

    expect(screen.getByRole("status")).toHaveTextContent(
      "Il reste 1 créneau vide sur 48",
    );
  });

  it("only counts the slots still to come during the edition", () => {
    // 21h10 in Paris: slots n° 1 and 2 are over, n° 3 (21h00–21h30) is running
    render(
      <EmptySlotsBanner
        editionId="edition-1"
        slots={edition(1, 3, 4, 10)}
        nowMs={START_MS + 70 * 60_000}
      />,
    );

    expect(screen.getByRole("status")).toHaveTextContent(
      "Il reste 2 créneaux vides sur 48",
    );
  });

  it("thanks everyone once the chain is complete", () => {
    render(
      <EmptySlotsBanner
        editionId="edition-1"
        slots={edition()}
        nowMs={START_MS - 1}
      />,
    );

    expect(screen.getByRole("status")).toHaveTextContent(
      "La chaîne est complète. Merci !",
    );
    expect(
      screen.queryByRole("list", { name: "Créneaux vides" }),
    ).not.toBeInTheDocument();
    // US-13.2: no empty slot, no draw
    expect(
      screen.queryByRole("button", { name: "Prendre un créneau vide" }),
    ).not.toBeInTheDocument();
  });

  it("offers to take an empty slot while one is left (F13-T06)", () => {
    render(
      <EmptySlotsBanner
        editionId="edition-1"
        slots={edition(9)}
        nowMs={START_MS - 1}
      />,
    );

    expect(
      screen.getByRole("button", { name: "Prendre un créneau vide" }),
    ).toBeInTheDocument();
  });

  it("is not shown once every slot is over", () => {
    const { container } = render(
      <EmptySlotsBanner
        editionId="edition-1"
        slots={edition(5)}
        nowMs={START_MS + 25 * 3600_000}
      />,
    );

    expect(container).toBeEmptyDOMElement();
  });

  it("scrolls to the slot and moves the focus onto it", () => {
    const card = document.createElement("article");
    card.id = "creneau-slot-9";
    card.tabIndex = -1;
    card.scrollIntoView = vi.fn();
    document.body.append(card);

    render(
      <EmptySlotsBanner
        editionId="edition-1"
        slots={edition(9)}
        nowMs={START_MS - 1}
      />,
    );
    fireEvent.click(screen.getByRole("button", { name: "0h00–0h30" }));

    expect(card.scrollIntoView).toHaveBeenCalled();
    expect(card).toHaveFocus();
    card.remove();
  });
});

describe("EmptySlotsBanner — longue liste (F13-T05)", () => {
  it("shows the first twelve schedules, the others on demand", () => {
    const all = Array.from({ length: 48 }, (_, index) => index + 1);
    render(
      <EmptySlotsBanner
        editionId="edition-1"
        slots={edition(...all)}
        nowMs={START_MS - 1}
      />,
    );

    const list = screen.getByRole("list", { name: "Créneaux vides" });
    expect(within(list).getAllByRole("button")).toHaveLength(12);

    const toggle = screen.getByRole("button", {
      name: "Afficher les 36 autres horaires",
    });
    expect(toggle).toHaveAttribute("aria-expanded", "false");
    fireEvent.click(toggle);

    expect(within(list).getAllByRole("button")).toHaveLength(48);
    expect(
      screen.getByRole("button", { name: "Afficher moins d'horaires" }),
    ).toHaveAttribute("aria-expanded", "true");
  });
});
