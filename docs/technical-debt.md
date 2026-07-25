# Technical Debt

## Quick wins

- Restore a single working `AuditLog` API or update callers; add tests for every affected admin action.
- Reconcile Composer manifest, lock, and installed framework; patch audited dependencies and add CI gates.
- Scope tenant ticket/invoice identifiers to the authenticated account and validate ticket stall ownership.
- Reject unsigned webhooks, make provider events idempotent, and remove raw chart JSON injection.
- Delete/move the stray `resources/resources/views/auth/two-factor-challenge.blade.php` only after confirming it is unused; retain the canonical view under `resources/views/auth`.

## Medium-term improvements

- Replace duplicated authentication (route login, modal login, unused `LoginForm`) and duplicated navigation with one supported flow.
- Move lease/payment/utility transitions into transaction-backed actions and add policies.
- Queue external notifications/PDF/report work and add retry/delivery state.
- Establish a private document service, image pipeline, retention policy, and safe log viewer.
- Consolidate Vite/DaisyUI/local legacy assets and standardize UI primitives.

## Long-term risks

The uncommitted framework migration is the largest maintenance risk: legacy removal and replacement are interleaved, so a partial commit could produce an application with neither architecture complete. The data model treats billing as mutable payment records rather than immutable invoices/transactions, which will become costly as reconciliation, adjustments, refunds, and audit needs grow. Scaling will be limited until session/cache/queue/storage are externalized and operated with observability.

Each item should be delivered behind focused tests and a rollback plan. The ordered work, impact, difficulty, risk, and expected gain are in [roadmap.md](roadmap.md).
