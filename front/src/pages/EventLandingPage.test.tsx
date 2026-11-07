import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";
import { EventLandingPage } from "./EventLandingPage";

const ME_URL = "http://localhost:8080/api/me";
const REGISTER_URL = "http://localhost:8080/auth/register";

beforeEach(() => {
  vi.stubEnv("TZ", "Europe/Paris");
  // A visitor by default: the e-mail form shows up
  server.use(http.get(ME_URL, () => new HttpResponse(null, { status: 401 })));
});

afterEach(() => {
  vi.unstubAllEnvs();
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
  it("is reachable at /landing, in its own light treatment", async () => {
    renderApp("/landing");

    expect(
      await screen.findByRole("heading", {
        level: 1,
        name: "Une chaîne de prière de 24 heures",
      }),
    ).toBeInTheDocument();
    expect(document.body.classList.contains("theme-dawn")).toBe(true);
  });

  it("does not offer « Recevoir mon lien » anywhere: the form is the page", async () => {
    renderApp("/landing");

    expect(
      await screen.findByRole("heading", {
        level: 1,
        name: "Une chaîne de prière de 24 heures",
      }),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("link", { name: "Recevoir mon lien" }),
    ).toBeNull();
  });

  it("welcomes people who know nothing: the event, then how it happens", async () => {
    renderPage();

    expect(
      screen.getByRole("heading", {
        level: 1,
        name: "Une chaîne de prière de 24 heures",
      }),
    ).toBeInTheDocument();
    expect(screen.getByText(/samedi 28 novembre à 22h/)).toBeInTheDocument();
    expect(screen.getByText(/dimanche 29 novembre à 22h/)).toBeInTheDocument();
    expect(
      screen.getByRole("heading", {
        level: 3,
        name: "L'Évangile selon saint Marc, d'un bout à l'autre",
      }),
    ).toBeInTheDocument();

    // Three editorial paragraphs, no interactive planning here
    expect(
      screen.getByRole("heading", { level: 2, name: "Une veillée continue" }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("heading", {
        level: 3,
        name: "Une demi-heure, offerte",
      }),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("region", { name: "Grille des créneaux" }),
    ).toBeNull();

    // Christ the King, by a named painter
    const image = screen.getByRole("img", {
      name: "Le Christ roi et la Vierge reine, couronnés, par Hans von Kulmbach",
    });
    expect(image).toHaveAttribute("src", "/img/christ-roi.jpg");
    expect(screen.getByText(/Hans von Kulmbach/)).toBeInTheDocument();

    // The steps, then the second capture form
    expect(
      screen.getByRole("heading", { level: 2, name: "Comment ça se passe" }),
    ).toBeInTheDocument();
    expect(screen.getByText("Entrez votre e-mail")).toBeInTheDocument();
    expect(
      screen.getByRole("heading", { level: 2, name: "Offrez une demi-heure" }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "Je m'inscris" }),
    ).toBeInTheDocument();
  });

  it("shows the form in the hero and after the pitch, both posting /auth/register", async () => {
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

    // Two instances, one above the fold and one after the pitch
    const emailFields = screen.getAllByLabelText("Adresse e-mail");
    expect(emailFields).toHaveLength(2);

    fireEvent.change(emailFields[0], {
      target: { value: "priant@example.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Je participe" }));

    expect(
      await screen.findByText(/Un lien de connexion vient de vous être envoyé/),
    ).toBeInTheDocument();

    expect(bodies).toHaveLength(1);
    expect(JSON.parse(bodies[0])).toEqual({
      email: "priant@example.org",
      website: "",
    });
  });

  it("replaces the forms by a link to my-space for a signed-in visitor", async () => {
    server.use(
      http.get(ME_URL, () =>
        HttpResponse.json({ email: "priant@example.org", isOrganizer: false }),
      ),
    );

    renderPage();

    // Both captures are replaced by a link to my-space
    expect(
      await screen.findAllByRole("link", {
        name: "Choisissez vos créneaux dans mon espace",
      }),
    ).toHaveLength(2);
    expect(screen.queryByLabelText("Adresse e-mail")).toBeNull();
  });

  it("shows a generic error when the register call fails", async () => {
    server.use(
      http.post(REGISTER_URL, () => new HttpResponse(null, { status: 500 })),
    );

    renderPage();

    fireEvent.change(screen.getAllByLabelText("Adresse e-mail")[0], {
      target: { value: "priant@example.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Je participe" }));

    await waitFor(() =>
      expect(
        screen.getByText("Une erreur est survenue. Veuillez réessayer."),
      ).toBeInTheDocument(),
    );
  });
});
