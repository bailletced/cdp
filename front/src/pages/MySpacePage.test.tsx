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
      screen.getByRole("button", { name: "Me déconnecter" }),
    ).toBeInTheDocument();
  });

  it("redirects a visitor to /receive-my-link", async () => {
    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return new HttpResponse(null, { status: 401 });
      }),
    );

    renderApp("/my-space");

    // The guard redirects before the page mounts: the link request form shows up
    expect(await screen.findByLabelText("Adresse e-mail")).toBeInTheDocument();
    expect(
      screen.queryByRole("button", { name: "Me déconnecter" }),
    ).not.toBeInTheDocument();
  });

  it("logs out and lands on /receive-my-link", async () => {
    let logoutPosted = false;
    server.use(
      http.post("http://localhost:8080/auth/logout", () => {
        logoutPosted = true;
        return new HttpResponse(null, { status: 200 });
      }),
    );

    renderApp("/my-space");

    fireEvent.click(
      await screen.findByRole("button", { name: "Me déconnecter" }),
    );

    expect(await screen.findByLabelText("Adresse e-mail")).toBeInTheDocument();
    expect(logoutPosted).toBe(true);
  });
});
