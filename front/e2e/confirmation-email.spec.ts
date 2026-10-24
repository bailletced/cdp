import { type APIRequestContext, expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { createOpenEdition } from "./helpers/edition";
import {
  extractLinkFromEmail,
  readAttachment,
  waitForEmail,
} from "./helpers/mailpit";

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
 * Confirmation email (F11-T08, US-11.1, US-11.2): after registering on two
 * slots, the priant receives one email in Mailpit recalling both slots in
 * Paris time, with the agenda file attached; its personal link opens the
 * personal space.
 */
test.describe("E-mail de confirmation (F11-T08)", () => {
  test.beforeAll(
    async ({ browser, request }) => {
      const page = await browser.newPage();
      await createOpenEdition(
        page,
        request,
        E2E_ORGANIZER_EMAIL,
        `24 heures de prière — confirmation E2E ${Date.now()}`,
      );
      await page.close();
    },
    { timeout: 120_000 },
  );

  test("one email with both slots, the .ics and a personal link to the personal space", async ({
    browser,
    request,
  }) => {
    test.setTimeout(90_000);
    const slots = await fetchCurrentSlots(request);
    const [first, second] = [slots[10], slots[11]];
    const address = `confirmation-e2e-${Date.now()}@example.org`;

    const context = await browser.newContext({ timezoneId: "Europe/Paris" });
    const page = await context.newPage();
    await page.goto("/");
    const grid = page.getByRole("region", { name: "Grille des créneaux" });
    for (const slot of [first, second]) {
      // react-aria hides the input in a clipped span: click the wrapping label
      await grid
        .getByRole("checkbox", {
          name: `Sélectionner le créneau de ${slot.label}`,
        })
        .locator("xpath=ancestor::label[1]")
        .click();
    }
    await page.getByRole("link", { name: "M'inscrire (2)" }).click();
    await page.getByLabel("Nom affiché").fill("Confirmation E2E");
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
    await context.close();

    // One email, both slots in Paris time
    const email = await waitForEmail(address, 30_000);
    expect(email, "the confirmation email reaches Mailpit").not.toBeNull();
    if (!email) {
      return;
    }
    expect(email.subject).toMatch(/^Votre inscription : /);
    expect(email.text).toContain(`${first.label} (heure de Paris)`);
    expect(email.text).toContain(`${second.label} (heure de Paris)`);

    // The agenda file: one event per slot, an alarm 10 minutes before
    expect(email.attachments).toHaveLength(1);
    const [ics] = email.attachments;
    expect(ics.fileName).toBe("mes-creneaux.ics");
    expect(ics.contentType).toBe("text/calendar");
    const calendar = await readAttachment(email, ics);
    expect(calendar.match(/BEGIN:VEVENT/g)).toHaveLength(2);
    expect(calendar).toContain("TRIGGER:-PT10M");

    // The personal link opens the personal space, on any device
    const link = extractLinkFromEmail(email);
    expect(link).not.toBeNull();
    const elsewhere = await browser.newContext();
    const priant = await elsewhere.newPage();
    await priant.goto(link ?? "");
    await priant.waitForURL("**/my-space");
    await expect(
      priant.getByRole("heading", { level: 1, name: "Mon espace" }),
    ).toBeVisible();
    await elsewhere.close();
  });
});
