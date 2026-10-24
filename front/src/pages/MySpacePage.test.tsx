import { fireEvent, screen, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

const MY_INSCRIPTIONS_URL = "http://localhost:8080/api/me/inscriptions";

beforeEach(() => {
  vi.stubEnv("TZ", "Europe/Paris");
});

afterEach(() => {
  vi.unstubAllEnvs();
});

function myInscription(overrides: Record<string, unknown> = {}) {
  return {
    id: "inscription-1",
    slotId: "slot-1",
    startsAt: "2099-11-28T19:00:00+00:00",
    endsAt: "2099-11-28T19:30:00+00:00",
    label: "20h00–20h30",
    dayLabel: "samedi 28 novembre",
    textTitle: null,
    textSource: null,
    state: "future",
    actions: ["cancel"],
    ...overrides,
  };
}

function mySpace(inscriptions: unknown[], displayName = "Famille Martin") {
  server.use(
    http.get(MY_INSCRIPTIONS_URL, () =>
      HttpResponse.json({
        "@context": "/api/contexts/MyInscriptions",
        "@id": "/api/me/inscriptions",
        "@type": "MyInscriptions",
        editionId: "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b",
        editionTitle: "24 heures de prière",
        displayName,
        inscriptions,
      }),
    ),
  );
}

describe("MySpacePage — mes créneaux (F14-T07, US-14.1)", () => {
  it("shows my name, then my slots with their schedule and their text", async () => {
    mySpace([
      myInscription(),
      myInscription({
        id: "inscription-2",
        slotId: "slot-5",
        startsAt: "2099-11-28T21:00:00+00:00",
        endsAt: "2099-11-28T21:30:00+00:00",
        label: "22h00–22h30",
        textTitle: "Que rien ne te trouble",
        textSource: "Thérèse d’Avila",
      }),
    ]);

    renderApp("/my-space");

    expect(await screen.findByText("Famille Martin")).toBeInTheDocument();
    const mine = screen.getByRole("region", { name: "Mes créneaux" });
    const [first, second] = within(mine).getAllByRole("listitem");
    expect(first).toHaveTextContent("Samedi 28 novembre");
    expect(first).toHaveTextContent("20h00–20h30");
    expect(first).toHaveTextContent(
      "Le texte de ce créneau sera bientôt disponible",
    );
    expect(
      within(first).queryByRole("link", { name: /Lire le texte/ }),
    ).toBeNull();
    expect(second).toHaveTextContent("Que rien ne te trouble");
    expect(
      within(second).getByRole("link", {
        name: "Lire le texte du créneau de 22h00–22h30",
      }),
    ).toHaveAttribute("href", "/textes/slot-5");
  });

  it("adds the local time for a priant outside Europe/Paris (RG-PL-3)", async () => {
    vi.stubEnv("TZ", "America/Montreal");
    mySpace([myInscription()]);

    renderApp("/my-space");

    expect(
      await screen.findByText("20h00–20h30 (14h00 chez vous)"),
    ).toBeInTheDocument();
  });

  it("invites to choose a slot when I have none yet", async () => {
    mySpace([]);

    renderApp("/my-space");

    expect(
      await screen.findByText("Vous n'avez pas encore de créneau."),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("link", { name: "Choisir un créneau" }),
    ).toHaveAttribute("href", "#ajouter-des-creneaux");
  });

  it("invites to receive the personal link again once the session is gone", async () => {
    server.use(
      http.get(
        MY_INSCRIPTIONS_URL,
        () => new HttpResponse(null, { status: 401 }),
      ),
    );

    renderApp("/my-space");

    expect(
      await screen.findByText(
        "Votre session a expiré : recevez votre lien personnel pour retrouver vos créneaux.",
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("link", { name: "Recevoir mon lien personnel" }),
    ).toHaveAttribute("href", "/receive-my-link");
  });
});

describe("MySpacePage", () => {
  it("shows the viewer email and a logout button for an authenticated viewer", async () => {
    renderApp("/my-space");

    expect(
      await screen.findByRole("heading", { name: "Mon espace" }),
    ).toBeInTheDocument();
    expect(
      await screen.findByText("thomas@bailletced.org"),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("heading", { name: "Ajouter des créneaux" }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "Me déconnecter" }),
    ).toBeInTheDocument();
  });

  it("shows the planning grid of the current edition", async () => {
    server.use(
      http.get("http://localhost:8080/api/editions/current", () =>
        HttpResponse.json({
          id: "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b",
          title: "24 heures de prière — novembre 2026",
          presentation: "",
          startsAt: "2026-11-27T18:00:00+00:00",
          opensAt: "2026-11-20T18:00:00+00:00",
          status: "open",
        }),
      ),
    );

    renderApp("/my-space");

    expect(
      await screen.findByRole("heading", {
        name: "24 heures de prière — novembre 2026",
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByText(/Début : vendredi 27 novembre 2026/),
    ).toBeInTheDocument();
    // The default MSW handler answers an empty grid
    expect(
      await screen.findByText("Aucun créneau pour le moment."),
    ).toBeInTheDocument();
  });

  it("redirects a visitor to the landing page", async () => {
    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return new HttpResponse(null, { status: 401 });
      }),
    );

    renderApp("/my-space");

    // The guard redirects before the page mounts: the landing form shows up
    expect(
      await screen.findByRole("button", { name: "S'inscrire" }),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("button", { name: "Me déconnecter" }),
    ).not.toBeInTheDocument();
  });

  it("logs out and lands on the landing page", async () => {
    let logoutPosted = false;
    server.use(
      http.post("http://localhost:8080/auth/logout", () => {
        logoutPosted = true;
        return new HttpResponse(null, { status: 200 });
      }),
      // Once logged out, the session is gone: /api/me answers 401
      http.get("http://localhost:8080/api/me", () =>
        logoutPosted
          ? new HttpResponse(null, { status: 401 })
          : HttpResponse.json({
              email: "thomas@bailletced.org",
              isOrganizer: false,
            }),
      ),
    );

    renderApp("/my-space");

    fireEvent.click(
      await screen.findByRole("button", { name: "Me déconnecter" }),
    );

    expect(
      await screen.findByRole("button", { name: "S'inscrire" }),
    ).toBeInTheDocument();
    expect(logoutPosted).toBe(true);
  });
});

describe("MySpacePage — changer mon nom (F14-T08, US-14.2)", () => {
  it("renames me everywhere, and the page shows the new name", async () => {
    let name = "Famille Martin";
    let patched: { body: unknown; contentType: string | null } | null = null;
    server.use(
      http.get(MY_INSCRIPTIONS_URL, () =>
        HttpResponse.json({
          editionId: "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b",
          editionTitle: "24 heures de prière",
          displayName: name,
          inscriptions: [],
        }),
      ),
      http.patch("http://localhost:8080/api/me", async ({ request }) => {
        patched = {
          body: await request.json(),
          contentType: request.headers.get("content-type"),
        };
        name = "Famille Martin-Dubois";

        return HttpResponse.json({ displayName: name });
      }),
    );

    renderApp("/my-space");
    fireEvent.click(
      await screen.findByRole("button", { name: "Changer mon nom" }),
    );

    const dialog = await screen.findByRole("dialog", {
      name: "Changer mon nom",
    });
    expect(dialog).toHaveTextContent(
      "Votre nom s'applique partout : dans le planning, le fil du relais, les intentions signées et l'historique.",
    );
    const field = within(dialog).getByLabelText("Nom affiché");
    expect(field).toHaveValue("Famille Martin");
    fireEvent.change(field, { target: { value: "Famille Martin-Dubois" } });
    fireEvent.click(
      within(dialog).getByRole("button", { name: "Enregistrer" }),
    );

    expect(
      await screen.findByText("Famille Martin-Dubois"),
    ).toBeInTheDocument();
    expect(screen.queryByRole("dialog")).toBeNull();
    expect(patched).toEqual({
      body: { displayName: "Famille Martin-Dubois" },
      contentType: "application/merge-patch+json",
    });
  });

  it("shows the refusal of the name on the field", async () => {
    mySpace([]);
    server.use(
      http.patch("http://localhost:8080/api/me", () =>
        HttpResponse.json(
          {
            status: 422,
            violations: [
              {
                propertyPath: "displayName",
                message:
                  "Le nom affiché doit comporter entre 1 et 80 caractères.",
              },
            ],
          },
          { status: 422 },
        ),
      ),
    );

    renderApp("/my-space");
    fireEvent.click(
      await screen.findByRole("button", { name: "Changer mon nom" }),
    );
    const dialog = await screen.findByRole("dialog", {
      name: "Changer mon nom",
    });
    fireEvent.change(within(dialog).getByLabelText("Nom affiché"), {
      target: { value: "   " },
    });
    fireEvent.click(
      within(dialog).getByRole("button", { name: "Enregistrer" }),
    );

    expect(
      await within(dialog).findByText(
        "Le nom affiché doit comporter entre 1 et 80 caractères.",
      ),
    ).toBeInTheDocument();
    expect(within(dialog).getByLabelText("Nom affiché")).toHaveAttribute(
      "aria-invalid",
      "true",
    );
  });
});
