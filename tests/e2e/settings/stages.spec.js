import { test, expect } from '../fixtures/crm-fixtures.js';

test.describe('Settings Pipeline Stages Regression Suite', () => {
    test('Stage edit action opens the correct stage editor @critical @settings @stage', async ({ auditedPage: page }) => {
        await page.goto('/settings/stages');
        await expect(page).toHaveURL(/\/settings\/stages/);

        // -------------------------------------------------------------
        // 1. PRIMARY STAGE TEST (جديد)
        // -------------------------------------------------------------
        const primaryRow = page.locator('table tbody tr:has-text("جديد")').first();
        await expect(primaryRow).toBeVisible();

        const primaryEditBtn = primaryRow.locator('button[title*="تعديل"], button[aria-label*="تعديل"], button:has(i.bi-pencil-square)').first();
        await expect(primaryEditBtn).toBeAttached();
        await expect(primaryEditBtn).toBeVisible();
        await expect(primaryEditBtn).toBeEnabled();

        // Normal click without force
        await primaryEditBtn.click();

        // Modal should open and be visible
        const editModal = page.locator('#editStageModal');
        await expect(editModal).toBeVisible();

        const modalDialog = editModal.locator('> div').first();
        await expect(modalDialog).toBeVisible();

        // Assert correct Stage identity
        const nameInput = page.locator('#editStageName');
        await expect(nameInput).toHaveValue('جديد');

        const editForm = page.locator('#editStageForm');
        const formAction = await editForm.getAttribute('action');
        expect(formAction).toMatch(/\/settings\/stages\/\d+$/);

        // Primary stage: active checkbox wrapper is hidden
        const activeWrap = page.locator('#editStageActiveWrap');
        await expect(activeWrap).toBeHidden();

        // Cancel / Back returns safely without modifying data
        const cancelBtn = editModal.locator('button.btn.soft:has-text("إلغاء"), button.btn.soft:has-text("Cancel")').first();
        await expect(cancelBtn).toBeVisible();
        await cancelBtn.click();

        await expect(editModal).toBeHidden();
        await expect(page).toHaveURL(/\/settings\/stages/);

        // -------------------------------------------------------------
        // 2. CUSTOM STAGE TEST (مؤجل or custom row)
        // -------------------------------------------------------------
        const customRow = page.locator('table tbody tr:has-text("مؤجل")').first();
        await expect(customRow).toBeVisible();

        const customEditBtn = customRow.locator('button[title*="تعديل"], button[aria-label*="تعديل"], button:has(i.bi-pencil-square)').first();
        await expect(customEditBtn).toBeAttached();
        await expect(customEditBtn).toBeVisible();
        await expect(customEditBtn).toBeEnabled();

        // Normal click without force
        await customEditBtn.click();

        await expect(editModal).toBeVisible();
        await expect(modalDialog).toBeVisible();

        // Assert correct Stage identity
        await expect(nameInput).toHaveValue('مؤجل');

        const customFormAction = await editForm.getAttribute('action');
        expect(customFormAction).toMatch(/\/settings\/stages\/\d+$/);
        expect(customFormAction).not.toBe(formAction); // Must be a different stage ID

        // Custom stage: active checkbox wrapper is visible
        await expect(activeWrap).toBeVisible();

        // Cancel / Back returns safely without modifying data
        await cancelBtn.click();
        await expect(editModal).toBeHidden();
        await expect(page).toHaveURL(/\/settings\/stages/);
    });

    test('Stage edit action handles double click safely without duplicate modals @settings @stage', async ({ auditedPage: page }) => {
        await page.goto('/settings/stages');

        const row = page.locator('table tbody tr').first();
        const editBtn = row.locator('button:has(i.bi-pencil-square)').first();

        await editBtn.dblclick();

        const editModal = page.locator('#editStageModal');
        await expect(editModal).toBeVisible();

        const allModals = await page.locator('#editStageModal').count();
        expect(allModals).toBe(1);

        const cancelBtn = editModal.locator('button.btn.soft').first();
        await cancelBtn.click();
        await expect(editModal).toBeHidden();
    });

    test('Stage edit button is isolated and does not trigger fields or delete actions @settings @stage', async ({ auditedPage: page }) => {
        await page.goto('/settings/stages');

        const row = page.locator('table tbody tr:has-text("غير مهتم")').first();
        const editBtn = row.locator('button:has(i.bi-pencil-square)').first();

        await editBtn.click();

        // Modal opens
        const editModal = page.locator('#editStageModal');
        await expect(editModal).toBeVisible();

        // URL must NOT have navigated to fields
        expect(page.url()).not.toContain('/fields');

        // Safe delete modal must NOT be open
        const deleteModal = page.locator('#safeDeleteStageModal');
        await expect(deleteModal).toBeHidden();

        // Close
        const cancelBtn = editModal.locator('button.btn.soft').first();
        await cancelBtn.click();
        await expect(editModal).toBeHidden();
    });
});
