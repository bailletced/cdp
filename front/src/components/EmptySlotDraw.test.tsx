import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import {
  fireEvent,
  render,
  screen,
  waitFor,
  within,
} from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { server } from "../test/setup";
import { EmptySlotDraw } from "./EmptySlotDraw";

const EDITION_ID = "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b";
const SUGGESTION_URL = `http://localhost:8080/api/editions/${EDITION_ID}/slot-suggestion`;

beforeEach(() => {
  vi.stubEnv("TZ", "Europe/Paris");
});

afterEach(() => {
  vi.unstubAllEnvs();
});

function suggestion(slotId: string, label: string, textTitle: string | null) {
  return {
    "@context": "/api/contexts/SlotSuggestion",
    "@id": `/api/editions/${EDITION_ID}/slot-suggestion`,
    "@type": "SlotSuggestion",
    id: EDITION_ID,
    slotId,
    startsAt: "2099-11-28T19:00:00+00:00",
    endsAt: "2099-11-28T19:30:00+00:00",
    label,
    textTitle,
    textSource: null,
  };
}

function renderDraw() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });

  return render(
    <QueryClientProvider client={queryClient}>
      <EmptySlotDraw editionId={EDITION_ID} />
    </QueryClientProvider>,
  );
}

describe("EmptySlotDraw (F13-T06, US-13.2)", () => {
  it("offers a drawn slot with its schedule and the title of its text", async () => {
    server.use(
      http.get(SUGGESTION_URL, () =>
        HttpResponse.json(
          suggestion("slot-12", "20h00–20h30", "Que rien ne te trouble"),
        ),
      ),
    );
    renderDraw();

    fireEvent.click(
      screen.getByRole("button", { name: "Prendre un créneau vide" }),
    );

    const dialog = await screen.findByRole("dialog", {
      name: "Un créneau vide pour vous",
    });
    expect(await within(dialog).findByText("20h00–20h30")).toBeInTheDocument();
    expect(
      within(dialog).getByText("Que rien ne te trouble"),
    ).toBeInTheDocument();
    expect(
      within(dialog).getByRole("link", { name: "Je le prends" }),
    ).toHaveAttribute("href", "/inscription?creneaux=slot-12");
    // The focus moved into the dialog (React Aria, next animation frame)
    await waitFor(() =>
      expect(dialog.contains(document.activeElement)).toBe(true),
    );
  });

  it("draws another slot, excluding the ones already offered", async () => {
    const excluded: string[] = [];
    server.use(
      http.get(SUGGESTION_URL, ({ request }) => {
        const exclude = new URL(request.url).searchParams.get("exclude") ?? "";
        excluded.push(exclude);

        return exclude === ""
          ? HttpResponse.json(suggestion("slot-12", "20h00–20h30", null))
          : HttpResponse.json(suggestion("slot-30", "5h00–5h30", null));
      }),
    );
    renderDraw();

    fireEvent.click(
      screen.getByRole("button", { name: "Prendre un créneau vide" }),
    );
    const dialog = await screen.findByRole("dialog");
    expect(await within(dialog).findByText("20h00–20h30")).toBeInTheDocument();
    // RG-TX-2 when the slot has no text yet
    expect(
      within(dialog).getByText(
        "Le texte de ce créneau sera bientôt disponible",
      ),
    ).toBeInTheDocument();

    fireEvent.click(within(dialog).getByRole("button", { name: "Un autre" }));

    expect(await within(dialog).findByText("5h00–5h30")).toBeInTheDocument();
    expect(excluded).toEqual(["", "slot-12"]);
    expect(
      within(dialog).getByRole("link", { name: "Je le prends" }),
    ).toHaveAttribute("href", "/inscription?creneaux=slot-30");
  });

  it("says when the drawn slot is the last empty one", async () => {
    server.use(
      http.get(SUGGESTION_URL, () =>
        HttpResponse.json(suggestion("slot-12", "20h00–20h30", null)),
      ),
    );
    renderDraw();

    fireEvent.click(
      screen.getByRole("button", { name: "Prendre un créneau vide" }),
    );
    const dialog = await screen.findByRole("dialog");
    await within(dialog).findByText("20h00–20h30");
    fireEvent.click(within(dialog).getByRole("button", { name: "Un autre" }));

    expect(
      await within(dialog).findByText("C'est le dernier créneau vide."),
    ).toBeInTheDocument();
  });

  it("says the chain is complete when no empty slot is left", async () => {
    server.use(
      http.get(SUGGESTION_URL, () =>
        HttpResponse.json({ title: "Not Found" }, { status: 404 }),
      ),
    );
    renderDraw();

    fireEvent.click(
      screen.getByRole("button", { name: "Prendre un créneau vide" }),
    );

    expect(
      await screen.findByText("La chaîne est complète. Merci !"),
    ).toBeInTheDocument();
    expect(screen.queryByRole("link", { name: "Je le prends" })).toBeNull();
  });

  it("gives the focus back to the button once closed", async () => {
    server.use(
      http.get(SUGGESTION_URL, () =>
        HttpResponse.json(suggestion("slot-12", "20h00–20h30", null)),
      ),
    );
    renderDraw();
    const trigger = screen.getByRole("button", {
      name: "Prendre un créneau vide",
    });

    // The focus returns to the element focused before the opening
    trigger.focus();
    fireEvent.click(trigger);
    const dialog = await screen.findByRole("dialog");
    fireEvent.click(within(dialog).getByRole("button", { name: "Fermer" }));

    await waitFor(() => expect(screen.queryByRole("dialog")).toBeNull());
    expect(trigger).toHaveFocus();
  });
});
