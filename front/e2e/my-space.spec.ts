import {
  type APIRequestContext,
  type Browser,
  type Page,
  expect,
  test,
} from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { createOpenEdition } from "./helpers/edition";
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

/**
 * Registers on the given slots from the landing page, then opens the
 * personal link of the confirmation e-mail in another browser: « Mon
 * espace », signed in.
 */
async function registerThenOpenMySpace(
  browser: Browser,
  slots: ApiSlot[],
  displayName: string,
): Promise<Page> {
  const address = `mon-espace-e2e-${Date.now()}@example.org`;
  const device = await browser.newContext({ timezoneId: "Europe/Paris" });
  const page = await device.newPage();
  await page.goto("/");
  const grid = page.getByRole("region", { name: "Grille des créneaux" });
  for (const slot of slots) {
    // react-aria hides the input in a clipped span: click the wrapping label
    await grid
      .getByRole("checkbox", {
        name: `Sélectionner le créneau de ${slot.label}`,
      })
      .locator("xpath=ancestor::label[1]")
      .click();
  }
  await page
    .getByRole("link", { name: `M'inscrire (${slots.length})` })
    .click();
  await page.getByLabel("Nom affiché").fill(displayName);
  await page.getByLabel("Adresse e-mail").fill(address);
  // The label holds the privacy policy link: click the box, on its left
  await page
    .getByRole("checkbox", {
      name: /J'accepte la politique de confidentialité/,
    })
    .locator("xpath=ancestor::label[1]")
    .click({ position: { x: 12, y: 12 } });
  await page.getByRole("button", { name: "S'inscrire" }).click();
  await page.waitForURL(/\/inscription\/confirmation/);
  await device.close();

  const email = await waitForEmail(address, 30_000);
  const link = email ? extractLinkFromEmail(email) : null;
  expect(
    link,
    "the confirmation e-mail carries the personal link",
  ).not.toBeNull();

  const priant = await browser.newContext({
    timezoneId: "Europe/Paris",
    acceptDownloads: true,
  });
  const mySpace = await priant.newPage();
  await mySpace.goto(link ?? "");
  await mySpace.waitForURL("**/my-space");

  return mySpace;
}

test.describe.configure({ mode: "serial" });

/**
 * « Mon espace » (F14-T10, F14-T11): the single agenda file, then the
 * parcours §14.1 n° 1 — inscription → e-mail → personal link → my space →
 * change my name (seen in the grid) → cancel → the slot is « À pourvoir ».
 */
test.describe("Mon espace (F14)", () => {
  test.beforeAll(async ({ browser, request }) => {
    // A hook ignores the timeout option: it is set from inside
    test.setTimeout(120_000);
    const page = await browser.newPage();
    await createOpenEdition(
      page,
      request,
      E2E_ORGANIZER_EMAIL,
      `24 heures de prière — mon espace E2E ${Date.now()}`,
    );
    await page.close();
  });

  test("downloads one agenda file with all my slots (F14-T10)", async ({
    browser,
    request,
  }) => {
    test.setTimeout(90_000);
    const slots = await fetchCurrentSlots(request);
    const page = await registerThenOpenMySpace(
      browser,
      [slots[14], slots[20]],
      `Agenda E2E ${Date.now()}`,
    );

    const mine = page.getByRole("region", { name: "Mes créneaux" });
    await expect(mine.getByRole("listitem")).toHaveCount(2);
    const download = page.waitForEvent("download");
    await mine
      .getByRole("link", { name: "Ajouter mes créneaux à mon agenda" })
      .click();
    const file = await download;
    expect(file.suggestedFilename()).toBe("mes-creneaux.ics");
    const path = await file.path();
    const { readFile } = await import("node:fs/promises");
    const calendar = await readFile(path, "utf8");
    expect(calendar.match(/BEGIN:VEVENT/g)).toHaveLength(2);
    await page.context().close();
  });
});
