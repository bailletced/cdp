import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { server } from "../test/setup";
import { ReceiveMyLinkPage } from "./ReceiveMyLinkPage";

const CONFIRMATION_MESSAGE =
  "Si un compte correspond à cette adresse, un lien de connexion vient de vous être envoyé.";

describe("ReceiveMyLinkPage", () => {
  it("renders the email form with a hidden honeypot field", () => {
    const { container } = render(<ReceiveMyLinkPage />);

    expect(screen.getByLabelText("Adresse e-mail")).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "Recevoir mon lien" }),
    ).toBeInTheDocument();

    // The honeypot must exist in the DOM but stay invisible to humans
    const honeypot = container.querySelector('input[name="website"]');
    expect(honeypot).not.toBeNull();
    expect(honeypot).toHaveAttribute("hidden");
    expect(honeypot).toHaveAttribute("aria-hidden", "true");
    expect(honeypot).toHaveAttribute("tabindex", "-1");
  });

  it("shows the identical confirmation message whatever the email", async () => {
    const { unmount } = render(<ReceiveMyLinkPage />);

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "thomas@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Recevoir mon lien" }));

    const firstMessage = await screen.findByRole("status");
    expect(firstMessage).toHaveTextContent(CONFIRMATION_MESSAGE);
    unmount();

    // A second request with an unknown address shows the exact same message
    render(<ReceiveMyLinkPage />);

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "inconnu@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Recevoir mon lien" }));

    const secondMessage = await screen.findByRole("status");
    expect(secondMessage.textContent).toBe(firstMessage.textContent);
  });

  it("posts the email and the honeypot field as JSON", async () => {
    let capturedBody: unknown = null;
    server.use(
      http.post(
        "http://localhost:8080/auth/link-request",
        async ({ request }) => {
          capturedBody = await request.json();
          return HttpResponse.json(
            { message: CONFIRMATION_MESSAGE },
            { status: 202 },
          );
        },
      ),
    );

    render(<ReceiveMyLinkPage />);

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "thomas@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Recevoir mon lien" }));

    await screen.findByRole("status");
    expect(capturedBody).toEqual({
      email: "thomas@bailletced.org",
      website: "",
    });
  });

  it("shows a generic error message when the request fails", async () => {
    server.use(
      http.post("http://localhost:8080/auth/link-request", () => {
        return new HttpResponse(null, { status: 500 });
      }),
    );

    render(<ReceiveMyLinkPage />);

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "thomas@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Recevoir mon lien" }));

    expect(await screen.findByRole("status")).toHaveTextContent(
      "Une erreur est survenue. Veuillez réessayer.",
    );
  });

  it("shows no anti-robot check while it is off", async () => {
    const { container } = render(<ReceiveMyLinkPage />);

    // The default handler answers 204: no challenge, no widget
    await waitFor(() =>
      expect(container.querySelector("altcha-widget")).toBeNull(),
    );
    expect(
      screen.getByRole("button", { name: "Recevoir mon lien" }),
    ).toBeEnabled();
  });

  it("sends the solution of the anti-robot check once it is on (F15)", async () => {
    let capturedBody: unknown = null;
    server.use(
      http.get("http://localhost:8080/api/altcha-challenge", () =>
        HttpResponse.json({
          parameters: { algorithm: "SHA-256" },
          signature: "s",
        }),
      ),
      http.post(
        "http://localhost:8080/auth/link-request",
        async ({ request }) => {
          capturedBody = await request.json();
          return HttpResponse.json(
            { message: CONFIRMATION_MESSAGE },
            { status: 202 },
          );
        },
      ),
    );

    const { container } = render(<ReceiveMyLinkPage />);
    await waitFor(() =>
      expect(container.querySelector("altcha-widget")).not.toBeNull(),
    );
    // The widget solves its challenge in the browser, then says so
    container.querySelector("altcha-widget")?.dispatchEvent(
      new CustomEvent("statechange", {
        detail: { state: "verified", payload: "solution" },
      }),
    );
    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "thomas@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Recevoir mon lien" }));

    await screen.findByRole("status");
    expect(capturedBody).toEqual({
      email: "thomas@bailletced.org",
      website: "",
      altcha: "solution",
    });
  });

  it("asks to try again later after too many requests (429)", async () => {
    server.use(
      http.post(
        "http://localhost:8080/auth/link-request",
        () => new HttpResponse(null, { status: 429 }),
      ),
    );

    render(<ReceiveMyLinkPage />);

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "thomas@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Recevoir mon lien" }));

    expect(await screen.findByRole("status")).toHaveTextContent(
      "Trop de tentatives, réessayez dans quelques minutes.",
    );
  });
});
