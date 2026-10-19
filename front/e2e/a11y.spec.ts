import { expect, test } from "@playwright/test";
import { expectNoA11yViolations } from "./helpers/a11y";

test.describe("accessibility audit (F01-T12, US-01.4)", () => {
  test("the root page (404 inside the layout) has no axe violation", async ({
    page,
  }) => {
    await page.goto("/");
    await expectNoA11yViolations(page);
  });

  test("/confidentialite has no axe violation", async ({ page }) => {
    await page.goto("/confidentialite");
    await expectNoA11yViolations(page);
  });

  test("the 404 page has no axe violation", async ({ page }) => {
    await page.goto("/chemin-inconnu");
    await expectNoA11yViolations(page);
  });

  test("a voluntary violation fails the audit (negative test)", async ({
    page,
  }) => {
    await page.goto("/confidentialite");

    // An image without alternative text: a WCAG 2.1 level A violation
    await page.evaluate(() => {
      const img = document.createElement("img");
      img.src =
        "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10'%3E%3C/svg%3E";
      document.body.appendChild(img);
    });

    await expect(expectNoA11yViolations(page)).rejects.toThrow(/image-alt/);
  });
});
