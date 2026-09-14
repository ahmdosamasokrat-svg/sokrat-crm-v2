import { test, expect } from '../fixtures/crm-fixtures.js';

test.describe('Stage Switching Chaos & State Integrity', () => {
    test('Sequence A & B switching: inactive fields are hidden and disabled @critical @stage', async ({ auditedPage: page }) => {
        await page.goto('/leads/create');

        const statusSelect = page.locator('select[name="lead_status_id"], select#leadStatus').first();
        await expect(statusSelect).toBeVisible();

        await statusSelect.selectOption({ label: 'جديد' });
        const reasonField = page.locator('select[name*="[reason]"]').first();
        if (await reasonField.count() > 0) {
            await expect(reasonField).toBeDisabled();
        }

        await statusSelect.selectOption({ label: 'غير مهتم' });
        await expect(reasonField).toBeVisible();
        await expect(reasonField).toBeEnabled();

        await statusSelect.selectOption({ label: 'مهتم' });
        await expect(reasonField).toBeDisabled();

        const companyField = page.locator('input[name*="[company_name]"]').first();
        if (await companyField.count() > 0) {
            await expect(companyField).toBeVisible();
            await expect(companyField).toBeEnabled();
        }

        await statusSelect.selectOption({ label: 'غير مهتم' });
        await expect(reasonField).toBeVisible();
        await expect(reasonField).toBeEnabled();
        if (await companyField.count() > 0) {
            await expect(companyField).toBeDisabled();
        }
    });

    test('Rapid Stage switching does not cause duplicate forms or JS errors @stage @resilience', async ({ auditedPage: page }) => {
        await page.goto('/leads/create');
        const statusSelect = page.locator('select[name="lead_status_id"], select#leadStatus').first();

        const statuses = ['جديد', 'غير مهتم', 'عرض سعر', 'مناقشة', 'غير مهتم'];
        for (const st of statuses) {
            await statusSelect.selectOption({ label: st });
            await page.waitForTimeout(50);
        }

        const reasonSelects = page.locator('select[name*="[reason]"]');
        const count = await reasonSelects.count();
        expect(count).toBe(1);
        await expect(reasonSelects.first()).toBeVisible();
        await expect(reasonSelects.first()).toBeEnabled();
    });
});
