# Repository Guidelines

## Project Overview

SokratCRM V2 is a Laravel 13 CRM for authenticated, active employees. It centralizes lead lifecycle and pipeline management, Kanban workflows, quotations, follow-ups/tasks, campaigns, reports, imports/exports, notifications, RBAC, VoIP, and technical-support network status. The production target is Ubuntu 24.04.4 with Apache and MySQL/MariaDB.

The UI is bilingual Arabic/English. Preserve the existing RTL/LTR behavior, shared shell, and design system when changing operational pages; see `PRODUCT.md` and `DESIGN.md`.

## Architecture & Data Flow

- `public/index.php` and `artisan` are the HTTP and CLI entry points. Laravel is configured in `bootstrap/app.php` with `routes/web.php` and `routes/console.php`.
- `routes/web.php` contains login/language routes, a throttled public Twilio status webhook, and the main authenticated route group. Most application routes require both `auth` and `active`, then apply permission middleware such as `can:leads.view`.
- `SetLocaleMiddleware` resolves Arabic/English locale behavior; `EnsureUserIsActive` prevents disabled accounts from continuing to use authenticated sessions.
- Controllers validate requests, authorize, query Eloquent models/repositories, and return Blade views, redirects, or JSON for web interactions. There is no separate API route tree; JSON endpoints are session-authenticated web routes.
- Domain logic belongs in services/support classes rather than growing controllers. Lead transitions use `LeadTransitionService` and database transactions with row locking, validation, canonical-field updates, campaign synchronization, and history/value persistence.
- Models define relationships, scopes, policies, and access boundaries. `Lead::accessibleTo()` and related permission logic are security-critical; do not replace scoped queries with unrestricted model access.
- Persistence is MySQL-backed. Migrations establish users/cache/jobs, pipeline stages/statuses, leads, access control, dynamic stage fields, documents/trash, calendar, campaigns, quotations, notifications, and technical-support data. `CrmDatabaseGuard` rejects an unexpected database in core flows.
- Notifications are planned on the minute, deduplicated, claimed transactionally, and delivered through queued jobs. Channel settings live in `config/crm_notifications.php`; external integrations include Tailscale (`TailscaleStatusService`) and VoIP (`VoipService`). Infrastructure unavailability should render a recoverable state, not crash the page.
- Blade is the primary frontend boundary. Shared pieces live under `resources/views/partials/` (sidebar, topbar, notification center); page-specific CSS/JavaScript is often inline or under `public/`. `resources/js/app.js` currently has no application behavior, so do not assume a conventional SPA entry point.

## Key Directories

- `app/Http/Controllers/`: HTTP orchestration for leads, dashboard, calendar, campaigns, quotations, reports, notifications, VoIP, technical support, and settings.
- `app/Http/Requests/`: request validation, including settings and campaign forms.
- `app/Models/`: Eloquent entities, relationships, casts, scopes, and model-level access behavior.
- `app/Services/`, `app/Services/Reports/`: transactional/domain workflows and reporting.
- `app/Support/`, `app/Security/`, `app/Policies/`: reusable schema/filter logic, permission and assignment boundaries, and authorization.
- `app/Jobs/`, `app/Notifications/`, `app/Mail/`, `app/Observers/`: deferred notifications and model-driven side effects.
- `database/migrations/`, `database/seeders/`, `database/factories/`: schema, CRM/RBAC bootstrap data, and test factories.
- `resources/views/`: Blade layouts, pages, and shared partials; `resources/css/`: Tailwind entry styles.
- `public/`: published CSS/JS and standalone modules such as `crm-notifications.js` and `quotation-generator/`.
- `routes/`: HTTP and scheduled CLI routes.
- `tests/Unit/`, `tests/Feature/`: PHPUnit unit and integration/HTTP/security coverage.

## Development Commands

```bash
# Install PHP dependencies and prepare a local app
composer setup

# Run the application, queue listener, logs, and Vite together
composer dev

# Run frontend production build
npm run build

# Run Vite alone
npm run dev

# Clear config and run the Laravel test suite
composer test

# Run a focused test or suite
php artisan test tests/Feature/AuthenticationTest.php
php artisan test tests/Feature/Security/AuthenticationAndPermissionEnforcementTest.php
php artisan test --filter test_name
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Feature
```

`composer setup` expects `.env`, a reachable database, and performs migrations. The production-oriented `install.sh` is root-only, Ubuntu-24.04-specific, configures Apache/MySQL, seeds the application, and is destructive when paired with `uninstall.sh`; use it only for deployment, not routine local development.

## Code Conventions & Common Patterns

- Follow PSR-4 namespaces (`App\\` → `app/`, `Tests\\` → `tests/`), PSR-style class names, and existing Laravel naming. Newer code commonly uses `declare(strict_types=1)`, typed parameters/returns, constructor injection, and small typed value/config methods.
- Prefer route names with the existing `v2.*` convention, Eloquent scopes such as `accessibleTo`/`visibleTo`, camelCase relationships, policies, and form request validation.
- Keep authorization at the query and policy boundaries. Preserve super-admin behavior and group/assignment/pipeline-stage scope rules; test both allowed and denied paths.
- Use `DB::transaction()` and `lockForUpdate()` for state transitions or competing writes. Observers that trigger notifications should preserve after-commit behavior and deduplication.
- Use Laravel fakes (`Storage`, `UploadedFile`, `Process`, `Queue`, `Mail`, `Http`) and Carbon test clocks in tests. Avoid live external services in tests.
- Return JSON only where the existing web endpoint expects it; notification clients use same-origin credentials and CSRF protection. Keep webhook throttling/CSRF exceptions intentional.
- Localization uses `lang/en*` and `lang/ar*`; add both locales for user-facing copy. Use logical CSS properties (`inline-start`/`inline-end`) rather than hard-coded left/right. Follow `DESIGN.md`: Tajawal for interface text, red for interaction emphasis, green only for positive/live state, and accessible focus/keyboard behavior.
- `.editorconfig` specifies UTF-8, LF, final newlines, trimmed trailing whitespace, four-space indentation (two for YAML). Do not introduce a second formatter or frontend framework without an explicit repository-level decision.

## Important Files

- `composer.json`: PHP dependencies and `setup`, `dev`, and `test` workflows.
- `package.json`, `package-lock.json`, `vite.config.js`: ESM Node tooling, Vite/Tailwind build, and locked frontend versions.
- `bootstrap/app.php`: middleware, route registration, CSRF exception, and JSON exception behavior.
- `routes/web.php`: complete web route and middleware/permission surface.
- `routes/console.php`: scheduled `crm:notifications:dispatch` command.
- `app/Services/LeadTransitionService.php`: atomic lead stage/status transition workflow.
- `app/Models/Lead.php`, `app/Models/User.php`, `app/Security/CrmPermission.php`, `app/Policies/`: access and domain rules.
- `app/Support/StageFieldSchema.php`: dynamic pipeline field rules and persistence support.
- `config/crm.php`, `config/crm_notifications.php`, `config/services.php`, `config/voip.php`: application, notification, and external-service settings.
- `.env.example`: required environment names and safe local defaults; never commit `.env` or credentials.
- `phpunit.xml`, `tests/TestCase.php`: test suites and database safety guard.
- `README.md`: deployment/install/uninstall and MicroSIP integration instructions.

## Runtime/Tooling Preferences

- Required PHP runtime: 8.3. Laravel framework: 13.x. PHP dependencies are installed with Composer 2.x.
- Frontend tooling uses Node.js/npm, ESM, Vite 8, Laravel Vite plugin, and Tailwind CSS 4. The installer provisions Node 20 LTS; the locked Vite toolchain requires a compatible modern Node release (Node 20.19+ or Node 22.12+).
- Development services are PHP `artisan serve`, `queue:listen`, `pail`, and Vite via `composer dev`. Production deployment uses Apache `public/` as the document root and `mod_rewrite`.
- MySQL is the normal runtime database. `.env.example` defaults to `sokrat_crm_v2`; do not point development or tests at customer/live data.
- No repository scripts currently define ESLint, Prettier, TypeScript, or a dedicated lint/typecheck command. Laravel Pint is installed as a dev dependency; run it only when deliberately formatting PHP changes and follow existing style first.
- Do not edit generated/runtime directories (`vendor/`, `node_modules/`, `public/build/`, `storage/`) or commit `.env` files.

## Testing & QA

- PHPUnit 12 is configured through `phpunit.xml`; suites are `tests/Unit` and `tests/Feature`, with `app/` as the source include. Tests are predominantly feature/security tests and use `RefreshDatabase`; a small number use `DatabaseTransactions`.
- Tests require a dedicated MySQL database named exactly `sokrat_crm_v2_testing`. `tests/TestCase.php` refuses to run unless the environment is `testing`, the driver is MySQL, and `SELECT DATABASE()` confirms that exact database. Configure `.env.testing`/PHPUnit values and migrate it before running tests.
- The test bootstrap seeds `CrmAccessControlSeeder` once per process and sets READ COMMITTED plus a lock wait timeout. Do not weaken these safety checks or run tests against production data.
- Prefer a targeted test while iterating, then `composer test` for the full suite when practical. Test observable HTTP/view/JSON/database behavior, authorization boundaries, state transitions, and failure recovery—not implementation details.
- Useful patterns: `RefreshDatabase`, named-route requests, `assertDatabase*`, `Storage::fake`, `Process::fake` for Tailscale, and `Queue`/`Mail`/`Http` fakes for notifications. Test Arabic and English behavior when changing localized UI or routes.
- There is no configured browser/e2e suite, CI workflow, coverage threshold/report, or JavaScript test script. `tests/verify_employee_reports.php` is a standalone 21-check diagnostic, not PHPUnit discovery; only run it deliberately against the isolated test database.
