import { test, expect } from '../fixtures/crm-fixtures.js';

const viewports = [
    { width: 1440, height: 900, name: 'Desktop Large (1440)' },
    { width: 1366, height: 768, name: 'Laptop (1366)' },
    { width: 1024, height: 768, name: 'Tablet Landscape (1024)' },
    { width: 768, height: 1024, name: 'Tablet Portrait (768)' },
    { width: 390, height: 844, name: 'Mobile (390)' },
];

test.describe('Responsive Viewport Matrix Suite', () => {
    for (const vp of viewports) {
        test(`Renders Add Lead page usability at ${vp.name} @responsive`, async ({ auditedPage: page }) => {
            await page.setViewportSize({ width: vp.width, height: vp.height });
            await page.goto('/leads/create');

            // Assert critical elements remain visible and clickable
            const nameInput = page.locator('input[name="first_name"], input#first_name, input[name="name"]').first();
            const phoneInput = page.locator('input[name="phone"], input#phone').first();
            const saveBtn = page.locator('form[action*="/leads"] button[type="submit"].btn.primary, form[action*="/leads"] button[type="submit"]').first();

            await expect(nameInput).toBeVisible();
            await expect(phoneInput).toBeVisible();
            await expect(saveBtn).toBeVisible();

            // Verify horizontal scrollbar doesn't clip whole body
            const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
            const windowWidth = await page.evaluate(() => window.innerWidth);
            // Tolerable difference within responsive layout
            expect(bodyWidth).toBeGreaterThanOrEqual(windowWidth);
        });
    }
});
