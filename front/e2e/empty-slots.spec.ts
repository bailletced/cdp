import { expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { createOpenEdition } from "./helpers/edition";

test.describe.configure({ mode: "serial" });

/**
 * Empty slots (F13-T08, §14.1, US-13.1 to US-13.3): « Prendre un créneau
 * vide » → inscription → the suggestion is taken too → the banner counts 2
 * empty slots less. The banner, the draw and the confirmation are audited
 * with axe-core.
 */
test.describe("Créneaux vides (F13-T08)", () => {
  test.beforeAll(
    async ({ browser, request }) => {
      const page = await browser.newPage();
      await createOpenEdition(
        page,
        request,
        E2E_ORGANIZER_EMAIL,
        `24 heures de prière — créneaux vides E2E ${Date.now()}`,
      );
      await page.close();
    },
    { timeout: 120_000 },
  );

  test("take an empty slot, then the suggested one: two empty slots less", async ({
    browser,
  }) => {
    test.setTimeout(60_000);
    const context = await browser.newContext({ timezoneId: "Europe/Paris" });
    const page = await context.newPage();

    // 1. The banner: a fresh edition, every slot is empty
    await page.goto("/");
    const banner = page.getByRole("region", { name: "Créneaux à pourvoir" });
    await expect(banner.getByRole("status")).toHaveText(
      "Il reste 48 créneaux vides sur 48",
    );
    await expectNoA11yViolations(page);

    // 2. « Prendre un créneau vide »: a slot at random, « Un autre », taken
    await banner
      .getByRole("button", { name: "Prendre un créneau vide" })
      .click();
    const dialog = page.getByRole("dialog", {
      name: "Un créneau vide pour vous",
    });
    const schedule = dialog.locator("p.font-display");
    await expect(schedule).toHaveText(/\d{1,2}h\d{2}–\d{1,2}h\d{2}/);
    const first = await schedule.textContent();
    await dialog.getByRole("button", { name: "Un autre" }).click();
    await expect(schedule).not.toHaveText(first ?? "");
    await expectNoA11yViolations(page);
    await dialog.getByRole("link", { name: "Je le prends" }).click();
    await page.waitForURL(/\/inscription\?creneaux=/);

    // 3. The inscription
    await page
      .getByLabel("Nom affiché")
      .fill(`Famille Martin E2E ${Date.now()}`);
    await page
      .getByLabel("Adresse e-mail")
      .fill(`vides-e2e-${Date.now()}@example.org`);
    await page
      .getByRole("checkbox", {
        name: /J'accepte la politique de confidentialité/,
      })
      .locator("xpath=ancestor::label[1]")
      .click({ position: { x: 12, y: 12 } });
    await page.getByRole("button", { name: "S'inscrire" }).click();
    await page.waitForURL(/\/inscription\/confirmation/);

    // 4. The suggestion, taken in one click
    const suggestion = page.getByRole("region", {
      name: "Un créneau voisin est encore vide",
    });
    await expect(suggestion).toBeVisible();
    await expectNoA11yViolations(page);
    await suggestion.getByRole("button", { name: "Le prendre aussi" }).click();
    await expect(page.getByText(/Vous êtes aussi inscrit sur/)).toBeVisible();
    await expect(
      page.getByRole("region", { name: "Vos créneaux" }).getByRole("listitem"),
    ).toHaveCount(2);

    // 5. Back home: two empty slots less
    await page.getByRole("link", { name: "Retour à l'accueil" }).click();
    await expect(banner.getByRole("status")).toHaveText(
      "Il reste 46 créneaux vides sur 48",
    );
    await context.close();
  });
});
