import { type APIRequestContext, expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { createOpenEdition } from "./helpers/edition";
import { registerOnSlots } from "./helpers/inscription";

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

/** The slot with the given id, from an /slots payload. */
async function slotView(
  request: APIRequestContext,
  editionId: string,
  slotId: string,
) {
  const response = await request.get(`/api/editions/${editionId}/slots`);
  const payload = await response.json();

  return payload.slots.find((slot: { id: string }) => slot.id === slotId);
}

test.describe.configure({ mode: "serial" });

/**
 * Device pass (F10-T11, US-10.1, US-10.2): once registered, the device
 * carries a pass (no e-mail to open) that unlocks the names of the priants
 * (RG-PL-4); a second browser, with no pass, only ever gets the count. The
 * pass alone never opens « Mon espace » — the space asks for a session
 * (F02-T19) — the personal link of the e-mail provides it.
 */
test.describe("Pass appareil (F10-T11)", () => {
  test.beforeAll(async ({ browser, request }) => {
    // A hook ignores the timeout option: it is set from inside
    test.setTimeout(120_000);
    const page = await browser.newPage();
    await createOpenEdition(
      page,
      request,
      E2E_ORGANIZER_EMAIL,
      `24 heures de prière — pass appareil E2E ${Date.now()}`,
    );
    await page.close();
  });

  test("the device that registered sees the names, another browser does not", async ({
    browser,
    request,
  }) => {
    test.setTimeout(60_000);
    const slot = (await fetchCurrentSlots(request))[7];
    const displayName = `Priant E2E ${Date.now()}`;

    // 1. Register from Marie's phone — the device pass cookie lands
    const phone = await browser.newContext({ timezoneId: "Europe/Paris" });
    const page = await phone.newPage();
    await registerOnSlots(
      page,
      [slot.id],
      displayName,
      `pass-e2e-${Date.now()}@example.org`,
    );
    expect(
      (await phone.cookies()).some((cookie) => cookie.name === "cdp_pass"),
    ).toBe(true);

    // 2. The device pass carries the API visibility of the names (RG-PL-4),
    //    but « Mon espace » asks for a session (F02-T19): the pass alone
    //    bounces back to the landing page
    const edition = await (
      await page.request.get("/api/editions/current")
    ).json();
    const mine = await slotView(page.request, edition.id, slot.id);
    expect(mine.registrationCount).toBe(1);
    expect(mine.participants[0].name).toBe(displayName);
    await expectNoA11yViolations(page);
    await page.goto("/my-space");
    await page.waitForURL("**/landing");
    await phone.close();

    // 3. Another browser, no pass: the count, never a name (RG-PL-4)
    const other = await browser.newContext({ timezoneId: "Europe/Paris" });
    const visitor = await other.newPage();
    const stranger = await slotView(visitor.request, edition.id, slot.id);
    expect(stranger.registrationCount).toBe(1);
    expect(stranger.participants).toBeNull();
    await other.close();
  });
});
