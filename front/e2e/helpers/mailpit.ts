/**
 * Helpers to interact with Mailpit in the E2E tests (F00-T27, F02-T20).
 * The HTTP API URL is configurable: `mailpit:8025` in the docker network,
 * `localhost:8025` on the host / in CI (MAILPIT_API_URL).
 */

export interface MailpitEmail {
  id: string;
  from: string;
  to: string[];
  subject: string;
  text: string;
  html: string;
  attachments: MailpitAttachment[];
}

export interface MailpitAttachment {
  fileName: string;
  contentType: string;
  partId: string;
}

const MAILPIT_API_URL =
  process.env.MAILPIT_API_URL ?? "http://mailpit:8025/api/v1";

// The list endpoint only returns a summary (no Text/HTML bodies).
interface MailpitMessageSummary {
  ID: string;
  To: Array<{ Address: string }>;
}

interface MailpitMessage extends MailpitMessageSummary {
  From: { Address: string };
  Subject: string;
  Text: string;
  HTML: string;
  Attachments?: Array<{
    FileName: string;
    ContentType: string;
    PartID: string;
  }>;
}

function toEmail(message: MailpitMessage): MailpitEmail {
  return {
    id: message.ID,
    from: message.From.Address,
    to: message.To.map((recipient) => recipient.Address),
    subject: message.Subject,
    text: message.Text,
    html: message.HTML,
    attachments: (message.Attachments ?? []).map((attachment) => ({
      fileName: attachment.FileName,
      contentType: attachment.ContentType,
      partId: attachment.PartID,
    })),
  };
}

/**
 * Returns the most recent email sent to the given address, or null.
 */
export async function getLastEmailTo(
  address: string,
): Promise<MailpitEmail | null> {
  // Plain fetch: the helpers also run in globalSetup, outside any test
  // fixture, where the Playwright `request` fixture is not available.
  const response = await fetch(`${MAILPIT_API_URL}/messages`);
  const body = (await response.json()) as {
    messages?: MailpitMessageSummary[];
  };

  const summary = (body.messages ?? [])
    .filter((message) =>
      message.To.some((recipient) => recipient.Address === address),
    )
    .at(-1);

  if (!summary) {
    return null;
  }

  // The bodies are only available on the single-message endpoint.
  const fullResponse = await fetch(`${MAILPIT_API_URL}/message/${summary.ID}`);
  const full = (await fullResponse.json()) as MailpitMessage;

  return toEmail(full);
}

/**
 * Extracts the first URL from an email body (plain text first, then HTML).
 */
export function extractLinkFromEmail(email: MailpitEmail): string | null {
  const fromText = email.text.match(/https?:\/\/[^\s"'<>]+/);
  if (fromText) {
    return fromText[0];
  }

  const fromHtml = email.html
    .replaceAll("&amp;", "&")
    .match(/https?:\/\/[^\s"'<>]+/);

  return fromHtml ? fromHtml[0] : null;
}

/**
 * The content of an attachment of an email, as text.
 */
export async function readAttachment(
  email: MailpitEmail,
  attachment: MailpitAttachment,
): Promise<string> {
  const response = await fetch(
    `${MAILPIT_API_URL}/message/${email.id}/part/${attachment.partId}`,
  );

  return response.text();
}

/**
 * Deletes every message from Mailpit.
 */
export async function clearMailpit(): Promise<void> {
  await fetch(`${MAILPIT_API_URL}/messages`, { method: "DELETE" });
}

/**
 * Waits until an email arrives for the given address.
 */
export async function waitForEmail(
  address: string,
  timeout = 30000,
  pollInterval = 1000,
): Promise<MailpitEmail | null> {
  const startTime = Date.now();

  while (Date.now() - startTime < timeout) {
    const email = await getLastEmailTo(address);
    if (email) {
      return email;
    }
    await new Promise((resolve) => setTimeout(resolve, pollInterval));
  }

  return null;
}
