import {
  type APIRequestContext,
  type Locator,
  type Page,
  expect,
  test,
} from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { createOpenEdition } from "./helpers/edition";

interface ApiSlot {
  id: string;
  rank: number;
  label: string;
}

/** The slots of the current public edition, in chronological (rank) order. */
async function fetchCurrentSlots(
  request: APIRequestContext,
): Promise<ApiSlot[]> {
  const edition = await (await request.get("/api/editions/current")).json();
  const response = await request.get(`/api/editions/${edition.id}/slots`);

  return ((await response.json()) as { slots: ApiSlot[] }).slots;
}

function slotCard(grid: Locator, slot: ApiSlot): Locator {
  return grid.getByRole("article").filter({ hasText: slot.label });
}

async function tick(grid: Locator, slot: ApiSlot) {
  const checkbox = grid.getByRole("checkbox", {
    name: `Sélectionner le créneau de ${slot.label}`,
  });
  // react-aria hides the input in a clipped span: click the wrapping label
  await checkbox.locator("xpath=ancestor::label[1]").click();
  await expect(checkbox).toBeChecked();
}

async function fillAndSend(page: Page, displayName: string, email: string) {
  await page.getByLabel("Nom affiché").fill(displayName);
  await page.getByLabel("Adresse e-mail").fill(email);
  const consent = page.getByRole("checkbox", {
    name: /J'accepte la politique de confidentialité/,
  });
  // The label holds the privacy policy link: click the box, on its left
  await consent
    .locator("xpath=ancestor::label[1]")
    .click({ position: { x: 12, y: 12 } });
  await expect(consent).toBeChecked();
  await page.getByRole("button", { name: "S'inscrire" }).click();
}

test.describe.configure({ mode: "serial" });

/**
 * Inscription (F09-T21): a visitor picks two slots, fills the form and gets
 * the confirmation; back on the grid both slots are « Occupé » with their
 * priant counted. Registering again on one of them is refused with the
 * RG-IN-3 message (409). Each screen is audited with axe-core.
 */
test.describe("Inscription (F09-T21)", () => {
  test.beforeAll(
    async ({ browser, request }) => {
      const page = await browser.newPage();
      await createOpenEdition(
        page,
        request,
        E2E_ORGANIZER_EMAIL,
        `24 heures de prière — inscription E2E ${Date.now()}`,
      );
      await page.close();
    },
    { timeout: 120_000 },
  );

  test("registers on two slots, then is refused a second time on one of them", async ({
    browser,
    request,
  }) => {
    test.setTimeout(60_000);
    const slots = await fetchCurrentSlots(request);
    const [first, second] = [slots[4], slots[5]];
    const email = `inscription-e2e-${Date.now()}@example.org`;

    const context = await browser.newContext({ timezoneId: "Europe/Paris" });
    const page = await context.newPage();

    // 1. Pick two empty slots on the public grid
    await page.goto("/");
    const grid = page.getByRole("region", { name: "Grille des créneaux" });
    await expect(slotCard(grid, first)).toContainText("À pourvoir");
    await tick(grid, first);
    await tick(grid, second);
    await page.getByRole("link", { name: "M'inscrire (2)" }).click();
    await page.waitForURL(/\/inscription\?creneaux=/);

    // 2. The form, then the confirmation with both slots
    await expect(
      page.getByRole("region", { name: "Créneaux sélectionnés" }),
    ).toContainText(first.label);
    await expectNoA11yViolations(page);
    await fillAndSend(page, "Famille E2E", email);
    await page.waitForURL(/\/inscription\/confirmation/);
    await expect(
      page.getByRole("heading", { level: 1, name: "Inscription confirmée" }),
    ).toBeVisible();
    const mine = page.getByRole("region", { name: "Vos créneaux" });
    await expect(mine).toContainText(first.label);
    await expect(mine).toContainText(second.label);
    await expectNoA11yViolations(page);

    // 3. Back on the grid: both slots are taken, their priant counted
    await page.getByRole("link", { name: "Retour à l'accueil" }).click();
    for (const slot of [first, second]) {
      await expect(slotCard(grid, slot)).toContainText("Occupé");
      await expect(slotCard(grid, slot)).toContainText("1 priant");
    }

    // 4. Registering again on the same slot is refused (RG-IN-3, 409)
    await tick(grid, first);
    await page.getByRole("link", { name: "M'inscrire (1)" }).click();
    await page.waitForURL(/\/inscription\?creneaux=/);
    await fillAndSend(page, "Famille E2E", email);
    await expect(
      page.getByText("Vous êtes déjà inscrit sur ce créneau."),
    ).toBeVisible();
    await expect(page).toHaveURL(/\/inscription\?creneaux=/);
    await expectNoA11yViolations(page);

    await context.close();
  });
});
