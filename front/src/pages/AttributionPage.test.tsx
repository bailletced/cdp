import { fireEvent, screen, waitFor, within } from "@testing-library/react";
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

  it("flags a text used by several slots (« Texte déjà utilisé », F07-T10)", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () =>
        HttpResponse.json(
          attributionEdition({
            slots: [
              attributionSlot(1, "20h00–20h30", "evening", therese, true),
              attributionSlot(2, "20h30–21h00", "evening", therese, true),
              attributionSlot(3, "2h00–2h30", "night"),
            ],
            slotsWithoutText: 1,
            duplicatedTextIds: [therese.id],
          }),
        ),
      ),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/texts`);

    const first = (await screen.findByText("20h00–20h30")).closest("li");
    expect(
      within(first as HTMLElement).getByText("Texte déjà utilisé"),
    ).toBeInTheDocument();
    expect(screen.getAllByText("Texte déjà utilisé")).toHaveLength(2);
    expect(
      within(
        screen.getByText("2h00–2h30").closest("li") as HTMLElement,
      ).queryByText("Texte déjà utilisé"),
    ).not.toBeInTheDocument();
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

  it("leads to « Répartir dans l’ordre »", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () => HttpResponse.json(attributionEdition())),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/texts`);

    expect(
      await screen.findByRole("link", { name: "Répartir dans l’ordre" }),
    ).toHaveAttribute(
      "href",
      `/organisation/editions/${EDITION_ID}/distribute`,
    );
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

const PSALM = {
  id: "bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb",
  title: "L’Éternel est mon berger",
  source: "Psaume 23",
};

const slotTextUrl = (slotId: string) =>
  `http://localhost:8080/api/admin/slots/${slotId}/text`;

const library = () =>
  http.get("http://localhost:8080/api/admin/texts", ({ request }) => {
    const q = new URL(request.url).searchParams.get("q") ?? "";
    const member = [therese, PSALM]
      .filter((text) =>
        `${text.title} ${text.source}`.toLowerCase().includes(q.toLowerCase()),
      )
      .map((text) => ({ ...text, attributionCount: 0 }));

    return HttpResponse.json({ member, totalItems: member.length });
  });

function slotItem(label: string): HTMLElement {
  const item = screen.getByText(label).closest("li");
  if (!item) {
    throw new Error(`No slot item for ${label}`);
  }

  return item;
}

describe("Text selector per slot (F07-T07)", () => {
  it("assigns a text found by search to an empty slot", async () => {
    let edition = attributionEdition();
    let body: unknown = null;
    server.use(
      organizerMe(),
      library(),
      http.get(editionUrl, () => HttpResponse.json(edition)),
      http.put(slotTextUrl("slot-2"), async ({ request }) => {
        body = await request.json();
        edition = attributionEdition({
          slots: [
            attributionSlot(1, "20h00–20h30", "evening", therese),
            attributionSlot(2, "20h30–21h00", "evening", PSALM),
            attributionSlot(3, "2h00–2h30", "night"),
          ],
          slotsWithoutText: 1,
        });

        return HttpResponse.json({ id: "slot-2", textId: PSALM.id });
      }),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/texts`);

    fireEvent.click(
      await screen.findByRole("button", {
        name: "Choisir un texte pour 20h30–21h00",
      }),
    );
    const dialog = await screen.findByRole("dialog", {
      name: "Texte pour 20h30–21h00",
    });
    fireEvent.change(
      within(dialog).getByRole("searchbox", { name: "Rechercher un texte" }),
      { target: { value: "psaume" } },
    );
    await waitFor(() =>
      expect(
        within(dialog).queryByRole("button", {
          name: /Que rien ne te trouble/,
        }),
      ).not.toBeInTheDocument(),
    );
    fireEvent.click(
      within(dialog).getByRole("button", {
        name: "L’Éternel est mon berger — Psaume 23",
      }),
    );

    await waitFor(() =>
      expect(
        within(slotItem("20h30–21h00")).getByText("L’Éternel est mon berger"),
      ).toBeInTheDocument(),
    );
    expect(body).toEqual({ textId: PSALM.id });
    expect(
      await screen.findByText("1 créneau sur 3 n’a pas encore de texte."),
    ).toBeInTheDocument();
  });

  it("withdraws the text of a slot, at once", async () => {
    let edition = attributionEdition();
    let body: unknown = null;
    let answer: () => void = () => undefined;
    const answered = new Promise<void>((resolve) => {
      answer = resolve;
    });
    server.use(
      organizerMe(),
      http.get(editionUrl, () => HttpResponse.json(edition)),
      http.put(slotTextUrl("slot-1"), async ({ request }) => {
        body = await request.json();
        // The server answers only once the screen has changed
        await answered;
        edition = attributionEdition({
          slots: [
            attributionSlot(1, "20h00–20h30", "evening"),
            attributionSlot(2, "20h30–21h00", "evening"),
            attributionSlot(3, "2h00–2h30", "night"),
          ],
          slotsWithoutText: 3,
        });

        return HttpResponse.json({ id: "slot-1", textId: null });
      }),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/texts`);

    fireEvent.click(
      await screen.findByRole("button", {
        name: "Retirer le texte de 20h00–20h30",
      }),
    );

    // Optimistic: the slot is emptied before the server answers back
    await waitFor(() =>
      expect(
        within(slotItem("20h00–20h30")).getByText("Sans texte"),
      ).toBeInTheDocument(),
    );
    expect(
      screen.getByText("3 créneaux sur 3 n’ont pas encore de texte."),
    ).toBeInTheDocument();
    answer();

    await waitFor(() => expect(body).toEqual({ textId: null }));
    expect(
      within(slotItem("20h00–20h30")).getByText("Sans texte"),
    ).toBeInTheDocument();
  });

  it("puts the text back and explains a conflict (409)", async () => {
    server.use(
      organizerMe(),
      http.get(editionUrl, () => HttpResponse.json(attributionEdition())),
      http.put(slotTextUrl("slot-1"), () =>
        HttpResponse.json(
          {
            status: 409,
            detail:
              "L’édition a été modifiée entre-temps. Rechargez la page pour voir les dernières modifications.",
          },
          { status: 409 },
        ),
      ),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/texts`);

    fireEvent.click(
      await screen.findByRole("button", {
        name: "Retirer le texte de 20h00–20h30",
      }),
    );

    expect(
      await screen.findByText(
        "L’édition a été modifiée entre-temps. Rechargez la page pour voir les dernières modifications.",
      ),
    ).toBeInTheDocument();
    expect(
      within(slotItem("20h00–20h30")).getByText("Que rien ne te trouble"),
    ).toBeInTheDocument();
  });
});
