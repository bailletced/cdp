import { describe, expect, it } from "vitest";
import { filterSentryEvent } from "./sentry";

/**
 * Tests for the Sentry event filter (F03-T20).
 * Done when: an event containing an email is filtered before being sent.
 */
describe("filterSentryEvent", () => {
  const emailPattern = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/;

  it("filters email from user data", () => {
    const event = {
      user: { email: "priant@example.com", id: "123" },
    };

    const filtered = filterSentryEvent(event as never);

    expect(filtered.user?.email).toBeUndefined();
    expect(filtered.user?.id).toBe("123");
  });

  it("masks email in extra data", () => {
    const event = {
      extra: { contact: "marie@example.org", name: "Marie" },
    };

    const filtered = filterSentryEvent(event as never);

    expect(filtered.extra?.contact).toBe("[FILTERED]");
    expect(filtered.extra?.name).toBe("Marie");
  });

  it("masks email in tags", () => {
    const event = {
      tags: { email: "thomas@example.com", env: "prod" },
    };

    const filtered = filterSentryEvent(event as never);

    expect(filtered.tags?.email).toBe("[FILTERED]");
    expect(filtered.tags?.env).toBe("prod");
  });

  it("masks email in exception messages", () => {
    const event = {
      exception: {
        values: [
          { type: "Error", value: "Failed to send to priant@example.com" },
        ],
      },
    };

    const filtered = filterSentryEvent(event as never);

    const values = filtered.exception?.values;
    expect(values?.[0]?.value).not.toMatch(emailPattern);
    expect(values?.[0]?.value).toContain("[FILTERED]");
  });

  it("masks email in breadcrumbs", () => {
    const event = {
      breadcrumbs: [{ message: "User priant@example.com logged in" }],
    };

    const filtered = filterSentryEvent(event as never);

    expect(filtered.breadcrumbs?.[0]?.message).not.toMatch(emailPattern);
  });

  it("returns event unchanged when no email is present", () => {
    const event = {
      user: { id: "123" },
      extra: { name: "Marie" },
    };

    const filtered = filterSentryEvent(event as never);

    expect(filtered.user?.id).toBe("123");
    expect(filtered.extra?.name).toBe("Marie");
  });
});
