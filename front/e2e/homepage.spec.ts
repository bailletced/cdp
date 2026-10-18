import { expect, test } from "@playwright/test";
import { clearMailpit } from "./helpers/mailpit";

test.beforeEach(async () => {
  // Empty Mailpit before each test
  await clearMailpit();
});

test.describe("Home page", () => {
  test("answers with a 200 status", async ({ page }) => {
    const response = await page.goto("/");
    expect(response?.status()).toBe(200);
    await expect(page).toHaveTitle(/CDP|Chaîne de prière/);
  });

  test("shows the application title", async ({ page }) => {
    await page.goto("/");
    await expect(
      page.getByRole("heading", { name: /CDP|Chaîne de prière/ }),
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
