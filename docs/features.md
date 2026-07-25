# Features and Completeness

| Feature | Evidence | Assessment |
| --- | --- | --- |
| Public catalogue | Home route, locations/stalls and responsive catalogue | Present; location links are rendered but the route ignores `location_id`, so filtering is incomplete. |
| Stall applications | `StallReservation`, application manager | Present; no spam controls, reservation hold, applicant portal, or secure document access. |
| Stalls, floors and grid | Admin managers and grid editor | Present; no unique grid-cell or lifecycle enforcement. |
| Leasing | Mapping manager and tenant renewal | Present; lease creation is not transactional and active-lease exclusivity is not enforced. |
| Billing and checkout | Payments, PayMongo service/webhook, PDF receipt | Present but payment idempotency, ownership, webhook fail-closed behavior, and reconciliation need correction. |
| Utilities | Readings and automatic payment creation | Present; rate/version, duplicate-period, and correction controls are missing. |
| Tickets | Tenant and staff Livewire chat | Present but tenant authorization and participant management are incomplete. |
| Notifications | Database notifications and bell | Present; synchronous channels and no delivery/audit state. |
| Analytics/reporting | Dashboard, advanced reports, CSV/PDF | Present; export/audit runtime defect and unbounded exports limit production use. |
| Account security | Password, OAuth, route 2FA | Partial; no password reset routes, email verification flow, consistent rate limiting, or robust OTP lifecycle. |

## Expected product capabilities not yet evidenced

- A staff-specific navigation/permission matrix; staff can access admin routes but the primary navigation checks only `isAdmin()`.
- Invoice period, line-item, adjustment, refund, failed-payment, and reconciliation records.
- Application status history, applicant self-service, duplicate detection, and reservation expiry.
- Audit retention/export controls, immutable financial history, and configurable data retention.
- Tenant search/filter/sort/pagination across public inventory; accessibility-checked loading, empty, and error states.

Feature enhancements and safety prerequisites are ordered in [roadmap.md](roadmap.md). Do not market automated notifications, real-time chat, or production payments as reliable until the P0 and P1 items are complete.
