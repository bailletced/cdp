import { fireEvent, screen, waitFor, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { beforeAll, describe, expect, it } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

const EDITION_ID = "11111111-1111-1111-1111-111111111111";
const editionUrl = `http://localhost:8080/api/admin/editions/${EDITION_ID}`;

const THERESE = {
  id: "aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa",
  title: "Que rien ne te trouble",
  source: "Thérèse d’Avila",
};
const PSALM = {
  id: "bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb",
  title: "L’Éternel est mon berger",
  source: "Psaume 23",
};
const NIGHT = {
  id: "cccccccc-cccc-cccc-cccc-cccccccccccc",
  title: "Nuit obscure",
  source: "Jean de la Croix",
};

const organizerMe = () =>
  http.get("http://localhost:8080/api/me", () =>
    HttpResponse.json({ email: "anne@bailletced.org", isOrganizer: true }),
  );

const library = () =>
  http.get("http://localhost:8080/api/admin/texts", () =>
    HttpResponse.json({
      member: [THERESE, PSALM, NIGHT].map((text) => ({
        ...text,
        attributionCount: 0,
      })),
      totalItems: 3,
    }),
  );

// 48 slots: the start slot and the preview depend on the real count
const LABELS = Array.from({ length: 48 }, (_, index) => {
  const minutes = 20 * 60 + index * 30;
  const hour = Math.floor(minutes / 60) % 24;
  const minute = minutes % 60 === 0 ? "00" : "30";
  const end = minutes + 30;
  const endHour = Math.floor(end / 60) % 24;
  const endMinute = end % 60 === 0 ? "00" : "30";

  return `${hour}h${minute}–${endHour}h${endMinute}`;
});

const edition = () =>
  http.get(editionUrl, () =>
    HttpResponse.json({
      id: EDITION_ID,
      title: "24 heures de prière — novembre 2026",
      presentation: "",
      startsAt: "2026-11-28T19:00:00+00:00",
      opensAt: "2026-11-21T19:00:00+00:00",
      lifecycle: "draft",
      status: "draft",
      scheduledAt: null,
      version: 3,
      slots: LABELS.map((label, index) => ({
        id: `slot-${index + 1}`,
        rank: index + 1,
        startsAt: "2026-11-28T19:00:00+00:00",
        endsAt: "2026-11-28T19:30:00+00:00",
        label,
        partOfDay: "evening",
        textId: null,
        textTitle: null,
        textSource: null,
        textAlreadyUsed: false,
      })),
      slotsWithoutText: 48,
      duplicatedTextIds: [],
    }),
  );

// RAC buttons answer the keyboard on keydown + keyup, like a real key press
function pressWithKeyboard(element: HTMLElement) {
  element.focus();
  fireEvent.keyDown(element, { key: "Enter" });
  fireEvent.keyUp(element, { key: "Enter" });
}

function orderedTitles(): string[] {
  const list = screen.getByRole("grid", { name: "Textes à répartir" });

  return within(list)
    .getAllByRole("row")
    .map(
      (row) =>
        row.querySelector("[data-text-title]")?.textContent?.trim() ?? "",
    );
}

async function addToList(title: string) {
  fireEvent.click(
    await screen.findByRole("button", { name: `Ajouter « ${title} »` }),
  );
}

beforeAll(async () => {
  await import("./DistributePage");
});

describe("DistributePage — ordered list (F07-T08)", () => {
  it("builds the ordered list from the library", async () => {
    server.use(organizerMe(), library(), edition());

    renderApp(`/organisation/editions/${EDITION_ID}/distribute`);

    expect(
      await screen.findByRole("heading", {
        level: 1,
        name: "Répartir dans l’ordre",
      }),
    ).toBeInTheDocument();
    await addToList(THERESE.title);
    await addToList(PSALM.title);

    await waitFor(() =>
      expect(orderedTitles()).toEqual([THERESE.title, PSALM.title]),
    );
  });

  it("adds every text shown at once", async () => {
    server.use(organizerMe(), library(), edition());

    renderApp(`/organisation/editions/${EDITION_ID}/distribute`);

    fireEvent.click(
      await screen.findByRole("button", {
        name: "Ajouter les 3 textes affichés",
      }),
    );

    await waitFor(() =>
      expect(orderedTitles()).toEqual([
        THERESE.title,
        PSALM.title,
        NIGHT.title,
      ]),
    );
  });

  it("reorders with the keyboard alone (« Monter » / « Descendre »)", async () => {
    server.use(organizerMe(), library(), edition());

    renderApp(`/organisation/editions/${EDITION_ID}/distribute`);

    await addToList(THERESE.title);
    await addToList(PSALM.title);
    await addToList(NIGHT.title);

    pressWithKeyboard(
      await screen.findByRole("button", {
        name: `Descendre « ${THERESE.title} »`,
      }),
    );
    await waitFor(() =>
      expect(orderedTitles()).toEqual([
        PSALM.title,
        THERESE.title,
        NIGHT.title,
      ]),
    );

    pressWithKeyboard(
      screen.getByRole("button", { name: `Monter « ${NIGHT.title} »` }),
    );
    await waitFor(() =>
      expect(orderedTitles()).toEqual([
        PSALM.title,
        NIGHT.title,
        THERESE.title,
      ]),
    );

    // The ends of the list cannot move further
    expect(
      screen.getByRole("button", { name: `Monter « ${PSALM.title} »` }),
    ).toBeDisabled();
    expect(
      screen.getByRole("button", { name: `Descendre « ${THERESE.title} »` }),
    ).toBeDisabled();
  });

  it("takes a text out of the list", async () => {
    server.use(organizerMe(), library(), edition());

    renderApp(`/organisation/editions/${EDITION_ID}/distribute`);

    await addToList(THERESE.title);
    await addToList(PSALM.title);
    fireEvent.click(
      await screen.findByRole("button", {
        name: `Retirer « ${THERESE.title} » de la liste`,
      }),
    );

    await waitFor(() => expect(orderedTitles()).toEqual([PSALM.title]));
  });
});
