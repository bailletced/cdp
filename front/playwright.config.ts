import path from "node:path";
import { defineConfig, devices } from "@playwright/test";

const frontDir = import.meta.dirname;
const apiDir = path.resolve(frontDir, "../api");

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
    baseURL: "http://localhost:8000",
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
  webServer: [
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
