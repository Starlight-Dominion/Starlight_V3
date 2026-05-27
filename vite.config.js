import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import istanbul from 'vite-plugin-istanbul';

const coverageEnabled = process.env.PLAYWRIGHT_COVERAGE === '1';

export default defineConfig({
  plugins: [
    svelte(),
    coverageEnabled
      ? istanbul({
          include: ['src/Resources/js/**/*'],
          extension: ['.js', '.svelte'],
          requireEnv: false,
          forceBuildInstrument: true,
        })
      : null,
  ].filter(Boolean),
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
    sourcemap: coverageEnabled,
    rollupOptions: {
      input: 'src/Resources/js/app.js',
    },
  },
});