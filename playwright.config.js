import { defineConfig, devices } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const baseURL = process.env.CRM_E2E_BASE_URL;
if (!baseURL) {
    throw new Error('CRM_E2E_BASE_URL is required');
}

export const AUTH_FILE = path.join(__dirname, 'tests/e2e/.auth/user.json');

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: 0,
    workers: 1,
    timeout: 45000,
    expect: {
        timeout: 10000,
    },
    reporter: [
        ['html', { outputFolder: 'tests/e2e/playwright-report', open: 'never' }],
        ['list'],
    ],
    use: {
        baseURL: baseURL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        locale: 'ar-EG',
        timezoneId: 'Africa/Cairo',
        ignoreHTTPSErrors: true,
        actionTimeout: 10000,
        navigationTimeout: 20000,
    },
    projects: [
        {
            name: 'setup',
            testMatch: /auth\.setup\.js/,
        },
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                storageState: AUTH_FILE,
            },
            dependencies: ['setup'],
        },
    ],
});
