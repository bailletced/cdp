import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

// In the docker stack the API is the `php` service (port 80 in the network);
// on the host / in CI it is a local server (override with VITE_API_TARGET).
const apiTarget = process.env.VITE_API_TARGET ?? "http://php:80";

export default defineConfig({
  // Code-based routing (src/router.tsx): no tanstackRouter() plugin needed,
  // it would fail looking for a file-based src/routes directory.
  plugins: [react()],
  server: {
    port: 8000,
    proxy: {
      // No changeOrigin: the API's SameOriginListener (F02-T10) compares the
      // browser Origin against the forwarded Host header.
      "/api": {
        target: apiTarget,
      },
      "/auth": {
        target: apiTarget,
      },
    },
  },
  build: {
    outDir: "dist",
    sourcemap: true,
  },
});
