import { expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { createOpenEdition } from "./helpers/edition";
import { extractLinkFromEmail, waitForEmail } from "./helpers/mailpit";

/**
 * The /landing journey: the event landing explains the 24-hour chain, an
 * e-mail earns a personal link, and clicking it signs the priant in — back
 * on /landing, on the slot grid, ready to pick slots.
 */
test.describe("Landing /landing", () => {
  test.beforeAll(async ({ browser, request }) => {
    // A hook ignores the timeout option: it is set from inside
    test.setTimeout(120_000);
    const page = await browser.newPage();
    await createOpenEdition(
      page,
      request,
      E2E_ORGANIZER_EMAIL,
      `24 heures de prière — landing E2E ${Date.now()}`,
    );
    await page.close();
  });

  test("explains the event, then e-mail → link → signed in on /landing with the slot grid", async ({
    page,
  }) => {
    test.setTimeout(90_000);
    const address = `landing-e2e-${Date.now()}@example.org`;

    // 1. The landing explains the event
    await page.goto("/landing");
    await expect(
      page.getByRole("heading", {
        level: 1,
        name: "Une chaîne de prière ininterrompue",
      }),
    ).toBeVisible();
    await expect(
      page.getByText(/samedi 28 novembre 2026 à 22h00/),
    ).toBeVisible();
    await expect(
      page.getByText(/dimanche 29 novembre 2026 à 22h00/),
    ).toBeVisible();
    await expect(
      page.getByRole("heading", { name: "Le principe" }),
    ).toBeVisible();
    await expect(
      page.getByRole("heading", { name: "Comment participer" }),
    ).toBeVisible();

    // 2. The open edition's slot grid is on the page
    const grid = page.getByRole("region", { name: "Grille des créneaux" });
    await expect(grid).toBeVisible();

    // 3. The e-mail form sends the personal link
    await page.getByLabel("Adresse e-mail").fill(address);
    await page.getByRole("button", { name: "Recevoir mon lien" }).click();
    await expect(
      page.getByText("Un lien de connexion vient de vous être envoyé"),
    ).toBeVisible();

    // 4. The link arrives by e-mail (sent by the messenger worker)
    const email = await waitForEmail(address, 30_000);
    if (!email) {
      throw new Error(`No e-mail received for ${address}`);
    }
    const link = extractLinkFromEmail(email);
    if (!link) {
      throw new Error("No link found in the e-mail");
    }
    expect(link).toContain("/auth/login?");

    // 5. Clicking the link signs in and lands back on /landing, on the grid
    await page.goto(link);
    await page.waitForURL("**/landing");
    await expect(
      page.getByRole("heading", {
        level: 1,
        name: "Une chaîne de prière ininterrompue",
      }),
    ).toBeVisible();
    await expect(grid).toBeVisible();
    // Signed in: the e-mail form is gone, « Mon espace » is offered
    await expect(page.getByLabel("Adresse e-mail")).toHaveCount(0);
    await expect(
      page.getByRole("link", { name: "Aller à mon espace" }),
    ).toBeVisible();
  });
});
