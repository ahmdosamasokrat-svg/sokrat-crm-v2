import { expect } from '@playwright/test';
import { registry } from '../fixtures/qa-record-registry.js';
import { execSync } from 'child_process';

export function extractLeadIdFromUrl(urlStr) {
    if (!urlStr) return null;
    const match = urlStr.match(/\/leads\/(\d+)/);
    return match ? Number(match[1]) : null;
}

/**
 * Creates a Lead via UI navigation.
 * Returns { id, name, phone }
 */
export async function createLeadUI(page, options = {}) {
    const runId = registry.getRunId();
    const seq = options.sequence || Math.floor(Math.random() * 900) + 100;
    const leadFirstName = options.firstName || `${runId}_LEAD_${String(seq).padStart(3, '0')}`;
    const phone = options.phone || registry.generatePhone(seq);

    await page.goto('/leads/create');
    await expect(page).toHaveURL(/leads\/create/);

    const nameInput = page.locator('input[name="first_name"], input#first_name, input[name="name"]').first();
    const phoneInput = page.locator('input[name="phone"], input#phone').first();
    const sourceInput = page.locator('input[name="source"], input#source').first();

    await nameInput.fill(leadFirstName);
    await phoneInput.fill(phone);
    if (await sourceInput.count() > 0 && await sourceInput.isVisible()) {
        await sourceInput.fill('E2E QA Test Source');
    }

    if (options.statusId || options.statusText) {
        const statusSelect = page.locator('select[name="lead_status_id"], select#leadStatus').first();
        if (options.statusId) {
            await statusSelect.selectOption(String(options.statusId));
        } else if (options.statusText) {
            await statusSelect.selectOption({ label: options.statusText });
        }
    }

    if (typeof options.beforeSubmit === 'function') {
        await options.beforeSubmit(page);
    }

    const saveBtn = page.locator('form button[type="submit"].btn.primary, form button[type="submit"]:has-text("حفظ"), form button[type="submit"]:has-text("Save")').first();
    await Promise.all([
        page.waitForURL(url => !url.pathname.includes('/create'), { timeout: 15000 }),
        saveBtn.click()
    ]);

    let leadId = null;
    try {
        const out = execSync(`php artisan tinker --execute="\\$l = \\App\\Models\\Lead::where('first_name', '${leadFirstName}')->first(['id']); echo \\$l ? \\$l->id : '';"`, {
            cwd: '/var/www/html/crm-v2',
            encoding: 'utf-8'
        }).trim();
        if (out && !isNaN(Number(out))) {
            leadId = Number(out);
            registry.registerLeadId(leadId);
        }
    } catch (e) {}

    return {
        id: leadId,
        firstName: leadFirstName,
        name: leadFirstName,
        phone: phone,
        url: page.url()
    };
}
