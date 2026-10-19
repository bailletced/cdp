import { screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { SITE_NAME } from "../config/site";
import { renderApp } from "../test/renderApp";

describe("public layout (F01-T07)", () => {
  it("renders the skip link and the landmarks", async () => {
    renderApp("/receive-my-link");

    // The router mounts asynchronously: wait for the layout first
    const banner = await screen.findByRole("banner");

    // Skip link jumps to the main landmark
    const skipLink = screen.getByRole("link", { name: "Aller au contenu" });
    expect(skipLink).toHaveAttribute("href", "#main");

    // Landmarks: banner, labelled navigation, main, contentinfo
    expect(banner).toBeInTheDocument();
    expect(
      screen.getByRole("navigation", { name: "Navigation principale" }),
    ).toBeInTheDocument();
    expect(screen.getByRole("main")).toHaveAttribute("id", "main");
    expect(screen.getByRole("contentinfo")).toBeInTheDocument();

    // Exactly one main landmark: pages render their content inside it
    expect(screen.getAllByRole("main")).toHaveLength(1);
  });

  it("shows the community name from the single site config in the header", async () => {
    renderApp("/receive-my-link");

    const banner = await screen.findByRole("banner");
    const brand = screen.getByRole("link", { name: SITE_NAME });
    expect(banner).toContainElement(brand);
    expect(brand).toHaveAttribute("href", "/");

    // The brand is not a heading: the page-level h1 comes from the page
    // itself (PageTitle, F01-T08)
    expect(banner.querySelector("h1, h2, h3, h4, h5, h6")).toBeNull();
  });

  it("shows the footer links", async () => {
    renderApp("/receive-my-link");

    const footer = await screen.findByRole("contentinfo");
    expect(
      screen.getByRole("link", { name: "Confidentialité" }),
    ).toHaveAttribute("href", "/confidentialite");
    const receiveLink = screen.getByRole("link", { name: "Recevoir mon lien" });
    expect(footer).toContainElement(receiveLink);
    expect(receiveLink).toHaveAttribute("href", "/receive-my-link");
  });

  it("offers Mon espace in the nav when a session is open (MSW: authenticated)", async () => {
    renderApp("/receive-my-link");

    expect(
      await screen.findByRole("link", { name: "Mon espace" }),
    ).toHaveAttribute("href", "/my-space");
  });
});

describe("404 and /confidentialite pages (F01-T11)", () => {
  it("renders the 404 page inside the layout for an unknown path", async () => {
    renderApp("/chemin-inconnu");

    // The layout wraps the 404 page
    expect(await screen.findByRole("banner")).toBeInTheDocument();
    expect(
      await screen.findByRole("heading", {
        name: "Page introuvable",
        level: 1,
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("link", { name: "Retour à l'accueil" }),
    ).toHaveAttribute("href", "/");
    expect(screen.getByRole("contentinfo")).toBeInTheDocument();

    // The document title is set by PageTitle (F01-T08)
    expect(document.title).toBe(`Page introuvable — ${SITE_NAME}`);
  });

  it("renders the provisional /confidentialite page inside the layout", async () => {
    renderApp("/confidentialite");

    expect(await screen.findByRole("banner")).toBeInTheDocument();
    expect(
      await screen.findByRole("heading", { name: "Confidentialité", level: 1 }),
    ).toBeInTheDocument();
    expect(screen.getByRole("contentinfo")).toBeInTheDocument();
    expect(document.title).toBe(`Confidentialité — ${SITE_NAME}`);
  });
});
