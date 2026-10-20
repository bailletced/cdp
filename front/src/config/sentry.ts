import * as Sentry from "@sentry/react";

/**
 * Sentry front configuration (F03-T20).
 * Initialises Sentry for production, filters email addresses from events,
 * and sets the release to the build SHA.
 */

const EMAIL_PATTERN = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;
const FILTERED_PLACEHOLDER = "[FILTERED]";

/**
 * Deep-clone a value, masking any email address found in strings.
 * Arrays and objects are traversed recursively.
 */
function maskEmailsDeep<T>(value: T): T {
  if (typeof value === "string") {
    return value.replace(EMAIL_PATTERN, FILTERED_PLACEHOLDER) as T;
  }
  if (Array.isArray(value)) {
    return value.map((item) => maskEmailsDeep(item)) as T;
  }
  if (value !== null && typeof value === "object") {
    const result: Record<string, unknown> = {};
    for (const [key, val] of Object.entries(value as Record<string, unknown>)) {
      result[key] = maskEmailsDeep(val);
    }
    return result as T;
  }
  return value;
}

/**
 * Sentry `beforeSend` hook: filters email addresses from the event
 * before it is sent to Sentry. The user.email field is removed entirely.
 */
export function filterSentryEvent(event: Sentry.ErrorEvent): Sentry.ErrorEvent {
  // Remove user email entirely (it is PII).
  if (event.user) {
    const { email: _email, ...userWithoutEmail } = event.user;
    event.user = userWithoutEmail;
  }

  // Mask emails in all other fields (extra, tags, breadcrumbs, exception, etc.).
  const masked = maskEmailsDeep(event);
  return masked;
}

/**
 * Initialise Sentry for the front application.
 * Only runs in production (import.meta.env.PROD) and when a DSN is configured.
 */
export function initSentry(): void {
  const dsn = import.meta.env.VITE_SENTRY_DSN as string | undefined;
  if (!import.meta.env.PROD || !dsn) {
    return;
  }

  Sentry.init({
    dsn,
    environment: "prod",
    release: import.meta.env.VITE_APP_SHA as string | undefined,
    // No Session Replay (privacy).
    replaysSessionSampleRate: 0,
    replaysOnErrorSampleRate: 0,
    // 10% of transactions for tracing.
    tracesSampleRate: 0.1,
    // Filter emails before sending (sendDefaultPii is false by default).
    beforeSend: filterSentryEvent,
  });
}
