/**
 * Setup controlled delay or simulation for specific paths
 */
export async function delayRequests(page, urlPattern, delayMs = 1500) {
    await page.route(urlPattern, async route => {
        await new Promise(resolve => setTimeout(resolve, delayMs));
        await route.continue();
    });
}

export async function abortRequests(page, urlPattern) {
    await page.route(urlPattern, async route => {
        await route.abort('failed');
    });
}
