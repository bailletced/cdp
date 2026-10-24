import { type APIRequestContext, expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { createOpenEdition } from "./helpers/edition";

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
 * Device pass (F10-T11, US-10.1, US-10.2): once registered, the device sees
 * the names of the priants and « Vous » on its slot, without opening any
 * email; a second browser, with no pass, sees the count but never a name.
 */
test.describe("Pass appareil (F10-T11)", () => {
  test.beforeAll(async ({ browser, request }) => {
    // A hook ignores the timeout option: it is set from inside
    test.setTimeout(120_000);
    const page = await browser.newPage();
    await createOpenEdition(
      page,
      request,
      E2E_ORGANIZER_EMAIL,
      `24 heures de prière — pass appareil E2E ${Date.now()}`,
    );
    await page.close();
  });

  test("the device that registered sees the names, another browser does not", async ({
    browser,
    request,
  }) => {
    test.setTimeout(60_000);
    const slot = (await fetchCurrentSlots(request))[7];
    const displayName = `Priant E2E ${Date.now()}`;

    // 1. Register from Marie's phone
    const phone = await browser.newContext({ timezoneId: "Europe/Paris" });
    const page = await phone.newPage();
    await page.goto("/");
    const grid = page.getByRole("region", { name: "Grille des créneaux" });
    const checkbox = grid.getByRole("checkbox", {
      name: `Sélectionner le créneau de ${slot.label}`,
    });
    // react-aria hides the input in a clipped span: click the wrapping label
    await checkbox.locator("xpath=ancestor::label[1]").click();
    await page.getByRole("link", { name: "M'inscrire (1)" }).click();
    await page.getByLabel("Nom affiché").fill(displayName);
    await page
      .getByLabel("Adresse e-mail")
      .fill(`pass-e2e-${Date.now()}@example.org`);
    const consent = page.getByRole("checkbox", {
      name: /J'accepte la politique de confidentialité/,
    });
    // The label holds the privacy policy link: click the box, on its left
    await consent
      .locator("xpath=ancestor::label[1]")
      .click({ position: { x: 12, y: 12 } });
    await page.getByRole("button", { name: "S'inscrire" }).click();
    await page.waitForURL(/\/inscription\/confirmation/);
    expect(
      (await phone.cookies()).some((cookie) => cookie.name === "cdp_pass"),
    ).toBe(true);

    // 2. Back on the grid, on this device: the name, and « Vous »
    await page.getByRole("link", { name: "Retour à l'accueil" }).click();
    const card = grid.getByRole("article").filter({ hasText: slot.label });
    await expect(
      card.getByRole("list", { name: "Priants du créneau" }),
    ).toContainText(displayName);
    await expect(card.getByText("Vous", { exact: true })).toBeVisible();
    await expectNoA11yViolations(page);
    await phone.close();

    // 3. Another browser, no pass: the count, never a name (RG-PL-4)
    const other = await browser.newContext({ timezoneId: "Europe/Paris" });
    const visitor = await other.newPage();
    await visitor.goto("/");
    const otherCard = visitor
      .getByRole("region", { name: "Grille des créneaux" })
      .getByRole("article")
      .filter({ hasText: slot.label });
    await expect(otherCard).toContainText("1 priant");
    await expect(otherCard).not.toContainText(displayName);
    await expect(
      otherCard.getByRole("list", { name: "Priants du créneau" }),
    ).toHaveCount(0);
    await other.close();
  });
});
