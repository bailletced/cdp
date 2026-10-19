import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { SITE_NAME } from "../config/site";
import { renderApp } from "../test/renderApp";
import { PageTitle } from "./PageTitle";

describe("PageTitle (F01-T08)", () => {
  it("renders the page h1 and sets the document title", () => {
    render(<PageTitle>Mon espace</PageTitle>);

    expect(
      screen.getByRole("heading", { level: 1, name: "Mon espace" }),
    ).toBeInTheDocument();
    expect(document.title).toBe(`Mon espace — ${SITE_NAME}`);
  });

  it("moves focus to the main heading", () => {
    render(<PageTitle>Mon espace</PageTitle>);

    expect(screen.getByRole("heading", { level: 1 })).toHaveFocus();
  });

  it("announces the page through a polite, visually hidden live region", () => {
    const { container } = render(<PageTitle>Mon espace</PageTitle>);

    const live = container.querySelector('[aria-live="polite"]');
    expect(live).toHaveTextContent("Mon espace");
    expect(live).toHaveClass("sr-only");
  });

  it("is never a tab stop (tabindex -1)", () => {
    render(<PageTitle>Mon espace</PageTitle>);

    expect(screen.getByRole("heading", { level: 1 })).toHaveAttribute(
      "tabindex",
      "-1",
    );
  });

  it("updates the document title and the focus on route change", () => {
    const { rerender } = render(<PageTitle>Connexion</PageTitle>);
    expect(document.title).toBe(`Connexion — ${SITE_NAME}`);

    rerender(<PageTitle>Mon espace</PageTitle>);

    expect(document.title).toBe(`Mon espace — ${SITE_NAME}`);
    expect(
      screen.getByRole("heading", { level: 1, name: "Mon espace" }),
    ).toHaveFocus();
  });

  it("sets the document title when the router mounts a page", async () => {
    renderApp("/receive-my-link");

    await screen.findByRole("heading", {
      level: 1,
      name: "Recevoir mon lien",
    });
    expect(document.title).toBe(`Recevoir mon lien — ${SITE_NAME}`);
  });
});
