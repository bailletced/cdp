import { type APIRequestContext, type Page, expect } from "@playwright/test";
import { loginViaPersonalLink } from "./login";

/**
 * Creates an edition through the back-office and schedules it, so that it is
 * the current public edition (US-08.1): open since yesterday, starting later
 * than any edition already open. Its 48 slots are all in the future
 * (selectable) and aligned on :00/:30 (the API rejects other minutes,
 * F05-T15), so their labels are stable. Extracted from editions.spec.ts for
 * the planning parcours.
 */
export async function createOpenEdition(
  page: Page,
  request: APIRequestContext,
  organizerEmail: string,
  title: string,
): Promise<void> {
  const startsAt = await freshStartsAt(request);
  const opensAt = roundDownToHalfHour(new Date(Date.now() - 24 * 3600_000));

  await loginViaPersonalLink(page, organizerEmail);

  await page.goto("/organisation/editions");
  await page.getByRole("link", { name: "Nouvelle édition" }).click();
  await page.waitForURL("**/organisation/editions/new");

  await page.getByLabel("Titre").fill(title);
  await page.getByLabel("Présentation").fill("Édition du parcours visiteur.");
  const starts = parisWallClock(startsAt);
  await page.getByLabel("Début (heure de Paris) — date").fill(starts.date);
  await page.getByLabel("Début (heure de Paris) — heure").fill(starts.time);
  const opens = parisWallClock(opensAt);
  await page
    .getByLabel("Ouverture des inscriptions (heure de Paris) — date")
    .fill(opens.date);
  await page
    .getByLabel("Ouverture des inscriptions (heure de Paris) — heure")
    .fill(opens.time);
  await page.getByRole("button", { name: "Créer l'édition" }).click();
  await expect(page.getByText("Édition créée.")).toBeVisible();

  // Schedule it from the detail page: the 48 slots are generated (US-05.4)
  await page
    .getByRole("link", { name: "Retour à la liste des éditions" })
    .click();
  await page.waitForURL("**/organisation/editions");
  const row = page.getByRole("listitem").filter({ hasText: title });
  await row.getByRole("link", { name: title }).click();
  await page.getByRole("button", { name: "Programmer" }).click();
  // The 48 slots have no text yet: RG-ED-4 asks for a confirmation
  await page
    .getByRole("alertdialog", { name: "Programmer l’édition" })
    .getByRole("button", { name: "Programmer quand même" })
    .click();
  // Scheduled: the reverse action appears, with the 48 generated slots. The
  // status badge shows the hybrid status (« Inscriptions ouvertes » here),
  // not the lifecycle word, so the action button is the reliable marker.
  await expect(
    page.getByRole("button", { name: "Repasser en brouillon" }),
  ).toBeVisible();
  await expect(page.getByText("48 créneaux de 30 minutes.")).toBeVisible();
}

/**
 * A start strictly later than the current edition's and about 2 hours ahead.
 * RG-ED-1: the current public edition is the most recent scheduled one whose
 * opening has been reached (ORDER BY starts_at DESC), and open editions
 * linger in the database — so the new edition must start strictly later to
 * win, whatever the runs already present (CI starts from an empty database).
 */
async function freshStartsAt(request: APIRequestContext): Promise<Date> {
  const minStart = roundDownToHalfHour(new Date(Date.now() + 2 * 3600_000));
  const response = await request.get("/api/editions/current");
  if (response.status() !== 200) {
    return minStart;
  }
  const current = (await response.json()) as { startsAt: string };
  const currentStart = new Date(current.startsAt).getTime();
  if (minStart.getTime() <= currentStart) {
    // Both instants are aligned on :00/:30, so +30 min stays aligned
    return new Date(currentStart + 30 * 60_000);
  }
  return minStart;
}

/** The API rejects minutes other than 00/30 (F05-T15): align to the half hour. */
function roundDownToHalfHour(date: Date): Date {
  const rounded = new Date(date.getTime());
  rounded.setMinutes(rounded.getMinutes() >= 30 ? 30 : 0, 0, 0);
  return rounded;
}

/**
 * Paris wall clock of an instant, split for the ParisDateTimeField inputs
 * (« Début (heure de Paris) — date/heure »). Independent of the browser zone.
 */
function parisWallClock(date: Date): { date: string; time: string } {
  const parts = new Intl.DateTimeFormat("fr-CA", {
    timeZone: "Europe/Paris",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    hourCycle: "h23",
  }).formatToParts(date);
  const get = (type: Intl.DateTimeFormatPartTypes): string =>
    parts.find((part) => part.type === type)?.value ?? "";

  return {
    date: `${get("year")}-${get("month")}-${get("day")}`,
    time: `${get("hour")}:${get("minute")}`,
  };
}
