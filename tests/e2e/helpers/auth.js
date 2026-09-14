import { expect } from '@playwright/test';

/**
 * Perform login using username/email and password.
 * NEVER prints credentials.
 */
export async function login(page, username, password) {
    await page.goto('/login');
    await expect(page).toHaveURL(/login/);

    const loginInput = page.locator('input[name="username"], input[name="email"], input#username, input#email').first();
    const passwordInput = page.locator('input[name="password"], input#password').first();
    const submitBtn = page.locator('button[type="submit"], input[type="submit"]').first();

    await expect(loginInput).toBeVisible();
    await expect(passwordInput).toBeVisible();

    await loginInput.fill(username);
    await passwordInput.fill(password);

    await Promise.all([
        page.waitForResponse(resp => (resp.url().includes('/login') && resp.request().method() === 'POST') || resp.status() === 302, { timeout: 15000 }).catch(() => null),
        submitBtn.click()
    ]);

    // Wait for redirect to CRM dashboard/leads
    await page.waitForURL(url => !url.pathname.includes('/login'), { timeout: 15000 });
}
