import { fireEvent, screen, waitFor, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

const TEXTS_URL = "http://localhost:8080/api/admin/texts";

const organizerMe = () =>
  http.get("http://localhost:8080/api/me", () => {
    return HttpResponse.json({
      email: "anne@bailletced.org",
      isOrganizer: true,
    });
  });

function text(overrides: Record<string, unknown> = {}) {
  return {
    "@id": "/api/admin/texts/11111111-1111-1111-1111-111111111111",
    "@type": "AdminText",
    id: "11111111-1111-1111-1111-111111111111",
    title: "Que rien ne te trouble",
    source: "Thérèse d'Avila",
    attributionCount: 3,
    ...overrides,
  };
}

function textsCollection(member: unknown[], next?: string) {
  return HttpResponse.json({
    "@context": "/api/contexts/AdminText",
    "@id": "/api/admin/texts",
    "@type": "Collection",
    member,
    totalItems: member.length,
    ...(next ? { view: { "@id": "/api/admin/texts?page=1", next } } : {}),
  });
}

describe("TextsPage (F06-T12)", () => {
  it("lists the texts with their source and attribution count", async () => {
    server.use(
      organizerMe(),
      http.get(TEXTS_URL, () =>
        textsCollection([
          text(),
          text({
            id: "22222222-2222-2222-2222-222222222222",
            title: "Psaume 22",
            source: "Psautier",
            attributionCount: 1,
          }),
          text({
            id: "33333333-3333-3333-3333-333333333333",
            title: "Nuit obscure",
            source: "Jean de la Croix",
            attributionCount: 0,
          }),
        ]),
      ),
    );

    renderApp("/organisation/texts");

    expect(
      await screen.findByRole("heading", { level: 1, name: "Textes" }),
    ).toBeInTheDocument();

    const therese = (await screen.findByText("Que rien ne te trouble")).closest(
      "li",
    );
    expect(therese).not.toBeNull();
    expect(
      within(therese as HTMLElement).getByText("Thérèse d'Avila"),
    ).toBeInTheDocument();
    expect(
      within(therese as HTMLElement).getByText("Attribué à 3 créneaux"),
    ).toBeInTheDocument();
    expect(screen.getByText("Attribué à 1 créneau")).toBeInTheDocument();
    expect(screen.getByText("Pas encore attribué")).toBeInTheDocument();
  });

  it("is reachable from the back-office navigation", async () => {
    server.use(
      organizerMe(),
      http.get(TEXTS_URL, () => textsCollection([])),
    );

    renderApp("/organisation/texts");

    const nav = await screen.findByRole("navigation", { name: "Back-office" });
    expect(within(nav).getByRole("link", { name: "Textes" })).toHaveAttribute(
      "href",
      "/organisation/texts",
    );
  });

  it("searches the title and the source", async () => {
    const queries: (string | null)[] = [];
    server.use(
      organizerMe(),
      http.get(TEXTS_URL, ({ request }) => {
        const q = new URL(request.url).searchParams.get("q");
        queries.push(q);

        return textsCollection(
          q === "therese"
            ? [text()]
            : [
                text(),
                text({ id: "2", title: "Psaume 22", source: "Psautier" }),
              ],
        );
      }),
    );

    renderApp("/organisation/texts");

    expect(await screen.findByText("Psaume 22")).toBeInTheDocument();

    fireEvent.change(screen.getByRole("searchbox", { name: "Rechercher" }), {
      target: { value: "therese" },
    });

    await waitFor(() =>
      expect(screen.queryByText("Psaume 22")).not.toBeInTheDocument(),
    );
    expect(screen.getByText("Que rien ne te trouble")).toBeInTheDocument();
    expect(queries).toContain("therese");
  });

  it("shows an empty state for an empty library", async () => {
    server.use(
      organizerMe(),
      http.get(TEXTS_URL, () => textsCollection([])),
    );

    renderApp("/organisation/texts");

    expect(
      await screen.findByText("Aucun texte pour le moment."),
    ).toBeInTheDocument();
  });

  it("says when no text matches the search", async () => {
    server.use(
      organizerMe(),
      http.get(TEXTS_URL, ({ request }) =>
        textsCollection(
          new URL(request.url).searchParams.get("q") ? [] : [text()],
        ),
      ),
    );

    renderApp("/organisation/texts");

    await screen.findByText("Que rien ne te trouble");
    fireEvent.change(screen.getByRole("searchbox", { name: "Rechercher" }), {
      target: { value: "augustin" },
    });

    expect(
      await screen.findByText("Aucun texte ne correspond à « augustin »."),
    ).toBeInTheDocument();
  });

  it("loads the next page on demand", async () => {
    server.use(
      organizerMe(),
      http.get(TEXTS_URL, ({ request }) =>
        new URL(request.url).searchParams.get("page") === "2"
          ? textsCollection([text({ id: "2", title: "Psaume 22" })])
          : textsCollection([text()], "/api/admin/texts?page=2"),
      ),
    );

    renderApp("/organisation/texts");

    fireEvent.click(
      await screen.findByRole("button", { name: "Afficher plus de textes" }),
    );

    expect(await screen.findByText("Psaume 22")).toBeInTheDocument();
    expect(screen.getByText("Que rien ne te trouble")).toBeInTheDocument();
    expect(
      screen.queryByRole("button", { name: "Afficher plus de textes" }),
    ).not.toBeInTheDocument();
  });
});
