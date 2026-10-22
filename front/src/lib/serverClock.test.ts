import { act, renderHook } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { afterEach, describe, expect, it, vi } from "vitest";
import { apiClient } from "../api/client";
import { server } from "../test/setup";
import {
  learnFromResponse,
  learnFromServerTimeHeader,
  now,
  resetServerClock,
  serverOffsetMs,
  useNow,
} from "./serverClock";

const TEN_MINUTES_MS = 10 * 60 * 1000;

// The true server time used across the tests: 2026-11-28T18:50:00Z
const SERVER_TIME_MS = Date.UTC(2026, 10, 28, 18, 50, 0);

afterEach(() => {
  vi.useRealTimers();
  resetServerClock();
});

describe("now", () => {
  it("follows the local clock when no offset has been estimated", () => {
    vi.useFakeTimers();
    vi.setSystemTime(SERVER_TIME_MS);

    expect(now()).toBe(SERVER_TIME_MS);
  });

  it("corrects a client clock 10 minutes ahead (US-08.4)", () => {
    vi.useFakeTimers();
    // The device clock is 10 minutes ahead of the server
    vi.setSystemTime(SERVER_TIME_MS + TEN_MINUTES_MS);

    learnFromServerTimeHeader(String(SERVER_TIME_MS / 1000));

    expect(serverOffsetMs()).toBe(-TEN_MINUTES_MS);
    expect(now()).toBe(SERVER_TIME_MS);
  });

  it("corrects a client clock 10 minutes behind", () => {
    vi.useFakeTimers();
    vi.setSystemTime(SERVER_TIME_MS - TEN_MINUTES_MS);

    learnFromServerTimeHeader(String(SERVER_TIME_MS / 1000));

    expect(serverOffsetMs()).toBe(TEN_MINUTES_MS);
    expect(now()).toBe(SERVER_TIME_MS);
  });
});

describe("learnFromServerTimeHeader", () => {
  it("ignores a missing or invalid header", () => {
    vi.useFakeTimers();
    vi.setSystemTime(SERVER_TIME_MS);

    learnFromServerTimeHeader(null);
    learnFromServerTimeHeader("not-a-number");

    expect(serverOffsetMs()).toBe(0);
    expect(now()).toBe(SERVER_TIME_MS);
  });

  it("keeps the previous estimate when the new header is invalid", () => {
    vi.useFakeTimers();
    vi.setSystemTime(SERVER_TIME_MS + TEN_MINUTES_MS);
    learnFromServerTimeHeader(String(SERVER_TIME_MS / 1000));

    learnFromServerTimeHeader("garbage");

    expect(serverOffsetMs()).toBe(-TEN_MINUTES_MS);
  });
});

describe("learnFromResponse", () => {
  it("reads X-Server-Time from a fetch Response", () => {
    vi.useFakeTimers();
    vi.setSystemTime(SERVER_TIME_MS + TEN_MINUTES_MS);

    learnFromResponse(
      new Response(null, {
        headers: { "X-Server-Time": String(SERVER_TIME_MS / 1000) },
      }),
    );

    expect(now()).toBe(SERVER_TIME_MS);
  });

  it("ignores a response without the header", () => {
    vi.useFakeTimers();
    vi.setSystemTime(SERVER_TIME_MS);

    learnFromResponse(new Response(null));

    expect(serverOffsetMs()).toBe(0);
  });
});

describe("useNow", () => {
  it("returns the corrected time and refreshes every second", () => {
    vi.useFakeTimers();
    vi.setSystemTime(SERVER_TIME_MS + TEN_MINUTES_MS);
    learnFromServerTimeHeader(String(SERVER_TIME_MS / 1000));

    const { result } = renderHook(() => useNow());
    expect(result.current).toBe(SERVER_TIME_MS);

    act(() => {
      vi.advanceTimersByTime(1000);
    });
    expect(result.current).toBe(SERVER_TIME_MS + 1000);

    act(() => {
      vi.advanceTimersByTime(5000);
    });
    expect(result.current).toBe(SERVER_TIME_MS + 6000);
  });

  it("follows the local clock when no offset is known", () => {
    vi.useFakeTimers();
    vi.setSystemTime(SERVER_TIME_MS);

    const { result } = renderHook(() => useNow(500));
    expect(result.current).toBe(SERVER_TIME_MS);

    act(() => {
      vi.advanceTimersByTime(1500);
    });
    expect(result.current).toBe(SERVER_TIME_MS + 1500);
  });
});

describe("API client wiring", () => {
  it("learns the server offset from X-Server-Time on API responses", async () => {
    // Only Date is faked: MSW and fetch keep their real timers
    vi.useFakeTimers({ toFake: ["Date"] });
    // The device clock is 10 minutes ahead of the server
    vi.setSystemTime(SERVER_TIME_MS + TEN_MINUTES_MS);

    server.use(
      http.get("http://localhost:8080/api/me", () => {
        return HttpResponse.json(
          {
            "@context": "/api/contexts/Me",
            "@id": "/api/me",
            "@type": "Me",
            email: "thomas@bailletced.org",
            isOrganizer: false,
          },
          { headers: { "X-Server-Time": String(SERVER_TIME_MS / 1000) } },
        );
      }),
    );

    const { error } = await apiClient.GET("/api/me");

    expect(error).toBeUndefined();
    expect(serverOffsetMs()).toBe(-TEN_MINUTES_MS);
    expect(now()).toBe(SERVER_TIME_MS);
  });
});
