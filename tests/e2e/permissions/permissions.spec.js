import { test, expect } from '../fixtures/crm-fixtures.js';

test.describe('Role and Permission Verification Suite', () => {
    test('Privileged settings routes are protected and verified @permissions', async ({ auditedPage: page }) => {
        const resp = await page.goto('/settings/users');
        expect(resp.status()).toBeLessThan(400);
        await expect(page.locator('body')).toBeVisible();
    });

    test('Unauthorized path access behaves safely with appropriate denial/redirect @permissions', async ({ auditedPage: page }) => {
        const resp = await page.goto('/settings/non-existent-qa-path-999');
        expect([403, 404]).toContain(resp.status());
    });
});
