/**
 * Where the SPA sends a just-authenticated visitor after a personal login
 * link (F02-T07): the page that submitted the e-mail form stores its own
 * path here before POSTing, the login page reads and consumes it.
 *
 * Whitelist only: never an open redirect. localStorage (not sessionStorage)
 * so the link opened in a new tab of the same browser still lands right.
 */
export const POST_LOGIN_TARGET_KEY = "cdp.postLoginTarget";

const ALLOWED_TARGETS = ["/", "/my-space", "/landing"] as const;

export const DEFAULT_POST_LOGIN_TARGET = "/my-space";

export function isPostLoginTarget(value: string | null): value is string {
  return (
    null !== value && (ALLOWED_TARGETS as readonly string[]).includes(value)
  );
}

/**
 * Reads and removes the stored target; falls back to /my-space when absent
 * or not whitelisted (e.g. the link was opened on another device).
 */
export function consumePostLoginTarget(
  storage: Pick<Storage, "getItem" | "removeItem"> = localStorage,
): string {
  const value = storage.getItem(POST_LOGIN_TARGET_KEY);
  storage.removeItem(POST_LOGIN_TARGET_KEY);

  return isPostLoginTarget(value) ? value : DEFAULT_POST_LOGIN_TARGET;
}
