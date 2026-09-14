# Sokrat CRM v2 Playwright E2E QA Lab

This directory contains the real end-to-end browser automation suite for CRM v2 built on Playwright.

## Prerequisites

1. Node.js >= 20
2. Installed dependencies (`@playwright/test`)
3. Chromium browser binary installed in Playwright cache (`npx playwright install chromium`)

## Environment Variables

The QA suite requires the following environment variables:

- `CRM_E2E_BASE_URL`: The origin URL for the CRM (e.g. `http://localhost` or `http://127.0.0.1`).
- `CRM_E2E_EMAIL` or `CRM_E2E_USERNAME`: Username or email of dedicated QA user (e.g. `admin`).
- `CRM_E2E_PASSWORD`: Password for the dedicated QA user.
- `CRM_E2E_RUN_ID` (Optional): Custom run identifier. Defaults to auto-generated timestamp run ID.

> **CRITICAL SECURITY NOTE:** Never commit credentials or test URLs into git or config files.

## Running Tests

### 1. Authenticate and Run Smoke Tests:
```bash
CRM_E2E_BASE_URL=http://localhost CRM_E2E_USERNAME=admin CRM_E2E_PASSWORD="<password>" npx playwright test --grep @smoke
```

### 2. Run Critical CRM Flows:
```bash
CRM_E2E_BASE_URL=http://localhost CRM_E2E_USERNAME=admin CRM_E2E_PASSWORD="<password>" npx playwright test --grep @critical
```

### 3. Run Abnormal and Resilience Scenarios:
```bash
CRM_E2E_BASE_URL=http://localhost CRM_E2E_USERNAME=admin CRM_E2E_PASSWORD="<password>" npx playwright test --grep @abnormal
```

### 4. Run Entire E2E Suite:
```bash
CRM_E2E_BASE_URL=http://localhost CRM_E2E_USERNAME=admin CRM_E2E_PASSWORD="<password>" npx playwright test
```

### 5. Visible (Headed) Debugging:
```bash
CRM_E2E_BASE_URL=http://localhost CRM_E2E_USERNAME=admin CRM_E2E_PASSWORD="<password>" npx playwright test --headed
```

## Inspecting Artifacts & Reports

### HTML Report:
```bash
npx playwright show-report tests/e2e/playwright-report
```

### Trace Viewer:
When a test fails, Playwright saves an execution trace in `test-results/`.
Inspect the trace using:
```bash
npx playwright show-trace test-results/<test-directory>/trace.zip
```

## QA Data Safety & Registry
All created test leads are tagged with a unique run ID and tracked in `tests/e2e/.auth/qa-registry.json`.
Post-test cleanup removes **ONLY** exact recorded primary IDs from the current test run. No wildcard queries or `LIKE 'QA%'` deletions are permitted.
