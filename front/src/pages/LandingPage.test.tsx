import { fireEvent, render, screen } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { server } from "../test/setup";
import { LandingPage } from "./LandingPage";

const CONFIRMATION_MESSAGE =
  "Un lien de connexion vient de vous être envoyé à cette adresse.";

describe("LandingPage", () => {
  it("renders the presentation and the email registration form", () => {
    const { container } = render(<LandingPage />);

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
    render(<LandingPage />);

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

    render(<LandingPage />);

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

    render(<LandingPage />);

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "marie@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "S'inscrire" }));

    expect(await screen.findByRole("status")).toHaveTextContent(
      "Une erreur est survenue. Veuillez réessayer.",
    );
  });
});
