import { test, expect } from '../fixtures/crm-fixtures.js';
import { extractLeadIdFromUrl } from '../helpers/lead.js';
import { execSync } from 'child_process';

test.describe('Reported غير مهتم (Not Interested) Bug Verification', () => {
    test('Create lead with status غير مهتم and verify single canonical reason control persists @critical @stage', async ({ auditedPage: page, qaRegistry }) => {
        const leadName = qaRegistry.generateLeadName(101);
        const phone = qaRegistry.generatePhone(101);

        await page.goto('/lang/ar');
        await page.goto('/leads/create');
        await expect(page).toHaveURL(/leads\/create/);

        await page.locator('input[name="first_name"], input#first_name').first().fill(leadName);
        await page.locator('input[name="phone"], input#phone').first().fill(phone);
        const sourceInput = page.locator('input[name="source"], input#source').first();
        if (await sourceInput.count() > 0 && await sourceInput.isVisible()) {
            await sourceInput.fill('E2E QA Test Source');
        }

        const statusSelect = page.locator('select[name="lead_status_id"], select#leadStatus').first();
        await statusSelect.selectOption({ label: 'غير مهتم' });

        // 3. Assert visible question: سبب عدم الاهتمام
        const reasonQuestion = page.locator('label:has-text("سبب عدم الاهتمام"), label:has-text("Reason")').first();
        await expect(reasonQuestion).toBeVisible({ timeout: 5000 });

        // 4. Check that only ONE canonical reason control exists (no legacy duplicate disinterest_reason)
        const legacyDisinterest = page.locator('select[name="disinterest_reason"], input[name="disinterest_reason"]');
        const legacyCount = await legacyDisinterest.count();
        expect(legacyCount, 'Legacy duplicate disinterest_reason control must not exist').toBe(0);

        // 5. Select reason: غير مقتنع بالفكرة (value: not_convinced)
        const reasonSelect = page.locator('select[name="stage_fields[17][reason]"], select[name="stage_fields[reason]"], select[data-field-key="reason"]').first();
        await expect(reasonSelect).toBeVisible();
        await expect(reasonSelect).toBeEnabled();

        // Select 'not_convinced'
        await reasonSelect.selectOption({ label: 'غير مقتنع بالفكرة' });

        // Check selected value remains selected
        const selectedVal = await reasonSelect.inputValue();
        expect(selectedVal).toBe('not_convinced');

        // Enter notes if textarea present
        const notesArea = page.locator('textarea[name*="[notes]"]').first();
        if (await notesArea.count() > 0 && await notesArea.isVisible()) {
            await notesArea.fill('QA verification for not interested stage flow.');
        }

        const saveBtn = page.locator('form button[type="submit"].btn.primary, form button[type="submit"]:has-text("حفظ"), form button[type="submit"]:has-text("Save")').first();
        await Promise.all([
            page.waitForURL(url => !url.pathname.includes('/create'), { timeout: 15000 }),
            saveBtn.click()
        ]);

        const validationError = page.locator('text="سبب عدم الاهتمام مطلوب"');
        await expect(validationError).toHaveCount(0);

        let leadId = null;
        try {
            const out = execSync(`php artisan tinker --execute="\\$l = \\App\\Models\\Lead::where('first_name', '${leadName}')->first(['id']); echo \\$l ? \\$l->id : '';"`, {
                cwd: '/var/www/html/crm-v2',
                encoding: 'utf-8'
            }).trim();
            if (out && !isNaN(Number(out))) {
                leadId = Number(out);
                qaRegistry.registerLeadId(leadId);
            }
        } catch (e) {}

        expect(leadId, 'Lead ID must be found for created lead').toBeTruthy();
    });
});
