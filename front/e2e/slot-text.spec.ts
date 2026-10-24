import { type APIRequestContext, expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { createOpenEdition } from "./helpers/edition";

const JSON_LD = { "Content-Type": "application/ld+json" };

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
 * Reading the text of a slot (F12-T06, US-12.1, US-12.2): the device that
 * registered opens the full text from the confirmation; another browser,
 * with no pass, gets the « réservé » page. Both pages are audited with
 * axe-core.
 */
test.describe("Lecture du texte (F12-T06)", () => {
  const textTitle = `Que rien ne te trouble E2E ${Date.now()}`;
  let slot: ApiSlot;

  test.beforeAll(
    async ({ browser, request }) => {
      const page = await browser.newPage();
      await createOpenEdition(
        page,
        request,
        E2E_ORGANIZER_EMAIL,
        `24 heures de prière — lecture E2E ${Date.now()}`,
      );

      // Attribute a text to one slot, with the organizer's session
      slot = (await fetchCurrentSlots(request))[5];
      const text = await page.request.post("/api/admin/texts", {
        headers: JSON_LD,
        data: {
          title: textTitle,
          source: "Thérèse d’Avila",
          body: "<p>Que rien ne te trouble, que rien ne t’effraie.</p>",
          hints: "Relire lentement chaque vers.",
        },
      });
      expect(text.status()).toBe(201);
      const assigned = await page.request.put(
        `/api/admin/slots/${slot.id}/text`,
        { headers: JSON_LD, data: { textId: (await text.json()).id } },
      );
      expect(assigned.ok()).toBe(true);
      await page.close();
    },
    { timeout: 120_000 },
  );

  test("the registered device reads the text, another browser is told it is reserved", async ({
    browser,
  }) => {
    test.setTimeout(60_000);

    // 1. Register from the phone
    const phone = await browser.newContext({ timezoneId: "Europe/Paris" });
    const page = await phone.newPage();
    await page.goto("/");
    const checkbox = page
      .getByRole("region", { name: "Grille des créneaux" })
      .getByRole("checkbox", {
        name: `Sélectionner le créneau de ${slot.label}`,
      });
    // react-aria hides the input in a clipped span: click the wrapping label
    await checkbox.locator("xpath=ancestor::label[1]").click();
    await page.getByRole("link", { name: "M'inscrire (1)" }).click();
    await page.getByLabel("Nom affiché").fill(`Lectrice E2E ${Date.now()}`);
    await page
      .getByLabel("Adresse e-mail")
      .fill(`lecture-e2e-${Date.now()}@example.org`);
    await page
      .getByRole("checkbox", {
        name: /J'accepte la politique de confidentialité/,
      })
      .locator("xpath=ancestor::label[1]")
      .click({ position: { x: 12, y: 12 } });
    await page.getByRole("button", { name: "S'inscrire" }).click();
    await page.waitForURL(/\/inscription\/confirmation/);

    // 2. From the confirmation, the full text and its hints
    await page
      .getByRole("link", {
        name: `Lire le texte du créneau de ${slot.label}`,
      })
      .click();
    await page.waitForURL(`**/textes/${slot.id}`);
    await expect(
      page.getByRole("heading", { level: 1, name: textTitle }),
    ).toBeVisible();
    await expect(
      page.getByText("Que rien ne te trouble, que rien ne t’effraie."),
    ).toBeVisible();
    await expect(
      page.getByRole("region", { name: "Pistes de méditation" }),
    ).toContainText("Relire lentement chaque vers.");
    await expectNoA11yViolations(page);
    await phone.close();

    // 3. Another browser, no pass: the text is reserved (RG-TX-1)
    const other = await browser.newContext({ timezoneId: "Europe/Paris" });
    const visitor = await other.newPage();
    await visitor.goto(`/textes/${slot.id}`);
    await expect(
      visitor.getByRole("heading", { level: 1, name: "Texte réservé" }),
    ).toBeVisible();
    await expect(visitor.getByText(textTitle)).toHaveCount(0);
    await expect(
      visitor.getByRole("link", { name: "M'inscrire sur ce créneau" }),
    ).toBeVisible();
    await expectNoA11yViolations(visitor);
    await other.close();
  });
});
