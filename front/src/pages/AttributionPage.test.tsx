import { screen, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { beforeAll, describe, expect, it } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

const EDITION_ID = "11111111-1111-1111-1111-111111111111";
const editionUrl = `http://localhost:8080/api/admin/editions/${EDITION_ID}`;
const THERESE_ID = "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa";

const organizerMe = () =>
  http.get("http://localhost:8080/api/me", () =>
    HttpResponse.json({ email: "anne@bailletced.org", isOrganizer: true }),
  );

function attributionSlot(
  rank: number,
  label: string,
  partOfDay: string,
  text: { id: string; title: string; source: string } | null = null,
  textAlreadyUsed = false,
) {
  return {
    id: `slot-${rank}`,
    rank,
    startsAt: "2026-11-28T19:00:00+00:00",
    endsAt: "2026-11-28T19:30:00+00:00",
    label,
    partOfDay,
    textId: text?.id ?? null,
    textTitle: text?.title ?? null,
    textSource: text?.source ?? null,
    textAlreadyUsed,
  };
}

const therese = {
  id: THERESE_ID,
  title: "Que rien ne te trouble",
  source: "Thérèse d’Avila",
};

function attributionEdition(overrides: Record<string, unknown> = {}) {
  return {
    id: EDITION_ID,
    title: "24 heures de prière — novembre 2026",
    presentation: "",
    startsAt: "2026-11-28T19:00:00+00:00",
    opensAt: "2026-11-21T19:00:00+00:00",
    lifecycle: "draft",
    status: "draft",
    scheduledAt: null,
    version: 3,
    slots: [
      attributionSlot(1, "20h00–20h30", "evening", therese),
      attributionSlot(2, "20h30–21h00", "evening"),
      attributionSlot(3, "2h00–2h30", "night"),
    ],
    slotsWithoutText: 2,
    duplicatedTextIds: [],
    ...overrides,
  };
}

// The route lazy-loads the page: warm the module cache for the first test
beforeAll(async () => {
  await import("./AttributionPage");
});

describe("AttributionPage (F07-T06)", () => {
  it("lists the slots by part of day with their text or « Sans texte »", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () => HttpResponse.json(attributionEdition())),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/texts`);

    expect(
      await screen.findByRole("heading", {
        level: 1,
        name: "Attribution des textes",
      }),
    ).toBeInTheDocument();
    expect(
      await screen.findByText("24 heures de prière — novembre 2026"),
    ).toBeInTheDocument();
    expect(
      screen.getByText("2 créneaux sur 3 n’ont pas encore de texte."),
    ).toBeInTheDocument();

    const evening = screen.getByRole("region", { name: "Soirée" });
    const first = within(evening).getByText("20h00–20h30").closest("li");
    expect(first).not.toBeNull();
    expect(
      within(first as HTMLElement).getByText("Que rien ne te trouble"),
    ).toBeInTheDocument();
    expect(
      within(first as HTMLElement).getByText("Thérèse d’Avila"),
    ).toBeInTheDocument();

    const second = within(evening).getByText("20h30–21h00").closest("li");
    expect(
      within(second as HTMLElement).getByText("Sans texte"),
    ).toBeInTheDocument();
    expect(
      within(screen.getByRole("region", { name: "Nuit" })).getByText(
        "2h00–2h30",
      ),
    ).toBeInTheDocument();
  });

  it("says when every slot has its text", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () =>
        HttpResponse.json(
          attributionEdition({
            slots: [attributionSlot(1, "20h00–20h30", "evening", therese)],
            slotsWithoutText: 0,
          }),
        ),
      ),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/texts`);

    expect(
      await screen.findByText("Chaque créneau a son texte."),
    ).toBeInTheDocument();
  });

  it("is reachable from the edition detail", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () => HttpResponse.json(attributionEdition())),
    );

    renderApp(`/organisation/editions/${EDITION_ID}`);

    expect(
      await screen.findByRole("link", { name: "Attribuer les textes" }),
    ).toHaveAttribute("href", `/organisation/editions/${EDITION_ID}/texts`);
  });
});
