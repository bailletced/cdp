import { type ChildProcess, execSync, spawn } from "node:child_process";
import { writeFileSync } from "node:fs";
import path from "node:path";
import { clearMailpit } from "./helpers/mailpit";
import { workerPidFile } from "./workerPid";

const apiDir = path.resolve(import.meta.dirname, "../../api");

export const E2E_ORGANIZER_EMAIL = "e2e-organizer@example.com";

function consoleCommand(command: string): void {
  execSync(`php bin/console ${command}`, {
    cwd: apiDir,
    env: process.env,
    stdio: "inherit",
  });
}

/**
 * Environment setup for the E2E run (F02-T20):
 * transport tables + migrations, the organizer account (idempotent),
 * a messenger worker (sends the emails to Mailpit), and a clean mailbox.
 * The worker pid is stored so globalTeardown can stop it.
 */
export default async function globalSetup(): Promise<void> {
  consoleCommand("messenger:setup-transports");
  consoleCommand("doctrine:migrations:migrate --no-interaction");
  consoleCommand(`app:organizer:create ${E2E_ORGANIZER_EMAIL}`);

  const worker: ChildProcess = spawn(
    "php",
    [
      "bin/console",
      "messenger:consume",
      "async_transactional",
      "--time-limit=300",
    ],
    {
      cwd: apiDir,
      env: process.env,
      detached: true,
      stdio: "ignore",
    },
  );
  worker.unref();
  writeFileSync(workerPidFile, String(worker.pid));

  await clearMailpit();
}
