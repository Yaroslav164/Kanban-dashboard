import { defineConfig } from '@playwright/test'

const baseURL = process.env.KANBAN_URL || 'http://localhost:8000'
export default defineConfig({
  testDir: './tests/e2e',
  workers: 1,
  timeout: 90000,
  use: { baseURL, trace: 'retain-on-failure' },
  webServer: process.env.KANBAN_URL
    ? undefined
    : [
        {
          command: 'php artisan serve --host=localhost --port=8000',
          url: baseURL + '/up',
          reuseExistingServer: !process.env.CI,
        },
        {
          command: 'php artisan reverb:start --host=localhost --port=8080',
          port: 8080,
          reuseExistingServer: !process.env.CI,
        },
      ],
})
