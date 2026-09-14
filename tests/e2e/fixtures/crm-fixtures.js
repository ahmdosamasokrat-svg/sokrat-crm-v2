import { test as base, expect } from '@playwright/test';
import { registry } from './qa-record-registry.js';
import { extractLeadIdFromUrl } from '../helpers/lead.js';
import { cleanRegisteredLeads } from '../helpers/cleanup.js';

export const test = base.extend({
    // Attach custom page with network / console auditing
    auditedPage: async ({ page }, use, testInfo) => {
        const consoleErrors = [];
        const pageErrors = [];
        const failedRequests = [];

        // Harmless console messages whitelist
        const harmlessPatterns = [
            /favicon\.ico/i,
            /Download the Vue Devtools/i,
            /\[vite\] connecting/i,
            /\[vite\] connected/i
        ];

        page.on('console', msg => {
            if (msg.type() === 'error') {
                const text = msg.text();
                const isHarmless = harmlessPatterns.some(p => p.test(text));
                if (!isHarmless) {
                    consoleErrors.push({
                        text: text,
                        location: msg.location()
                    });
                }
            }
        });

        page.on('pageerror', err => {
            pageErrors.push(err.message);
        });

        page.on('response', resp => {
            const status = resp.status();
            const url = resp.url();
            // Collect unexpected 4xx and 5xx (ignore expected 422 validation and 404 on optional resources)
            if (status >= 400 && status !== 422 && status !== 404) {
                try {
                    const parsedUrl = new URL(url);
                    failedRequests.push({
                        method: resp.request().method(),
                        pathname: parsedUrl.pathname,
                        status: status
                    });
                } catch (e) {}
            }
        });

        // Register hook to inspect response redirects or lead creations
        page.on('response', async resp => {
            if (resp.request().method() === 'POST') {
                const url = resp.url();
                const status = resp.status();
                if (status === 302 || status === 201) {
                    const loc = resp.headers()['location'];
                    if (loc) {
                        const id = extractLeadIdFromUrl(loc);
                        if (id) {
                            registry.registerLeadId(id);
                        }
                    }
                }
            }
        });

        await use(page);

        // Fail-safe post-test check for uncaught fatal page errors
        if (pageErrors.length > 0) {
            console.error(`[AUDIT] Page errors during ${testInfo.title}:`, pageErrors);
        }
    },
    qaRegistry: async ({}, use) => {
        await use(registry);
    }
});

export { expect };
