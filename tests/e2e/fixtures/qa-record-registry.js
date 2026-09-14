import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const REGISTRY_FILE = path.join(__dirname, '..', '.auth', 'qa-registry.json');

class QaRecordRegistry {
    constructor() {
        this.runId = process.env.CRM_E2E_RUN_ID || `E2E_${new Date().toISOString().replace(/[-:T]/g, '').slice(0, 14)}_${Math.random().toString(36).substring(2, 8)}`;
        this.leadIds = new Set();
        this.load();
    }

    getRunId() {
        return this.runId;
    }

    generateLeadName(sequence = 1) {
        return `${this.runId}_LEAD_${String(sequence).padStart(3, '0')}`;
    }

    generatePhone(sequence = 1) {
        // Safe generated phone format 059 + 7 unique digits
        const suffix = Math.floor(1000000 + Math.random() * 9000000).toString();
        return `059${suffix.slice(0, 7)}`;
    }

    registerLeadId(id) {
        if (id) {
            const numId = Number(id);
            if (!isNaN(numId) && numId > 0) {
                this.leadIds.add(numId);
                this.save();
            }
        }
    }

    getRegisteredLeadIds() {
        return Array.from(this.leadIds);
    }

    save() {
        try {
            const dir = path.dirname(REGISTRY_FILE);
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
            }
            const data = {
                runId: this.runId,
                leadIds: Array.from(this.leadIds),
                updatedAt: new Date().toISOString()
            };
            fs.writeFileSync(REGISTRY_FILE, JSON.stringify(data, null, 2), 'utf-8');
        } catch (err) {
            console.error('[QA Registry] Failed to save registry:', err.message);
        }
    }

    load() {
        try {
            if (fs.existsSync(REGISTRY_FILE)) {
                const content = fs.readFileSync(REGISTRY_FILE, 'utf-8');
                const parsed = JSON.parse(content);
                if (Array.isArray(parsed.leadIds)) {
                    parsed.leadIds.forEach(id => this.leadIds.add(id));
                }
            }
        } catch (err) {
            // Ignore load failures
        }
    }
}

export const registry = new QaRecordRegistry();
