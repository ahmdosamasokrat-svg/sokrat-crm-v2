import { expect } from '@playwright/test';

/**
 * Validates that there are no duplicate controls with identical name/id
 */
export async function assertNoDuplicateControls(page, selector) {
    const count = await page.locator(selector).count();
    expect(count).toBeLessThanOrEqual(1);
}

/**
 * Validates that an element is completely disabled and not submitted
 */
export async function assertInputDisabledAndNotRequired(locator) {
    await expect(locator).toBeDisabled();
    const isRequired = await locator.getAttribute('required');
    expect(isRequired).toBeFalsy();
}
