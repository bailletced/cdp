import { type APIRequestContext, expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { createOpenEdition } from "./helpers/edition";
import { registerOnSlots } from "./helpers/inscription";
import { extractLinkFromEmail, waitForEmail } from "./helpers/mailpit";

interface ApiSlot {
  id: string;
  label: string;
}

/** The slots of the current public edition, in chronological order. */
async function fetchCurrentSlots(
  request: APIRequestContext,
): Promise<ApiSlot[]> {
  const edition = await (await request.get("/api/editions/current")).json();
  const response = await request.get(`/api/editions/${edition.id}/slots`);

  return ((await response.json()) as { slots: ApiSlot[] }).slots;
}

test.describe.configure({ mode: "serial" });

/**
 * Empty slots (F13-T08, §14.1, US-13.1 to US-13.3), from « Mon espace »:
 * a visitor registers on a slot, takes the suggested neighbour, opens their
 * personal link, draws another empty slot (« Un autre ») and takes it in
 * one click. The draw and the confirmation are audited with axe-core.
 */
test.describe("Créneaux vides (F13-T08)", () => {
  test.beforeAll(async ({ browser, request }) => {
    // A hook ignores the timeout option: it is set from inside
    test.setTimeout(120_000);
    const page = await browser.newPage();
    await createOpenEdition(
      page,
      request,
      E2E_ORGANIZER_EMAIL,
      `24 heures de prière — créneaux vides E2E ${Date.now()}`,
    );
    await page.close();
  });

  test("registers, takes the suggested neighbour, then draws from my-space", async ({
    browser,
    request,
  }) => {
    test.setTimeout(90_000);
    const slot = (await fetchCurrentSlots(request))[3];
    const address = `vides-e2e-${Date.now()}@example.org`;

    const context = await browser.newContext({ timezoneId: "Europe/Paris" });
    const page = await context.newPage();

    // 1. Direct inscription (the URL the my-space picker builds)
    await registerOnSlots(page, [slot.id], "Famille Martin E2E", address);
    await expectNoA11yViolations(page);

    // 2. The neighbour suggestion, taken in one click
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

    // 3. The personal link opens « Mon espace »
    const email = await waitForEmail(address, 30_000);
    const link = email ? extractLinkFromEmail(email) : null;
    expect(
      link,
      "the confirmation e-mail carries the personal link",
    ).not.toBeNull();
    await page.goto(link ?? "");
    await page.waitForURL("**/my-space");

    // 4. « Prendre un créneau vide »: a slot at random, « Un autre », then
    // « Choisir » — the one-click inscription of the already-consented priant
    await page.getByRole("button", { name: "Prendre un créneau vide" }).click();
    const dialog = page.getByRole("dialog", {
      name: "Un créneau vide pour vous",
    });
    const schedule = dialog.locator("p.font-display");
    await expect(schedule).toHaveText(/\d{1,2}h\d{2}–\d{1,2}h\d{2}/);
    const first = await schedule.textContent();
    await dialog.getByRole("button", { name: "Un autre" }).click();
    await expect(schedule).not.toHaveText(first ?? "");
    await expectNoA11yViolations(page);
    await dialog.getByRole("link", { name: "Choisir" }).click();
    await page.waitForURL(/\/inscription\?creneaux=/);
    await page.getByRole("button", { name: "S'inscrire" }).click();
    await page.waitForURL(/\/inscription\/confirmation/);
    await expect(
      page.getByRole("region", { name: "Vos créneaux" }).getByRole("listitem"),
    ).toHaveCount(3);

    await context.close();
  });
});
