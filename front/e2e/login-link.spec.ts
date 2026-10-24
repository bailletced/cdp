import { expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { extractLinkFromEmail, waitForEmail } from "./helpers/mailpit";

/**
 * End-to-end skeleton of the personal link journey (F02-T20):
 * request a link -> email (Mailpit) -> /login -> /my-space -> reload -> logout.
 * The organizer account is created by the global setup (app:organizer:create).
 */
test.describe("Personal link journey", () => {
  test("request a link, log in, stay logged in on reload, log out", async ({
    page,
  }) => {
    // 1. Request the personal link
    await page.goto("/receive-my-link");
    await page.getByLabel("Adresse e-mail").fill(E2E_ORGANIZER_EMAIL);
    await page.getByRole("button", { name: "Recevoir mon lien" }).click();
    await expect(
      page.getByText("Si un compte correspond à cette adresse"),
    ).toBeVisible();

    // 2. The link arrives by email (sent by the messenger worker)
    const email = await waitForEmail(E2E_ORGANIZER_EMAIL, 30000);
    if (!email) {
      throw new Error(`No email received for ${E2E_ORGANIZER_EMAIL}`);
    }
    const link = extractLinkFromEmail(email);
    if (!link) {
      throw new Error("No link found in the email");
    }
    expect(link).toContain("/auth/login?");

    // 3. Opening the link lands on the SPA /login page, which POSTs automatically
    await page.goto(link);
    await page.waitForURL("**/my-space");
    await expect(
      page.getByRole("heading", { name: "Mon espace" }),
    ).toBeVisible();
    await expect(page.getByText(E2E_ORGANIZER_EMAIL)).toBeVisible();

    // 4. The session survives a reload
    await page.reload();
    await expect(
      page.getByRole("heading", { name: "Mon espace" }),
    ).toBeVisible();

    // 5. Logout returns to the landing page and its email form
    await page.getByRole("button", { name: "Me déconnecter" }).click();
    await page.waitForURL((url) => url.pathname === "/");
    await expect(page.getByLabel("Adresse e-mail")).toBeVisible();
  });
});
