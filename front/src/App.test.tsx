import { screen, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { SITE_NAME } from "./config/site";
import { renderApp } from "./test/renderApp";
import { server } from "./test/setup";

describe("Root component", () => {
  it("renders the community brand in the header", async () => {
    renderApp("/");
    expect(
      await screen.findByRole("link", { name: SITE_NAME }),
    ).toBeInTheDocument();
  });

  it("renders a heading on a page", async () => {
    renderApp("/receive-my-link");
    expect(
      await screen.findByRole("heading", { name: "Recevoir mon lien" }),
    ).toBeInTheDocument();
  });
});

describe("API integration with MSW", () => {
  it("can mock the /api/me endpoint", async () => {
    // This test demonstrates that MSW works against the dev-stack URL
    const response = await fetch("http://localhost:8080/api/me");
    const data = await response.json();

    expect(data.email).toBe("thomas@bailletced.org");
  });
});

describe("Header session state", () => {
  it("shows the 'Mon espace' link when authenticated", async () => {
    renderApp("/");
    expect(
      await screen.findByRole("link", { name: "Mon espace" }),
    ).toBeInTheDocument();
  });

  it("shows the 'Recevoir mon lien' link for a visitor", async () => {
    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return new HttpResponse(null, { status: 401 });
      }),
    );

    renderApp("/");
    const nav = await screen.findByRole("navigation", {
      name: "Navigation principale",
    });
    expect(
      await within(nav).findByRole("link", { name: "Recevoir mon lien" }),
    ).toBeInTheDocument();
  });
});
