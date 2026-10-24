import { fireEvent, screen } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

describe("MySpacePage", () => {
  it("shows the viewer email and a logout button for an authenticated viewer", async () => {
    renderApp("/my-space");

    expect(
      await screen.findByRole("heading", { name: "Mon espace" }),
    ).toBeInTheDocument();
    expect(screen.getByText("thomas@bailletced.org")).toBeInTheDocument();
    expect(
      screen.getByRole("heading", { name: "Choisissez vos créneaux" }),
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
