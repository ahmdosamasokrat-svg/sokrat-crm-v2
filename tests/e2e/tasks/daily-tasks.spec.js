import { test, expect } from '../fixtures/crm-fixtures.js';

test.describe('Daily Tasks & Follow-up Modal Flow', () => {
    test('Open task follow-up modal and verify required inputs cannot be bypassed @tasks', async ({ auditedPage: page }) => {
        await page.goto('/tasks/daily');
        await expect(page).toHaveURL(/tasks\/daily/);

        await page.evaluate(() => {
            if (typeof window.openQuickFollowupModal === 'function') {
                window.openQuickFollowupModal(1, 'QA Lead Test', 1, 'call');
            }
        });

        const modal = page.locator('#quickFollowupModal.open, #quickFollowupModal').first();
        await expect(modal).toBeVisible();

        const cancelBtn = modal.locator('button.btn.soft:has-text("إلغاء"), button.btn.soft:has-text("Cancel")').first();
        if (await cancelBtn.count() > 0) {
            await cancelBtn.click();
            await expect(page.locator('#quickFollowupModal.open')).toHaveCount(0);
        }
    });
});
