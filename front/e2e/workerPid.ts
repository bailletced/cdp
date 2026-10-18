import path from "node:path";

/**
 * File used to pass the messenger worker pid from globalSetup
 * to globalTeardown (Playwright loads them as separate modules).
 */
export const workerPidFile = path.resolve(
  import.meta.dirname,
  ".messenger-worker.pid",
);
