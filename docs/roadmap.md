# Prioritized Roadmap

## P0 — Release blockers

### R1. Restore reproducible, patched dependencies — 1–2 days

- **Why:** `composer.json` requires Laravel 13, while the committed lock and installed vendor tree are Laravel 11.55.0; Composer audit reports known Laravel advisories.
- **Impact:** High security and release integrity impact.
- **Difficulty / risk:** Medium / high migration compatibility risk.
- **Expected improvement:** A clean install reproduces the tested framework and blocks known vulnerable versions.
- **Change safety:** Select the intended Laravel major version, update lock in a branch, run the complete test suite and smoke tests, then deploy through a staged environment. Roll back by restoring the prior lock/vendor release artifact.

### R2. Enforce tenant object authorization — 2–3 days

- **Why:** Tenant ticket and checkout actions trust selected IDs without uniformly scoping them to the authenticated tenant; ticket creation accepts any stall.
- **Impact:** High confidentiality and integrity impact (IDOR/cross-tenant actions).
- **Difficulty / risk:** Medium / medium; valid staff workflows must remain unaffected.
- **Expected improvement:** A tenant can only view/change their tickets, active-lease stalls, and pending payments.
- **Change safety:** Introduce policies and relationship-scoped queries, then feature-test malicious IDs for every Livewire action. Roll back code only; no data migration is needed.

### R3. Make PayMongo confirmation fail closed and idempotent — 2–4 days

- **Why:** A missing webhook secret accepts unsigned events; session/event duplication and ownership are not constrained.
- **Impact:** High, potentially financial-loss impact.
- **Difficulty / risk:** Medium / medium; webhook payload assumptions must match PayMongo’s production format.
- **Expected improvement:** Only verified, fresh events transition the intended pending invoices once.
- **Change safety:** Add secret validation, signature/timestamp/event-ID persistence, transactions, and replay tests in sandbox first. Keep a manual reconciliation path and reversible migration.

### R4. Fix the stored XSS and audit runtime fault — 1–2 days

- **Why:** Raw JSON in analytics can break out of a script, and multiple admin actions call undefined `AuditLog::log()`.
- **Impact:** High privilege-escalation risk plus broken admin operations.
- **Difficulty / risk:** Low / low.
- **Expected improvement:** Safe chart serialization and functioning auditable admin writes/exports.
- **Change safety:** Replace raw output with `@js`, define one tested audit API, and add component tests. Roll back application code only.

## P1 — Production hardening

### R5. Consolidate identity, OTP, and rate limits — 3–5 days

- **Why:** Login paths duplicate behavior; route login lacks the available limiter; OTPs are plaintext/no attempt limit and one path logs them.
- **Impact:** High account-takeover resistance.
- **Difficulty / risk:** Medium / medium; users may need to reauthenticate.
- **Expected improvement:** A single tested login/verification state machine with throttling, hashed expiring codes, and no secret logging.
- **Change safety:** Deploy behind feature flags, retain a monitored break-glass administrator process, and roll back the new route/component together.

### R6. Establish lease and billing invariants — 4–7 days

- **Why:** Active lease, grid cell, invoice period, and provider-session uniqueness are not database-enforced; scheduled invoices have an N+1/race.
- **Impact:** High data correctness and finance impact.
- **Difficulty / risk:** High / high due to historical data cleanup.
- **Expected improvement:** Atomic allocations and idempotent invoices with traceable line items.
- **Change safety:** Profile/clean duplicate data, add forward-only nullable fields/indexes, backfill, validate, then enforce constraints. Take verified backups and provide a down migration.

### R7. Secure files, receipts, logs, and configuration — 2–4 days

- **Why:** Application documents use public storage; public receipt IDs disclose data; a fallback app key and unprotected log operations weaken defense in depth.
- **Impact:** Medium-to-high privacy and operational impact.
- **Difficulty / risk:** Medium / medium because existing links/files need migration.
- **Expected improvement:** Private, authorized download paths; minimal public verification; safe startup and controlled logs.
- **Change safety:** Copy files to private storage, preserve signed/redirected old links temporarily, test access matrix, and retain a reversible manifest.

## P2 — Reliability, performance, and UX

### R8. Queue external work and optimize read paths — 4–6 days

- **Why:** Provider calls, PDFs, exports, and scheduled notifications are synchronous; reports repeat aggregations and exports load all rows.
- **Impact:** Medium reliability/performance impact.
- **Difficulty / risk:** Medium / medium operational risk.
- **Expected improvement:** Faster web requests, retryable delivery, bounded memory, and stable dashboards.
- **Change safety:** Add queue tables/worker supervision, idempotent jobs, metrics, and canary one command before broad enablement. Disable workers to roll back.

### R9. Deliver a capability-led design system — 3–5 days

- **Why:** Navigation ignores staff capability, page styles diverge, controls are visually dense, and loading/accessibility states are inconsistent.
- **Impact:** Medium adoption/support impact.
- **Difficulty / risk:** Medium / low functional risk.
- **Expected improvement:** Faster staff navigation, consistent mature SaaS presentation, and accessible feedback.
- **Change safety:** Inventory components, replace one page family at a time with visual regression and keyboard tests; feature changes are unnecessary.

### R10. Add CI/CD, backups, and observability — 3–6 days

- **Why:** No pipeline, release recipe, backup/restore test, alerting, metrics, or incident runbook was found.
- **Impact:** High operational-resilience impact.
- **Difficulty / risk:** Medium / medium infrastructure risk.
- **Expected improvement:** Detect regressions before release and recover predictably from failure.
- **Change safety:** Start with non-blocking CI and staging backup restores; promote checks after baseline stability. Infrastructure changes require owner approval and rollback runbooks.

## Future enhancements

After the above, add applicant self-service/status history, reservation holds with expiry, payment adjustments/refunds/reconciliation, advanced filters and export permissions, retention/audit policy, image derivatives/CDN, and staff workload analytics. Estimate each only after the revised billing and authorization model is accepted.
