import type { Page } from "@playwright/test";
import { extractLinkFromEmail, waitForEmail } from "./mailpit";

/**
 * Logs in through the personal link journey: request a link on
 * /receive-my-link, fetch it from Mailpit, open it (lands on /my-space).
 */
export async function loginViaPersonalLink(
  page: Page,
  email: string,
): Promise<void> {
  await page.goto("/receive-my-link");
  await page.getByLabel("Adresse e-mail").fill(email);
  await page.getByRole("button", { name: "Recevoir mon lien" }).click();

  const received = await waitForEmail(email, 30000);
  if (!received) {
    throw new Error(`No email received for ${email}`);
  }
  const link = extractLinkFromEmail(received);
  if (!link) {
    throw new Error("No link found in the email");
  }

  await page.goto(link);
  await page.waitForURL("**/my-space");
}
