import { test, expect } from '../fixtures/crm-fixtures.js';
import { createLeadUI } from '../helpers/lead.js';

test.describe('Standard Lead Creation and Editing Suite', () => {
    test('Add new lead with valid information and verify persistence @critical', async ({ auditedPage: page, qaRegistry }) => {
        const lead = await createLeadUI(page, {
            statusText: 'جديد'
        });

        expect(lead.id, 'Lead ID must be generated').toBeTruthy();

        await page.goto('/leads');
        await expect(page.locator(`text="${lead.firstName}"`).first()).toBeVisible();
    });

    test('Edit existing QA lead and verify updated details persist @critical', async ({ auditedPage: page, qaRegistry }) => {
        const lead = await createLeadUI(page, {
            statusText: 'جديد'
        });

        expect(lead.id).toBeTruthy();

        await page.goto(`/leads/${lead.id}/edit`);
        await expect(page).toHaveURL(new RegExp(`/leads/${lead.id}/edit`));

        const updatedName = `${lead.firstName}_UPD`;
        const nameInput = page.locator('input[name="first_name"], input#first_name, input[name="name"]').first();
        await nameInput.fill(updatedName);

        const updateBtn = page.locator('form[action*="/leads/"] button[type="submit"]').first();
        await Promise.all([
            page.waitForURL(url => !url.pathname.includes('/edit'), { timeout: 15000 }),
            updateBtn.click()
        ]);

        await page.goto('/leads');
        await expect(page.locator(`text="${updatedName}"`).first()).toBeVisible();
    });
});
