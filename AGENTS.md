# PROJECT KNOWLEDGE BASE

**Generated:** 2026-09-13
**Commit:** 12cfd58
**Branch:** main

## OVERVIEW
SokratCRM V2 is a Laravel 13 CRM on PHP 8.3 & MySQL running on Ubuntu 24.04. It centralizes lead pipelines, dynamic stage forms, quotations, task scheduling, campaigns, RBAC, VoIP, and notifications.

## STRUCTURE
```
sokrat-crm-v2/
├── app/
│   ├── Http/Controllers/   # Web route handlers & settings controllers (no separate API tree)
│   ├── Models/             # 26 Eloquent entities with accessibleTo/visibleTo scopes
│   ├── Services/           # Transactional domain logic (transitions, distribution, notifications, VoIP)
│   ├── Support/            # CrmDatabaseGuard, StageFieldSchema, dynamic filters
│   ├── Security/           # CrmPermission enum & LeadAssignment logic
│   └── Policies/           # Authorization policies combining permissions with record scopes
├── database/migrations/    # Schema definitions for CRM pipelines, dynamic fields, RBAC
├── resources/views/        # Blade templates with bilingual Arabic/English RTL/LTR shell
├── public/                 # Assets, crm-notifications.js, quotation-generator module
├── routes/                 # web.php (all authenticated web routes) & console.php (notification scheduler)
└── tests/                  # PHPUnit test suites guarded by strict testing DB isolation
```

## WHERE TO LOOK
| Task | Location | Notes |
|------|----------|-------|
| Lead stage transitions | `app/Services/LeadTransitionService.php` | Atomic row-locked state machine with history & validation |
| Lead access & scoping | `app/Models/Lead.php` | `accessibleTo()` scope restricts non-admin access |
| Dynamic stage fields | `app/Support/StageFieldSchema.php` | Normalizes canonical/custom stage field definitions |
| RBAC & permissions | `app/Security/CrmPermission.php` | Enum-backed permissions; gates registered in AppServiceProvider |
| Lead distribution | `app/Services/LeadDistributionService.php` | 6 distribution strategies (round-robin, weighted, etc.) |
| Notification scheduler | `app/Console/Commands/DispatchCrmNotifications.php` | Scheduled every minute via `routes/console.php` |
| VoIP & MicroSIP | `app/Services/VoipService.php` | External PBX integration & `tel:` handler routes |
| Web routing & security | `routes/web.php` | Monolithic web routes with `auth`, `active`, and `can:` guards |
| Database safety guard | `app/Support/CrmDatabaseGuard.php` | Hard restriction to `sokrat_crm_v2` / `sokrat_crm_v2_testing` |

## CODE MAP
| Symbol | Type | Location | Refs | Role |
|--------|------|----------|------|------|
| `LeadTransitionService` | Class | `app/Services/LeadTransitionService.php` | 30 | Atomic lead status & pipeline transition engine |
| `CrmPermission` | Enum | `app/Security/CrmPermission.php` | 201 | Central CRM permission definitions and module metadata |
| `Lead` | Model | `app/Models/Lead.php` | 100+ | Primary CRM lead aggregate with `accessibleTo()` scope |
| `User` | Model | `app/Models/User.php` | 80+ | Authenticated actor, RBAC roles, stage restriction logic |
| `StageFieldSchema` | Class | `app/Support/StageFieldSchema.php` | 18 | Dynamic pipeline stage custom field engine |
| `LeadAssignment` | Class | `app/Security/LeadAssignment.php` | 14 | Group assignment resolution and permission checking |
| `CrmDatabaseGuard` | Class | `app/Support/CrmDatabaseGuard.php` | 12 | Prevents execution outside designated CRM databases |
| `ReminderPlanner` | Class | `app/Services/Notifications/ReminderPlanner.php` | 14 | Plans lead & event notification occurrences |
| `NotificationDispatcher` | Class | `app/Services/Notifications/NotificationDispatcher.php` | 10 | Dispatches pending notifications across channels |
| `VoipService` | Class | `app/Services/VoipService.php` | 12 | VoIP server API client and session ticket generator |
| `EmployeeReportService` | Class | `app/Services/Reports/EmployeeReportService.php` | 3 | Multi-metric employee performance report aggregator |

## CONVENTIONS
- Strict types: `declare(strict_types=1);` and full parameter/return typing across all PHP classes.
- Scoped Eloquent: Non-admin queries must use `accessibleTo($user)` or `visibleTo($user)` scopes.
- Atomic mutation: Use `DB::transaction()` and `lockForUpdate()` for competing state changes.
- Bilingual UI: All user copy in `lang/ar*` and `lang/en*`; use logical CSS (`inline-start`/`inline-end`).
- Single route surface: No `routes/api.php`; JSON responses served via session-authenticated web routes.

## ANTI-PATTERNS (THIS PROJECT)
- Unrestricted Queries: Never bypass `Lead::accessibleTo()` for non-super-admins.
- Foreign Database: Never run on any DB other than `sokrat_crm_v2` (prod) or `sokrat_crm_v2_testing` (tests).
- Inactive User Action: Inactive users cannot authenticate or hold active permissions.
- Direct Lead Deletion: Never delete leads directly; use `LeadTrashService` with audit trail.
- Client-only Authorization: Direct routes must enforce permissions and policies, not just sidebar hiding.
- Untracked Field Columns: Do not bind arbitrary columns in dynamic stage fields (`id`, `password` blocked).

## UNIQUE STYLES
- Interface Typography: Tajawal font family across all Arabic and English UI.
- Color Semantics: Red accent for primary actions; green strictly reserved for positive/live states.
- Dynamic Pipeline Values: Custom fields stored as typed records in `lead_stage_field_values`.

## COMMANDS
```bash
composer setup          # Prepare app: install deps, migrate DB, build Vite
composer dev            # Run concurrently: serve, queue:listen, pail, vite dev
composer test           # Clear cache and run PHPUnit test suite
npm run build           # Compile Tailwind 4 and Vite frontend assets
php artisan test --filter {TestName} # Run focused test on sokrat_crm_v2_testing
```

## NOTES
- Testing DB: `tests/TestCase.php` hard-fails if DB is not `sokrat_crm_v2_testing`.
- CLI Notification Schedule: `routes/console.php` requires external cron/systemd executing `schedule:run`.
- Queue Listener: Queued notifications and digests require an active `queue:listen` or worker process.
- MicroSIP: Windows `tel:` handler integration requires IP/host configuration in `open-crm-caller.cmd`.

