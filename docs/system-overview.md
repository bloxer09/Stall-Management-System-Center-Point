# System Overview

## Scope and evidence

Audit completed 2026-07-25 against the current working tree: Laravel application code, routes, migrations, models, Livewire and Blade views, configuration, tests, build files, README, Git history, and deployment-related files. `php artisan test` passed 20 tests, PHP lint passed, and `npm.cmd run build` completed. Composer validation and audit did not pass cleanly.

The repository is mid-migration from a custom `src/` MVC application to Laravel: legacy files are deleted while Laravel directories are untracked. No CI workflow, deployment manifest, container configuration, backup runbook, monitoring integration, or production environment was found. The ignored real `.env`, payment provider, mail/SMS providers, and production infrastructure were not inspected or exercised.

## Product and modules

STALLM manages commercial stalls, tenants, leases, invoices, PayMongo checkout, utility readings, support tickets, notifications, applications, reports, and staff administration. Laravel 11.55.0 is installed with Livewire 3.8.2, Tailwind/DaisyUI, MySQL/MariaDB, Dompdf, Socialite, Mailjet, Semaphore, and PayMongo.

| Area | Primary implementation |
| --- | --- |
| Public catalogue and application | `routes/web.php`, `pages/home`, `Public/StallReservation` |
| Identity | Livewire login/register, OAuth controller, role and 2FA middleware |
| Administration | `Admin/*` Livewire components and `/admin/*` routes |
| Tenant portal | `User/*` Livewire components and `/user/*` routes |
| Billing | `Payment`, `InvoicesCheckout`, `PayMongoService`, webhook controller |
| Operations | artisan invoice/reminder/expiry commands and `routes/console.php` |

## Operational commands

```bash
composer install       # currently blocked by the lock/manifest mismatch
php artisan migrate --seed
php artisan test       # 20 passing tests in the audited workspace
npm.cmd run build      # production Vite build; use npm.cmd on this Windows host
php artisan schedule:run
```

## Current blockers

1. `composer.json` requires Laravel 13 but `composer.lock` and `vendor` install Laravel 11.55.0; a clean, reproducible release cannot be produced.
2. Several admin writes/exports call missing `AuditLog::log()`, causing a runtime exception when invoked.
3. Tenant ticket and invoice actions do not consistently scope client-supplied IDs to the authenticated user.

See [roadmap.md](roadmap.md) for prioritised remediation and [change-log.md](change-log.md) for this audit-only change.
