import { screen, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

const SLOT_ID = "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b";
const TEXT_URL = `http://localhost:8080/api/slots/${SLOT_ID}/text`;

beforeEach(() => {
  vi.stubEnv("TZ", "Europe/Paris");
});

afterEach(() => {
  vi.unstubAllEnvs();
});

function slotText(text: unknown) {
  server.use(
    http.get(TEXT_URL, () =>
      HttpResponse.json({
        "@context": "/api/contexts/SlotText",
        "@id": `/api/slots/${SLOT_ID}/text`,
        "@type": "SlotText",
        id: SLOT_ID,
        editionId: "01936f2e-0000-7b1e-8f2a-3c4d5e6f7a8b",
        editionTitle: "24 heures de prière",
        startsAt: "2099-11-28T19:00:00+00:00",
        endsAt: "2099-11-28T19:30:00+00:00",
        label: "20h00–20h30",
        dayLabel: "samedi 28 novembre",
        text,
      }),
    ),
  );
}

describe("SlotTextPage (F12-T04, US-12.1)", () => {
  it("shows the schedule, the title, the source, the body and the hints", async () => {
    slotText({
      title: "Que rien ne te trouble",
      source: "Thérèse d’Avila",
      body: "<p>Que rien ne te <strong>trouble</strong>.</p>",
      hints: "Relire lentement.\nS’arrêter sur un mot.",
    });

    renderApp(`/textes/${SLOT_ID}`);

    expect(
      await screen.findByRole("heading", {
        level: 1,
        name: "Que rien ne te trouble",
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByText("Samedi 28 novembre, 20h00–20h30"),
    ).toBeInTheDocument();
    expect(screen.getByText("Thérèse d’Avila")).toBeInTheDocument();
    // The body is the sanitized HTML of the library, rendered as is
    expect(
      screen.getByText("trouble", { selector: "strong" }),
    ).toBeInTheDocument();

    const hints = screen.getByRole("region", { name: "Pistes de méditation" });
    expect(hints).toHaveTextContent("Relire lentement.");
    expect(hints).toHaveTextContent("S’arrêter sur un mot.");
  });

  it("adds the local time for a priant outside Europe/Paris (RG-PL-3)", async () => {
    vi.stubEnv("TZ", "America/Montreal");
    slotText(null);

    renderApp(`/textes/${SLOT_ID}`);

    expect(
      await screen.findByText(
        "Samedi 28 novembre, 20h00–20h30 (14h00 chez vous)",
      ),
    ).toBeInTheDocument();
  });

  it("says the text will soon be available when none is attributed (RG-TX-2)", async () => {
    slotText(null);

    renderApp(`/textes/${SLOT_ID}`);

    expect(
      await screen.findByRole("heading", {
        level: 1,
        name: "Le texte de votre créneau",
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByText("Le texte de ce créneau sera bientôt disponible."),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("region", { name: "Pistes de méditation" }),
    ).not.toBeInTheDocument();
  });

  it("explains that the text is reserved to the priants of the slot (US-12.2)", async () => {
    server.use(
      http.get(TEXT_URL, () =>
        HttpResponse.json({ title: "Not Found" }, { status: 404 }),
      ),
    );

    renderApp(`/textes/${SLOT_ID}`);

    expect(
      await screen.findByRole("heading", { level: 1, name: "Texte réservé" }),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        "Le texte complet est réservé aux priants inscrits sur ce créneau.",
      ),
    ).toBeInTheDocument();
    const main = screen.getByRole("main");
    expect(
      within(main).getByRole("link", { name: "M'inscrire sur ce créneau" }),
    ).toHaveAttribute("href", `/inscription?creneaux=${SLOT_ID}`);
    expect(
      within(main).getByRole("link", { name: "Recevoir mon lien personnel" }),
    ).toHaveAttribute("href", "/receive-my-link");
  });
});
