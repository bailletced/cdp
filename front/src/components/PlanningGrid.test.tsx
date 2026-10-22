import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render, screen, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { editionSlotsQueryOptions } from "../api/hooks";
import { server } from "../test/setup";
import { PlanningGrid } from "./PlanningGrid";

const EDITION_ID = "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b";
const SLOTS_URL = `http://localhost:8080/api/editions/${EDITION_ID}/slots`;

function renderGrid() {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  });

  return render(
    <QueryClientProvider client={queryClient}>
      <PlanningGrid editionId={EDITION_ID} />
    </QueryClientProvider>,
  );
}

interface SlotOverrides {
  textTitle?: string | null;
  textSource?: string | null;
}

function slot(
  rank: number,
  partOfDay: string,
  label: string,
  overrides: SlotOverrides = {},
) {
  return {
    id: `slot-${rank}`,
    rank,
    startsAt: "2026-11-28T18:00:00+00:00",
    endsAt: "2026-11-28T18:30:00+00:00",
    label,
    partOfDay,
    state: "future",
    textTitle: null,
    textSource: null,
    ...overrides,
  };
}

function serveSlots(slots: unknown[]) {
  server.use(
    http.get(SLOTS_URL, () => {
      return HttpResponse.json({
        "@context": "/api/contexts/PublicEditionSlots",
        "@id": `/api/editions/${EDITION_ID}/slots`,
        "@type": "PublicEditionSlots",
        id: EDITION_ID,
        slots,
      });
    }),
  );
}

describe("PlanningGrid (US-08.2)", () => {
  it("groups the slots by part of day, with section titles, in chronological order", async () => {
    serveSlots([
      slot(1, "evening", "19h00–19h30"),
      slot(2, "evening", "19h30–20h00"),
      slot(3, "night", "0h00–0h30"),
      slot(4, "morning", "6h00–6h30"),
    ]);

    renderGrid();

    expect(
      await screen.findByRole("region", { name: "Grille des créneaux" }),
    ).toBeInTheDocument();

    const titles = screen
      .getAllByRole("heading", { level: 2 })
      .map((heading) => heading.textContent);
    expect(titles).toEqual(["Soirée", "Nuit", "Matin"]);

    // Chronological order inside a group
    const soiree = screen.getByRole("region", { name: "Soirée" });
    const cards = within(soiree).getAllByRole("article");
    expect(cards).toHaveLength(2);
    expect(cards[0].textContent).toContain("19h00–19h30");
    expect(cards[1].textContent).toContain("19h30–20h00");
  });

  it("keeps two separate « evening » runs when the edition wraps past midnight", async () => {
    serveSlots([
      slot(1, "evening", "19h00–19h30"),
      slot(2, "night", "0h00–0h30"),
      slot(3, "morning", "6h00–6h30"),
      slot(4, "afternoon", "12h00–12h30"),
      slot(5, "evening", "19h00–19h30"),
    ]);

    renderGrid();

    await screen.findByRole("region", { name: "Grille des créneaux" });

    const titles = screen
      .getAllByRole("heading", { level: 2 })
      .map((heading) => heading.textContent);
    expect(titles).toEqual(["Soirée", "Nuit", "Matin", "Après-midi", "Soirée"]);
  });

  it("renders a SlotCard per slot, with its state and its text (or RG-TX-2)", async () => {
    serveSlots([
      slot(1, "evening", "19h00–19h30", {
        textTitle: "Oraison de Thérèse",
        textSource: "Thérèse de Lisieux",
      }),
      slot(2, "evening", "19h30–20h00"),
    ]);

    renderGrid();

    await screen.findByRole("region", { name: "Grille des créneaux" });

    // The registration count is not part of the payload yet (F09-T13):
    // slots show as empty
    expect(screen.getAllByText("À pourvoir")).toHaveLength(2);
    expect(screen.getAllByText("0 priant")).toHaveLength(2);
    expect(screen.getByText("Oraison de Thérèse")).toBeInTheDocument();
    expect(screen.getByText("Thérèse de Lisieux")).toBeInTheDocument();
    expect(
      screen.getByText("Le texte de ce créneau sera bientôt disponible"),
    ).toBeInTheDocument();
  });

  it("shows an empty state when the edition has no slot", async () => {
    serveSlots([]);

    renderGrid();

    expect(
      await screen.findByText("Aucun créneau pour le moment."),
    ).toBeInTheDocument();
  });

  it("shows an error state when the slots cannot be loaded", async () => {
    server.use(
      http.get(SLOTS_URL, () => {
        return new HttpResponse(null, { status: 500 });
      }),
    );

    renderGrid();

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Les créneaux n'ont pas pu être chargés.",
    );
    expect(
      screen.getByRole("button", { name: "Réessayer" }),
    ).toBeInTheDocument();
  });

  it("refreshes the slots every 60 s and on window focus (US-08.4)", () => {
    const options = editionSlotsQueryOptions(EDITION_ID);

    expect(options.refetchInterval).toBe(60_000);
    expect(options.refetchOnWindowFocus).toBe(true);
  });
});
