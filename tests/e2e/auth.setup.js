import { test as setup, expect } from '@playwright/test';
import { AUTH_FILE } from '../../playwright.config.js';
import fs from 'fs';
import path from 'path';

setup('authenticate as QA user', async ({ page }) => {
    const emailOrUsername = process.env.CRM_E2E_EMAIL || process.env.CRM_E2E_USERNAME;
    const password = process.env.CRM_E2E_PASSWORD;

    if (!emailOrUsername || !password) {
        console.warn('BLOCKED — QA LOGIN CREDENTIALS REQUIRED. (Set CRM_E2E_EMAIL and CRM_E2E_PASSWORD)');
        throw new Error('BLOCKED — QA LOGIN CREDENTIALS REQUIRED');
    }

    await page.goto('/login');
    await expect(page).toHaveURL(/login/);

    const userInput = page.locator('input[name="username"], input[name="email"]').first();
    const passInput = page.locator('input[name="password"]').first();
    const submitBtn = page.locator('button[type="submit"], input[type="submit"]').first();

    await expect(userInput).toBeVisible();
    await expect(passInput).toBeVisible();

    await userInput.fill(emailOrUsername);
    await passInput.fill(password);

    await Promise.all([
        page.waitForResponse(resp => (resp.url().includes('/login') && resp.request().method() === 'POST') || resp.status() === 302, { timeout: 15000 }).catch(() => null),
        submitBtn.click(),
    ]);

    // Check successful redirect to dashboard / home
    await expect(page).not.toHaveURL(/login/);

    // Verify authenticated page indicator
    const authIndicator = page.locator('.crm-sidebar, .sidebar, [data-user-profile], header, nav').first();
    await expect(authIndicator).toBeVisible({ timeout: 10000 });

    const dir = path.dirname(AUTH_FILE);
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }

    await page.goto('/lang/ar');
    await page.context().storageState({ path: AUTH_FILE });
});
