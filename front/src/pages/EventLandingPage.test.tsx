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
  it("is reachable at /landing through the router", async () => {
    renderApp("/landing");

    expect(
      await screen.findByRole("heading", {
        level: 1,
        name: "Une chaîne de prière de 24 heures",
      }),
    ).toBeInTheDocument();
  });

  it("explains the event: 24 h chain from Sat 28 Nov 22:00 to Sun 29 Nov 22:00, Gospel of Mark, 30-minute slots", async () => {
    renderPage();

    expect(
      await screen.findByRole("heading", {
        level: 1,
        name: "Une chaîne de prière de 24 heures",
      }),
    ).toBeInTheDocument();
    expect(screen.getByText(/samedi 28 novembre à 22h00/)).toBeInTheDocument();
    expect(
      screen.getByText(/dimanche 29 novembre à 22h00/),
    ).toBeInTheDocument();
    expect(
      screen.getAllByText(/Évangile selon saint Marc/i).length,
    ).toBeGreaterThan(0);
    // The chain timeline is drawn and labelled, but not interactive
    expect(
      screen.getByRole("img", { name: /Frise des 24 heures/ }),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("region", { name: "Grille des créneaux" }),
    ).toBeNull();

    // The three steps of the journey
    expect(screen.getByText("Entrez votre e-mail")).toBeInTheDocument();
    expect(screen.getByText("Cliquez le lien")).toBeInTheDocument();
    expect(screen.getByText("Choisissez vos créneaux")).toBeInTheDocument();

    // Hero CTA anchors to the form
    expect(screen.getByRole("link", { name: "Je participe" })).toHaveAttribute(
      "href",
      "#inscription",
    );
  });

  it("submits the e-mail to /auth/register and confirms the welcome e-mail", async () => {
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
    fireEvent.click(screen.getByRole("button", { name: "Je participe" }));

    await waitFor(() =>
      expect(
        screen.getByText("Une erreur est survenue. Veuillez réessayer."),
      ).toBeInTheDocument(),
    );
  });
});
