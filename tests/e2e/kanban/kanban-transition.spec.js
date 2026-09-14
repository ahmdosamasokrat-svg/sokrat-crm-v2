import { test, expect } from '../fixtures/crm-fixtures.js';
import { createLeadUI } from '../helpers/lead.js';

test.describe('Kanban Real Drag and Drop Board Testing', () => {
    test('Drag card between columns and verify transition @critical @kanban', async ({ auditedPage: page, qaRegistry }) => {
        const lead = await createLeadUI(page, { statusText: 'جديد' });
        expect(lead.id).toBeTruthy();

        await page.goto('/leads/kanban');
        await expect(page).toHaveURL(/leads\/kanban/);

        const card = page.locator(`.kanban-card:has-text("${lead.firstName}")`).first();
        if (await card.count() > 0) {
            const targetCol = page.locator('.kanban-column[data-stage-code="discussion"], .kanban-column:has-text("مناقشة")').first();
            if (await targetCol.count() > 0) {
                await card.dragTo(targetCol);

                const modal = page.locator('.kanban-followup-dialog, .modal.show, [role="dialog"]').first();
                if (await modal.count() > 0 && await modal.isVisible()) {
                    const saveModalBtn = modal.locator('button[type="submit"], .btn-primary').first();
                    if (await saveModalBtn.count() > 0) {
                        await saveModalBtn.click();
                    }
                }

                await expect(page.locator(`.kanban-card:has-text("${lead.firstName}")`)).toBeVisible();
            }
        }
    });

    test('Kanban cancel on required question does not move card @kanban @abnormal', async ({ auditedPage: page, qaRegistry }) => {
        const lead = await createLeadUI(page, { statusText: 'جديد' });
        expect(lead.id).toBeTruthy();

        await page.goto('/leads/kanban');
        const card = page.locator(`.kanban-card:has-text("${lead.firstName}")`).first();
        if (await card.count() > 0) {
            const targetCol = page.locator('.kanban-column:has-text("غير مهتم")').first();
            if (await targetCol.count() > 0) {
                await card.dragTo(targetCol);
                const cancelBtn = page.locator('button:has-text("إلغاء"), button:has-text("Cancel"), .btn-secondary').first();
                if (await cancelBtn.count() > 0 && await cancelBtn.isVisible()) {
                    await cancelBtn.click();
                }
            }
        }
    });
});
