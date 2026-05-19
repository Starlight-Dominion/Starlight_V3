import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';

export default defineConfig({
  plugins: [svelte()],
  server: {
    host: '0.0.0.0', // Allow access from host Mac
    port: 5173,
    strictPort: true,
    hmr: {
      host: 'localhost', // Ensure Hot Module Replacement works in browser
    },
    watch: {
      usePolling: true, // Necessary for some Docker setups on macOS
    },
  },
  build: {
    outDir: 'public/dist',
    manifest: true,
    rollupOptions: {
      input: 'src/Resources/js/app.js',
    },
  },
});