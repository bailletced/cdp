import { screen } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { renderApp } from "./test/renderApp";
import { server } from "./test/setup";

describe("The sorter at “/”", () => {
  it("sends a visitor to the event landing", async () => {
    server.use(
      http.get(
        "http://localhost:8080/api/me",
        () => new HttpResponse(null, { status: 401 }),
      ),
    );

    renderApp("/");

    expect(
      await screen.findByRole("button", { name: "Je participe" }),
    ).toBeInTheDocument();
  });

  it("sends a signed-in priant to « Mon espace »", async () => {
    server.use(
      http.get("http://localhost:8080/api/me", () =>
        HttpResponse.json({
          email: "priant@example.org",
          isOrganizer: false,
        }),
      ),
      http.get(
        "http://localhost:8080/api/editions/current",
        () => new HttpResponse(null, { status: 404 }),
      ),
    );

    renderApp("/");

    expect(
      await screen.findByRole("heading", { name: "Mon espace" }),
    ).toBeInTheDocument();
  });
});
