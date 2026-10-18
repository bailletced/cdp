import { existsSync, readFileSync, rmSync } from "node:fs";
import { workerPidFile } from "./workerPid";

/**
 * Stops the messenger worker started by globalSetup.
 */
export default async function globalTeardown(): Promise<void> {
  if (!existsSync(workerPidFile)) {
    return;
  }

  const pid = Number(readFileSync(workerPidFile, "utf8"));
  rmSync(workerPidFile, { force: true });

  try {
    // Negative pid: the whole detached process group
    process.kill(-pid, "SIGTERM");
  } catch {
    // The worker is already gone
  }
}
