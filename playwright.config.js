import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1';
const useDevServer = process.env.PLAYWRIGHT_USE_DEV_SERVER !== '0' && baseURL === 'http://127.0.0.1';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false,
  retries: process.env.CI ? 2 : 0,
  timeout: process.env.PLAYWRIGHT_LIVE === '1' ? 90_000 : 60_000,
  use: {
    baseURL,
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  webServer: useDevServer
    ? {
        command: 'npm run dev -- --host 127.0.0.1 --port 4173 --strictPort',
        url: 'http://127.0.0.1:4173/resources/js/app.js',
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
      }
    : undefined,
});
