import { test, expect } from '../fixtures/crm-fixtures.js';

test.describe('Stage File Upload Verification', () => {
    test('Handles PDF file upload on quotation stage correctly @stage @files', async ({ auditedPage: page, qaRegistry }) => {
        const leadName = qaRegistry.generateLeadName(104);
        const phone = qaRegistry.generatePhone(104);

        await page.goto('/leads/create');

        await page.locator('input[name="first_name"], input#first_name').first().fill(leadName);
        await page.locator('input[name="phone"], input#phone').first().fill(phone);

        const statusSelect = page.locator('select[name="lead_status_id"], select#leadStatus').first();
        await statusSelect.selectOption({ label: 'عرض سعر' });

        const fileInput = page.locator('input[type="file"][name*="quotation_file_path"]').first();
        if (await fileInput.count() > 0) {
            await expect(fileInput).toBeVisible();
            await expect(fileInput).toBeEnabled();

            await fileInput.setInputFiles({
                name: 'test-quotation.pdf',
                mimeType: 'application/pdf',
                buffer: Buffer.from('%PDF-1.4 ... test file content ... %%EOF')
            });

            await statusSelect.selectOption({ label: 'جديد' });
            await expect(fileInput).toBeDisabled();

            await statusSelect.selectOption({ label: 'عرض سعر' });
            await expect(fileInput).toBeEnabled();
        }
    });
});
