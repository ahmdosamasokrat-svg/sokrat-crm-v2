import { test, expect } from '../fixtures/crm-fixtures.js';
import { login } from '../helpers/auth.js';

test.describe('Login Smoke Flow', () => {
    // Isolated browser context without pre-existing storageState
    test.use({ storageState: { cookies: [], origins: [] } });

    test('Loads login page with valid inputs and handles invalid credentials cleanly @smoke', async ({ auditedPage: page }) => {
        await page.goto('/login');
        await expect(page).toHaveURL(/login/);

        const usernameInput = page.locator('input[name="username"], input[name="email"]').first();
        const passwordInput = page.locator('input[name="password"]').first();
        const submitBtn = page.locator('button[type="submit"], input[type="submit"]').first();

        await expect(usernameInput).toBeVisible();
        await expect(passwordInput).toBeVisible();

        // Attempt invalid login
        await usernameInput.fill('invalid_qa_user_999');
        await passwordInput.fill('InvalidPassword999!');
        await submitBtn.click();

        // Should remain on login page with error indication
        await expect(page).toHaveURL(/login/);
        const errorAlert = page.locator('.alert, .text-danger, .error, [role="alert"]').first();
        await expect(errorAlert).toBeVisible();
    });
});
