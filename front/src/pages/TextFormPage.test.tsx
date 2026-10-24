import { fireEvent, screen, waitFor, within } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { beforeAll, describe, expect, it } from "vitest";
import { renderApp } from "../test/renderApp";
import { server } from "../test/setup";

const TEXTS_URL = "http://localhost:8080/api/admin/texts";
const TEXT_ID = "11111111-1111-1111-1111-111111111111";

const organizerMe = () =>
  http.get("http://localhost:8080/api/me", () => {
    return HttpResponse.json({
      email: "anne@bailletced.org",
      isOrganizer: true,
    });
  });

const emptyList = () =>
  http.get(TEXTS_URL, () => HttpResponse.json({ member: [], totalItems: 0 }));

// Pastes HTML into the editor, as from a word processor (US-06.1): jsdom
// cannot type into a contenteditable, ProseMirror handles the paste event
function paste(target: HTMLElement, html: string) {
  const event = new Event("paste", { bubbles: true, cancelable: true });
  Object.defineProperty(event, "clipboardData", {
    value: {
      types: ["text/html", "text/plain"],
      getData: (type: string) =>
        type === "text/html" ? html : html.replace(/<[^>]+>/g, ""),
    },
  });
  fireEvent(target, event);
}

// The route lazy-loads the form and Tiptap: warm the module cache so the
// first test does not wait for it under a loaded full run
beforeAll(async () => {
  await import("./TextFormPage");
});

describe("TextFormPage (F06-T13)", () => {
  it("creates a text from a pasted body and goes back to the library", async () => {
    let posted: Record<string, unknown> | null = null;
    server.use(
      organizerMe(),
      emptyList(),
      http.post(TEXTS_URL, async ({ request }) => {
        posted = (await request.json()) as Record<string, unknown>;

        return HttpResponse.json({ id: TEXT_ID, ...posted }, { status: 201 });
      }),
    );

    renderApp("/organisation/texts/new");

    expect(
      await screen.findByRole("heading", { level: 1, name: "Nouveau texte" }),
    ).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText("Titre"), {
      target: { value: "Que rien ne te trouble" },
    });
    fireEvent.change(screen.getByLabelText("Source"), {
      target: { value: "Thérèse d'Avila" },
    });
    paste(
      await screen.findByRole("textbox", { name: "Corps du texte" }),
      '<p style="color:red">Que rien <b>ne te trouble</b></p>',
    );
    fireEvent.change(screen.getByLabelText("Pistes de méditation"), {
      target: { value: "Qu'est-ce qui me trouble aujourd'hui ?" },
    });

    // The preview renders the body as the priants will read it
    const preview = screen.getByRole("region", { name: "Aperçu" });
    await waitFor(() =>
      expect(
        within(preview).getByText("ne te trouble", { selector: "strong" }),
      ).toBeInTheDocument(),
    );

    fireEvent.click(screen.getByRole("button", { name: "Créer le texte" }));

    expect(
      await screen.findByRole("heading", { level: 1, name: "Textes" }),
    ).toBeInTheDocument();
    expect(posted).toEqual({
      title: "Que rien ne te trouble",
      source: "Thérèse d'Avila",
      body: "<p>Que rien <strong>ne te trouble</strong></p>",
      hints: "Qu'est-ce qui me trouble aujourd'hui ?",
    });
    expect(await screen.findByText("Texte créé.")).toBeInTheDocument();
  });

  it("shows each 422 violation on its own field", async () => {
    server.use(
      organizerMe(),
      http.post(TEXTS_URL, () =>
        HttpResponse.json(
          {
            status: 422,
            violations: [
              {
                propertyPath: "title",
                message: "Le titre doit comporter entre 1 et 120 caractères.",
              },
              {
                propertyPath: "body",
                message: "Le corps du texte est obligatoire.",
              },
            ],
          },
          { status: 422 },
        ),
      ),
    );

    renderApp("/organisation/texts/new");

    fireEvent.click(
      await screen.findByRole("button", { name: "Créer le texte" }),
    );

    expect(
      await screen.findByText(
        "Le titre doit comporter entre 1 et 120 caractères.",
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByRole("textbox", { name: "Corps du texte" }),
    ).toHaveAccessibleDescription(
      expect.stringContaining("Le corps du texte est obligatoire."),
    );
  });

  it("pre-fills an existing text and saves the changes", async () => {
    let patched: Record<string, unknown> | null = null;
    server.use(
      organizerMe(),
      http.get(`${TEXTS_URL}/${TEXT_ID}`, () =>
        HttpResponse.json({
          id: TEXT_ID,
          title: "Que rien ne te trouble",
          source: "Thérèse d'Avila",
          body: "<p>Que rien ne te trouble.</p>",
          hints: null,
          attributionCount: 3,
        }),
      ),
      http.patch(`${TEXTS_URL}/${TEXT_ID}`, async ({ request }) => {
        patched = (await request.json()) as Record<string, unknown>;

        return HttpResponse.json({ id: TEXT_ID, ...patched });
      }),
    );

    renderApp(`/organisation/texts/${TEXT_ID}/edit`);

    expect(
      await screen.findByRole("heading", {
        level: 1,
        name: "Modifier le texte",
      }),
    ).toBeInTheDocument();
    await waitFor(() =>
      expect(screen.getByLabelText("Titre")).toHaveValue(
        "Que rien ne te trouble",
      ),
    );
    expect(
      screen.getByRole("textbox", { name: "Corps du texte" }),
    ).toHaveTextContent("Que rien ne te trouble.");
    // US-06.2: a correction applies wherever the text is attributed
    expect(
      screen.getByText(
        "Attribué à 3 créneaux : la correction s'y appliquera aussi.",
      ),
    ).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText("Source"), {
      target: { value: "Thérèse de Jésus (d'Avila)" },
    });
    fireEvent.click(
      screen.getByRole("button", { name: "Enregistrer les modifications" }),
    );

    expect(
      await screen.findByText("Modifications enregistrées."),
    ).toBeInTheDocument();
    expect(patched).toEqual({
      title: "Que rien ne te trouble",
      source: "Thérèse de Jésus (d'Avila)",
      body: "<p>Que rien ne te trouble.</p>",
      hints: "",
    });
  });

  it("links the library to the creation and edit forms", async () => {
    server.use(
      organizerMe(),
      http.get(TEXTS_URL, () =>
        HttpResponse.json({
          member: [
            {
              id: TEXT_ID,
              title: "Que rien ne te trouble",
              source: "Thérèse d'Avila",
              attributionCount: 0,
            },
          ],
          totalItems: 1,
        }),
      ),
    );

    renderApp("/organisation/texts");

    expect(
      await screen.findByRole("link", { name: "Que rien ne te trouble" }),
    ).toHaveAttribute("href", `/organisation/texts/${TEXT_ID}/edit`);
    expect(screen.getByRole("link", { name: "Nouveau texte" })).toHaveAttribute(
      "href",
      "/organisation/texts/new",
    );
  });
});

const existingText = () =>
  http.get(`${TEXTS_URL}/${TEXT_ID}`, () =>
    HttpResponse.json({
      id: TEXT_ID,
      title: "Que rien ne te trouble",
      source: "Thérèse d'Avila",
      body: "<p>Que rien ne te trouble.</p>",
      hints: null,
      attributionCount: 0,
    }),
  );

describe("Text deletion (F06-T14)", () => {
  it("deletes the text after confirmation and goes back to the library", async () => {
    let deleted = false;
    server.use(
      organizerMe(),
      existingText(),
      emptyList(),
      http.delete(`${TEXTS_URL}/${TEXT_ID}`, () => {
        deleted = true;

        return new HttpResponse(null, { status: 204 });
      }),
    );

    renderApp(`/organisation/texts/${TEXT_ID}/edit`);

    fireEvent.click(
      await screen.findByRole("button", { name: "Supprimer le texte" }),
    );

    const dialog = await screen.findByRole("alertdialog", {
      name: "Supprimer le texte",
    });
    expect(
      within(dialog).getByText(
        "Supprimer « Que rien ne te trouble » de la bibliothèque ?",
      ),
    ).toBeInTheDocument();
    fireEvent.click(within(dialog).getByRole("button", { name: "Supprimer" }));

    expect(
      await screen.findByRole("heading", { level: 1, name: "Textes" }),
    ).toBeInTheDocument();
    expect(deleted).toBe(true);
    expect(await screen.findByText("Texte supprimé.")).toBeInTheDocument();
  });

  it("explains a refusal when the text belongs to a scheduled edition (409)", async () => {
    server.use(
      organizerMe(),
      existingText(),
      http.delete(`${TEXTS_URL}/${TEXT_ID}`, () =>
        HttpResponse.json(
          {
            status: 409,
            title: "text.attributed-to-scheduled-edition",
            detail:
              "Ce texte est attribué à une édition programmée : il ne peut pas être supprimé.",
          },
          { status: 409 },
        ),
      ),
    );

    renderApp(`/organisation/texts/${TEXT_ID}/edit`);

    fireEvent.click(
      await screen.findByRole("button", { name: "Supprimer le texte" }),
    );
    fireEvent.click(
      within(
        await screen.findByRole("alertdialog", { name: "Supprimer le texte" }),
      ).getByRole("button", {
        name: "Supprimer",
      }),
    );

    expect(
      await screen.findByText(
        "Ce texte est attribué à une édition programmée : il ne peut pas être supprimé.",
      ),
    ).toBeInTheDocument();
    // The form stays: nothing was deleted
    expect(
      screen.getByRole("heading", { level: 1, name: "Modifier le texte" }),
    ).toBeInTheDocument();
  });

  it("offers no deletion on the creation form", async () => {
    server.use(organizerMe());

    renderApp("/organisation/texts/new");

    await screen.findByRole("button", { name: "Créer le texte" });
    expect(
      screen.queryByRole("button", { name: "Supprimer le texte" }),
    ).not.toBeInTheDocument();
  });
});
