import { test, expect } from '../fixtures/crm-fixtures.js';

test.describe('Radio, Checkbox and Multiselect Conditional Suite', () => {
    test('Multiselect and conditional fields enable and disable dynamically @stage @conditional', async ({ auditedPage: page }) => {
        await page.goto('/leads/create');

        const statusSelect = page.locator('select[name="lead_status_id"], select#leadStatus').first();
        await statusSelect.selectOption({ label: 'لم يرد' });

        const multiselectField = page.locator('select[name*="q_z88ua5"], [data-field-key="q_z88ua5"]').first();
        if (await multiselectField.count() > 0) {
            await expect(multiselectField).toBeVisible();
            await expect(multiselectField).toBeEnabled();

            const conditionalText = page.locator('input[name*="q_xmi2ak"]').first();
            const conditionalFile = page.locator('input[name*="q_nhmdbd"]').first();

            await multiselectField.selectOption('mady');
            await page.waitForTimeout(200);

            if (await conditionalText.count() > 0) {
                await expect(conditionalText).toBeVisible();
                await expect(conditionalText).toBeEnabled();
            }

            await statusSelect.selectOption({ label: 'جديد' });
            if (await multiselectField.count() > 0) {
                await expect(multiselectField).toBeDisabled();
            }
            if (await conditionalText.count() > 0) {
                await expect(conditionalText).toBeDisabled();
            }
        }
    });

    test('Radio group conditional field toggling @stage @conditional', async ({ auditedPage: page }) => {
        await page.goto('/leads/create');

        const statusSelect = page.locator('select[name="lead_status_id"], select#leadStatus').first();
        const techSupportOption = statusSelect.locator('option:has-text("الدعم الفني")');

        if (await techSupportOption.count() > 0) {
            await statusSelect.selectOption({ label: 'الدعم الفني' });

            const radios = page.locator('input[type="radio"][name*="company_organization"]');
            if (await radios.count() > 0) {
                await expect(radios.first()).toBeVisible();
                await radios.first().check();

                const childField = page.locator('input[name*="q_rmpfmb"]').first();
                if (await childField.count() > 0) {
                    await expect(childField).toBeVisible();
                    await expect(childField).toBeEnabled();
                }

                await statusSelect.selectOption({ label: 'جديد' });
                const count = await radios.count();
                for (let i = 0; i < count; i++) {
                    await expect(radios.nth(i)).toBeDisabled();
                }
            }
        }
    });
});
