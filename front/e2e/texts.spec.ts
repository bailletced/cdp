import { expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { loginViaPersonalLink } from "./helpers/login";

/**
 * Text library (F06-T16): an organizer writes a formatted text (bold, a
 * psalm-like line break), finds the formatting intact after a reload,
 * corrects the source, then deletes the text. Each screen is audited with
 * axe-core.
 */
test.describe("Back-office /organisation/texts", () => {
  test("an organizer creates a formatted text, edits it and deletes it", async ({
    page,
  }) => {
    await loginViaPersonalLink(page, E2E_ORGANIZER_EMAIL);

    const title = `Que rien ne te trouble — E2E ${Date.now()}`;

    await page.goto("/organisation/texts");
    await expect(
      page.getByRole("heading", { level: 1, name: "Textes" }),
    ).toBeVisible();
    await expectNoA11yViolations(page);

    // Create the text: typed in the editor with the toolbar and Shift + Enter
    await page.getByRole("link", { name: "Nouveau texte" }).click();
    await page.waitForURL("**/organisation/texts/new");
    await page.getByLabel("Titre").fill(title);
    await page.getByLabel("Source").fill("Thérèse d’Avila");

    const body = page.getByRole("textbox", { name: "Corps du texte" });
    await body.click();
    await page.keyboard.type("Que rien ");
    await page.getByRole("button", { name: "Gras" }).click();
    await page.keyboard.type("ne te trouble");
    await page.getByRole("button", { name: "Gras" }).click();
    await page.keyboard.press("Shift+Enter");
    await page.keyboard.type("tout passe");

    // The preview shows the text as the priants will read it
    const preview = page.getByRole("region", { name: "Aperçu" });
    await expect(preview.locator("strong")).toHaveText("ne te trouble");
    await expectNoA11yViolations(page);

    await page.getByRole("button", { name: "Créer le texte" }).click();
    await page.waitForURL("**/organisation/texts");
    await expect(page.getByText("Texte créé.")).toBeVisible();

    // The formatting survives the round trip through the server
    await page.getByLabel("Rechercher").fill(title);
    await page.getByRole("link", { name: title }).click();
    await page.waitForURL("**/organisation/texts/*/edit");
    await page.reload();
    await expect(body.locator("strong")).toHaveText("ne te trouble");
    await expect(body.locator("br")).toHaveCount(1);
    await expect(body).toContainText("tout passe");

    // Correct the source
    await page.getByLabel("Source").fill("Thérèse d’Avila, Poésies");
    await page
      .getByRole("button", { name: "Enregistrer les modifications" })
      .click();
    await expect(page.getByText("Modifications enregistrées.")).toBeVisible();
    await page.reload();
    await expect(page.getByLabel("Source")).toHaveValue(
      "Thérèse d’Avila, Poésies",
    );

    // Delete it after confirmation
    await page.getByRole("button", { name: "Supprimer le texte" }).click();
    const dialog = page.getByRole("alertdialog", {
      name: "Supprimer le texte",
    });
    await expectNoA11yViolations(page);
    await dialog.getByRole("button", { name: "Supprimer" }).click();
    await page.waitForURL("**/organisation/texts");
    await expect(page.getByText("Texte supprimé.")).toBeVisible();
    await page.getByLabel("Rechercher").fill(title);
    await expect(
      page.getByText(`Aucun texte ne correspond à « ${title} ».`),
    ).toBeVisible();
  });
});
