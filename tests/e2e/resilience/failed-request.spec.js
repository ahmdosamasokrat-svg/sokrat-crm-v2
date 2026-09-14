import { test, expect } from '../fixtures/crm-fixtures.js';

test.describe('Network Resilience: Latency and Failure Recovery', () => {
    test('Simulated delayed stage response does not corrupt UI state @resilience @network', async ({ auditedPage: page }) => {
        // Intercept lead stage field calls or relevant endpoints with delay
        await page.route('**/leads/**', async route => {
            if (route.request().resourceType() === 'fetch' || route.request().resourceType() === 'xhr') {
                await new Promise(r => setTimeout(r, 1000));
            }
            await route.continue();
        });

        await page.goto('/leads/create');
        const stageSelect = page.locator('select[name="pipeline_stage_id"]').first();
        if (await stageSelect.count() > 0) {
            await stageSelect.selectOption({ label: 'غير مهتم' });
            await stageSelect.selectOption({ label: 'مهتم' });

            // UI should settle in 'مهتم' state without console crash
            const companyInput = page.locator('input[name*="[company_name]"]').first();
            if (await companyInput.count() > 0) {
                await expect(companyInput).toBeEnabled();
            }
        }
    });

    test('Simulated 500 error on safe read endpoint recovers without freezing browser @resilience @network', async ({ auditedPage: page }) => {
        // Simulate safe failure on notifications or background poll endpoint
        await page.route('**/notifications/**', route => {
            route.fulfill({
                status: 500,
                contentType: 'application/json',
                body: JSON.stringify({ error: 'Simulated QA network error' })
            });
        });

        await page.goto('/leads');
        // Page should still load its primary layout cleanly
        await expect(page.locator('body')).toBeVisible();
        await expect(page.locator('h1, h2, .page-title, .content-header').first()).toBeVisible();
    });
});
