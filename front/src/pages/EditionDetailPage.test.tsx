import { fireEvent, screen, within } from "@testing-library/react";
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

function slot(rank: number, label: string, partOfDay: string) {
  return {
    rank,
    startsAt: "2026-11-28T19:00:00+00:00",
    endsAt: "2026-11-28T19:30:00+00:00",
    label,
    partOfDay,
    textId: null,
  };
}

function editionItem(overrides: Record<string, unknown> = {}) {
  return {
    "@context": "/api/contexts/AdminEdition",
    "@id": `/api/admin/editions/${EDITION_ID}`,
    "@type": "AdminEdition",
    id: EDITION_ID,
    title: "24 heures de prière — novembre 2026",
    presentation: "Une chaîne de prière continue.",
    // 20:00 in Paris (CET, UTC+1)
    startsAt: "2026-11-28T19:00:00+00:00",
    opensAt: "2026-11-21T19:00:00+00:00",
    lifecycle: "draft",
    status: "draft",
    scheduledAt: null,
    version: 3,
    slots: null,
    ...overrides,
  };
}

const scheduledEdition = editionItem({
  lifecycle: "scheduled",
  status: "scheduled",
  scheduledAt: "2026-10-21T08:00:00+00:00",
  slots: [
    slot(1, "20h00–20h30", "evening"),
    slot(2, "2h00–2h30", "night"),
    slot(3, "8h00–8h30", "morning"),
  ],
});

describe("EditionDetailPage", () => {
  it("shows the edition, its status, dates and slots grouped by part of day", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () => HttpResponse.json(scheduledEdition)),
    );

    renderApp(`/organisation/editions/${EDITION_ID}`);

    expect(
      await screen.findByRole("heading", {
        name: "24 heures de prière — novembre 2026",
      }),
    ).toBeInTheDocument();
    // Status label (spec §5.1)
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
      screen.getByText("Une chaîne de prière continue."),
    ).toBeInTheDocument();
    // Slots grouped by part of day (spec §5.2)
    expect(screen.getByRole("heading", { name: "Soirée" })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "Nuit" })).toBeInTheDocument();
    expect(screen.getByRole("heading", { name: "Matin" })).toBeInTheDocument();
    expect(screen.getByText("20h00–20h30")).toBeInTheDocument();
    expect(screen.getByText("2h00–2h30")).toBeInTheDocument();
    expect(screen.getByText("8h00–8h30")).toBeInTheDocument();
    // Link to the edit form (F05-T20)
    expect(screen.getByRole("link", { name: "Modifier" })).toHaveAttribute(
      "href",
      `/organisation/editions/${EDITION_ID}/edit`,
    );
    // Scheduled edition: the unschedule action, not the schedule one
    expect(
      screen.getByRole("button", { name: "Repasser en brouillon" }),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("button", { name: "Programmer" }),
    ).not.toBeInTheDocument();
  });

  it("schedules a draft edition", async () => {
    let scheduled = false;
    let scheduleCalled = false;
    let scheduleContentType: string | null = null;

    server.use(
      organizerMe(),
      http.get(editionUrl, () =>
        HttpResponse.json(scheduled ? scheduledEdition : editionItem()),
      ),
      http.post(`${editionUrl}/schedule`, async ({ request }) => {
        scheduleCalled = true;
        scheduleContentType = request.headers.get("content-type");
        scheduled = true;

        return HttpResponse.json(scheduledEdition, { status: 201 });
      }),
    );

    renderApp(`/organisation/editions/${EDITION_ID}`);

    fireEvent.click(await screen.findByRole("button", { name: "Programmer" }));

    // The edition query is invalidated: the page reflects the new lifecycle
    expect(await screen.findByText("Programmée")).toBeInTheDocument();
    expect(
      await screen.findByRole("button", { name: "Repasser en brouillon" }),
    ).toBeInTheDocument();
    expect(scheduleCalled).toBe(true);
    // API Platform only accepts JSON-LD bodies on this resource
    expect(scheduleContentType).toContain("application/ld+json");
  });

  it("asks for a confirmation when slots have no text yet (US-07.3, RG-ED-4)", async () => {
    let scheduleCalled = false;
    server.use(
      organizerMe(),
      http.get(editionUrl, () =>
        HttpResponse.json(
          scheduleCalled
            ? scheduledEdition
            : editionItem({ slotsWithoutText: 3 }),
        ),
      ),
      http.post(`${editionUrl}/schedule`, () => {
        scheduleCalled = true;

        return HttpResponse.json(scheduledEdition, { status: 201 });
      }),
    );

    renderApp(`/organisation/editions/${EDITION_ID}`);

    fireEvent.click(await screen.findByRole("button", { name: "Programmer" }));

    const dialog = await screen.findByRole("alertdialog", {
      name: "Programmer l’édition",
    });
    expect(
      within(dialog).getByText("3 créneaux n’ont pas encore de texte."),
    ).toBeInTheDocument();
    // Nothing is sent before the confirmation
    expect(scheduleCalled).toBe(false);

    fireEvent.click(
      within(dialog).getByRole("button", { name: "Programmer quand même" }),
    );

    expect(await screen.findByText("Programmée")).toBeInTheDocument();
    expect(scheduleCalled).toBe(true);
  });

  it("returns a scheduled edition to draft after confirmation", async () => {
    let unscheduled = false;
    let unscheduleCalled = false;

    server.use(
      organizerMe(),
      http.get(editionUrl, () =>
        HttpResponse.json(unscheduled ? editionItem() : scheduledEdition),
      ),
      http.post(`${editionUrl}/unschedule`, () => {
        unscheduleCalled = true;
        unscheduled = true;

        return HttpResponse.json(editionItem(), { status: 201 });
      }),
    );

    renderApp(`/organisation/editions/${EDITION_ID}`);

    fireEvent.click(
      await screen.findByRole("button", { name: "Repasser en brouillon" }),
    );

    // The action requires an explicit confirmation (US-05.4)
    const dialog = await screen.findByRole("alertdialog");
    expect(
      within(dialog).getByText(/Elle redeviendra invisible du public/),
    ).toBeInTheDocument();
    expect(unscheduleCalled).toBe(false);
    fireEvent.click(
      within(dialog).getByRole("button", { name: "Repasser en brouillon" }),
    );

    expect(await screen.findByText("Brouillon")).toBeInTheDocument();
    expect(
      await screen.findByRole("button", { name: "Programmer" }),
    ).toBeInTheDocument();
    expect(unscheduleCalled).toBe(true);
  });

  it("shows an error state when the edition fails to load", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () => {
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

    renderApp(`/organisation/editions/${EDITION_ID}`);

    expect(
      await screen.findByText("L'édition n'a pas pu être chargée."),
    ).toBeInTheDocument();
  });
});
