# Database Design

## Relationship map

```text
User 1--* StallMapping *--1 Stall *--1 StallLocation
User 1--* Payment *--1 Stall
User 1--* Ticket *--1 Stall; Ticket 1--* TicketMessage; Ticket 1--* TicketParticipant
User 1--* Notification, AuditLog, TwoFactorCode
StallApplication optionally references Stall and reviewer User
```

The schema uses InnoDB-style foreign keys, decimals for money/meters, timestamps, selected status enums, and indexes on common owner/status fields. It has no soft-delete strategy; deleting a user, stall, or floor can conflict with financial history or cascade related operational history.

## Integrity and performance gaps

| Priority | Finding | Why / expected improvement |
| --- | --- | --- |
| P0 | No uniqueness/check constraint prevents concurrent active mappings for a stall, overlapping leases, or mismatch between mapping and stall status. | Enforce lease invariants in a transaction plus DB constraints appropriate to MySQL; prevents double allocation. |
| P0 | Payments lack a billing period/line-item model and unique provider event/session ownership rule. | Add immutable invoice/transaction identifiers and idempotency constraints; makes reconciliation trustworthy. |
| P1 | `stall_mappings` lacks an index for `is_active,start_date,end_date`; invoice scan does per-row lookups. | Add query-shaped indexes after `EXPLAIN`; speeds scheduled billing. |
| P1 | Grid positions have no unique `(location_id,grid_row,grid_col)` rule. | Prevent overlapping floor-plan placement. |
| P1 | `tickets.stall_id` only validates existence at UI level, not that the tenant has an active mapping. | Scope ticket creation to active lease or document the allowed policy; preserves tenant boundaries. |
| P2 | Public application documents, audit/detail retention, and direct deletion lack a data lifecycle policy. | Define private storage, retention, legal hold, export, and restoration; reduces privacy/compliance risk. |

Use new forward-only migrations after the migration branch is reconciled. Do not edit the January baseline migrations in a deployed environment; each schema change needs a backup, staging data check, `EXPLAIN`, rollback migration, and a restore-tested backup.
