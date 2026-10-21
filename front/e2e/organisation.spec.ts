import { expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { loginViaPersonalLink } from "./helpers/login";

/**
 * Back-office section (F04-T06): the organizer reaches /organisation, adds
 * then removes an organizer, and a removed organizer (now a priant) is
 * redirected away from /organisation. Every page of the journey is audited
 * with axe-core.
 */
test.describe("Back-office /organisation", () => {
  test("an organizer reaches /organisation with the back-office navigation", async ({
    page,
  }) => {
    await loginViaPersonalLink(page, E2E_ORGANIZER_EMAIL);

    await page.goto("/organisation");

    await expect(
      page.getByRole("heading", { name: "Organisateurs" }),
    ).toBeVisible();
    await expect(
      page.getByRole("navigation", { name: "Back-office" }),
    ).toBeVisible();
    // The organizer sees himself in the list
    await expect(
      page.getByText(E2E_ORGANIZER_EMAIL, { exact: true }),
    ).toBeVisible();
    await expectNoA11yViolations(page);
  });

  test("an organizer adds then removes an organizer", async ({ page }) => {
    await loginViaPersonalLink(page, E2E_ORGANIZER_EMAIL);
    await page.goto("/organisation");

    const addedEmail = `e2e-ajout-${Date.now()}@bailletced.org`;

    // Add: the new organizer appears in the list
    await page.getByLabel("Adresse e-mail").fill(addedEmail);
    await page.getByRole("button", { name: "Ajouter" }).click();
    await expect(page.getByText(addedEmail, { exact: true })).toBeVisible();

    // Remove: the confirmation dialog asks before deleting
    await page.getByRole("button", { name: `Retirer ${addedEmail}` }).click();
    await page
      .getByRole("alertdialog")
      .getByRole("button", { name: "Retirer" })
      .click();
    await expect(page.getByText(addedEmail, { exact: true })).not.toBeVisible();
    await expectNoA11yViolations(page);
  });

  test("a removed organizer (now a priant) is redirected away from /organisation", async ({
    page,
  }) => {
    const priantEmail = `e2e-priant-${Date.now()}@bailletced.org`;

    // Create the future priant: add an organizer, then remove it — the
    // account stays, without the organizer role.
    await loginViaPersonalLink(page, E2E_ORGANIZER_EMAIL);
    await page.goto("/organisation");
    await page.getByLabel("Adresse e-mail").fill(priantEmail);
    await page.getByRole("button", { name: "Ajouter" }).click();
    await expect(page.getByText(priantEmail, { exact: true })).toBeVisible();
    await page.getByRole("button", { name: `Retirer ${priantEmail}` }).click();
    await page
      .getByRole("alertdialog")
      .getByRole("button", { name: "Retirer" })
      .click();
    await expect(
      page.getByText(priantEmail, { exact: true }),
    ).not.toBeVisible();

    // Log out, then log in with the removed account: it is a priant now
    await page.goto("/my-space");
    await page.getByRole("button", { name: "Me déconnecter" }).click();
    await page.waitForURL("**/receive-my-link");

    await loginViaPersonalLink(page, priantEmail);

    // The guard redirects a priant to /my-space
    await page.goto("/organisation");
    await page.waitForURL("**/my-space");
    await expect(
      page.getByRole("heading", { name: "Mon espace" }),
    ).toBeVisible();
    await expect(
      page.getByRole("heading", { name: "Organisateurs" }),
    ).not.toBeVisible();
    await expectNoA11yViolations(page);
  });
});
