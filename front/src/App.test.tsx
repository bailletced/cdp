import { screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { renderApp } from "./test/renderApp";

describe("Root component", () => {
  it("renders the title", async () => {
    renderApp("/");
    expect(
      await screen.findByText("Chaîne de prière - CDP"),
    ).toBeInTheDocument();
  });

  it("renders a heading", async () => {
    renderApp("/");
    expect(await screen.findByRole("heading")).toBeInTheDocument();
  });
});

describe("API integration with MSW", () => {
  it("can mock the /api/me endpoint", async () => {
    // This test demonstrates that MSW works against the dev-stack URL
    const response = await fetch("http://localhost:8080/api/me");
    const data = await response.json();

    expect(data.email).toBe("thomas@example.com");
  });
});
