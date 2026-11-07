import {
  type APIRequestContext,
  type Browser,
  type Page,
  expect,
  test,
} from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
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
  await page.goto("/planning");
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
 * change my name → cancel → then the empty-slot draw to add one again.
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
      `24 heures de prière — espace personnel E2E ${Date.now()}`,
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

  test("chooses a specific slot from the picker, with the members count (F14-T12)", async ({
    browser,
    request,
  }) => {
    test.setTimeout(90_000);
    const slots = await fetchCurrentSlots(request);
    const wanted = slots[5];
    const stamp = Date.now();
    const page = await registerThenOpenMySpace(
      browser,
      [slots[30]],
      `Famille Bertrand ${stamp}`,
    );

    // « Choisir un créneau » opens the compact picker: one line per slot to
    // come, its schedule and its priants count, one « Je le prends »
    await page.getByRole("button", { name: "Choisir un créneau" }).click();
    const dialog = page.getByRole("dialog", { name: "Choisir un créneau" });
    const list = dialog.getByRole("list", { name: "Créneaux à venir" });
    const wantedRow = list
      .getByRole("listitem")
      .filter({ hasText: wanted.label });
    await expect(wantedRow).toBeVisible();
    await expect(wantedRow).toContainText(/\d priant/);
    // the whole grid never mounts: no 48 cards, no empty-slot banner
    await expect(
      page.getByRole("region", { name: "Grille des créneaux" }),
    ).toHaveCount(0);

    // Taking it lands on the inscription form, pre-filled: the consent was
    // already given, so no checkbox — one last « S'inscrire » and done
    await wantedRow.getByRole("link", { name: "Je le prends" }).click();
    await page.waitForURL("**/inscription**");
    await expect(page.getByLabel("Nom affiché")).toHaveValue(
      `Famille Bertrand ${stamp}`,
    );
    const confirmInscription = page.getByRole("button", { name: "S'inscrire" });
    await confirmInscription.click();
    await page.waitForURL(/\/inscription\/confirmation/);
    await expect(
      page.getByRole("heading", { name: "Inscription confirmée" }),
    ).toBeVisible();
    await page.context().close();
  });

  test("parcours n° 1: rename myself, then cancel a slot (F14-T11)", async ({
    browser,
    request,
  }) => {
    test.setTimeout(90_000);
    const slots = await fetchCurrentSlots(request);
    const kept = slots[30];
    const freed = slots[31];
    const stamp = Date.now();
    const page = await registerThenOpenMySpace(
      browser,
      [slots[30], freed],
      `Famille Martin ${stamp}`,
    );

    // 1. « Mon espace »: my name, then my two slots
    // The name heads the page, before the grid lists it again
    await expect(
      page.getByText(`Famille Martin ${stamp}`, { exact: true }).first(),
    ).toBeVisible();
    const mine = page.getByRole("region", { name: "Mes créneaux" });
    await expect(mine.getByRole("listitem")).toHaveCount(2);
    // The state of the chain is at a glance: before the start — the countdown
    const banner = page.getByRole("region", { name: "État de l'édition" });
    await expect(banner.getByText(/La chaîne commence/)).toBeVisible();
    await expect(banner.getByRole("timer")).toBeVisible();
    // Each card offers the prayer funnel first: the golden CTA, and
    // « Me désinscrire » stays a muted link away from it
    const keptCard = mine.getByRole("listitem").filter({ hasText: kept.label });
    await expect(
      keptCard.getByRole("link", { name: `Voir le créneau — ${kept.label}` }),
    ).toBeVisible();
    await expectNoA11yViolations(page);

    // 2. Change my name: the new name shows at once
    await page.getByRole("button", { name: "Changer mon nom" }).click();
    const rename = page.getByRole("dialog", { name: "Changer mon nom" });
    await expectNoA11yViolations(page);
    await rename
      .getByLabel("Nom affiché")
      .fill(`Famille Martin-Dubois ${stamp}`);
    await rename.getByRole("button", { name: "Enregistrer" }).click();
    await expect(rename).toBeHidden();
    await expect(
      page.getByText(`Famille Martin-Dubois ${stamp}`, { exact: true }).first(),
    ).toBeVisible();

    // 2bis. The prayer funnel indeed leads to the reading page
    await page
      .getByRole("link", { name: `Voir le créneau — ${kept.label}` })
      .click();
    await page.waitForURL("**/textes/**");
    await expect(
      page.getByRole("heading", { level: 1, name: "Texte du créneau" }),
    ).toBeVisible();
    await page.goBack();
    await expect(
      page.getByRole("region", { name: "Mes créneaux" }),
    ).toBeVisible();

    // 3. Cancel the second slot: announced on screen, one slot left
    await mine
      .getByRole("button", {
        name: `Me désinscrire du créneau de ${freed.label}`,
      })
      .click();
    const confirm = page.getByRole("alertdialog", { name: "Me désinscrire" });
    await expectNoA11yViolations(page);
    await confirm.getByRole("button", { name: "Me désinscrire" }).click();
    await expect(
      page.getByText(`Vous êtes désinscrit du créneau de ${freed.label}.`),
    ).toBeVisible();
    await expect(mine.getByRole("listitem")).toHaveCount(1);
    await expect(
      page.getByRole("heading", { level: 2, name: "Ajouter des créneaux" }),
    ).toBeVisible();
    await expect(
      page.getByRole("button", { name: "Prendre un créneau vide" }),
    ).toBeVisible();
    // Same warm-ivory treatment as the event landing
    expect(
      await page.evaluate(
        () => getComputedStyle(document.body).backgroundColor,
      ),
    ).toBe("rgb(250, 247, 240)");
    await page.context().close();
  });
});
