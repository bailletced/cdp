import { type APIRequestContext, expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { createOpenEdition } from "./helpers/edition";
import { registerOnSlots } from "./helpers/inscription";

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

  test.beforeAll(async ({ browser, request }) => {
    // A hook ignores the timeout option: it is set from inside
    test.setTimeout(120_000);
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
      },
    });
    expect(text.status()).toBe(201);
    const assigned = await page.request.put(
      `/api/admin/slots/${slot.id}/text`,
      { headers: JSON_LD, data: { textId: (await text.json()).id } },
    );
    expect(assigned.ok()).toBe(true);
    await page.close();
  });

  test("the registered device reads the text, another browser is told it is reserved", async ({
    browser,
  }) => {
    test.setTimeout(60_000);

    // 1. Register from the phone
    const phone = await browser.newContext({ timezoneId: "Europe/Paris" });
    const page = await phone.newPage();
    await registerOnSlots(
      page,
      [slot.id],
      `Lectrice E2E ${Date.now()}`,
      `lecture-e2e-${Date.now()}@example.org`,
    );

    // 2. From the confirmation, the full text
    await page
      .getByRole("link", {
        name: `Lire le texte du créneau de ${slot.label}`,
      })
      .click();
    await page.waitForURL(`**/textes/${slot.id}`);
    // Same warm-ivory treatment as the landing and « Mon espace »
    expect(
      await page.evaluate(
        () => getComputedStyle(document.body).backgroundColor,
      ),
    ).toBe("rgb(250, 247, 240)");
    await expect(
      page.getByRole("heading", { level: 1, name: textTitle }),
    ).toBeVisible();
    await expect(
      page.getByText("Que rien ne te trouble, que rien ne t’effraie."),
    ).toBeVisible();
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
