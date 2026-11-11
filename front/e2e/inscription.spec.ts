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

async function fillAndSend(page: Page, displayName: string, email: string) {
  await page.getByLabel("Nom affiché").fill(displayName);
  await page.getByLabel("Adresse e-mail").fill(email);
  // No consent checkbox anymore: sending the registration is accepting
  await page.getByRole("button", { name: "S'inscrire" }).click();
}

/** One line of the my-space picker, by slot label. */
function pickerRow(page: Page, slot: ApiSlot): Locator {
  const list = page
    .getByRole("dialog", { name: "Choisir un créneau" })
    .getByRole("list", { name: "Créneaux à venir" });

  return list.getByRole("listitem").filter({ hasText: slot.label });
}

test.describe.configure({ mode: "serial" });

/**
 * Inscription (F09-T21): a visitor registers on two slots (the URL that
 * the my-space picker builds), fills the form and gets the confirmation.
 * Back in « Mon espace », the picker counts their priants and marks their
 * rows « Vous y êtes ». Registering again on one of them is refused with
 * the RG-IN-3 message (409). Each screen is audited with axe-core.
 */
test.describe("Inscription (F09-T21)", () => {
  test.beforeAll(async ({ browser, request }) => {
    // A hook ignores the timeout option: it is set from inside
    test.setTimeout(120_000);
    const page = await browser.newPage();
    await createOpenEdition(
      page,
      request,
      E2E_ORGANIZER_EMAIL,
      `24 heures de prière — inscription E2E ${Date.now()}`,
    );
    await page.close();
  });

  test("registers on two slots, then cannot take one of them twice", async ({
    browser,
    request,
  }) => {
    test.setTimeout(60_000);
    const slots = await fetchCurrentSlots(request);
    const [first, second] = [slots[4], slots[5]];
    const email = `inscription-e2e-${Date.now()}@example.org`;

    const context = await browser.newContext({ timezoneId: "Europe/Paris" });
    const page = await context.newPage();

    // 1. Both slots straight on /inscription — the URL the my-space picker
    // builds for its signed-in users
    await page.goto(`/inscription?creneaux=${first.id},${second.id}`);

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

    // 3. « Mon espace »: the picker counts both slots' priants (RG-PL-1)
    // and marks them « Vous y êtes » — neither offers « Choisir » (RG-IN-3)
    await page.getByRole("link", { name: "Retour à mon espace" }).click();
    await page.waitForURL("**/my-space");
    await page.getByRole("button", { name: "Choisir un créneau" }).click();
    for (const slot of [first, second]) {
      const row = pickerRow(page, slot);
      await expect(row).toContainText("1 priant");
      await expect(row).toContainText("Vous y êtes");
      await expect(row.getByRole("link", { name: "Choisir" })).toHaveCount(0);
    }

    // 4. Another device, same e-mail: the API refuses it (RG-IN-3, 409)
    const other = await browser.newContext({ timezoneId: "Europe/Paris" });
    const otherPage = await other.newPage();
    await otherPage.goto(`/inscription?creneaux=${first.id}`);
    await fillAndSend(otherPage, "Famille E2E", email);
    await expect(
      otherPage.getByText("Vous êtes déjà inscrit sur ce créneau."),
    ).toBeVisible();
    await expect(otherPage).toHaveURL(/\/inscription\?creneaux=/);
    await expectNoA11yViolations(otherPage);
    await other.close();

    await context.close();
  });
});
