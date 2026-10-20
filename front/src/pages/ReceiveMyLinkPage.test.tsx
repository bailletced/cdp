import { fireEvent, render, screen } from "@testing-library/react";
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
});
