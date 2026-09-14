import { test, expect } from '../fixtures/crm-fixtures.js';

test.describe('Abnormal Journey: Back, Forward and Refresh Handling', () => {
    test('Form state and DOM integrity across browser navigation @abnormal @resilience', async ({ auditedPage: page, qaRegistry }) => {
        const leadName = qaRegistry.generateLeadName(108);

        await page.goto('/leads/create');
        const nameInput = page.locator('input[name="first_name"], input#first_name, input[name="name"]').first();
        await nameInput.fill(leadName);

        // Navigate away and back
        await page.goto('/leads');
        await page.goBack();

        await expect(page).toHaveURL(/leads\/create/);
        // Form DOM should be interactive and healthy
        const restoredInput = page.locator('input[name="first_name"], input#first_name, input[name="name"]').first();
        await expect(restoredInput).toBeVisible();
        await expect(restoredInput).toBeEnabled();

        // Refresh page
        await page.reload();
        await expect(page.locator('input[name="first_name"], input#first_name, input[name="name"]').first()).toBeVisible();
    });
});
