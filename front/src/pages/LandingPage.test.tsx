import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { fireEvent, render, screen, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { SITE_NAME } from "../config/site";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";
import { LandingPage } from "./LandingPage";

const CONFIRMATION_MESSAGE =
  "Un lien de connexion vient de vous être envoyé à cette adresse.";

const ME_URL = "http://localhost:8080/api/me";

beforeEach(() => {
  // Deterministic local time: a Paris visitor sees no local-time suffix (RG-PL-3)
  vi.stubEnv("TZ", "Europe/Paris");
  // A visitor by default: the email form is only offered without a session
  server.use(http.get(ME_URL, () => new HttpResponse(null, { status: 401 })));
});

afterEach(() => {
  vi.unstubAllEnvs();
});

// A fresh QueryClient per test so the ['public', 'editions', 'current']
// cache never leaks between tests
function renderPage() {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  });

  return render(
    <QueryClientProvider client={queryClient}>
      <LandingPage />
    </QueryClientProvider>,
  );
}

const CURRENT_EDITION_URL = "http://localhost:8080/api/editions/current";

const EDITION_ID = "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b";

function openEdition() {
  server.use(
    http.get(CURRENT_EDITION_URL, () => {
      return HttpResponse.json({
        "@context": "/api/contexts/PublicEdition",
        "@id": "/api/editions/current",
        "@type": "PublicEdition",
        id: EDITION_ID,
        title: "24 heures de prière — novembre 2026",
        presentation: "Une chaîne de prière continue.",
        startsAt: "2026-11-27T18:00:00+00:00",
        opensAt: "2026-11-20T18:00:00+00:00",
        startsAtLabel: "19h00–19h30",
        opensAtLabel: "19h00–19h30",
        status: "open",
      });
    }),
  );

  server.use(
    http.get(`http://localhost:8080/api/editions/${EDITION_ID}/slots`, () => {
      return HttpResponse.json({
        "@context": "/api/contexts/PublicEditionSlots",
        "@id": `/api/editions/${EDITION_ID}/slots`,
        "@type": "PublicEditionSlots",
        id: EDITION_ID,
        slots: [
          {
            id: "slot-1",
            rank: 1,
            startsAt: "2026-11-27T18:00:00+00:00",
            endsAt: "2026-11-27T18:30:00+00:00",
            label: "19h00–19h30",
            partOfDay: "evening",
            state: "future",
            textTitle: "Oraison de Thérèse",
            textSource: "Thérèse de Lisieux",
          },
        ],
      });
    }),
  );
}

describe("LandingPage", () => {
  it("shows the edition header when an edition is open (US-08.1)", async () => {
    openEdition();

    renderPage();

    // Nom de la Communion, titre, dates et présentation de l'édition
    expect(
      await screen.findByRole("heading", {
        name: "24 heures de prière — novembre 2026",
      }),
    ).toBeInTheDocument();
    expect(screen.getByText(SITE_NAME)).toBeInTheDocument();
    // 18:00 UTC = 19:00 in Paris (CET)
    expect(
      screen.getByText(/Début : vendredi 27 novembre 2026 à 19:00/),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        /Ouverture des inscriptions : vendredi 20 novembre 2026 à 19:00/,
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText("Une chaîne de prière continue."),
    ).toBeInTheDocument();

    // The planning grid of the edition is displayed (US-08.2)
    const grid = await screen.findByRole("region", {
      name: "Grille des créneaux",
    });
    expect(
      screen.getByRole("heading", { level: 2, name: "Soirée" }),
    ).toBeInTheDocument();
    expect(within(grid).getByText("19h00–19h30")).toBeInTheDocument();

    // The registration form stays available
    expect(
      screen.getByRole("button", { name: "S'inscrire" }),
    ).toBeInTheDocument();
  });

  it("shows the announcement and the « Recevoir mon lien » link when no edition is public (US-08.1, Q18)", async () => {
    // Default MSW handler: GET /api/editions/current answers 404
    renderPage();

    expect(
      await screen.findByText("La prochaine édition sera bientôt annoncée."),
    ).toBeInTheDocument();

    const link = screen.getByRole("link", { name: "Recevoir mon lien" });
    expect(link).toHaveAttribute("href", "/receive-my-link");

    // The registration form stays available
    expect(
      screen.getByRole("button", { name: "S'inscrire" }),
    ).toBeInTheDocument();
  });

  it("renders the presentation and the email registration form", () => {
    const { container } = renderPage();

    expect(
      screen.getByRole("heading", { name: "24 heures de prière" }),
    ).toBeInTheDocument();
    expect(screen.getByLabelText("Adresse e-mail")).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "S'inscrire" }),
    ).toBeInTheDocument();

    // The honeypot must exist in the DOM but stay invisible to humans
    const honeypot = container.querySelector('input[name="website"]');
    expect(honeypot).not.toBeNull();
    expect(honeypot).toHaveAttribute("hidden");
    expect(honeypot).toHaveAttribute("aria-hidden", "true");
    expect(honeypot).toHaveAttribute("tabindex", "-1");
  });

  it("shows the confirmation message after submitting a valid email", async () => {
    renderPage();

    // Wait for the edition query to settle: the loading <output> has the
    // same implicit role "status" as the submission result
    await screen.findByText("La prochaine édition sera bientôt annoncée.");

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "marie@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "S'inscrire" }));

    expect(await screen.findByRole("status")).toHaveTextContent(
      CONFIRMATION_MESSAGE,
    );
  });

  it("posts the email and the honeypot field to /auth/register as JSON", async () => {
    let capturedBody: unknown = null;
    server.use(
      http.post("http://localhost:8080/auth/register", async ({ request }) => {
        capturedBody = await request.json();
        return HttpResponse.json(
          { message: CONFIRMATION_MESSAGE },
          { status: 202 },
        );
      }),
    );

    renderPage();

    // Wait for the edition query to settle before submitting
    await screen.findByText("La prochaine édition sera bientôt annoncée.");

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "thomas@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "S'inscrire" }));

    await screen.findByRole("status");
    expect(capturedBody).toEqual({
      email: "thomas@bailletced.org",
      website: "",
    });
  });

  it("shows a generic error message when the request fails", async () => {
    server.use(
      http.post("http://localhost:8080/auth/register", () => {
        return new HttpResponse(null, { status: 500 });
      }),
    );

    renderPage();

    // Wait for the edition query to settle before submitting
    await screen.findByText("La prochaine édition sera bientôt annoncée.");

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "marie@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "S'inscrire" }));

    expect(await screen.findByRole("status")).toHaveTextContent(
      "Une erreur est survenue. Veuillez réessayer.",
    );
  });
});

describe("Route /", () => {
  it("sends a signed-in priant straight to « Mon espace »", async () => {
    // The default MSW handler answers /api/me with an authenticated priant
    server.resetHandlers();

    renderApp("/");

    expect(
      await screen.findByRole("heading", { name: "Mon espace" }),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("button", { name: "S'inscrire" }),
    ).not.toBeInTheDocument();
  });

  it("shows the landing page to a visitor", async () => {
    renderApp("/");

    expect(
      await screen.findByRole("button", { name: "S'inscrire" }),
    ).toBeInTheDocument();
  });
});
