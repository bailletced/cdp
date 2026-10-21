import { screen } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

describe("OrganisationPage", () => {
  it("shows the back-office section to an organizer", async () => {
    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return HttpResponse.json({
          email: "anne@bailletced.org",
          isOrganizer: true,
        });
      }),
    );

    renderApp("/organisation");

    expect(
      await screen.findByRole("heading", { name: "Organisateurs" }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("navigation", { name: "Back-office" }),
    ).toBeInTheDocument();
  });

  it("redirects a visitor to /receive-my-link", async () => {
    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return new HttpResponse(null, { status: 401 });
      }),
    );

    renderApp("/organisation");

    // The guard redirects before the page mounts: the link request form shows up
    expect(await screen.findByLabelText("Adresse e-mail")).toBeInTheDocument();
    expect(
      screen.queryByRole("heading", { name: "Organisateurs" }),
    ).not.toBeInTheDocument();
  });

  it("redirects an authenticated priant to /my-space", async () => {
    // The default MSW handler answers /api/me with isOrganizer: false
    renderApp("/organisation");

    expect(
      await screen.findByRole("heading", { name: "Mon espace" }),
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("heading", { name: "Organisateurs" }),
    ).not.toBeInTheDocument();
  });
});
