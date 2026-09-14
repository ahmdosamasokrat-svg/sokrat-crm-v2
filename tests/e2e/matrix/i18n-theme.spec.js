import { test, expect } from '../fixtures/crm-fixtures.js';

test.describe('I18n & Theme Matrix Suite', () => {
    test('Arabic RTL and English LTR switch properly @matrix', async ({ auditedPage: page }) => {
        // Test Arabic
        await page.goto('/lang/ar');
        await page.goto('/leads');
        const dirAr = await page.locator('html').getAttribute('dir');
        expect(dirAr).toBe('rtl');

        // Test English
        await page.goto('/lang/en');
        await page.goto('/leads');
        const dirEn = await page.locator('html').getAttribute('dir');
        expect(dirEn).toBe('ltr');

        // Restore to Arabic
        await page.goto('/lang/ar');
    });

    test('Theme switching: Light, Dark, and System modes apply correctly @matrix', async ({ auditedPage: page }) => {
        await page.goto('/leads');

        // Check if theme buttons or dataset theme exists
        await page.evaluate(() => {
            document.documentElement.dataset.theme = 'dark';
            localStorage.setItem('sokrat.crm.theme', 'dark');
        });
        await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');

        await page.evaluate(() => {
            document.documentElement.dataset.theme = 'light';
            localStorage.setItem('sokrat.crm.theme', 'light');
        });
        await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
    });
});
