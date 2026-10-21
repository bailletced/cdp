import { screen } from "@testing-library/react";
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

function edition(overrides: Record<string, unknown> = {}) {
  return {
    "@context": "/api/contexts/AdminEdition",
    "@id": "/api/admin/editions/11111111-1111-1111-1111-111111111111",
    "@type": "AdminEdition",
    id: "11111111-1111-1111-1111-111111111111",
    title: "24 heures de prière — novembre 2026",
    presentation: "Une chaîne de prière continue.",
    startsAt: "2026-11-28T19:00:00+00:00",
    opensAt: "2026-11-21T19:00:00+00:00",
    lifecycle: "draft",
    status: "draft",
    scheduledAt: null,
    version: 0,
    slots: null,
    ...overrides,
  };
}

function editionsCollection(member: unknown[]) {
  return HttpResponse.json({
    "@context": "/api/contexts/AdminEdition",
    "@id": "/api/admin/editions",
    "@type": "Collection",
    member,
    totalItems: member.length,
  });
}

describe("EditionsPage", () => {
  it("lists the editions with Paris dates and status labels", async () => {
    server.use(
      organizerMe(),
      http.get("http://localhost:8080/api/admin/editions", () =>
        editionsCollection([
          edition(),
          edition({
            id: "22222222-2222-2222-2222-222222222222",
            title: "24 heures de prière — décembre 2026",
            startsAt: "2026-12-05T19:00:00+00:00",
            opensAt: "2026-11-28T19:00:00+00:00",
            lifecycle: "scheduled",
            status: "scheduled",
            scheduledAt: "2026-10-21T08:00:00+00:00",
          }),
        ]),
      ),
    );

    renderApp("/organisation/editions");

    expect(
      await screen.findByRole("heading", { name: "Éditions" }),
    ).toBeInTheDocument();
    // The back-office navigation exposes the editions section
    expect(screen.getByRole("link", { name: "Éditions" })).toBeInTheDocument();
    // The header links to the creation form (F05-T20)
    expect(
      screen.getByRole("link", { name: "Nouvelle édition" }),
    ).toHaveAttribute("href", "/organisation/editions/new");
    expect(
      await screen.findByText("24 heures de prière — novembre 2026"),
    ).toBeInTheDocument();
    expect(
      screen.getByText("24 heures de prière — décembre 2026"),
    ).toBeInTheDocument();
    // Status labels (spec §5.1)
    expect(screen.getByText("Brouillon")).toBeInTheDocument();
    expect(screen.getByText("Programmée")).toBeInTheDocument();
    // Dates formatted in Paris time: 19:00 UTC = 20:00 in Paris (CET)
    expect(
      screen.getByText(/Début : samedi 28 novembre 2026 à 20:00/),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        /Ouverture des inscriptions : samedi 21 novembre 2026 à 20:00/,
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(/Début : samedi 5 décembre 2026 à 20:00/),
    ).toBeInTheDocument();
  });

  it("shows the empty state when there is no edition", async () => {
    server.use(
      organizerMe(),
      http.get("http://localhost:8080/api/admin/editions", () =>
        editionsCollection([]),
      ),
    );

    renderApp("/organisation/editions");

    expect(
      await screen.findByText("Aucune édition pour le moment."),
    ).toBeInTheDocument();
  });

  it("shows an error state when the editions fail to load", async () => {
    server.use(
      organizerMe(),
      http.get("http://localhost:8080/api/admin/editions", () => {
        return HttpResponse.json(
          {
            "@context": "/api/contexts/Error",
            "@id": "/api/errors/500",
            "@type": "Error",
            title: "An error occurred",
            detail: "Erreur interne.",
            status: 500,
          },
          { status: 500 },
        );
      }),
    );

    renderApp("/organisation/editions");

    expect(
      await screen.findByText("La liste des éditions n'a pas pu être chargée."),
    ).toBeInTheDocument();
  });
});
