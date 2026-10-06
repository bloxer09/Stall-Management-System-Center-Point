# System Implementation Plan

## Purpose and delivery rules

This plan turns the audit roadmap into safe, testable releases. It assumes one full-stack engineer; the estimates are working days and exclude external approvals, vendor onboarding, and historical data repair. Do not start visual redesign or feature expansion until the release blockers pass.

Every implementation pull request must state: purpose, affected files/data, authorization impact, migration and rollback plan, automated tests, and manual acceptance checks. Keep the current Laravel migration as a dedicated, reviewable baseline commit before mixing it with remediation work.

## Delivery sequence

```text
0 Baseline/release decision
        -> 1 Security and payment P0
        -> 2 Lease and billing data integrity
        -> 3 Authentication, files, and configuration
        -> 4 Operations and resilience
        -> 5 Performance and product-quality UX
        -> 6 Future product capabilities
```

Phases 2 and 3 can overlap after phase 1 passes. Phases 4 and 5 may run in parallel only after the new authorization and billing tests are stable.

## Phase 0 — Establish a releasable baseline (1–2 days)

### Work

1. Freeze the migration worktree into a branch/PR and inventory every intentional deletion from `src/`.
2. Decide and record one supported framework target:
   - remain on a patched Laravel 11 release, or
   - complete a tested Laravel 13 upgrade.
3. Regenerate and commit a matching `composer.lock`; remove the current manifest/lock disagreement.
4. Add baseline CI checks: `composer validate`, `composer audit --locked`, PHP lint, `php artisan test`, and `npm run build`.
5. Capture a staging database backup and record restore ownership before any data migration.

### Files / systems

`composer.json`, `composer.lock`, CI workflow (new), README/deployment instructions, and the Git migration branch. No application behavior should change in this phase.

### Exit criteria

- A clean clone can run `composer install` without changing dependencies.
- No high/critical advisory remains in the locked application dependency set, or an approved compensating control has an expiry date.
- CI runs the baseline checks on a pull request.
- A staging backup restore has been demonstrated.

### Rollback

Deploy the previous immutable release artifact and restore the lockfile with it. Do not use `composer update` directly in production.

## Phase 1 — Remove P0 security and runtime defects (5–8 days)

### 1A. Restore audit logging and safe analytics output (1 day)

Create one tested `AuditLog` recording API or update all callers to the existing `record()` signature. Replace raw Chart.js data output with Blade `@js` serialization; add a CSP-compatible chart initialization path. Add tests for every affected admin action and a regression test containing a script-breaking location name.

Expected touch points: `app/Models/AuditLog.php`, affected `app/Livewire/Admin/*` components, `resources/views/livewire/admin/analytics-dashboard.blade.php`, and new feature/component tests.

### 1B. Enforce authorization at the server boundary (2–3 days)

Define policies for tickets, ticket messages, payments, mappings/contracts, and applications. Scope every Livewire lookup through the authenticated principal or an explicit staff capability:

- tenant ticket creation may use only a tenant-owned active mapping;
- selection, read state, reply, attachment, and renewal actions verify ticket/mapping ownership;
- payment selection, aggregate totals, and checkout-session writes apply the same tenant + pending scope;
- staff capabilities are explicit, rather than treating all staff/admin behavior as interchangeable.

Expected touch points: new `app/Policies/*`, provider registration if required by the selected Laravel version, `TicketManager`, `InvoicesCheckout`, `MyLeases`, file/receipt controllers, navigation, and feature tests.

### 1C. Make payment confirmation fail closed (2–4 days)

Require a configured PayMongo webhook secret before accepting a production webhook. Verify signature and timestamp, parse only the provider's documented event shape, persist a unique provider event identifier, lock/select affected payment rows in a transaction, and transition only owned pending payments. Return idempotent success for an already-processed valid event. Add a controlled reconciliation command for unmatched events; do not silently mark transactions paid.

Expected touch points: `PayMongoWebhookController`, `PayMongoService`, `Payment` or a new webhook-event model, a forward migration, configuration validation, and webhook feature tests using valid, invalid, stale, replayed, unmatched, and multi-invoice payloads.

### Phase 1 exit criteria

- An unauthorised tenant receives 403/404 and cannot create/read/reply to another tenant's ticket, attachment, lease, or payment.
- Unsigned, invalid, stale, duplicate, and unmatched webhooks cannot alter payment state.
- A valid replayed webhook does not create duplicate state or receipts.
- Admin create/update/delete/export actions no longer call an undefined method.
- Stored-XSS regression test passes and browser CSP is verified in staging.

### Rollback

Authorization and XSS fixes are code-only. Webhook schema changes must be additive; retain the old event data, feature-flag the new handler, and use the reconciliation command for provider events received during rollback.

## Phase 2 — Establish lease and billing integrity (6–10 days)

### Work

1. Write a data-profile script/report before adding constraints: duplicate active mappings, overlapping leases, duplicated grid coordinates/readings, duplicate provider session IDs, and payment rows without a valid billing purpose.
2. Define a billing model decision record: retain `payments` as invoices, or introduce immutable invoices/line items and payment attempts. The recommended model separates invoice, line item, checkout attempt, provider event, and settlement.
3. Move lease allocation, termination, renewal approval, and monthly invoice generation into transaction-backed actions under `app/Actions` or focused services.
4. Add forward-only migrations after data cleanup:
   - query-shaped indexes for active lease scans and payment due-state queries;
   - unique floor-grid position rules;
   - provider event/session idempotency keys;
   - a billing-period identity that prevents duplicate monthly rent invoices.
5. Refactor the invoice command into an idempotent, set-oriented process; record counts, failures, and a run identifier.

### Files / systems

New migrations and tests; `StallMapping`, `Stall`, `Payment`; lease/payment Livewire components; `GenerateMonthlyInvoices`; seeders; potentially new action/value-object classes. The exact constraint implementation must be selected after profiling the target MySQL/MariaDB version—do not assume partial-index support.

### Exit criteria

- A transaction cannot create conflicting active leases or duplicate invoice/readings for the agreed period.
- Changing a lease/stall status is atomic and audited.
- Running invoice generation twice is safe and produces a reconciled report.
- `EXPLAIN` confirms new indexes serve scheduled invoice and overdue-payment queries.
- Historical duplicate/exception rows have an approved treatment and audit trail.

### Rollback

Use additive columns/indexes first, retain a backup and verification report, deploy code that reads both representations during backfill, then enforce constraints. A down migration must remove only newly added structures; never automatically delete finance history.

## Phase 3 — Harden identity, files, and configuration (5–7 days)

### Work

1. Select one login/register implementation. Remove or retire the parallel modal flow and unused form only after route-level behavior is covered by tests.
2. Apply Laravel rate limiting to login, registration, password reset, reservation submission, 2FA challenge, and webhook endpoints as appropriate.
3. Store hashed single-use OTPs with TTL, attempt count, resend cooldown, and audit metadata. Remove OTP values from logs. Decide whether 2FA is mandatory for admins, staff, or all users.
4. Restore a complete password-reset and email-verification flow if required; otherwise remove dead controller/table/claims from product documentation.
5. Move application documents and contracts to a private disk. Migrate existing files via manifest; serve them only through policy-protected responses. Make receipt verification token-based and disclose only the agreed minimum public data.
6. Remove the fallback `APP_KEY`, standardize configuration names, validate required production variables at deployment, and return opaque OAuth/provider failures to users.
7. Add HTTPS, HSTS, CSP, frame, referrer, and content-type policies at the approved application/edge layer. Disable Dompdf remote access unless an approved need remains; generate QR codes locally or through a vetted service.

### Exit criteria

- Brute-force/rate-limit, OTP expiry/replay, and resend tests pass.
- A public URL cannot retrieve a private contract/application; authorized owner and staff access still work.
- A missing application key or payment secret fails deployment health validation.
- Security headers are present in staging and do not break Livewire, Vite, payment redirects, or required OAuth callbacks.

### Rollback

Use staged secure-file copies and keep a short-lived authorized redirect mapping. Deploy CSP in report-only mode first. Preserve a break-glass, documented administrator recovery path while moving authentication flows.

## Phase 4 — Operational reliability and release engineering (5–8 days)

### Work

1. Introduce queue infrastructure and jobs for email, SMS, notifications, QR/PDF generation, and large exports. Configure retry, timeout, backoff, dead-letter/failed-job review, and idempotency.
2. Run scheduler and workers under an approved service manager; add a deployment sequence for maintenance mode, migrations, cache, workers, and health checks.
3. Replace staff log clearing with role-gated, auditable log access and central error reporting. Add request correlation IDs and redaction guidance.
4. Define backup coverage for database and private documents, encryption/retention, RPO/RTO, restore test schedule, alert ownership, and incident runbook.
5. Protect `/up` and `/api/health` according to platform requirements and add uptime, queue, failed-job, payment-webhook, and scheduled-command alerts.

### Exit criteria

- Provider outage retries do not block web requests or lose jobs.
- Worker/scheduler restart and failed-job recovery are rehearsed in staging.
- Backup restore meets the approved RPO/RTO.
- A deployment checklist has been executed twice without manual surprises.

### Rollback

Feature-flag job dispatch, retain synchronous fallback only for critical low-volume operations, and stop new workers before rolling application code back. Infrastructure changes need their own runbook and owner approval.

## Phase 5 — Performance and product-quality UX (6–10 days)

### Work

1. Replace repeated dashboard/month-trend queries with a grouped date-range query; cache bounded aggregates and invalidate on payment changes.
2. Stream or queue large CSV/PDF exports; add date/location/status filters, result counts, export audit entries, and permission checks.
3. Paginate public catalogue results and make the `location_id` filter actually affect its query. Add search/sort only after measuring expected catalogue size.
4. Consolidate assets under Vite and remove/retire only verified unused legacy copies. Add image variants/optimization and cache headers.
5. Create capability-driven navigation and a small visual system: page header, action bar, table, form, status badge, modal, alert, empty state, skeleton, and error state. Reduce routine radius/shadow use; preserve the product's indigo/emerald identity.
6. Add accessible names to icon controls, focus management/return for dialogs, keyboard tests, visible loading states, chart summaries/tables, contrast checks, and responsive tests at phone/tablet/desktop widths.

### Exit criteria

- Query count and response-time budgets are recorded for home, dashboard, payments, ticket center, and reports.
- No report/export request loads the entire payment dataset into memory.
- Staff navigation reflects actual capabilities.
- Keyboard-only smoke tests and automated accessibility checks pass for auth, application, payments, tickets, and one admin management page.
- Visual regression review approves each page family in light and dark themes.

### Rollback

Release by page family and keep old read paths/asset manifests until dashboards and exports are verified. UI changes must not carry unreviewed authorization or schema changes.

## Phase 6 — Product enhancements (estimate after phases 1–5)

Prioritize using usage data and support volume:

1. Applicant self-service, duplicate application detection, reservation holds, and expiry workflow.
2. Invoice line items, adjustments, refunds, failed-payment recovery, settlement reconciliation, and financial close reporting.
3. Search/filter/export permissions, bulk operations, audit retention, and staff workload analytics.
4. Tenant-facing notifications preferences, application/lease history, and maintenance workflows.

Each feature requires a short product specification, authorization matrix, data model decision, test plan, metrics, and rollback approach before implementation.

## Decisions required before implementation

| Decision | Owner | Needed by | Default if not decided |
| --- | --- | --- | --- |
| Laravel 11 patch vs Laravel 13 upgrade | Technical owner | Phase 0 | Stay on a patched Laravel 11 release; do not mix upgrade with security fixes. |
| Staff capability matrix | Product + operations | Phase 1 | Least privilege: staff have only named operational capabilities. |
| Ticket eligibility | Product + operations | Phase 1 | Only tenant-owned active-lease stalls can open tickets. |
| Public receipt disclosure | Product + privacy owner | Phase 3 | Public view shows validity, amount/status, and masked identifiers only. |
| Billing domain model | Product + finance | Phase 2 | Introduce immutable invoice/line-item/payment-attempt records. |
| Backup RPO/RTO and retention | Operations owner | Phase 4 | No production release until explicit values are approved. |

## Definition of done

A phase is complete only when its exit criteria pass in CI and staging, documentation is updated, release/rollback steps are rehearsed where applicable, and the change log links the deployed version to its migration and recovery plan. Production release remains blocked until phases 0–3 are complete.
