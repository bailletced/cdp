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

const EDITION_ID = "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b";

/**
 * A priant in session who already consented, an open edition with one
 * slot to come, and the given answer to POST /api/inscriptions.
 */
function knownPriantWithOneSlot(
  answerInscription: Parameters<typeof http.post>[1],
) {
  server.use(
    http.get("http://localhost:8080/api/me", () =>
      HttpResponse.json({
        email: "thomas@bailletced.org",
        isOrganizer: false,
        displayName: "Thomas Baillet",
        consentGiven: true,
      }),
    ),
    http.get("http://localhost:8080/api/editions/current", () =>
      HttpResponse.json({
        id: EDITION_ID,
        title: "24 heures de prière",
        presentation: "",
        startsAt: "2099-11-27T18:00:00+00:00",
        opensAt: "2026-11-20T18:00:00+00:00",
        status: "open",
      }),
    ),
    http.get(`http://localhost:8080/api/editions/${EDITION_ID}/slots`, () =>
      HttpResponse.json({
        id: EDITION_ID,
        slots: [
          {
            id: "slot-1",
            rank: 1,
            startsAt: "2099-11-27T18:00:00+00:00",
            endsAt: "2099-11-27T18:30:00+00:00",
            label: "19h00–19h30",
            partOfDay: "evening",
            state: "future",
            textTitle: null,
            textSource: null,
            registrationCount: 0,
          },
        ],
      }),
    ),
    http.post("http://localhost:8080/api/inscriptions", answerInscription),
  );
}

async function tickAndRegister() {
  fireEvent.click(
    await screen.findByRole("checkbox", {
      name: "Sélectionner le créneau de 19h00–19h30",
    }),
  );
  fireEvent.click(screen.getByRole("button", { name: "M'inscrire (1)" }));
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

  it("registers a known priant on the ticked slots in one click, without the form", async () => {
    let posted: unknown = null;
    knownPriantWithOneSlot(async ({ request }) => {
      posted = await request.json();
      return HttpResponse.json(
        { id: EDITION_ID, displayName: "Thomas Baillet", inscriptions: [] },
        { status: 201 },
      );
    });

    renderApp("/my-space");
    await tickAndRegister();

    expect(
      await screen.findByRole("heading", { name: "Inscription confirmée" }),
    ).toBeInTheDocument();
    expect(posted).toEqual({
      slotIds: ["slot-1"],
      displayName: "Thomas Baillet",
      email: "thomas@bailletced.org",
      consent: true,
      website: "",
    });
  });

  it("says why the one-click inscription failed, and stays on the planning", async () => {
    knownPriantWithOneSlot(() =>
      HttpResponse.json(
        { detail: "Vous êtes déjà inscrit sur ce créneau." },
        { status: 409 },
      ),
    );

    renderApp("/my-space");
    await tickAndRegister();

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Vous êtes déjà inscrit sur ce créneau.",
    );
    expect(
      screen.getByRole("region", { name: "Grille des créneaux" }),
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

describe("MySpacePage — me désinscrire (F14-T09, US-14.3)", () => {
  it("offers to cancel only the slots that have not started", async () => {
    mySpace([
      myInscription(),
      myInscription({
        id: "inscription-0",
        slotId: "slot-0",
        label: "19h30–20h00",
        state: "current",
        actions: [],
      }),
    ]);

    renderApp("/my-space");

    expect(
      await screen.findByRole("button", {
        name: "Me désinscrire du créneau de 20h00–20h30",
      }),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("button", {
        name: "Me désinscrire du créneau de 19h30–20h00",
      }),
    ).toBeNull();
  });

  it("cancels after confirmation, says so and refreshes the list and the planning", async () => {
    let cancelled = false;
    let deleted: string | null = null;
    let planningRequests = 0;
    server.use(
      http.get(MY_INSCRIPTIONS_URL, () =>
        HttpResponse.json({
          editionId: "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b",
          editionTitle: "24 heures de prière",
          displayName: "Marie Dupont",
          inscriptions: cancelled ? [] : [myInscription()],
        }),
      ),
      http.delete(
        "http://localhost:8080/api/inscriptions/:id",
        ({ params }) => {
          deleted = String(params.id);
          cancelled = true;

          return new HttpResponse(null, { status: 204 });
        },
      ),
      http.get("http://localhost:8080/api/editions/current", () => {
        planningRequests += 1;

        return HttpResponse.json({ title: "Not Found" }, { status: 404 });
      }),
    );

    renderApp("/my-space");
    fireEvent.click(
      await screen.findByRole("button", {
        name: "Me désinscrire du créneau de 20h00–20h30",
      }),
    );
    const confirm = await screen.findByRole("alertdialog", {
      name: "Me désinscrire",
    });
    expect(confirm).toHaveTextContent(
      "Vous ne serez plus inscrit sur le créneau de 20h00–20h30 du samedi 28 novembre. Aucun e-mail ne vous sera envoyé.",
    );
    const before = planningRequests;
    fireEvent.click(
      within(confirm).getByRole("button", { name: "Me désinscrire" }),
    );

    expect(
      await screen.findByText(
        "Vous êtes désinscrit du créneau de 20h00–20h30.",
      ),
    ).toBeInTheDocument();
    expect(deleted).toBe("inscription-1");
    expect(
      await screen.findByText("Vous n'avez pas encore de créneau."),
    ).toBeInTheDocument();
    // The planning and its banner are refreshed (RG-EP-1)
    expect(planningRequests).toBeGreaterThan(before);
  });

  it("says why when the slot has started in the meantime", async () => {
    mySpace([myInscription()]);
    server.use(
      http.delete("http://localhost:8080/api/inscriptions/:id", () =>
        HttpResponse.json(
          {
            status: 403,
            detail:
              "Ce créneau a déjà commencé : la désinscription n’est plus possible.",
          },
          { status: 403 },
        ),
      ),
    );

    renderApp("/my-space");
    fireEvent.click(
      await screen.findByRole("button", {
        name: "Me désinscrire du créneau de 20h00–20h30",
      }),
    );
    fireEvent.click(
      within(
        await screen.findByRole("alertdialog", { name: "Me désinscrire" }),
      ).getByRole("button", { name: "Me désinscrire" }),
    );

    expect(
      await screen.findByText(
        "Ce créneau a déjà commencé : la désinscription n’est plus possible.",
      ),
    ).toHaveAttribute("role", "alert");
  });
});

describe("MySpacePage — ajouter des créneaux et mon agenda (F14-T10, US-14.4)", () => {
  it("leads to the planning and offers a single agenda file for all my slots", async () => {
    mySpace([myInscription(), myInscription({ id: "inscription-2" })]);

    renderApp("/my-space");

    expect(
      await screen.findByRole("link", { name: "Ajouter des créneaux" }),
    ).toHaveAttribute("href", "#ajouter-des-creneaux");
    // Arbitrage Q15: one button for the whole edition, none per slot
    const agenda = screen.getAllByRole("link", {
      name: "Ajouter mes créneaux à mon agenda",
    });
    expect(agenda).toHaveLength(1);
    expect(agenda[0]).toHaveAttribute(
      "href",
      "http://localhost:8080/api/me/calendar.ics",
    );
    expect(agenda[0]).toHaveAttribute("download", "mes-creneaux.ics");
  });

  it("offers no agenda file while I have no slot", async () => {
    mySpace([]);

    renderApp("/my-space");

    expect(
      await screen.findByText("Vous n'avez pas encore de créneau."),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("link", { name: "Ajouter mes créneaux à mon agenda" }),
    ).toBeNull();
  });
});
