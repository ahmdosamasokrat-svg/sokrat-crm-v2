import { test, expect } from '../fixtures/crm-fixtures.js';
import { createLeadUI } from '../helpers/lead.js';

test.describe('Multi-Tab Concurrency Suite', () => {
    test('Simultaneous updates in two tabs document concurrency behavior @resilience', async ({ browser, qaRegistry }) => {
        const context = await browser.newContext({ storageState: 'tests/e2e/.auth/user.json' });
        const page1 = await context.newPage();

        const lead = await createLeadUI(page1, { statusText: 'جديد' });
        expect(lead.id).toBeTruthy();

        const editUrl = `/leads/${lead.id}/edit`;

        const page2 = await context.newPage();
        await page1.goto(editUrl);
        await page2.goto(editUrl);

        const inputA = page1.locator('input[name="first_name"], input#first_name, input[name="name"]').first();
        await inputA.fill(`${lead.firstName}_FROM_TAB_A`);
        const submitA = page1.locator('form[action*="/leads/"] button[type="submit"]').first();
        await Promise.all([
            page1.waitForURL(url => !url.pathname.endsWith('/edit'), { timeout: 15000 }),
            submitA.click()
        ]);

        const inputB = page2.locator('input[name="phone"], input#phone').first();
        await inputB.fill(qaRegistry.generatePhone(110));
        const submitB = page2.locator('form[action*="/leads/"] button[type="submit"]').first();
        await Promise.all([
            page2.waitForURL(url => !url.pathname.endsWith('/edit'), { timeout: 15000 }),
            submitB.click()
        ]);

        expect(page2.url()).not.toContain('/500');
        await context.close();
    });
});
