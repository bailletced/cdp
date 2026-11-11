import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { fireEvent, render, screen, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { server } from "../test/setup";
import { SlotPickerDialog } from "./SlotPickerDialog";

const EDITION_ID = "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b";
const SLOTS_URL = `http://localhost:8080/api/editions/${EDITION_ID}/slots`;

beforeEach(() => {
  vi.stubEnv("TZ", "Europe/Paris");
});

function openSlot(overrides: Record<string, unknown> = {}) {
  return {
    id: "slot-1",
    rank: 1,
    startsAt: "2099-11-28T21:00:00+00:00",
    endsAt: "2099-11-28T21:30:00+00:00",
    label: "22h00–22h30",
    partOfDay: "evening",
    state: "future",
    textTitle: null,
    textSource: null,
    registrationCount: 0,
    participants: [],
    mine: false,
    ...overrides,
  };
}

function renderDialog() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });

  return render(
    <QueryClientProvider client={queryClient}>
      <SlotPickerDialog editionId={EDITION_ID} />
    </QueryClientProvider>,
  );
}

/** One compact line per slot to come: schedule, priants count, take link. */
describe("SlotPickerDialog", () => {
  it("opens on « Choisir un créneau », lists the slots to come with their priants count, one link each", async () => {
    server.use(
      http.get(SLOTS_URL, () =>
        HttpResponse.json({
          id: EDITION_ID,
          slots: [
            openSlot(),
            openSlot({
              id: "slot-2",
              rank: 2,
              startsAt: "2099-11-28T21:30:00+00:00",
              endsAt: "2099-11-28T22:00:00+00:00",
              label: "22h30–23h00",
              registrationCount: 3,
            }),
          ],
        }),
      ),
    );

    renderDialog();

    expect(
      screen.queryByRole("dialog", { name: "Choisir un créneau" }),
    ).toBeNull();
    fireEvent.click(screen.getByRole("button", { name: "Choisir un créneau" }));

    const dialog = await screen.findByRole("dialog", {
      name: "Choisir un créneau",
    });
    const list = await within(dialog).findByRole("list", {
      name: "Créneaux à venir",
    });

    expect(within(list).getAllByRole("listitem")).toHaveLength(2);
    expect(within(list).getByText("22h00–22h30")).toBeInTheDocument();
    expect(within(list).getByText("0 priant")).toBeInTheDocument();
    expect(within(list).getByText("22h30–23h00")).toBeInTheDocument();
    expect(within(list).getByText("3 priants")).toBeInTheDocument();

    const takeLinks = within(list).getAllByRole("link", {
      name: "Choisir",
    });
    expect(takeLinks[0]).toHaveAttribute(
      "href",
      "/inscription?creneaux=slot-1",
    );
    expect(takeLinks[1]).toHaveAttribute(
      "href",
      "/inscription?creneaux=slot-2",
    );

    const closeFooter = within(dialog)
      .getAllByRole("button", {
        name: "Fermer",
      })
      .at(-1);
    fireEvent.click(
      closeFooter ?? within(dialog).getByRole("button", { name: "Fermer" }),
    );
    expect(
      screen.queryByRole("dialog", { name: "Choisir un créneau" }),
    ).toBeNull();
  });

  it("hides the past, shows my own slots marked and untakeable", async () => {
    server.use(
      http.get(SLOTS_URL, () =>
        HttpResponse.json({
          id: EDITION_ID,
          slots: [
            openSlot(),
            openSlot({
              id: "slot-past",
              startsAt: "2020-11-28T21:00:00+00:00",
              label: "22h00–22h30 (passé)",
            }),
            openSlot({
              id: "slot-mine",
              mine: true,
              rank: 2,
              startsAt: "2099-11-28T22:00:00+00:00",
              endsAt: "2099-11-28T22:30:00+00:00",
              label: "23h00–23h30",
            }),
          ],
        }),
      ),
    );

    renderDialog();

    fireEvent.click(screen.getByRole("button", { name: "Choisir un créneau" }));
    const dialog = await screen.findByRole("dialog", {
      name: "Choisir un créneau",
    });
    const list = await within(dialog).findByRole("list", {
      name: "Créneaux à venir",
    });

    expect(within(list).getAllByRole("listitem")).toHaveLength(2);
    expect(within(list).getByText("22h00–22h30")).toBeInTheDocument();
    expect(screen.queryByText("22h00–22h30 (passé)")).toBeNull();

    // My own slot stays visible (RG-IN-3): muted, wearing the « Vous y êtes »
    // mark, its priants count — and no « Choisir » to re-register on it
    const rows = within(list).getAllByRole("listitem");
    const mineRow = rows[1] as HTMLElement;
    expect(mineRow).toHaveTextContent(/Vous y êtes/);
    expect(mineRow).toHaveTextContent(/0 priant/);
    expect(within(mineRow).queryByRole("link", { name: "Choisir" })).toBeNull();
  });

  it("says the chain is complete once no slot is left to come", async () => {
    server.use(
      http.get(SLOTS_URL, () =>
        HttpResponse.json({
          id: EDITION_ID,
          slots: [
            openSlot({
              startsAt: "2020-11-28T21:00:00+00:00",
              endsAt: "2020-11-28T21:30:00+00:00",
              label: "22h00–22h30 (passé)",
            }),
          ],
        }),
      ),
    );

    renderDialog();

    fireEvent.click(screen.getByRole("button", { name: "Choisir un créneau" }));
    expect(
      await screen.findByText(/La chaîne est complète/),
    ).toBeInTheDocument();
  });
});
