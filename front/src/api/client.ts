import createClient from "openapi-fetch";
import type { paths } from "./schema";

// In tests, requests target the absolute dev-stack URL intercepted by MSW;
// in the browser the API is same-origin (proxied by Vite).
export const baseUrl =
  import.meta.env.MODE === "test" ? "http://localhost:8080" : "";

// Resolve globalThis.fetch at call time (not at createClient time) so test
// interceptors patching globalThis.fetch (MSW) are picked up.
const fetchAtCallTime: typeof fetch = (input, init) =>
  globalThis.fetch(input, init);

export const apiClient = createClient<paths>({
  baseUrl,
  credentials: "same-origin",
  fetch: fetchAtCallTime,
});

export default apiClient;
