import { screen, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

const EDITION_ID = "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b";
const CURRENT_EDITION_URL = "http://localhost:8080/api/editions/current";
const SLOTS_URL = `http://localhost:8080/api/editions/${EDITION_ID}/slots`;

const SLOT_FUTURE_1 = "slot-future-1";
const SLOT_FUTURE_2 = "slot-future-2";

beforeEach(() => {
  // Deterministic local time: a Paris visitor sees no local-time suffix (RG-PL-3)
  vi.stubEnv("TZ", "Europe/Paris");
});

afterEach(() => {
  vi.unstubAllEnvs();
});

function planningSlot(id: string, rank: number, label: string) {
  return {
    id,
    rank,
    // Far-future dates, so the fixtures never depend on the day the tests run
    startsAt: "2099-11-27T18:00:00+00:00",
    endsAt: "2099-11-27T18:30:00+00:00",
    label,
    partOfDay: "evening",
    state: "future",
    textTitle: null,
    textSource: null,
  };
}

function openEditionWithSlots(slots: unknown[]) {
  server.use(
    http.get(CURRENT_EDITION_URL, () => {
      return HttpResponse.json({
        "@context": "/api/contexts/PublicEdition",
        "@id": "/api/editions/current",
        "@type": "PublicEdition",
        id: EDITION_ID,
        title: "24 heures de prière — novembre 2099",
        presentation: "Une chaîne de prière continue.",
        startsAt: "2099-11-27T18:00:00+00:00",
        opensAt: "2099-11-20T18:00:00+00:00",
        status: "open",
      });
    }),
  );
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

describe("InscriptionConfirmationPage (F09-T19, US-09.6)", () => {
  it("récapitule les créneaux inscrits et annonce l'e-mail de confirmation", async () => {
    openEditionWithSlots([
      planningSlot(SLOT_FUTURE_1, 1, "19h00–19h30"),
      planningSlot(SLOT_FUTURE_2, 2, "19h30–20h00"),
    ]);

    renderApp(
      `/inscription/confirmation?creneaux=${SLOT_FUTURE_2},${SLOT_FUTURE_1}`,
    );

    expect(
      await screen.findByRole("heading", { name: "Inscription confirmée" }),
    ).toBeInTheDocument();
    expect(
      screen.getByText("Un e-mail de confirmation vous a été envoyé."),
    ).toBeInTheDocument();

    // The récapitulatif survives a reload: it is rebuilt from ?creneaux=
    const recap = await screen.findByRole("region", { name: "Vos créneaux" });
    const items = within(recap).getAllByRole("listitem");
    expect(items).toHaveLength(2);
    expect(items[0].textContent).toContain("19h00–19h30");
    expect(items[1].textContent).toContain("19h30–20h00");

    // Placeholders for the text link (F12) and the suggestion (F13)
    expect(
      screen.getByText(
        "Le texte à méditer pendant votre créneau sera bientôt disponible.",
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        "Vous pourrez bientôt suggérer un texte pour votre créneau.",
      ),
    ).toBeInTheDocument();

    expect(
      screen.getByRole("link", { name: "Retour à l'accueil" }),
    ).toHaveAttribute("href", "/");
  });

  it("affiche la confirmation même sans paramètre (visite directe)", async () => {
    renderApp("/inscription/confirmation");

    expect(
      await screen.findByRole("heading", { name: "Inscription confirmée" }),
    ).toBeInTheDocument();
    expect(
      screen.getByText("Un e-mail de confirmation vous a été envoyé."),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("link", { name: "Retour à l'accueil" }),
    ).toHaveAttribute("href", "/");
    // No récapitulatif, but never an empty screen
    expect(
      screen.queryByRole("region", { name: "Vos créneaux" }),
    ).not.toBeInTheDocument();
  });

  it("affiche la confirmation même quand les créneaux sont introuvables", async () => {
    openEditionWithSlots([planningSlot(SLOT_FUTURE_1, 1, "19h00–19h30")]);

    renderApp("/inscription/confirmation?creneaux=slot-inconnu");

    expect(
      await screen.findByRole("heading", { name: "Inscription confirmée" }),
    ).toBeInTheDocument();
    expect(
      screen.getByText("Un e-mail de confirmation vous a été envoyé."),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("region", { name: "Vos créneaux" }),
    ).not.toBeInTheDocument();
  });
});
