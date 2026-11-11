import type { Page } from "@playwright/test";

/**
 * Registers a fresh visitor on the given slots, straight on /inscription: the
 * public planning grid is gone, the slot ids come from the API (the my-space
 * picker builds this same URL for the signed-in priants). The page collects
 * the display name and the e-mail — sending the registration is accepting
 * the privacy policy (no consent checkbox anymore) — then confirms.
 */
export async function registerOnSlots(
  page: Page,
  slotIds: string[],
  displayName: string,
  email: string,
): Promise<void> {
  await page.goto(`/inscription?creneaux=${slotIds.join(",")}`);
  await page.getByLabel("Nom affiché").fill(displayName);
  await page.getByLabel("Adresse e-mail").fill(email);
  await page.getByRole("button", { name: "S'inscrire" }).click();
  await page.waitForURL(/\/inscription\/confirmation/);
}
