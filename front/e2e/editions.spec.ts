import { expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { loginViaPersonalLink } from "./helpers/login";

/**
 * Editions back-office (F05-T22): an organizer creates an edition, schedules
 * it from its detail page, and finds it « Programmée » in the editions list.
 * Every page of the journey is audited with axe-core.
 */
test.describe("Back-office /organisation/editions", () => {
  test("an organizer creates an edition, schedules it and finds it « Programmée » in the list", async ({
    page,
  }) => {
    await loginViaPersonalLink(page, E2E_ORGANIZER_EMAIL);

    const title = `24 heures de prière — E2E ${Date.now()}`;

    // Create the edition from the editions list
    await page.goto("/organisation/editions");
    await page.getByRole("link", { name: "Nouvelle édition" }).click();
    await page.waitForURL("**/organisation/editions/new");

    await page.getByLabel("Titre").fill(title);
    await page
      .getByLabel("Présentation")
      .fill("Édition créée de bout en bout.");
    // The dates are entered as a Paris wall clock
    await page.getByLabel("Début (heure de Paris) — date").fill("2026-12-05");
    await page.getByLabel("Début (heure de Paris) — heure").fill("20:00");
    await page
      .getByLabel("Ouverture des inscriptions (heure de Paris) — date")
      .fill("2026-11-28");
    await page
      .getByLabel("Ouverture des inscriptions (heure de Paris) — heure")
      .fill("20:00");
    await page.getByRole("button", { name: "Créer l'édition" }).click();
    await expect(page.getByText("Édition créée.")).toBeVisible();
    await expectNoA11yViolations(page);

    // The new edition is a draft in the list
    await page
      .getByRole("link", { name: "Retour à la liste des éditions" })
      .click();
    await page.waitForURL("**/organisation/editions");
    const row = page.getByRole("listitem").filter({ hasText: title });
    await expect(row).toBeVisible();
    await expect(row.getByText("Brouillon", { exact: true })).toBeVisible();

    // Schedule it from the detail page (US-05.4)
    await row.getByRole("link", { name: title }).click();
    await expect(page.getByRole("heading", { name: title })).toBeVisible();
    await page.getByRole("button", { name: "Programmer" }).click();
    // The 48 slots have no text yet: RG-ED-4 asks for a confirmation
    await page
      .getByRole("alertdialog", { name: "Programmer l’édition" })
      .getByRole("button", { name: "Programmer quand même" })
      .click();
    await expect(page.getByText("Programmée", { exact: true })).toBeVisible();
    await expectNoA11yViolations(page);

    // The list now shows the edition as « Programmée »
    await page.goto("/organisation/editions");
    await expect(row.getByText("Programmée", { exact: true })).toBeVisible();
    await expectNoA11yViolations(page);
  });
});
