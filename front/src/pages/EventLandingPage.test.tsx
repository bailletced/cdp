import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import {
  fireEvent,
  render,
  screen,
  waitFor,
  within,
} from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { POST_LOGIN_TARGET_KEY } from "../lib/postLoginTarget";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";
import { EventLandingPage } from "./EventLandingPage";

const ME_URL = "http://localhost:8080/api/me";
const CURRENT_EDITION_URL = "http://localhost:8080/api/editions/current";
const REGISTER_URL = "http://localhost:8080/auth/register";
const EDITION_ID = "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b";

beforeEach(() => {
  vi.stubEnv("TZ", "Europe/Paris");
  localStorage.clear();
  // A visitor by default: the e-mail form shows up
  server.use(http.get(ME_URL, () => new HttpResponse(null, { status: 401 })));
  // No edition open by default
  server.use(
    http.get(
      CURRENT_EDITION_URL,
      () => new HttpResponse(null, { status: 404 }),
    ),
  );
});

afterEach(() => {
  vi.unstubAllEnvs();
  localStorage.clear();
});

function renderPage() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });

  return render(
    <QueryClientProvider client={queryClient}>
      <EventLandingPage />
    </QueryClientProvider>,
  );
}

describe("EventLandingPage (/landing)", () => {
  it("is reachable at /landing through the router", async () => {
    renderApp("/landing");

    expect(
      await screen.findByRole("heading", {
        level: 1,
        name: "Une chaîne de prière ininterrompue",
      }),
    ).toBeInTheDocument();
  });

  it("explains the event: 24 h chain from Sat 28 Nov 22:00 to Sun 29 Nov 22:00, Gospel of Mark, 30-minute slots", async () => {
    renderPage();

    expect(
      await screen.findByRole("heading", {
        level: 1,
        name: "Une chaîne de prière ininterrompue",
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByText(/samedi 28 novembre 2026 à 22h00/),
    ).toBeInTheDocument();
    expect(
      screen.getByText(/dimanche 29 novembre 2026 à 22h00/),
    ).toBeInTheDocument();
    expect(
      screen.getAllByText(/Évangile selon saint Marc/i).length,
    ).toBeGreaterThan(0);
    expect(screen.getByText(/48 × 30 min/)).toBeInTheDocument();

    // The three steps of the journey
    expect(screen.getByText("Entrez votre e-mail")).toBeInTheDocument();
    expect(screen.getByText("Cliquez le lien")).toBeInTheDocument();
    expect(screen.getByText("Choisissez vos créneaux")).toBeInTheDocument();

    // Double CTA of the hero
    expect(screen.getByRole("link", { name: "Je participe" })).toHaveAttribute(
      "href",
      "#inscription",
    );
    expect(
      screen.getByRole("link", { name: "Voir les créneaux" }),
    ).toHaveAttribute("href", "#planning");
  });

  it("announces the event dates when no edition is open yet", async () => {
    renderPage();

    expect(
      await screen.findByText(
        /Le planning s'ouvrira à l'ouverture des inscriptions/,
      ),
    ).toBeInTheDocument();
  });

  it("shows the planning grid of the open edition", async () => {
    server.use(
      http.get(CURRENT_EDITION_URL, () =>
        HttpResponse.json({
          "@context": "/api/contexts/PublicEdition",
          "@id": "/api/editions/current",
          "@type": "PublicEdition",
          id: EDITION_ID,
          title: "24 heures de prière — novembre 2026",
          presentation: "Une chaîne de prière continue.",
          startsAt: "2026-11-28T21:00:00+00:00",
          opensAt: "2026-11-20T18:00:00+00:00",
          status: "open",
        }),
      ),
      http.get(`http://localhost:8080/api/editions/${EDITION_ID}/slots`, () =>
        HttpResponse.json({
          "@context": "/api/contexts/PublicEditionSlots",
          "@id": `/api/editions/${EDITION_ID}/slots`,
          "@type": "PublicEditionSlots",
          id: EDITION_ID,
          slots: [
            {
              id: "slot-1",
              rank: 1,
              startsAt: "2099-11-28T21:00:00+00:00",
              endsAt: "2099-11-28T21:30:00+00:00",
              label: "22h00–22h30",
              partOfDay: "evening",
              state: "future",
              textTitle: null,
              textSource: null,
              registrationCount: 0,
              participants: [],
            },
          ],
        }),
      ),
    );

    renderPage();

    const grid = await screen.findByRole("region", {
      name: "Grille des créneaux",
    });
    expect(
      screen.getByRole("heading", { level: 2, name: "Soirée" }),
    ).toBeInTheDocument();
    expect(within(grid).getAllByText("22h00–22h30").length).toBeGreaterThan(0);
  });

  it("submits the e-mail to /auth/register, stores /landing as post-login target and confirms", async () => {
    const bodies: string[] = [];
    server.use(
      http.post(REGISTER_URL, async ({ request }) => {
        bodies.push(await request.text());
        return HttpResponse.json(
          {
            message:
              "Un lien de connexion vient de vous être envoyé à cette adresse.",
          },
          { status: 202 },
        );
      }),
    );

    renderPage();

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "priant@example.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Recevoir mon lien" }));

    expect(
      await screen.findByText(
        "Un lien de connexion vient de vous être envoyé à cette adresse.",
      ),
    ).toBeInTheDocument();

    expect(bodies).toHaveLength(1);
    expect(JSON.parse(bodies[0])).toEqual({
      email: "priant@example.org",
      website: "",
    });
    expect(localStorage.getItem(POST_LOGIN_TARGET_KEY)).toBe("/landing");
  });

  it("hides the e-mail form for a signed-in priant and links to /my-space", async () => {
    server.use(
      http.get(ME_URL, () =>
        HttpResponse.json({ email: "priant@example.org", isOrganizer: false }),
      ),
    );

    renderPage();

    expect(
      await screen.findByRole("link", { name: "Aller à mon espace" }),
    ).toHaveAttribute("href", "/my-space");
    expect(screen.queryByLabelText("Adresse e-mail")).toBeNull();
  });

  it("shows a generic error when the register call fails", async () => {
    server.use(
      http.post(REGISTER_URL, () => new HttpResponse(null, { status: 500 })),
    );

    renderPage();

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "priant@example.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Recevoir mon lien" }));

    await waitFor(() =>
      expect(
        screen.getByText("Une erreur est survenue. Veuillez réessayer."),
      ).toBeInTheDocument(),
    );
  });
});
