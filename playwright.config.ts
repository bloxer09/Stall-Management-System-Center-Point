import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e/specs',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    forbidOnly: !!process.env.CI,
    timeout: 30000,
    expect: { timeout: 7000 },
    outputDir: 'test-results/e2e',
    reporter: [
        ['list'],
        ['html', { outputFolder: 'playwright-report', open: 'never' }],
        ['json', { outputFile: 'test-results/e2e-results.json' }],
        ['junit', { outputFile: 'test-results/e2e-junit.xml' }],
    ],
    use: {
        baseURL: 'http://127.0.0.1:8877',
        actionTimeout: 10000,
        navigationTimeout: 15000,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        video: 'retain-on-failure',
        serviceWorkers: 'block',
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
        { name: 'mobile-chromium', use: { ...devices['Pixel 7'] } },
        { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
        { name: 'webkit', use: { ...devices['Desktop Safari'] } },
    ],
    webServer: {
        command: 'node tests/e2e/support/server.mjs',
        url: 'http://127.0.0.1:8877/up',
        reuseExistingServer: false,
        timeout: 120000,
        stdout: 'ignore',
        stderr: 'pipe',
    },
});
