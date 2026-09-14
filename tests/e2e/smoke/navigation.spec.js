import { test, expect } from '../fixtures/crm-fixtures.js';

test.describe('Authentication & Navigation Smoke Suite', () => {
    test('Verify dashboard loads without fatal errors @smoke', async ({ auditedPage: page }) => {
        await page.goto('/');
        await expect(page).toHaveURL(/\/(dashboard|leads)?/);
        await expect(page.locator('body')).toBeVisible();
        await expect(page).not.toHaveURL(/login/);
    });

    test('Navigate main CRM sections successfully @smoke @critical', async ({ auditedPage: page }) => {
        const sections = [
            { name: 'Dashboard', path: '/' },
            { name: 'Leads', path: '/leads' },
            { name: 'Kanban', path: '/leads/kanban' },
            { name: 'Daily Tasks', path: '/tasks/daily' },
            { name: 'Campaign Reports', path: '/campaigns/reports' },
            { name: 'Quotations', path: '/quotations' },
            { name: 'Calendar', path: '/calendar' },
        ];

        for (const sec of sections) {
            const resp = await page.goto(sec.path, { waitUntil: 'domcontentloaded' });
            expect(resp.status(), `Status code for ${sec.name} should be successful`).toBeLessThan(400);
            await expect(page).not.toHaveURL(/login/);
            await expect(page.locator('body')).toBeVisible();
        }
    });

    test('Check no redirect loops or infinite loading spinners on leads page @smoke', async ({ auditedPage: page }) => {
        await page.goto('/leads');
        const spinner = page.locator('#crm-page-loader, .crm-spinner, .loading-spinner').first();
        if (await spinner.count() > 0) {
            // Must disappear within 10s
            await expect(spinner).not.toBeVisible({ timeout: 10000 });
        }
        await expect(page.locator('h1, h2, .page-title, .content-header').first()).toBeVisible();
    });
});
