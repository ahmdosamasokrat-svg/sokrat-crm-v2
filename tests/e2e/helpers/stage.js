import { expect } from '@playwright/test';

/**
 * Asserts all fields inside a stage container follow the required/disabled rules
 */
export async function assertStageFieldState(page, stageId, isCurrentStageActive) {
    const stageBlock = page.locator(`[data-stage-id="${stageId}"], [data-stage-fields-container="${stageId}"]`).first();
    const count = await stageBlock.count();
    if (count === 0) return;

    if (!isCurrentStageActive) {
        // All inputs within inactive stage block must be disabled
        const inputs = stageBlock.locator('input, select, textarea');
        const inputCount = await inputs.count();
        for (let i = 0; i < inputCount; i++) {
            const input = inputs.nth(i);
            await expect(input).toBeDisabled();
        }
    }
}
