import { expect, test } from "@playwright/test";
import { clearMailpit } from "./helpers/mailpit";

const SITE_NAME = "Communion de prière Notre-Dame du Mont-Carmel";

test.beforeEach(async () => {
  // Empty Mailpit before each test
  await clearMailpit();
});

test.describe("Home page", () => {
  test("answers with a 200 status", async ({ page }) => {
    const response = await page.goto("/");
    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle(SITE_NAME);
  });

  test("shows the public layout: brand, skip link, footer", async ({
    page,
  }) => {
    await page.goto("/");
    // Header: the community name from the single site config, linking home
    await expect(page.getByRole("banner")).toBeVisible();
    await expect(page.getByRole("link", { name: SITE_NAME })).toBeVisible();
    // Skip link (visually hidden until focused, but present for keyboards)
    const skipLink = page.getByRole("link", { name: "Aller au contenu" });
    await expect(skipLink).toBeAttached();
    await expect(skipLink).toHaveAttribute("href", "#main");
    // Footer
    await expect(
      page.getByRole("contentinfo").getByRole("link", {
        name: "Confidentialité",
      }),
    ).toBeVisible();
  });
});

test.describe("API", () => {
  test("answers on /auth", async ({ request }) => {
    // The API is reachable from the frontend: in development the Vite
    // proxy forwards /auth to the PHP server, keeping a single origin.
    const response = await request.get("/auth/status");
    expect(response.ok()).toBeTruthy();
  });
});
