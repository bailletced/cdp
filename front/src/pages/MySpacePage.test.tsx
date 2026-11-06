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

/** The current public edition starting at the given ISO instant. */
function currentEditionStartingAt(startsAt: string) {
  server.use(
    http.get("http://localhost:8080/api/editions/current", () =>
      HttpResponse.json({
        id: EDITION_ID,
        title: "24 heures de prière — novembre 2026",
        presentation: "",
        startsAt,
        opensAt: "2026-10-20T18:00:00+00:00",
        status: "open",
      }),
    ),
  );
}

/** An open public edition, answered to GET /api/editions/current. */
function openEdition() {
  server.use(
    http.get("http://localhost:8080/api/editions/current", () =>
      HttpResponse.json({
        id: EDITION_ID,
        title: "24 heures de prière — novembre 2026",
        presentation: "",
        startsAt: "2099-11-27T18:00:00+00:00",
        opensAt: "2026-11-20T18:00:00+00:00",
        status: "open",
      }),
    ),
  );
}
describe("MySpacePage — l'état de la chaîne (F14)", () => {
  it("counts down to the start of the edition before it begins", async () => {
    openEdition();
    mySpace([]);

    renderApp("/my-space");

    const banner = await screen.findByRole("region", {
      name: "État de l'édition",
    });
    expect(within(banner).getByText(/La chaîne commence/)).toBeInTheDocument();
    expect(within(banner).getByText(/heure de Paris/)).toBeInTheDocument();
    // The countdown is a live figure, tied to the server clock
    expect(within(banner).getByRole("timer")).toBeInTheDocument();
    expect(within(banner).getByRole("timer").textContent ?? "").toMatch(
      /\u202f[jms]/,
    );
  });

  it("says the chain is running while it is underway, with what is left to pray", async () => {
    const startsAt = new Date(Date.now() - 2 * 3600_000).toISOString();
    currentEditionStartingAt(startsAt);
    mySpace([]);

    renderApp("/my-space");

    const banner = await screen.findByRole("region", {
      name: "État de l'édition",
    });
    expect(
      within(banner).getByText(/La chaîne est en cours/),
    ).toBeInTheDocument();
    expect(within(banner).getByText(/encore/)).toBeInTheDocument();
    expect(within(banner).queryByRole("timer")).not.toBeInTheDocument();
  });

  it("thanks the priant once the edition is over", async () => {
    const startsAt = new Date(Date.now() - 25 * 3600_000).toISOString();
    currentEditionStartingAt(startsAt);
    mySpace([]);

    renderApp("/my-space");

    const banner = await screen.findByRole("region", {
      name: "État de l'édition",
    });
    expect(
      within(banner).getByText(/Cette édition est terminée/),
    ).toBeInTheDocument();
    expect(within(banner).getByText(/Merci d'avoir prié/)).toBeInTheDocument();
  });

  it("offers the one-way funnel of prayer on each of my slots", async () => {
    openEdition();
    mySpace([
      myInscription(),
      myInscription({
        id: "inscription-past",
        slotId: "slot-past",
        startsAt: new Date(Date.now() - 3600_000).toISOString(),
        endsAt: new Date(Date.now() - 1800_000).toISOString(),
        label: "22h00–22h30",
        dayLabel: "samedi 28 novembre",
        actions: [],
      }),
    ]);

    renderApp("/my-space");

    const mine = await screen.findByRole("region", { name: "Mes créneaux" });
    const [upcoming, past] = within(mine).getAllByRole("listitem");

    // The pray CTA is the first, full-width action of every card
    const prayUpcoming = within(upcoming).getByRole("link", {
      name: "Prier ce créneau — 20h00–20h30",
    });
    expect(prayUpcoming).toHaveAttribute("href", "/textes/slot-1");
    expect(
      within(prayUpcoming).getByText("Prier ce créneau"),
    ).toBeInTheDocument();

    const prayPast = within(past).getByRole("link", {
      name: "Relire ce créneau — 22h00–22h30",
    });
    expect(prayPast).toHaveAttribute("href", "/textes/slot-past");
    expect(within(prayPast).getByText("Relire ce créneau")).toBeInTheDocument();

    // The states are labelled, never colour alone (§5.3-1)
    expect(within(upcoming).getByText("À venir")).toBeInTheDocument();
    expect(within(past).getByText("Terminé")).toBeInTheDocument();

    // « Me désinscrire » stays away from the pray funnel: a muted link
    // below, not a competing button
    const unsubscribe = within(upcoming).getByRole("button", {
      name: /Me désinscrire du créneau de 20h00–20h30/,
    });
    expect(unsubscribe.className).toContain("text-muted");
    expect(
      within(past).queryByRole("button", { name: /Me désinscrire/ }),
    ).toBeNull();
  });
});

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
    expect(second).toHaveTextContent("Que rien ne te trouble");
    expect(
      within(second).getByRole("link", {
        name: "Prier ce créneau — 22h00–22h30",
      }),
    ).toHaveAttribute("href", "/textes/slot-5");
    expect(within(second).getByText("À venir")).toBeInTheDocument();
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

  it("offers the empty-slot draw and the picker, not the whole grid", async () => {
    openEdition();
    server.use(
      http.get(`http://localhost:8080/api/editions/${EDITION_ID}/slots`, () =>
        HttpResponse.json({
          id: EDITION_ID,
          slots: [
            {
              id: "slot-14",
              rank: 15,
              startsAt: "2099-11-28T21:00:00+00:00",
              endsAt: "2099-11-28T21:30:00+00:00",
              label: "22h00\u201322h30",
              partOfDay: "evening",
              state: "future",
              textTitle: null,
              textSource: null,
              registrationCount: 2,
            },
          ],
        }),
      ),
    );

    renderApp("/my-space");

    // Not the banner of the empty slots, not the 48 cards: two actions
    expect(
      await screen.findByRole("button", { name: "Prendre un créneau vide" }),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("region", { name: "Grille des créneaux" }),
    ).toBeNull();
    expect(screen.queryByText(/Il reste/)).toBeNull();

    // « Choisir un créneau » lists every slot to come, compactly, with the
    // priants count, and links to the one-click inscription
    fireEvent.click(screen.getByRole("button", { name: "Choisir un créneau" }));
    const dialog = await screen.findByRole("dialog", {
      name: "Choisir un créneau",
    });
    expect(within(dialog).getByText("22h00\u201322h30")).toBeInTheDocument();
    expect(within(dialog).getByText("2 priants")).toBeInTheDocument();
    expect(
      within(dialog).getByRole("link", { name: "Je le prends" }),
    ).toHaveAttribute("href", "/inscription?creneaux=slot-14");
  });

  it("announces the next edition when none is open yet", async () => {
    server.use(
      http.get("http://localhost:8080/api/editions/current", () =>
        HttpResponse.json({ title: "Not Found" }, { status: 404 }),
      ),
    );

    renderApp("/my-space");

    expect(
      await screen.findByText("La prochaine édition sera bientôt annoncée."),
    ).toBeInTheDocument();
  });

  it("draws an empty slot, leading to the one-click inscription", async () => {
    openEdition();
    server.use(
      http.get(
        `http://localhost:8080/api/editions/${EDITION_ID}/slot-suggestion`,
        () =>
          HttpResponse.json({
            "@context": "/api/contexts/SlotSuggestion",
            "@id": `/api/editions/${EDITION_ID}/slot-suggestion`,
            "@type": "SlotSuggestion",
            id: EDITION_ID,
            slotId: "slot-12",
            startsAt: "2099-11-28T19:00:00+00:00",
            endsAt: "2099-11-28T19:30:00+00:00",
            label: "20h00–20h30",
            textTitle: "Que rien ne te trouble",
            textSource: "Thérèse d’Avila",
          }),
      ),
    );

    renderApp("/my-space");

    fireEvent.click(
      await screen.findByRole("button", { name: "Prendre un créneau vide" }),
    );
    const dialog = await screen.findByRole("dialog");
    expect(await within(dialog).findByText("20h00–20h30")).toBeVisible();
    expect(
      within(dialog).getByRole("link", { name: "Je le prends" }),
    ).toHaveAttribute("href", "/inscription?creneaux=slot-12");
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
