import createClient from "openapi-fetch";
import { learnFromResponse } from "../lib/serverClock";
import type { paths } from "./schema";

// In tests, requests target the absolute dev-stack URL intercepted by MSW;
// in the browser the API is same-origin (proxied by Vite).
export const baseUrl =
  import.meta.env.MODE === "test" ? "http://localhost:8080" : "";

// Resolve globalThis.fetch at call time (not at createClient time) so test
// interceptors patching globalThis.fetch (MSW) are picked up. Every response
// feeds the server-clock estimate through its X-Server-Time header (US-08.4).
const fetchAtCallTime: typeof fetch = async (input, init) => {
  const response = await globalThis.fetch(input, init);
  learnFromResponse(response);

  return response;
};

export const apiClient = createClient<paths>({
  baseUrl,
  credentials: "same-origin",
  fetch: fetchAtCallTime,
});

export default apiClient;
