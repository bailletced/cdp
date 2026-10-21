import { fireEvent, screen, waitFor } from "@testing-library/react";
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

  it("lists the organizers with who added them", async () => {
    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return HttpResponse.json({
          email: "anne@bailletced.org",
          isOrganizer: true,
        });
      }),
      http.get("http://localhost:8080/api/admin/organizers", () => {
        return HttpResponse.json({
          "@context": "/api/contexts/AdminOrganizer",
          "@id": "/api/admin/organizers",
          "@type": "Collection",
          member: [
            {
              "@id":
                "/api/admin/organizers/11111111-1111-1111-1111-111111111111",
              "@type": "AdminOrganizer",
              id: "11111111-1111-1111-1111-111111111111",
              email: "anne@bailletced.org",
              addedByEmail: "system@bailletced.org",
              addedAt: "2026-10-01T10:00:00+00:00",
            },
            {
              "@id":
                "/api/admin/organizers/22222222-2222-2222-2222-222222222222",
              "@type": "AdminOrganizer",
              id: "22222222-2222-2222-2222-222222222222",
              email: "bob@bailletced.org",
              addedByEmail: "anne@bailletced.org",
              addedAt: "2026-10-02T10:00:00+00:00",
            },
          ],
          totalItems: 2,
        });
      }),
    );

    renderApp("/organisation");

    expect(await screen.findByText("anne@bailletced.org")).toBeInTheDocument();
    expect(screen.getByText("bob@bailletced.org")).toBeInTheDocument();
    expect(
      screen.getByText(/Ajouté par anne@bailletced\.org/),
    ).toBeInTheDocument();
  });

  it("adds an organizer by email", async () => {
    let postedContentType: string | null = null;
    let organizers = [
      {
        "@id": "/api/admin/organizers/11111111-1111-1111-1111-111111111111",
        "@type": "AdminOrganizer",
        id: "11111111-1111-1111-1111-111111111111",
        email: "anne@bailletced.org",
        addedByEmail: "system@bailletced.org",
        addedAt: "2026-10-01T10:00:00+00:00",
      },
    ];

    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return HttpResponse.json({
          email: "anne@bailletced.org",
          isOrganizer: true,
        });
      }),
      http.get("http://localhost:8080/api/admin/organizers", () => {
        return HttpResponse.json({
          "@context": "/api/contexts/AdminOrganizer",
          "@id": "/api/admin/organizers",
          "@type": "Collection",
          member: organizers,
          totalItems: organizers.length,
        });
      }),
      http.post(
        "http://localhost:8080/api/admin/organizers",
        async ({ request }) => {
          postedContentType = request.headers.get("content-type");
          const body = (await request.json()) as { email?: string };
          const created = {
            "@id": "/api/admin/organizers/22222222-2222-2222-2222-222222222222",
            "@type": "AdminOrganizer",
            id: "22222222-2222-2222-2222-222222222222",
            email: body.email ?? "",
            addedByEmail: "anne@bailletced.org",
            addedAt: "2026-10-02T10:00:00+00:00",
          };
          organizers = [...organizers, created];

          return HttpResponse.json(created, { status: 201 });
        },
      ),
    );

    renderApp("/organisation");

    expect(await screen.findByText("anne@bailletced.org")).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "bob@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Ajouter" }));

    // After the POST, the list is refetched and shows the new organizer
    expect(await screen.findByText("bob@bailletced.org")).toBeInTheDocument();
    // API Platform only accepts JSON-LD bodies on this endpoint
    expect(postedContentType).toContain("application/ld+json");
  });

  it("removes an organizer after confirmation", async () => {
    let organizers = [
      {
        "@id": "/api/admin/organizers/11111111-1111-1111-1111-111111111111",
        "@type": "AdminOrganizer",
        id: "11111111-1111-1111-1111-111111111111",
        email: "anne@bailletced.org",
        addedByEmail: "system@bailletced.org",
        addedAt: "2026-10-01T10:00:00+00:00",
      },
      {
        "@id": "/api/admin/organizers/22222222-2222-2222-2222-222222222222",
        "@type": "AdminOrganizer",
        id: "22222222-2222-2222-2222-222222222222",
        email: "bob@bailletced.org",
        addedByEmail: "anne@bailletced.org",
        addedAt: "2026-10-02T10:00:00+00:00",
      },
    ];

    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return HttpResponse.json({
          email: "anne@bailletced.org",
          isOrganizer: true,
        });
      }),
      http.get("http://localhost:8080/api/admin/organizers", () => {
        return HttpResponse.json({
          "@context": "/api/contexts/AdminOrganizer",
          "@id": "/api/admin/organizers",
          "@type": "Collection",
          member: organizers,
          totalItems: organizers.length,
        });
      }),
      http.delete(
        "http://localhost:8080/api/admin/organizers/:id",
        ({ params }) => {
          organizers = organizers.filter(
            (organizer) => organizer.id !== params.id,
          );

          return new HttpResponse(null, { status: 204 });
        },
      ),
    );

    renderApp("/organisation");

    expect(await screen.findByText("bob@bailletced.org")).toBeInTheDocument();

    fireEvent.click(
      screen.getByRole("button", { name: "Retirer bob@bailletced.org" }),
    );

    // The confirmation dialog asks before removing
    expect(
      await screen.findByRole("alertdialog", {
        name: "Retirer un organisateur",
      }),
    ).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "Retirer" }));

    // After the DELETE, the list is refetched without the removed organizer
    await waitFor(() => {
      expect(screen.queryByText("bob@bailletced.org")).not.toBeInTheDocument();
    });
    expect(screen.getByText("anne@bailletced.org")).toBeInTheDocument();
  });

  it("refuses to remove the last organizer (422)", async () => {
    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return HttpResponse.json({
          email: "anne@bailletced.org",
          isOrganizer: true,
        });
      }),
      http.get("http://localhost:8080/api/admin/organizers", () => {
        return HttpResponse.json({
          "@context": "/api/contexts/AdminOrganizer",
          "@id": "/api/admin/organizers",
          "@type": "Collection",
          member: [
            {
              "@id":
                "/api/admin/organizers/11111111-1111-1111-1111-111111111111",
              "@type": "AdminOrganizer",
              id: "11111111-1111-1111-1111-111111111111",
              email: "anne@bailletced.org",
              addedByEmail: "system@bailletced.org",
              addedAt: "2026-10-01T10:00:00+00:00",
            },
          ],
          totalItems: 1,
        });
      }),
      http.delete("http://localhost:8080/api/admin/organizers/:id", () => {
        return HttpResponse.json(
          {
            "@context": "/api/contexts/Error",
            "@id": "/api/errors/422",
            "@type": "Error",
            title: "An error occurred",
            detail: "Il doit toujours rester au moins un organisateur.",
            status: 422,
          },
          { status: 422 },
        );
      }),
    );

    renderApp("/organisation");

    expect(await screen.findByText("anne@bailletced.org")).toBeInTheDocument();

    fireEvent.click(
      screen.getByRole("button", { name: "Retirer anne@bailletced.org" }),
    );
    fireEvent.click(await screen.findByRole("button", { name: "Retirer" }));

    // The API refuses (422): the reason is shown and the organizer stays
    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Il doit toujours rester au moins un organisateur.",
    );
    expect(screen.getByText("anne@bailletced.org")).toBeInTheDocument();
  });

  it("shows the API error when adding an organizer fails", async () => {
    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return HttpResponse.json({
          email: "anne@bailletced.org",
          isOrganizer: true,
        });
      }),
      http.get("http://localhost:8080/api/admin/organizers", () => {
        return HttpResponse.json({
          "@context": "/api/contexts/AdminOrganizer",
          "@id": "/api/admin/organizers",
          "@type": "Collection",
          member: [
            {
              "@id":
                "/api/admin/organizers/11111111-1111-1111-1111-111111111111",
              "@type": "AdminOrganizer",
              id: "11111111-1111-1111-1111-111111111111",
              email: "anne@bailletced.org",
              addedByEmail: "system@bailletced.org",
              addedAt: "2026-10-01T10:00:00+00:00",
            },
          ],
          totalItems: 1,
        });
      }),
      http.post("http://localhost:8080/api/admin/organizers", () => {
        return HttpResponse.json(
          {
            "@context": "/api/contexts/Error",
            "@id": "/api/errors/400",
            "@type": "Error",
            title: "An error occurred",
            detail: "Cette adresse e-mail est déjà organisatrice.",
            status: 400,
          },
          { status: 400 },
        );
      }),
    );

    renderApp("/organisation");

    expect(await screen.findByText("anne@bailletced.org")).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText("Adresse e-mail"), {
      target: { value: "bob@bailletced.org" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Ajouter" }));

    // The API error detail is shown to the organizer
    expect(await screen.findByRole("alert")).toHaveTextContent(
      "Cette adresse e-mail est déjà organisatrice.",
    );
  });
});
