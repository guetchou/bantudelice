const { defineConfig, devices } = require('@playwright/test');

const baseURL = process.env.BD_BASE_URL || 'https://bantudelice.cg';
const executablePath = process.env.BD_CHROME_PATH || '/root/.cache/puppeteer/chrome/linux-146.0.7680.76/chrome-linux64/chrome';

module.exports = defineConfig({
  testDir: './tests/e2e',
  timeout: 60000,
  expect: {
    timeout: 10000,
  },
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: 1,
  reporter: 'line',
  use: {
    baseURL,
    browserName: 'chromium',
    headless: true,
    ignoreHTTPSErrors: true,
    screenshot: 'off',
    trace: 'off',
    video: 'off',
    launchOptions: {
      executablePath,
      args: [
        '--no-sandbox',
        '--disable-dev-shm-usage',
        '--disable-crash-reporter',
        '--disable-crashpad',
      ],
    },
  },
  projects: [
    {
      name: 'desktop-chromium',
      use: {
        viewport: { width: 1440, height: 960 },
      },
    },
    {
      name: 'mobile-iphone',
      use: {
        ...devices['iPhone 13'],
        baseURL,
        browserName: 'chromium',
        headless: true,
        ignoreHTTPSErrors: true,
        screenshot: 'off',
        trace: 'off',
        video: 'off',
        launchOptions: {
          executablePath,
          args: [
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-crash-reporter',
            '--disable-crashpad',
          ],
        },
      },
    },
  ],
});
