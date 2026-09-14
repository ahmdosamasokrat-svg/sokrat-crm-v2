import { test, expect } from '../fixtures/crm-fixtures.js';

test.describe('Deterministic Randomized Stage Journeys', () => {
    test('Runs deterministic pseudorandom stage interactions @randomized', async ({ auditedPage: page }) => {
        const SEED = 20260913;
        console.log(`Running randomized stage test with fixed SEED: ${SEED}`);

        // Linear congruential generator for reproducible deterministic randomness
        let state = SEED;
        const nextRand = () => {
            state = (state * 1664525 + 1013904223) % 4294967296;
            return state / 4294967296;
        };

        await page.goto('/leads/create');

        const statusSelect = page.locator('select[name="lead_status_id"], select#leadStatus').first();
        const stageOptions = await statusSelect.locator('option').allInnerTexts();
        const validOptions = stageOptions.filter(t => t && t.trim().length > 0 && !t.includes('اختر') && !t.includes('Choose'));

        for (let i = 0; i < 5; i++) {
            const randomIndex = Math.floor(nextRand() * validOptions.length);
            const chosenStage = validOptions[randomIndex];

            await statusSelect.selectOption({ label: chosenStage });
            await page.waitForTimeout(100);

            // Assert no page crash
            await expect(page.locator('body')).toBeVisible();
        }
    });

    test('Safe Monkey-Lite abnormal interaction on leads list @randomized @abnormal', async ({ auditedPage: page }) => {
        await page.goto('/leads');

        const safeButtons = [
            'button:has-text("تصفية")',
            'button:has-text("Filter")',
            'a[href*="/leads/create"]',
            'a[href*="/leads/kanban"]'
        ];

        // Perform 10 safe fast interactions
        for (let i = 0; i < 6; i++) {
            await page.evaluate(() => window.scrollBy(0, 200));
            await page.waitForTimeout(50);
            await page.evaluate(() => window.scrollBy(0, -200));
            await page.waitForTimeout(50);
        }

        await expect(page.locator('body')).toBeVisible();
    });
});
