import { type APIRequestContext, expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { loginViaPersonalLink } from "./helpers/login";

const JSON_LD = { "Content-Type": "application/ld+json" };

/** A draft edition far ahead: once scheduled it stays « Programmée », never public. */
async function createDraftEdition(
  request: APIRequestContext,
  title: string,
): Promise<string> {
  const response = await request.post("/api/admin/editions", {
    headers: JSON_LD,
    data: {
      title,
      presentation: "Édition du parcours d’attribution.",
      startsAt: "2027-03-06T20:00:00+01:00",
      opensAt: "2027-02-27T20:00:00+01:00",
    },
  });
  expect(response.status()).toBe(201);

  return (await response.json()).id;
}

async function createTexts(
  request: APIRequestContext,
  prefix: string,
): Promise<string[]> {
  const ids: string[] = [];
  for (let n = 1; n <= 48; n++) {
    const number = String(n).padStart(2, "0");
    const response = await request.post("/api/admin/texts", {
      headers: JSON_LD,
      data: {
        title: `${prefix} n° ${number}`,
        source: "Parcours E2E",
        body: `<p>Texte n° ${number}.</p>`,
      },
    });
    expect(response.status()).toBe(201);
    ids.push((await response.json()).id);
  }

  return ids;
}

/**
 * Text attribution (F07-T12): an organizer distributes 48 texts over an
 * edition and schedules it with no warning; once a slot is emptied,
 * scheduling again asks to confirm « 1 créneau n’a pas encore de texte ».
 * The screens are audited with axe-core.
 */
test.describe("Back-office — attribution des textes", () => {
  test("distribute 48 texts, schedule without warning, then with one", async ({
    page,
  }) => {
    test.setTimeout(120_000);
    await loginViaPersonalLink(page, E2E_ORGANIZER_EMAIL);

    const stamp = Date.now();
    const prefix = `Attribution E2E ${stamp}`;
    const editionId = await createDraftEdition(
      page.request,
      `24 heures de prière — attribution E2E ${stamp}`,
    );
    const textIds = await createTexts(page.request, prefix);

    try {
      // Distribute the 48 texts from the first slot
      await page.goto(`/organisation/editions/${editionId}/texts`);
      await expect(
        page.getByText("48 créneaux sur 48 n’ont pas encore de texte."),
      ).toBeVisible();
      await expectNoA11yViolations(page);
      await page.getByRole("link", { name: "Répartir dans l’ordre" }).click();
      await page.waitForURL("**/distribute");

      await page.getByLabel("Rechercher un texte").fill(prefix);
      // The library answers 20 texts per page: load the three pages
      const listed = page.getByRole("button", { name: `Ajouter « ${prefix}` });
      const more = page.getByRole("button", {
        name: "Afficher plus de textes",
      });
      const addAll = page.getByRole("button", {
        name: "Ajouter les 48 textes affichés",
      });
      await expect(listed).toHaveCount(20);
      await more.click();
      await expect(listed).toHaveCount(40);
      await more.click();
      await expect(listed).toHaveCount(48);
      await addAll.click();
      await expect(
        page.getByText("Les créneaux n° 1 à 48 recevront 48 textes."),
      ).toBeVisible();
      await expectNoA11yViolations(page);

      await page.getByRole("button", { name: "Répartir 48 textes" }).click();
      await page.waitForURL("**/texts");
      await expect(page.getByText("Chaque créneau a son texte.")).toBeVisible();

      // Every slot has its text: « Programmer » asks nothing
      await page.getByRole("link", { name: "Retour à l’édition" }).click();
      await page.getByRole("button", { name: "Programmer" }).click();
      await expect(
        page.getByRole("button", { name: "Repasser en brouillon" }),
      ).toBeVisible();
      await expect(
        page.getByRole("alertdialog", { name: "Programmer l’édition" }),
      ).toHaveCount(0);

      // Back to draft, empty one slot, schedule again: the warning shows up
      await page.getByRole("button", { name: "Repasser en brouillon" }).click();
      await page
        .getByRole("alertdialog", { name: "Repasser en brouillon" })
        .getByRole("button", { name: "Repasser en brouillon" })
        .click();
      await expect(
        page.getByRole("button", { name: "Programmer" }),
      ).toBeVisible();

      await page.getByRole("link", { name: "Attribuer les textes" }).click();
      await page
        .getByRole("button", { name: /^Retirer le texte de / })
        .first()
        .click();
      await expect(
        page.getByText("1 créneau sur 48 n’a pas encore de texte."),
      ).toBeVisible();

      await page.getByRole("link", { name: "Retour à l’édition" }).click();
      await page.getByRole("button", { name: "Programmer" }).click();
      const warning = page.getByRole("alertdialog", {
        name: "Programmer l’édition",
      });
      await expect(
        warning.getByText("1 créneau n’a pas encore de texte."),
      ).toBeVisible();
      await expectNoA11yViolations(page);
      await warning
        .getByRole("button", { name: "Programmer quand même" })
        .click();
      await expect(
        page.getByRole("button", { name: "Repasser en brouillon" }),
      ).toBeVisible();
    } finally {
      // Leave the library as it was: a draft edition releases its texts
      await page.request.post(`/api/admin/editions/${editionId}/unschedule`, {
        headers: JSON_LD,
        data: {},
      });
      for (const id of textIds) {
        await page.request.delete(`/api/admin/texts/${id}`);
      }
    }
  });
});
