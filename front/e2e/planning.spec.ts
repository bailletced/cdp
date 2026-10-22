import { type APIRequestContext, expect, test } from "@playwright/test";
import { E2E_ORGANIZER_EMAIL } from "./globalSetup";
import { expectNoA11yViolations } from "./helpers/a11y";
import { createOpenEdition } from "./helpers/edition";

/**
 * Parcours visiteur du planning public (F08-T11): an organizer opens an
 * edition (48 slots, all in the future), then a visitor browses the grid,
 * sees their local time from America/Montreal (US-08.3), selects three slots
 * and lands on the inscription URL (US-08.5). The grid is audited with
 * axe-core. The edition is created through the back-office, so the parcours
 * is self-contained on a fresh database (CI) as well as on the dev stack.
 */

interface ApiSlot {
  id: string;
  rank: number;
  startsAt: string;
  endsAt: string;
  label: string;
  partOfDay: string;
}

interface ApiEdition {
  id: string;
  title: string;
}

/** The current public edition (US-08.1, RG-ED-1). */
async function fetchCurrentEdition(
  request: APIRequestContext,
): Promise<ApiEdition> {
  const response = await request.get("/api/editions/current");
  expect(response.status()).toBe(200);
  return (await response.json()) as ApiEdition;
}

/** The slots of the current public edition, in chronological (rank) order. */
async function fetchCurrentSlots(
  request: APIRequestContext,
): Promise<ApiSlot[]> {
  const edition = await fetchCurrentEdition(request);
  const slotsResponse = await request.get(`/api/editions/${edition.id}/slots`);
  const payload = (await slotsResponse.json()) as { slots: ApiSlot[] };
  return payload.slots;
}

/** Local hour of an instant, formatted like the app (« 21h00 », fr-FR 24h). */
function localHourLabel(isoUtc: string, timeZone: string): string {
  const parts: Record<string, string> = {};
  const formatter = new Intl.DateTimeFormat("fr-FR", {
    timeZone,
    hour: "2-digit",
    minute: "2-digit",
    hourCycle: "h23",
  });
  for (const part of formatter.formatToParts(new Date(isoUtc))) {
    if (part.type !== "literal") {
      parts[part.type] = part.value;
    }
  }
  return `${parts.hour}h${parts.minute}`;
}

test.describe.configure({ mode: "serial" });

test.describe("Planning public (F08-T11, US-08.2/08.3/08.5)", () => {
  // The login roundtrip (email) plus the creation flow exceed the 30 s test
  // timeout on a loaded machine
  test.beforeAll(
    async ({ browser, request }) => {
      const page = await browser.newPage();
      const title = `24 heures de prière — planning E2E ${Date.now()}`;
      await createOpenEdition(page, request, E2E_ORGANIZER_EMAIL, title);
      await page.close();
    },
    { timeout: 120_000 },
  );

  test("shows the 48 slots of the open edition, grouped by part of day", async ({
    browser,
    request,
  }) => {
    const edition = await fetchCurrentEdition(request);

    const context = await browser.newContext({ timezoneId: "Europe/Paris" });
    const page = await context.newPage();
    await page.goto("/");

    // Edition header (US-08.1): the current public edition
    await expect(
      page.getByRole("heading", { level: 1, name: edition.title }),
    ).toBeVisible();

    const grid = page.getByRole("region", { name: "Grille des créneaux" });
    await expect(grid).toBeVisible();

    // The 48 slots (US-08.2), grouped by part of day. A 24h window wrapping
    // past midnight yields two « Soirée » runs (F08-T09): assert the set of
    // the four French labels, not the group count.
    await expect(grid.getByRole("article")).toHaveCount(48);
    const groupTitles = await grid
      .getByRole("heading", { level: 2 })
      .allTextContents();
    expect([...new Set(groupTitles)].sort()).toEqual([
      "Après-midi",
      "Matin",
      "Nuit",
      "Soirée",
    ]);

    // Every slot is empty (« À pourvoir »), with its schedule, its count and
    // the RG-TX-2 placeholder; none is past (the edition starts in ~2 hours)
    await expect(grid.getByText("À pourvoir", { exact: true })).toHaveCount(48);
    await expect(grid.getByText("0 priant", { exact: true })).toHaveCount(48);
    await expect(
      grid.getByText("Le texte de ce créneau sera bientôt disponible"),
    ).toHaveCount(48);
    await expect(grid.getByText("Créneau passé")).toHaveCount(0);

    // A Paris visitor gets no local time (RG-PL-3), and every slot carries a
    // selection checkbox (US-08.5)
    await expect(grid.getByText(/chez vous/)).toHaveCount(0);
    await expect(grid.getByRole("checkbox")).toHaveCount(48);
    await expect(
      page.getByText("Aucun créneau sélectionné.", { exact: true }),
    ).toBeVisible();

    await expectNoA11yViolations(page);
    await context.close();
  });

  test("shows the local time to a visitor in America/Montreal (US-08.3)", async ({
    browser,
    request,
  }) => {
    const slots = await fetchCurrentSlots(request);
    const nightSlot = slots.find((slot) => slot.label === "3h00–3h30");
    if (!nightSlot) {
      throw new Error("the 24h window always contains a 3h00–3h30 slot");
    }

    const context = await browser.newContext({
      timezoneId: "America/Montreal",
    });
    const page = await context.newPage();
    await page.goto("/");

    const grid = page.getByRole("region", { name: "Grille des créneaux" });
    await expect(grid).toBeVisible();

    // « 3h00–3h30 (21h00 chez vous) »: the local hour is computed with the
    // same Intl rules as the app, so the assertion survives DST changes
    const local = localHourLabel(nightSlot.startsAt, "America/Montreal");
    await expect(
      grid.getByText(`3h00–3h30 (${local} chez vous)`, { exact: true }),
    ).toBeVisible();
    // Every slot shows the visitor's local hour
    await expect(grid.getByText(/chez vous/)).toHaveCount(48);

    await expectNoA11yViolations(page);
    await context.close();
  });

  test("selects three slots and lands on the inscription URL (US-08.5)", async ({
    browser,
    request,
  }) => {
    const slots = await fetchCurrentSlots(request);
    expect(slots).toHaveLength(48);
    const firstThree = slots.slice(0, 3);

    const context = await browser.newContext({ timezoneId: "Europe/Paris" });
    const page = await context.newPage();
    await page.goto("/");

    const grid = page.getByRole("region", { name: "Grille des créneaux" });
    await expect(grid.getByRole("article")).toHaveCount(48);

    for (const slot of firstThree) {
      const checkbox = grid.getByRole("checkbox", {
        name: `Sélectionner le créneau de ${slot.label}`,
      });
      // react-aria hides the input in a 1px clipped span (check()/click() on
      // the input itself time out): click the wrapping label, like a user
      // clicking the visible box
      await checkbox.locator("xpath=ancestor::label[1]").click();
      await expect(checkbox).toBeChecked();
    }
    await expect(
      page.getByText("3 créneaux sélectionnés.", { exact: true }),
    ).toBeVisible();

    await expectNoA11yViolations(page);

    // « M'inscrire (3) » leads to the inscription page with the selected
    // slot ids, in chronological order (the /inscription page itself is F09)
    await page.getByRole("link", { name: "M'inscrire (3)" }).click();
    await page.waitForURL(/\/inscription\?creneaux=/);
    const url = new URL(page.url());
    expect(url.pathname).toBe("/inscription");
    expect(url.searchParams.get("creneaux")).toBe(
      firstThree.map((slot) => slot.id).join(","),
    );

    await context.close();
  });
});
