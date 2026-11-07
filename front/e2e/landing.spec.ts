import { expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { createOpenEdition } from "./helpers/edition";
import { extractLinkFromEmail, waitForEmail } from "./helpers/mailpit";

/**
 * The /landing journey: the event landing welcomes people who know nothing,
 * takes their e-mail, and the welcome e-mail's personal link signs them in
 * on /my-space, where the slot picker lives.
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

  test("welcomes, takes the e-mail, then the link signs in on /my-space", async ({
    page,
  }) => {
    test.setTimeout(90_000);
    const address = `landing-e2e-${Date.now()}@example.org`;

    // 1. The landing presents the event (nothing about slots, no links to
    //    request one: the form is the page)
    await page.goto("/landing");
    await expect(
      page.getByRole("heading", {
        level: 1,
        name: "Une chaîne de prière de 24 heures",
      }),
    ).toBeVisible();
    await expect(page.getByText(/samedi 28 novembre à 22h/)).toBeVisible();
    // The page carries its own light treatment (warm ivory), unlike the dark
    // vigil theme of the rest of the app
    expect(
      await page.evaluate(
        () => getComputedStyle(document.body).backgroundColor,
      ),
    ).toBe("rgb(250, 247, 240)");
    // Christ the King, an existing painting by a named painter, heads the hero
    const image = page.getByRole("img", {
      name: "Le Christ roi et la Vierge reine, couronnés, par Hans von Kulmbach",
    });
    await expect(image).toBeVisible();
    expect(await image.getAttribute("src")).toBe("/img/christ-roi.jpg");
    // The painter is named on the page
    await expect(page.getByText(/Hans von Kulmbach/)).toBeVisible();
    // The e-mail may be misfiled: said before and after sending
    await expect(page.getByText(/indésirables/).first()).toBeVisible();
    // The focused (empty) e-mail field stays light: the field states are
    // re-derived from the page theme, not the dark one
    await page.locator("#inscription input[name=email]").click();
    await page.waitForFunction(() => {
      const input = document.querySelector("#inscription input[name=email]");
      return getComputedStyle(input).backgroundColor === "rgb(255, 255, 255)";
    });
    await expect(
      page.getByRole("link", { name: "Recevoir mon lien" }),
    ).toHaveCount(0);
    await expect(
      page.getByRole("region", { name: "Grille des créneaux" }),
    ).toHaveCount(0);

    // 2. The hero form sends the welcome e-mail with the personal link
    await page.locator("#inscription input[name=email]").fill(address);
    await page.getByRole("button", { name: "Je participe" }).click();
    await expect(
      page.getByText(/Un lien de connexion vient de vous être envoyé/),
    ).toBeVisible();

    // 3. The link arrives by e-mail (sent by the messenger worker)
    const email = await waitForEmail(address, 30_000);
    if (!email) {
      throw new Error(`No e-mail received for ${address}`);
    }
    const link = extractLinkFromEmail(email);
    if (!link) {
      throw new Error("No link found in the e-mail");
    }
    expect(link).toContain("/auth/login?");

    // 4. Clicking the link signs in on /my-space, where slots are picked
    await page.goto(link);
    await page.waitForURL("**/my-space");
    await expect(
      page.getByRole("heading", { name: "Mon espace" }),
    ).toBeVisible();
    // The slot picker section is right there on /my-space
    await expect(
      page.getByRole("heading", { level: 2, name: "Ajouter des créneaux" }),
    ).toBeVisible();
  });

  test("the root sends the visitor to /landing, then the member to /my-space", async ({
    page,
  }) => {
    test.setTimeout(90_000);

    // A visitor with no session on / is oriented towards the landing
    await page.goto("/");
    await page.waitForURL("**/landing");

    // The hero form registers a new priant and sends the personal link
    const address = `sorter-e2e-${Date.now()}@example.org`;
    await page.locator("#inscription input[name=email]").fill(address);
    await page.getByRole("button", { name: "Je participe" }).click();
    const email = await waitForEmail(address, 30_000);
    if (!email) {
      throw new Error(`No welcome e-mail received for ${address}`);
    }
    const link = extractLinkFromEmail(email);
    if (!link) {
      throw new Error("No link found in the welcome e-mail");
    }

    // Signed in through the link, / leads to the member's space instead
    await page.goto(link);
    await page.waitForURL("**/my-space");
    await page.goto("/");
    await page.waitForURL("**/my-space");
    await expect(
      page.getByRole("heading", { name: "Mon espace" }),
    ).toBeVisible();
  });
});
