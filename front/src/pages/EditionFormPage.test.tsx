import { fireEvent, screen } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { describe, expect, it } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

const organizerMe = () =>
  http.get("http://localhost:8080/api/me", () => {
    return HttpResponse.json({
      email: "anne@bailletced.org",
      isOrganizer: true,
    });
  });

const EDITION_ID = "11111111-1111-1111-1111-111111111111";

function editionItem(overrides: Record<string, unknown> = {}) {
  return {
    "@context": "/api/contexts/AdminEdition",
    "@id": `/api/admin/editions/${EDITION_ID}`,
    "@type": "AdminEdition",
    id: EDITION_ID,
    title: "24 heures de prière — novembre 2026",
    presentation: "Une chaîne de prière continue.",
    // 20:00 in Paris (CET, UTC+1)
    startsAt: "2026-11-28T19:00:00+00:00",
    opensAt: "2026-11-21T19:00:00+00:00",
    lifecycle: "draft",
    status: "draft",
    scheduledAt: null,
    version: 3,
    slots: null,
    ...overrides,
  };
}

function fillForm() {
  fireEvent.change(screen.getByLabelText("Titre"), {
    target: { value: "24 heures de prière — décembre 2026" },
  });
  fireEvent.change(screen.getByLabelText("Présentation"), {
    target: { value: "Thème : l'espérance." },
  });
  fireEvent.change(screen.getByLabelText("Début (heure de Paris) — date"), {
    target: { value: "2026-12-05" },
  });
  fireEvent.change(screen.getByLabelText("Début (heure de Paris) — heure"), {
    target: { value: "20:00" },
  });
  fireEvent.change(
    screen.getByLabelText("Ouverture des inscriptions (heure de Paris) — date"),
    { target: { value: "2026-11-28" } },
  );
  fireEvent.change(
    screen.getByLabelText(
      "Ouverture des inscriptions (heure de Paris) — heure",
    ),
    { target: { value: "20:00" } },
  );
}

describe("EditionFormPage", () => {
  it("labels the date fields with the Paris time zone", async () => {
    server.use(organizerMe());

    renderApp("/organisation/editions/new");

    expect(
      await screen.findByText("Début (heure de Paris)"),
    ).toBeInTheDocument();
    expect(
      screen.getByText("Ouverture des inscriptions (heure de Paris)"),
    ).toBeInTheDocument();
  });

  it("creates an edition with the dates sent in Paris time", async () => {
    let postedBody: Record<string, unknown> | null = null;
    let postedContentType: string | null = null;

    server.use(
      organizerMe(),
      http.post(
        "http://localhost:8080/api/admin/editions",
        async ({ request }) => {
          postedContentType = request.headers.get("content-type");
          postedBody = (await request.json()) as Record<string, unknown>;

          return HttpResponse.json(
            editionItem({
              title: "24 heures de prière — décembre 2026",
              startsAt: "2026-12-05T19:00:00+00:00",
              opensAt: "2026-11-28T19:00:00+00:00",
              version: 1,
            }),
            { status: 201 },
          );
        },
      ),
    );

    renderApp("/organisation/editions/new");

    expect(
      await screen.findByRole("heading", { name: "Nouvelle édition" }),
    ).toBeInTheDocument();

    fillForm();
    fireEvent.click(screen.getByRole("button", { name: "Créer l'édition" }));

    expect(await screen.findByText("Édition créée.")).toBeInTheDocument();

    // API Platform only accepts JSON-LD bodies on this resource
    expect(postedContentType).toContain("application/ld+json");
    // 20:00 in Paris = +01:00 in November/December (CET)
    expect(postedBody).toEqual({
      title: "24 heures de prière — décembre 2026",
      presentation: "Thème : l'espérance.",
      startsAt: "2026-12-05T20:00:00+01:00",
      opensAt: "2026-11-28T20:00:00+01:00",
    });
    // The start was not adjusted: no retained-time hint
    expect(screen.queryByText(/Heure retenue/)).not.toBeInTheDocument();
  });

  it("requires the dates before sending anything", async () => {
    let postCalled = false;

    server.use(
      organizerMe(),
      http.post("http://localhost:8080/api/admin/editions", () => {
        postCalled = true;

        return HttpResponse.json(editionItem(), { status: 201 });
      }),
    );

    renderApp("/organisation/editions/new");

    expect(await screen.findByLabelText("Titre")).toBeInTheDocument();
    fireEvent.change(screen.getByLabelText("Titre"), {
      target: { value: "24 heures de prière — décembre 2026" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Créer l'édition" }));

    expect(
      await screen.findByText(
        "La date et l'heure du début de l'édition sont requises.",
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        "La date et l'heure de l'ouverture des inscriptions sont requises.",
      ),
    ).toBeInTheDocument();
    expect(postCalled).toBe(false);
  });

  it("shows the 422 violations on the right fields", async () => {
    server.use(
      organizerMe(),
      http.post("http://localhost:8080/api/admin/editions", () => {
        return HttpResponse.json(
          {
            "@context": "/api/contexts/ConstraintViolation",
            "@id": "/api/errors/422",
            "@type": "ConstraintViolation",
            title: "An error occurred",
            detail:
              "title: Le titre doit comporter entre 3 et 120 caractères.\nstartsAt: L'heure du début de l'édition doit tomber sur l'heure pleine ou la demi-heure (heure de Paris).",
            status: 422,
            violations: [
              {
                propertyPath: "title",
                message: "Le titre doit comporter entre 3 et 120 caractères.",
              },
              {
                propertyPath: "startsAt",
                message:
                  "L'heure du début de l'édition doit tomber sur l'heure pleine ou la demi-heure (heure de Paris).",
              },
            ],
          },
          { status: 422 },
        );
      }),
    );

    renderApp("/organisation/editions/new");

    expect(await screen.findByLabelText("Titre")).toBeInTheDocument();
    fillForm();
    fireEvent.click(screen.getByRole("button", { name: "Créer l'édition" }));

    // Each violation lands on its own field
    expect(
      await screen.findByText(
        "Le titre doit comporter entre 3 et 120 caractères.",
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        "L'heure du début de l'édition doit tomber sur l'heure pleine ou la demi-heure (heure de Paris).",
      ),
    ).toBeInTheDocument();
    expect(screen.getByLabelText("Titre")).toHaveAttribute(
      "aria-invalid",
      "true",
    );
    expect(
      screen.getByLabelText("Début (heure de Paris) — date"),
    ).toHaveAttribute("aria-invalid", "true");
    // The other fields stay valid
    expect(screen.getByLabelText("Présentation")).not.toHaveAttribute(
      "aria-invalid",
    );
    expect(
      screen.getByLabelText(
        "Ouverture des inscriptions (heure de Paris) — date",
      ),
    ).not.toHaveAttribute("aria-invalid");
  });

  it("shows the retained start when the API adjusted the entry (RG-ED-7)", async () => {
    server.use(
      organizerMe(),
      http.post("http://localhost:8080/api/admin/editions", () => {
        return HttpResponse.json(
          editionItem({
            title: "Veillée de Pâques",
            // Entered 2h30 on the spring-forward night: retained 3h30
            startsAt: "2027-03-28T01:30:00+00:00",
            resolvedStartsAtUtc: "2027-03-28T01:30:00+00:00",
            resolvedStartsAtLabel: "3h30–4h00",
            version: 1,
          }),
          { status: 201 },
        );
      }),
    );

    renderApp("/organisation/editions/new");

    expect(await screen.findByLabelText("Titre")).toBeInTheDocument();
    fireEvent.change(screen.getByLabelText("Titre"), {
      target: { value: "Veillée de Pâques" },
    });
    fireEvent.change(screen.getByLabelText("Début (heure de Paris) — date"), {
      target: { value: "2027-03-28" },
    });
    fireEvent.change(screen.getByLabelText("Début (heure de Paris) — heure"), {
      target: { value: "02:30" },
    });
    fireEvent.change(
      screen.getByLabelText(
        "Ouverture des inscriptions (heure de Paris) — date",
      ),
      { target: { value: "2027-03-14" } },
    );
    fireEvent.change(
      screen.getByLabelText(
        "Ouverture des inscriptions (heure de Paris) — heure",
      ),
      { target: { value: "20:00" } },
    );
    fireEvent.click(screen.getByRole("button", { name: "Créer l'édition" }));

    expect(await screen.findByText("Édition créée.")).toBeInTheDocument();
    expect(screen.getByText("Heure retenue : 3h30–4h00")).toBeInTheDocument();
  });

  it("pre-fills the form and patches with the optimistic-lock version", async () => {
    let patchedBody: Record<string, unknown> | null = null;
    let patchedContentType: string | null = null;

    server.use(
      organizerMe(),
      http.get(`http://localhost:8080/api/admin/editions/${EDITION_ID}`, () => {
        return HttpResponse.json(editionItem());
      }),
      http.patch(
        `http://localhost:8080/api/admin/editions/${EDITION_ID}`,
        async ({ request }) => {
          patchedContentType = request.headers.get("content-type");
          patchedBody = (await request.json()) as Record<string, unknown>;

          return HttpResponse.json(
            editionItem({ title: "Titre modifié", version: 4 }),
          );
        },
      ),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/edit`);

    // Pre-filled from the API: UTC instants shown as the Paris wall clock
    expect(await screen.findByLabelText<HTMLInputElement>("Titre")).toHaveValue(
      "24 heures de prière — novembre 2026",
    );
    expect(screen.getByLabelText<HTMLInputElement>("Présentation")).toHaveValue(
      "Une chaîne de prière continue.",
    );
    expect(
      screen.getByLabelText<HTMLInputElement>("Début (heure de Paris) — date"),
    ).toHaveValue("2026-11-28");
    expect(
      screen.getByLabelText<HTMLInputElement>("Début (heure de Paris) — heure"),
    ).toHaveValue("20:00");
    expect(
      screen.getByLabelText<HTMLInputElement>(
        "Ouverture des inscriptions (heure de Paris) — date",
      ),
    ).toHaveValue("2026-11-21");
    expect(
      screen.getByLabelText<HTMLInputElement>(
        "Ouverture des inscriptions (heure de Paris) — heure",
      ),
    ).toHaveValue("20:00");

    fireEvent.change(screen.getByLabelText("Titre"), {
      target: { value: "Titre modifié" },
    });
    fireEvent.click(
      screen.getByRole("button", { name: "Enregistrer les modifications" }),
    );

    expect(
      await screen.findByText("Modifications enregistrées."),
    ).toBeInTheDocument();

    expect(patchedContentType).toContain("application/merge-patch+json");
    expect(patchedBody).toEqual({
      title: "Titre modifié",
      presentation: "Une chaîne de prière continue.",
      startsAt: "2026-11-28T20:00:00+01:00",
      opensAt: "2026-11-21T20:00:00+01:00",
      version: 3,
    });
  });

  it("shows the conflict message when the edition was modified meanwhile (409)", async () => {
    server.use(
      organizerMe(),
      http.get(`http://localhost:8080/api/admin/editions/${EDITION_ID}`, () => {
        return HttpResponse.json(editionItem());
      }),
      http.patch(
        `http://localhost:8080/api/admin/editions/${EDITION_ID}`,
        () => {
          return HttpResponse.json(
            {
              "@context": "/api/contexts/Error",
              "@id": "/api/errors/409",
              "@type": "Error",
              title: "An error occurred",
              detail:
                "L’édition a été modifiée par quelqu’un d’autre. Veuillez recharger.",
              status: 409,
            },
            { status: 409 },
          );
        },
      ),
    );

    renderApp(`/organisation/editions/${EDITION_ID}/edit`);

    expect(await screen.findByLabelText("Titre")).toHaveValue(
      "24 heures de prière — novembre 2026",
    );

    fireEvent.click(
      screen.getByRole("button", { name: "Enregistrer les modifications" }),
    );

    expect(await screen.findByRole("alert")).toHaveTextContent(
      "L’édition a été modifiée par quelqu’un d’autre. Veuillez recharger.",
    );
  });
});
