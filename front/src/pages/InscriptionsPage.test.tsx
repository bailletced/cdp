import { screen, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

const organizerMe = () =>
  http.get("http://localhost:8080/api/me", () => {
    return HttpResponse.json({
      email: "anne@bailletced.org",
      isOrganizer: true,
    });
  });

const EDITION_ID = "11111111-1111-1111-1111-111111111111";
const editionUrl = `http://localhost:8080/api/admin/editions/${EDITION_ID}`;
const inscriptionsUrl = "http://localhost:8080/api/admin/inscriptions";

function editionItem() {
  return {
    "@context": "/api/contexts/AdminEdition",
    "@id": `/api/admin/editions/${EDITION_ID}`,
    "@type": "AdminEdition",
    id: EDITION_ID,
    title: "24 heures de prière — novembre 2026",
    lifecycle: "scheduled",
    status: "open",
    slots: null,
    slotsWithoutText: 0,
  };
}

function inscriptionsCollection(member: unknown[]) {
  return HttpResponse.json({
    "@context": "/api/contexts/AdminInscription",
    "@id": "/api/admin/inscriptions",
    "@type": "Collection",
    member,
    totalItems: member.length,
  });
}

function inscription(overrides: Record<string, unknown> = {}, index = 0) {
  return {
    "@context": "/api/contexts/AdminInscription",
    "@id": `/api/admin/inscriptions/${index + 1}`,
    "@type": "AdminInscription",
    id: `22222222-2222-2222-2222-00000000000${index + 1}`,
    displayName: "Famille Martin",
    email: "famille.martin@bailletced.org",
    origin: "self",
    slotId: "33333333-3333-3333-3333-000000000001",
    slotRank: 1,
    slotLabel: "20h00–20h30",
    startsAt: "2026-11-28T19:00:00+00:00",
    endsAt: "2026-11-28T19:30:00+00:00",
    ...overrides,
  };
}

const inscriptions = [
  inscription(),
  inscription(
    { displayName: "Marie Dupont", email: "marie@bailletced.org" },
    1,
  ),
];

describe("InscriptionsPage", () => {
  it("lists the registered names and e-mails, slot by slot", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () => HttpResponse.json(editionItem())),
      http.get(inscriptionsUrl, () => inscriptionsCollection(inscriptions)),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/inscriptions`);

    // Edition title, from the admin edition query
    expect(
      await screen.findByRole("heading", {
        name: "Inscriptions — 24 heures de prière — novembre 2026",
      }),
    ).toBeInTheDocument();

    // Accessible table with column headers (F16-T12)
    const table = await screen.findByRole("table");
    const headers = within(table).getAllByRole("columnheader");
    expect(headers.map((header) => header.textContent)).toEqual([
      "Créneau",
      "Nom",
      "Adresse e-mail",
    ]);

    const rows = within(table).getAllByRole("row");
    // Header row + one row per inscription
    expect(rows).toHaveLength(3);

    const firstRow = rows[1];
    expect(firstRow).toHaveTextContent("20h00–20h30");
    expect(firstRow).toHaveTextContent("Famille Martin");
    expect(firstRow).toHaveTextContent("famille.martin@bailletced.org");

    expect(rows[2]).toHaveTextContent("Marie Dupont");
    expect(rows[2]).toHaveTextContent("marie@bailletced.org");

    // Back to the edition detail
    expect(
      screen.getByRole("link", { name: "Retour à l’édition" }),
    ).toHaveAttribute("href", `/organisation/editions/${EDITION_ID}`);
  });

  it("shows a dash for a priant without e-mail (registered by phone)", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () => HttpResponse.json(editionItem())),
      http.get(inscriptionsUrl, () =>
        inscriptionsCollection([
          // API Platform skips null values: no email key at all
          inscription({
            "@id": "/api/admin/inscriptions/1",
            id: "22222222-2222-2222-2222-000000000011",
            displayName: "Marie-grand-mère de Thomas",
            email: undefined,
            origin: "organizer",
            slotId: "33333333-3333-3333-3333-000000000004",
            slotRank: 4,
            slotLabel: "21h30–22h00",
            startsAt: "2026-11-28T20:30:00+00:00",
            endsAt: "2026-11-28T21:00:00+00:00",
          }),
        ]),
      ),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/inscriptions`);

    const table = await screen.findByRole("table");
    const rows = within(table).getAllByRole("row");
    expect(rows).toHaveLength(2);
    expect(rows[1]).toHaveTextContent("Marie-grand-mère de Thomas");
    expect(rows[1]).toHaveTextContent("—");
  });

  it("shows an empty state when the edition has no inscription yet", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () => HttpResponse.json(editionItem())),
      http.get(inscriptionsUrl, () => inscriptionsCollection([])),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/inscriptions`);

    expect(
      await screen.findByText("Aucune inscription pour le moment."),
    ).toBeInTheDocument();
    expect(screen.queryByRole("table")).not.toBeInTheDocument();
  });

  it("shows an error state when the list cannot be loaded", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () => HttpResponse.json(editionItem())),
      http.get(inscriptionsUrl, () =>
        HttpResponse.json({ detail: "Error" }, { status: 500 }),
      ),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/inscriptions`);

    expect(
      await screen.findByText("Les inscriptions n’ont pas pu être chargées."),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "Réessayer" }),
    ).toBeInTheDocument();
  });
});
