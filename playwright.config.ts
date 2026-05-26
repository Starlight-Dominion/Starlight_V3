import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8080';
const useExternalServer = process.env.PLAYWRIGHT_USE_EXTERNAL_SERVER === '1';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 2 : undefined,
  timeout: 45_000,
  expect: {
    timeout: 10_000,
  },
  reporter: process.env.CI
    ? [['github'], ['junit', { outputFile: 'test-results/junit.xml' }], ['html', { open: 'never' }], ['list']]
    : [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
  ],
  webServer: useExternalServer
    ? undefined
    : {
        command: 'php -S 127.0.0.1:8080 -t public public/index.php',
        env: {
          ...process.env,
          APP_ENV: 'production',
        },
        url: `${baseURL}/login`,
        timeout: 120_000,
        reuseExistingServer: !process.env.CI,
      },
});
