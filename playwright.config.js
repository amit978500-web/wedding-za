const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests',
  timeout: 30000,
  expect: {
    timeout: 5000,
  },
  retries: 1,
  reporter: 'line',
  use: {
    baseURL: 'http://127.0.0.1:8088',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  webServer: {
    command: 'php -S 127.0.0.1:8088',
    url: 'http://127.0.0.1:8088',
    reuseExistingServer: false,
    timeout: 30000,
  },
  projects: [
    {
      name: 'desktop-chromium',
      use: {
        ...devices['Desktop Chrome'],
      },
    },
    {
      name: 'mobile-chromium',
      use: {
        ...devices['iPhone 13'],
      },
    },
  ],
});
