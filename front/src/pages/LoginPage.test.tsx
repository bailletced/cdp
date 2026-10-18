import { screen } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

const LINK_QUERY = "user=abc&expires=123&hash=def";

describe("LoginPage", () => {
  it("shows a loading state then redirects to the target returned by the API", async () => {
    // Hold the POST until the test releases it, to observe the loading state
    let releasePost: () => void = () => {};
    server.use(
      http.post("http://localhost:8080/auth/login", async () => {
        await new Promise<void>((resolve) => {
          releasePost = resolve;
        });
        return HttpResponse.json({ target: "/my-space" });
      }),
    );

    renderApp(`/login?${LINK_QUERY}`);

    // Loading state while the POST is in flight
    expect(await screen.findByText("Connexion en cours…")).toBeInTheDocument();

    releasePost();

    // The API answers 200 { target: "/my-space" }: the placeholder page shows up
    expect(await screen.findByText("Mon espace")).toBeInTheDocument();
  });

  it("posts the link parameters as form params, exactly once", async () => {
    const bodies: string[] = [];
    server.use(
      http.post("http://localhost:8080/auth/login", async ({ request }) => {
        bodies.push(await request.text());
        return HttpResponse.json({ target: "/my-space" });
      }),
    );

    renderApp(`/login?${LINK_QUERY}`);

    await screen.findByText("Mon espace");

    expect(bodies).toHaveLength(1);
    expect(bodies[0]).toContain("user=abc");
    expect(bodies[0]).toContain("expires=123");
    expect(bodies[0]).toContain("hash=def");
    expect(bodies[0]).toContain("_target_path=%2Fmy-space");
  });

  it("shows the expired-link state with a link to request a new one on 401", async () => {
    server.use(
      http.post("http://localhost:8080/auth/login", () => {
        return HttpResponse.json(
          { error: "invalid_login_link", type: "expired" },
          { status: 401 },
        );
      }),
    );

    renderApp(`/login?${LINK_QUERY}`);

    expect(
      await screen.findByText(
        "Ce lien n'est plus valide : il a peut-être expiré ou a déjà été utilisé.",
      ),
    ).toBeInTheDocument();

    const newLink = screen.getByRole("link", {
      name: "Recevoir un nouveau lien",
    });
    expect(newLink).toHaveAttribute("href", "/receive-my-link");
  });

  it("shows the invalid state without posting when the link parameters are missing", async () => {
    let posted = false;
    server.use(
      http.post("http://localhost:8080/auth/login", () => {
        posted = true;
        return HttpResponse.json({ target: "/my-space" });
      }),
    );

    renderApp("/login");

    expect(
      await screen.findByText(
        "Ce lien n'est plus valide : il a peut-être expiré ou a déjà été utilisé.",
      ),
    ).toBeInTheDocument();
    expect(posted).toBe(false);
  });
});
