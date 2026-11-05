import { describe, expect, it } from "vitest";
import {
  DEFAULT_POST_LOGIN_TARGET,
  POST_LOGIN_TARGET_KEY,
  consumePostLoginTarget,
  isPostLoginTarget,
} from "./postLoginTarget";

function fakeStorage(initial: Record<string, string> = {}) {
  const map = new Map(Object.entries(initial));

  return {
    getItem: (key: string) => map.get(key) ?? null,
    removeItem: (key: string) => void map.delete(key),
    dump: () => Object.fromEntries(map),
  };
}

describe("postLoginTarget", () => {
  it("accepts only whitelisted paths", () => {
    expect(isPostLoginTarget("/")).toBe(true);
    expect(isPostLoginTarget("/my-space")).toBe(true);
    expect(isPostLoginTarget("/landing")).toBe(true);
    expect(isPostLoginTarget("/organisation")).toBe(false);
    expect(isPostLoginTarget("https://evil.example")).toBe(false);
    expect(isPostLoginTarget("//evil.example")).toBe(false);
    expect(isPostLoginTarget(null)).toBe(false);
  });

  it("returns the stored target and consumes it", () => {
    const storage = fakeStorage({ [POST_LOGIN_TARGET_KEY]: "/landing" });

    expect(consumePostLoginTarget(storage)).toBe("/landing");
    expect(storage.dump()).toEqual({});
  });

  it("falls back to the default when nothing is stored", () => {
    expect(consumePostLoginTarget(fakeStorage())).toBe(
      DEFAULT_POST_LOGIN_TARGET,
    );
  });

  it("falls back to the default when the stored value is not whitelisted", () => {
    const storage = fakeStorage({
      [POST_LOGIN_TARGET_KEY]: "https://evil.example",
    });

    expect(consumePostLoginTarget(storage)).toBe(DEFAULT_POST_LOGIN_TARGET);
    expect(storage.dump()).toEqual({});
  });
});
