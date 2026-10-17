import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { tanstackRouter } from '@tanstack/router-plugin/vite';

export default defineConfig({
  plugins: [
    tanstackRouter(),
    react(),
  ],
  server: {
    port: 8000,
    proxy: {
      '/api': {
        target: 'http://php:8080',
        changeOrigin: true,
      },
      '/auth': {
        target: 'http://php:8080',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'dist',
    sourcemap: true,
  },
});
