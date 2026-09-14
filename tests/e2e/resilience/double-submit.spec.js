import { test, expect } from '../fixtures/crm-fixtures.js';
import { createLeadUI } from '../helpers/lead.js';
import { execSync } from 'child_process';

test.describe('Double Submit & Form Resilience', () => {
    test('Double-clicking Save creates exactly ONE lead @critical @resilience', async ({ auditedPage: page, qaRegistry }) => {
        const leadName = qaRegistry.generateLeadName(105);
        const phone = qaRegistry.generatePhone(105);

        await page.goto('/leads/create');
        await page.locator('input[name="first_name"], input#first_name').first().fill(leadName);
        await page.locator('input[name="phone"], input#phone').first().fill(phone);
        await page.locator('input[name="source"], input#source').first().fill('E2E QA Double Click Test');
        await page.locator('select[name="lead_status_id"], select#leadStatus').first().selectOption({ label: 'جديد' });

        const saveBtn = page.locator('form button[type="submit"].btn.primary').first();

        await saveBtn.click({ clickCount: 2, delay: 50 });
        await page.waitForTimeout(3000);

        let createdCount = 0;
        let createdIds = [];
        try {
            const out = execSync(`php artisan tinker --execute="\\$ls = \\App\\Models\\Lead::where('first_name', '${leadName}')->get(['id']); echo \\$ls->pluck('id')->implode(',');"`, {
                cwd: '/var/www/html/crm-v2',
                encoding: 'utf-8'
            }).trim();
            if (out) {
                createdIds = out.split(',').map(Number);
                createdCount = createdIds.length;
                createdIds.forEach(id => qaRegistry.registerLeadId(id));
            }
        } catch (e) {}

        expect(createdCount, `Expected 1 lead but found ${createdCount} created by double click`).toBe(1);
    });
});
