import { fireEvent, screen, waitFor, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import {
  CONSENT_REQUIRED_MESSAGE,
  DISPLAY_NAME_LENGTH_MESSAGE,
  DISPLAY_NAME_REQUIRED_MESSAGE,
  EMAIL_INVALID_MESSAGE,
} from "../lib/inscription";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

const EDITION_ID = "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b";
const CURRENT_EDITION_URL = "http://localhost:8080/api/editions/current";
const SLOTS_URL = `http://localhost:8080/api/editions/${EDITION_ID}/slots`;
const INSCRIPTIONS_URL = "http://localhost:8080/api/inscriptions";
const ME_URL = "http://localhost:8080/api/me";

const SLOT_FUTURE_1 = "slot-future-1";
const SLOT_FUTURE_2 = "slot-future-2";
const SLOT_PAST = "slot-past";

beforeEach(() => {
  // Deterministic local time: a Paris visitor sees no local-time suffix (RG-PL-3)
  vi.stubEnv("TZ", "Europe/Paris");
});

afterEach(() => {
  vi.unstubAllEnvs();
});

function planningSlot(overrides: Record<string, unknown> = {}) {
  return {
    id: SLOT_FUTURE_1,
    rank: 1,
    // Far-future dates (CET in November: 18:00 UTC = 19:00 in Paris), so
    // the temporal state never depends on the day the tests run
    startsAt: "2099-11-27T18:00:00+00:00",
    endsAt: "2099-11-27T18:30:00+00:00",
    label: "19h00–19h30",
    partOfDay: "evening",
    state: "future",
    textTitle: null,
    textSource: null,
    ...overrides,
  };
}

const FUTURE_SLOT_1 = planningSlot();
const FUTURE_SLOT_2 = planningSlot({
  id: SLOT_FUTURE_2,
  rank: 2,
  startsAt: "2099-11-27T18:30:00+00:00",
  endsAt: "2099-11-27T19:00:00+00:00",
  label: "19h30–20h00",
});
const PAST_SLOT = planningSlot({
  id: SLOT_PAST,
  rank: 0,
  startsAt: "2020-01-01T18:00:00+00:00",
  endsAt: "2020-01-01T18:30:00+00:00",
  state: "past",
});

function openEdition() {
  server.use(
    http.get(CURRENT_EDITION_URL, () => {
      return HttpResponse.json({
        "@context": "/api/contexts/PublicEdition",
        "@id": "/api/editions/current",
        "@type": "PublicEdition",
        id: EDITION_ID,
        title: "24 heures de prière — novembre 2099",
        presentation: "Une chaîne de prière continue.",
        startsAt: "2099-11-27T18:00:00+00:00",
        opensAt: "2099-11-20T18:00:00+00:00",
        status: "open",
      });
    }),
  );
}

function serveSlots(slots: unknown[]) {
  server.use(
    http.get(SLOTS_URL, () => {
      return HttpResponse.json({
        "@context": "/api/contexts/PublicEditionSlots",
        "@id": `/api/editions/${EDITION_ID}/slots`,
        "@type": "PublicEditionSlots",
        id: EDITION_ID,
        slots,
      });
    }),
  );
}

function openEditionWithSlots(slots: unknown[]) {
  openEdition();
  serveSlots(slots);
}

// A 401 on /api/me: the visitor, a normal state (RG-IN-5)
function visitor() {
  server.use(
    http.get(ME_URL, () => {
      return new HttpResponse(null, { status: 401 });
    }),
  );
}

// A recognized priant who already gave its consent (RG-IN-7)
function consentedSession() {
  server.use(
    http.get(ME_URL, () => {
      return HttpResponse.json({
        "@context": "/api/contexts/Me",
        "@id": "/api/me",
        "@type": "Me",
        email: "thomas@bailletced.org",
        isOrganizer: false,
        displayName: "Thomas Baillet",
        consentGiven: true,
      });
    }),
  );
}

function inscriptionItem() {
  return {
    "@context": "/api/contexts/Inscription",
    "@id": `/api/inscriptions/${EDITION_ID}`,
    "@type": "Inscription",
    id: EDITION_ID,
    priantId: "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8c",
    displayName: "Marie Dupont",
    inscriptions: [
      {
        id: "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8d",
        slotId: SLOT_FUTURE_1,
        editionId: EDITION_ID,
      },
    ],
  };
}

/** Captures the POST /api/inscriptions body; answers 201. */
function captureRegistrations() {
  const captured: { body: unknown; contentType: string | null } = {
    body: null,
    contentType: null,
  };

  server.use(
    http.post(INSCRIPTIONS_URL, async ({ request }) => {
      captured.body = await request.json();
      captured.contentType = request.headers.get("content-type");

      return HttpResponse.json(inscriptionItem(), { status: 201 });
    }),
  );

  return captured;
}

/** Tracks whether POST /api/inscriptions was ever called. */
function trackRegistrations() {
  const state = { called: false };

  server.use(
    http.post(INSCRIPTIONS_URL, () => {
      state.called = true;

      return HttpResponse.json(inscriptionItem(), { status: 201 });
    }),
  );

  return state;
}

/**
 * Fills the form as a valid visitor: the email is pre-filled from /api/me,
 * only the display name and the consent checkbox remain.
 */
function fillValidForm() {
  fireEvent.change(screen.getByLabelText("Nom affiché"), {
    target: { value: "Marie Dupont" },
  });
  fireEvent.click(screen.getByRole("checkbox"));
}

function submitForm() {
  fireEvent.click(screen.getByRole("button", { name: "S'inscrire" }));
}

describe("InscriptionPage — récapitulatif des créneaux (F09-T15)", () => {
  it("récapitule les créneaux choisis dans l'ordre chronologique, quel que soit l'ordre du paramètre", async () => {
    openEditionWithSlots([FUTURE_SLOT_1, FUTURE_SLOT_2]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_2},${SLOT_FUTURE_1}`);

    const recap = await screen.findByRole("region", {
      name: "Créneaux sélectionnés",
    });
    const items = within(recap).getAllByRole("listitem");
    expect(items).toHaveLength(2);
    expect(items[0].textContent).toContain("19h00–19h30");
    expect(items[1].textContent).toContain("19h30–20h00");
  });

  it("signale qu'aucun créneau n'est sélectionné sans paramètre", async () => {
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp("/inscription");

    expect(
      await screen.findByText("Aucun créneau sélectionné."),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("link", { name: "Retour à l'accueil" }),
    ).toHaveAttribute("href", "/");
  });

  it("signale qu'aucun créneau n'est sélectionné avec un paramètre vide", async () => {
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp("/inscription?creneaux=");

    expect(
      await screen.findByText("Aucun créneau sélectionné."),
    ).toBeInTheDocument();
  });

  it("signale des créneaux introuvables quand aucun id ne correspond", async () => {
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp("/inscription?creneaux=slot-inconnu");

    expect(
      await screen.findByText("Les créneaux sélectionnés sont introuvables."),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("link", { name: "Retour à l'accueil" }),
    ).toHaveAttribute("href", "/");
  });

  it("récapitule les créneaux connus et signale les ids qui n'existent plus", async () => {
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1},slot-inconnu`);

    const recap = await screen.findByRole("region", {
      name: "Créneaux sélectionnés",
    });
    expect(within(recap).getAllByRole("listitem")).toHaveLength(1);
    expect(
      screen.getByText("Certains créneaux sélectionnés n'existent plus."),
    ).toBeInTheDocument();
  });

  it("signale une édition fermée quand aucune édition n'est ouverte", async () => {
    // Default MSW handler: GET /api/editions/current answers 404
    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(
      await screen.findByText(
        "Les inscriptions ne sont pas ouvertes pour le moment.",
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("link", { name: "Retour à l'accueil" }),
    ).toHaveAttribute("href", "/");
  });

  it("retire un créneau du récapitulatif via le paramètre d'URL", async () => {
    openEditionWithSlots([FUTURE_SLOT_1, FUTURE_SLOT_2]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1},${SLOT_FUTURE_2}`);

    const recap = await screen.findByRole("region", {
      name: "Créneaux sélectionnés",
    });
    fireEvent.click(
      within(recap).getByRole("button", {
        name: "Retirer le créneau de 19h00–19h30",
      }),
    );

    await waitFor(() => {
      const items = within(
        screen.getByRole("region", { name: "Créneaux sélectionnés" }),
      ).getAllByRole("listitem");
      expect(items).toHaveLength(1);
      expect(items[0].textContent).toContain("19h30–20h00");
    });
  });

  it("signale les créneaux passés et ne les soumet jamais (US-09.5)", async () => {
    const captured = captureRegistrations();
    openEditionWithSlots([PAST_SLOT, FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_PAST},${SLOT_FUTURE_1}`);

    // The past slot is flagged by a label, never by the color alone (§5.3-1)
    expect(await screen.findByText(/\(créneau passé\)/)).toBeInTheDocument();

    fillValidForm();
    submitForm();

    expect(
      await screen.findByRole("heading", { name: "Inscription confirmée" }),
    ).toBeInTheDocument();
    // The past slot is excluded from the payload
    expect(captured.body).toEqual({
      slotIds: [SLOT_FUTURE_1],
      displayName: "Marie Dupont",
      email: "thomas@bailletced.org",
      consent: true,
      website: "",
    });
  });

  it("signale les créneaux où le priant est déjà inscrit et ne les soumet jamais (RG-IN-3)", async () => {
    const captured = captureRegistrations();
    openEditionWithSlots([{ ...FUTURE_SLOT_1, mine: true }, FUTURE_SLOT_2]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1},${SLOT_FUTURE_2}`);

    expect(await screen.findByText(/\(déjà inscrit\)/)).toBeInTheDocument();

    fillValidForm();
    submitForm();

    expect(
      await screen.findByRole("heading", { name: "Inscription confirmée" }),
    ).toBeInTheDocument();
    expect(captured.body).toMatchObject({ slotIds: [SLOT_FUTURE_2] });
  });

  it("remplace le formulaire quand le priant est déjà inscrit sur tous les créneaux (RG-IN-3)", async () => {
    openEditionWithSlots([{ ...FUTURE_SLOT_1, mine: true }]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Vous êtes déjà inscrit sur les créneaux sélectionnés.",
    );
    expect(
      screen.queryByRole("button", { name: "S'inscrire" }),
    ).not.toBeInTheDocument();
  });

  it("remplace le formulaire quand tous les créneaux sélectionnés sont passés", async () => {
    openEditionWithSlots([PAST_SLOT]);

    renderApp(`/inscription?creneaux=${SLOT_PAST}`);

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Les créneaux sélectionnés sont passés.",
    );
    expect(screen.queryByLabelText("Nom affiché")).not.toBeInTheDocument();
  });
});

describe("InscriptionPage — validation du formulaire (F09-T16)", () => {
  it("exige le nom affiché avant tout envoi", async () => {
    const posts = trackRegistrations();
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    // The email is pre-filled; only the consent remains to tick
    expect(await screen.findByLabelText("Nom affiché")).toBeInTheDocument();
    fireEvent.click(screen.getByRole("checkbox"));
    submitForm();

    expect(
      await screen.findByText(DISPLAY_NAME_REQUIRED_MESSAGE),
    ).toBeInTheDocument();
    expect(posts.called).toBe(false);
  });

  it("borne le nom affiché à 80 caractères", async () => {
    const posts = trackRegistrations();
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByLabelText("Nom affiché")).toBeInTheDocument();
    fireEvent.change(screen.getByLabelText("Nom affiché"), {
      target: { value: "M".repeat(81) },
    });
    fireEvent.click(screen.getByRole("checkbox"));
    submitForm();

    expect(
      await screen.findByText(DISPLAY_NAME_LENGTH_MESSAGE),
    ).toBeInTheDocument();
    expect(posts.called).toBe(false);
  });

  it("rejette une adresse e-mail invalide", async () => {
    const posts = trackRegistrations();
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByLabelText("Nom affiché")).toBeInTheDocument();
    fireEvent.change(screen.getByLabelText("Nom affiché"), {
      target: { value: "Marie Dupont" },
    });
    // No dot in the domain: valid for the HTML input, rejected by the schema
    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "marie@bailletced" },
    });
    fireEvent.click(screen.getByRole("checkbox"));
    submitForm();

    expect(await screen.findByText(EMAIL_INVALID_MESSAGE)).toBeInTheDocument();
    expect(posts.called).toBe(false);
  });
});

describe("InscriptionPage — consentement, champ piège, état d'envoi (F09-T17)", () => {
  it("affiche la case de consentement avec un lien vers la politique de confidentialité, hors session", async () => {
    visitor();
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByRole("checkbox")).toBeInTheDocument();
    expect(
      screen.getByRole("link", { name: "politique de confidentialité" }),
    ).toHaveAttribute("href", "/confidentialite");
  });

  it("rend le consentement obligatoire hors session", async () => {
    const posts = trackRegistrations();
    visitor();
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByLabelText("Nom affiché")).toBeInTheDocument();
    fireEvent.change(screen.getByLabelText("Nom affiché"), {
      target: { value: "Marie Dupont" },
    });
    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "marie@bailletced.org" },
    });
    submitForm();

    expect(
      await screen.findByText(CONSENT_REQUIRED_MESSAGE),
    ).toBeInTheDocument();
    expect(posts.called).toBe(false);
  });

  it("affiche la case de consentement en session quand le consentement n'a jamais été donné", async () => {
    // Default MSW handler: /api/me without consentGiven
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByRole("checkbox")).toBeInTheDocument();
  });

  it("masque la case et pré-remplit le formulaire quand le priant est reconnu et a déjà consenti (RG-IN-7)", async () => {
    const captured = captureRegistrations();
    consentedSession();
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByLabelText("Nom affiché")).toHaveValue(
      "Thomas Baillet",
    );
    expect(screen.getByLabelText("Adresse e-mail")).toHaveValue(
      "thomas@bailletced.org",
    );
    // The consent checkbox is hidden: the consent was already given
    expect(screen.queryByRole("checkbox")).not.toBeInTheDocument();

    submitForm();

    expect(
      await screen.findByRole("heading", { name: "Inscription confirmée" }),
    ).toBeInTheDocument();
    expect(captured.body).toEqual({
      slotIds: [SLOT_FUTURE_1],
      displayName: "Thomas Baillet",
      email: "thomas@bailletced.org",
      consent: true,
      website: "",
    });
  });

  it("garde le champ piège hors de l'arbre d'accessibilité (§6.7)", async () => {
    openEditionWithSlots([FUTURE_SLOT_1]);

    const { container } = renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByLabelText("Nom affiché")).toBeInTheDocument();

    // The honeypot must exist in the DOM but stay invisible to humans
    const honeypot = container.querySelector('input[name="website"]');
    expect(honeypot).not.toBeNull();
    expect(honeypot).toHaveAttribute("hidden");
    expect(honeypot).toHaveAttribute("aria-hidden", "true");
    expect(honeypot).toHaveAttribute("tabindex", "-1");
    expect(honeypot).not.toBeVisible();
  });

  it("désactive le bouton et signale l'envoi en cours", async () => {
    let resolvePost: ((response: Response) => void) | undefined;
    server.use(
      http.post(INSCRIPTIONS_URL, () => {
        return new Promise<Response>((resolve) => {
          resolvePost = resolve;
        });
      }),
    );
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByLabelText("Nom affiché")).toBeInTheDocument();
    fillValidForm();
    submitForm();

    const pendingButton = await screen.findByRole("button", {
      name: "Envoi en cours…",
    });
    expect(pendingButton).toBeDisabled();

    resolvePost?.(HttpResponse.json(inscriptionItem(), { status: 201 }));

    expect(
      await screen.findByRole("heading", { name: "Inscription confirmée" }),
    ).toBeInTheDocument();
  });
});

describe("InscriptionPage — envoi (F09-T18)", () => {
  it("envoie l'inscription, affiche la confirmation et invalide le planning (RG-PL-1)", async () => {
    const captured = captureRegistrations();
    openEdition();
    let slotsRequests = 0;
    server.use(
      http.get(SLOTS_URL, () => {
        slotsRequests += 1;

        return HttpResponse.json({
          "@context": "/api/contexts/PublicEditionSlots",
          "@id": `/api/editions/${EDITION_ID}/slots`,
          "@type": "PublicEditionSlots",
          id: EDITION_ID,
          slots: [FUTURE_SLOT_1],
        });
      }),
    );

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByLabelText("Nom affiché")).toBeInTheDocument();
    fillValidForm();
    submitForm();

    expect(
      await screen.findByRole("heading", { name: "Inscription confirmée" }),
    ).toBeInTheDocument();
    expect(captured.body).toEqual({
      slotIds: [SLOT_FUTURE_1],
      displayName: "Marie Dupont",
      email: "thomas@bailletced.org",
      consent: true,
      website: "",
    });
    // API Platform only accepts JSON-LD bodies on this resource
    expect(captured.contentType).toContain("application/ld+json");
    // The public planning queries are invalidated on success: the slots
    // are re-fetched so the grid shows the updated counts (RG-PL-1)
    await waitFor(() => {
      expect(slotsRequests).toBeGreaterThanOrEqual(2);
    });
  });

  it("affiche les violations 422 sur leur champ", async () => {
    server.use(
      http.post(INSCRIPTIONS_URL, () => {
        return HttpResponse.json(
          {
            "@context": "/api/contexts/ConstraintViolationList",
            "@type": "ConstraintViolationList",
            status: 422,
            title: "An error occurred",
            violations: [
              {
                propertyPath: "displayName",
                message: "Nom affiché refusé par le serveur.",
              },
              {
                propertyPath: "email",
                message: "Adresse e-mail refusée par le serveur.",
              },
            ],
          },
          { status: 422 },
        );
      }),
    );
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByLabelText("Nom affiché")).toBeInTheDocument();
    fillValidForm();
    submitForm();

    // Each violation lands on its own field; the form stays on screen
    expect(
      await screen.findByText("Nom affiché refusé par le serveur."),
    ).toBeInTheDocument();
    expect(
      screen.getByText("Adresse e-mail refusée par le serveur."),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("heading", { name: "Inscription" }),
    ).toBeInTheDocument();
  });

  it("affiche le 409 en message métier (RG-IN-3)", async () => {
    server.use(
      http.post(INSCRIPTIONS_URL, () => {
        return HttpResponse.json(
          {
            "@context": "/api/contexts/Error",
            "@type": "hydra:Error",
            title: "An error occurred",
            detail: "Vous êtes déjà inscrit sur ce créneau.",
            status: 409,
          },
          { status: 409 },
        );
      }),
    );
    openEditionWithSlots([FUTURE_SLOT_1]);

    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);

    expect(await screen.findByLabelText("Nom affiché")).toBeInTheDocument();
    fillValidForm();
    submitForm();

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Vous êtes déjà inscrit sur ce créneau.",
    );
    // Still on the form: the priant can retry
    expect(
      screen.getByRole("heading", { name: "Inscription" }),
    ).toBeInTheDocument();
  });
});

describe("InscriptionPage — suggestion après inscription (F13-T07, US-13.3)", () => {
  const SUGGESTION = {
    slotId: SLOT_FUTURE_2,
    startsAt: "2099-11-27T18:30:00+00:00",
    endsAt: "2099-11-27T19:00:00+00:00",
    label: "19h30–20h00",
    textTitle: "Que rien ne te trouble",
    textSource: "Thérèse d’Avila",
  };

  /** Captures every POST body; the first answers with the suggestion. */
  function registrationsSuggesting(suggestion: unknown) {
    const bodies: unknown[] = [];

    server.use(
      http.post(INSCRIPTIONS_URL, async ({ request }) => {
        bodies.push(await request.json());

        return HttpResponse.json(
          {
            ...inscriptionItem(),
            suggestion: bodies.length === 1 ? suggestion : null,
          },
          { status: 201 },
        );
      }),
    );

    return bodies;
  }

  async function registerOnTheFirstSlot() {
    renderApp(`/inscription?creneaux=${SLOT_FUTURE_1}`);
    expect(await screen.findByLabelText("Nom affiché")).toBeInTheDocument();
    fillValidForm();
    submitForm();
    expect(
      await screen.findByRole("heading", { name: "Inscription confirmée" }),
    ).toBeInTheDocument();
  }

  it("takes the suggested slot too, in one click, with the same information", async () => {
    const bodies = registrationsSuggesting(SUGGESTION);
    openEditionWithSlots([FUTURE_SLOT_1, FUTURE_SLOT_2]);

    await registerOnTheFirstSlot();

    const suggestion = await screen.findByRole("region", {
      name: "Un créneau voisin est encore vide",
    });
    expect(suggestion).toHaveTextContent("19h30–20h00");
    expect(suggestion).toHaveTextContent("Que rien ne te trouble");
    fireEvent.click(
      within(suggestion).getByRole("button", { name: "Le prendre aussi" }),
    );

    // Both slots in the récapitulatif, no new typing
    await waitFor(() =>
      expect(
        within(
          screen.getByRole("region", { name: "Vos créneaux" }),
        ).getAllByRole("listitem"),
      ).toHaveLength(2),
    );
    expect(bodies[1]).toEqual({
      slotIds: [SLOT_FUTURE_2],
      displayName: "Marie Dupont",
      email: "thomas@bailletced.org",
      consent: true,
      website: "",
    });
    expect(
      screen.getByText("Vous êtes aussi inscrit sur 19h30–20h00."),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("region", {
        name: "Un créneau voisin est encore vide",
      }),
    ).not.toBeInTheDocument();
  });

  it("dismisses the suggestion with « Non merci »", async () => {
    const bodies = registrationsSuggesting(SUGGESTION);
    openEditionWithSlots([FUTURE_SLOT_1, FUTURE_SLOT_2]);

    await registerOnTheFirstSlot();
    fireEvent.click(await screen.findByRole("button", { name: "Non merci" }));

    expect(
      screen.queryByRole("region", {
        name: "Un créneau voisin est encore vide",
      }),
    ).not.toBeInTheDocument();
    expect(bodies).toHaveLength(1);
  });

  it("suggests nothing once the chain is complete", async () => {
    registrationsSuggesting(null);
    openEditionWithSlots([FUTURE_SLOT_1, FUTURE_SLOT_2]);

    await registerOnTheFirstSlot();

    expect(
      screen.queryByRole("region", {
        name: "Un créneau voisin est encore vide",
      }),
    ).not.toBeInTheDocument();
  });

  it("says when the suggested slot was taken in the meantime", async () => {
    let posts = 0;
    server.use(
      http.post(INSCRIPTIONS_URL, () => {
        posts += 1;

        return posts === 1
          ? HttpResponse.json(
              { ...inscriptionItem(), suggestion: SUGGESTION },
              { status: 201 },
            )
          : HttpResponse.json(
              { title: "Conflict", detail: "Ce créneau est passé." },
              { status: 409 },
            );
      }),
    );
    openEditionWithSlots([FUTURE_SLOT_1, FUTURE_SLOT_2]);

    await registerOnTheFirstSlot();
    fireEvent.click(
      await screen.findByRole("button", { name: "Le prendre aussi" }),
    );

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Ce créneau est passé.",
    );
  });
});
