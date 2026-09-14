import { registry } from '../fixtures/qa-record-registry.js';
import { execSync } from 'child_process';

/**
 * Deletes ONLY the exact registered lead IDs created during THIS test run.
 * Strictly no wildcards, no LIKE 'QA%', no bulk deletes without exact IDs.
 */
export function cleanRegisteredLeads() {
    const ids = registry.getRegisteredLeadIds();
    if (!ids || ids.length === 0) {
        return { cleanedCount: 0, cleanedIds: [] };
    }

    const safeIds = ids.filter(id => typeof id === 'number' && id > 0);
    if (safeIds.length === 0) {
        return { cleanedCount: 0, cleanedIds: [] };
    }

    const idListStr = safeIds.join(',');
    const phpCommand = `
        \\DB::transaction(function() {
            \\App\\Models\\Lead::whereIn('id', [${idListStr}])->forceDelete();
        });
        echo 'DELETED:' . count([${idListStr}]);
    `;

    try {
        const out = execSync(`php artisan tinker --execute="${phpCommand.replace(/"/g, '\\"')}"`, {
            cwd: '/var/www/html/crm-v2',
            encoding: 'utf-8'
        });
        return {
            cleanedCount: safeIds.length,
            cleanedIds: safeIds,
            rawOutput: out.trim()
        };
    } catch (err) {
        console.error('[CLEANUP ERROR] Failed to clean registered QA leads:', err.message);
        return {
            cleanedCount: 0,
            cleanedIds: safeIds,
            error: err.message
        };
    }
}
