import path from "node:path";
import { defineConfig, devices } from "@playwright/test";

const frontDir = import.meta.dirname;
const apiDir = path.resolve(frontDir, "../api");

// Host-network defaults for local runs (`pnpm exec playwright test` on a
// machine with the docker stack up). In the docker stack and in CI these are
// set explicitly (docker-compose, .github/workflows/e2e.yml) and win.
// They must reach the API server (webServer) and the messenger worker
// (globalSetup), which both inherit this process environment.
process.env.VITE_API_TARGET ??= "http://localhost:8080";
process.env.MAILPIT_API_URL ??= "http://localhost:8025/api/v1";
process.env.MAILER_DSN ??= "smtp://localhost:1025";
process.env.DEFAULT_URI ??= "http://localhost:8000";

// Prod-image mode (Image CI, F03-T16): PLAYWRIGHT_BASE_URL points at the
// production container started by docker-compose.e2e.yml. No local webServer
// is started and globalSetup skips its console commands: the stack already
// runs the migrations and a messenger worker.
const prodImageUrl = process.env.PLAYWRIGHT_BASE_URL;

export default defineConfig({
  testDir: "./e2e",
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  timeout: 30000,
  expect: {
    timeout: 5000,
  },
  globalSetup: "./e2e/globalSetup.ts",
  globalTeardown: "./e2e/globalTeardown.ts",
  use: {
    baseURL: prodImageUrl ?? "http://localhost:8000",
    trace: "on-first-retry",
  },
  projects: [
    {
      name: "chromium",
      use: {
        ...devices["Desktop Chrome"],
      },
    },
  ],
  webServer: prodImageUrl
    ? []
    : [
        {
          // Vite dev server (the SPA). The proxy target is set via VITE_API_TARGET.
          command: "pnpm dev",
          url: "http://localhost:8000",
          reuseExistingServer: !process.env.CI,
          cwd: frontDir,
        },
        {
          // The API. In the docker stack the php service already listens on
          // localhost:8080 (reused); in CI a local PHP server is started.
          command: "php -S 127.0.0.1:8080 -t public public/index.php",
          url: "http://localhost:8080/auth/status",
          reuseExistingServer: !process.env.CI,
          cwd: apiDir,
        },
      ],
});
