# Feature Improvements — Design

Date: 2026-10-07 · Branch: `defense-sprint` · Defense: Saturday 2026-10-10 (localhost demo)

## Goal

Close the gaps found in the 2026-10-07 feature audit: two bugs, three improvements to existing
features, and five new features. Each item lands as its own tested commit so the branch is
demo-safe after every step. Pre-defense batch first (items 1, 2, 6, 5, 3), then the rest
(4, 7, 8, 10, 9). Nothing in this spec changes existing behaviour except where stated.

## Build order

| Order | Item | Kind |
|---|---|---|
| 1 | #1 Lease termination | bug |
| 2 | #2 Terms wording | bug |
| 3 | #6 Announcements | new |
| 4 | #5 Applicant status lookup | improvement |
| 5 | #3 Tenant accounts | improvement |
| 6 | #4 Statement of account | improvement |
| 7 | #7 Stall waitlist | new |
| 8 | #8 Calendar | new |
| 9 | #10 Global search | new |
| 10 | #9 Database backup | new |
| 11 | ERD + system flowchart regenerated | docs |

## Schema changes

- `stall_mappings`: add `termination_reason` (text, nullable), `terminated_at` (timestamp, nullable).
- `users`: add `deactivated_at` (timestamp, nullable).
- New `announcements`: `id`, `title` (string 150), `body` (text), `stall_location_id`
  (nullable FK → stall_locations, null = all tenants, nullOnDelete), `emailed` (bool),
  `posted_by` (nullable FK → users, nullOnDelete), timestamps.
- New `stall_waitlist`: `id`, `stall_id` (FK → stalls, cascadeOnDelete), `name` (string 100),
  `email` (string 120), `notified_at` (timestamp, nullable), timestamps; unique
  (`stall_id`, `email`).
- Data migration: replace the saved `terms_conditions` setting only when it equals the old
  default text exactly.

## #1 Lease termination

- `LeaseMappingManager::terminateLease` no longer acts on a bare click. "Terminate Lease"
  opens a modal with a required **reason** (min 10, max 500 chars) and the lease's **unpaid
  balance** (sum of `pending` invoices for that tenant + stall).
- When the balance is > 0, the admin must tick "I understand ₱X stays owed by the tenant";
  without it validation fails.
- `LeaseService::terminateLease(StallMapping $lease, string $reason)`:
  - in one transaction: `is_active = false`, `termination_reason`, `terminated_at = now()`,
    `payment_status = 'settled'` when the unpaid balance is 0 else `'past_due'`;
    stall → `available`.
  - Unpaid invoices are kept unchanged (the debt remains visible in Payments).
  - After commit: tenant gets a bell notification and an email containing the reason.
  - Audit log includes the reason.
- The lease detail modal shows the reason and date for terminated leases.

## #2 Terms wording

- Default text in `SystemSettings` replaces "2. Utility Metering …" with
  "2. Payments — Rent is billed monthly. Invoices not paid by their due date are flagged as
  overdue."
- Migration updates the stored setting only if it still equals the old default.

## #6 Announcements

- Model `Announcement`, admin Livewire page `Admin\AnnouncementManager` at
  `/admin/announcements` (sidebar entry), admin + 2FA only.
- Create form: title (required, ≤150), body (required, ≤2000), audience (All tenants / one
  floor), "Also email tenants" checkbox.
- Recipients = users with an active lease (`stall_mappings.is_active = true`), restricted to
  stalls on the chosen floor when one is set; deduplicated.
- Each recipient gets a bell notification (type `announcement`, link `/user/dashboard`); email
  only when checked. Audit-logged.
- List of past announcements (newest first, paginated) with delete (with confirm). Deleting
  does not retract sent notifications.
- Tenant dashboard: "Announcements" card with the latest 3 that apply to the tenant (all, or
  a floor where they hold an active lease).

## #5 Applicant status lookup

- `StallApplication::reference()` → `APP-{Y of created_at}-{id padded to 6}`;
  `StallApplication::findByReference(string)` parses it back (id only; year must match).
- Confirmation email and the success message after applying show the reference.
- Public Livewire page `Portal\ApplicationStatus` at `/application-status` (guest + auth).
  Inputs: reference, email. On match (reference id exists, year matches, email matches
  case-insensitively): show status badge, stall number, submitted date, review date, and the
  rejection reason when rejected; approved shows "Check your email for your account set-up
  link".
- Mismatch of any kind → the single message "No application matches that reference and email."
- Rate limit: 10 lookups per minute per IP (`RateLimiter`, key `app-status:{ip}`).
- Links: public navbar and footer "Check application status".

## #3 Tenant accounts

- Admin page `Admin\TenantManager` at `/admin/tenants` (sidebar entry), admin + 2FA.
- Table (search by name/email/phone, filter Active/Deactivated, paginated): name, email,
  phone, active leases count, unpaid balance, status, joined.
- Actions:
  - **Deactivate** (confirm) → `deactivated_at = now()`; **Reactivate** → null. Refused for
    admins (including self). Audit-logged; tenant notified by email on deactivate.
  - **Resend set-password link** → same signed URL + email as approval uses; audit-logged.
  - **View statement** → statement modal (#4).
- `User::isDeactivated()`. Sign-in is refused for deactivated users in password login
  (`AuthModals::login`), Google and Facebook callbacks, with "This account is deactivated.
  Please contact the office."
- New middleware `EnsureAccountActive` appended to the `web` group (and Livewire persistent
  middleware): an authenticated deactivated user is logged out, session invalidated,
  redirected to `/` with the same message.

## #4 Statement of account

- `StatementService::forUser(User $user): array` returns
  `['lines' => [...], 'charges' => float, 'payments' => float, 'balance' => float]`.
  Lines: invoices with status ≠ `cancelled` (charge = `total_amount`, dated `created_at`,
  label = invoice number + stall) and `payment_transactions` with status `successful` for
  that user's invoices (credit = `amount`, dated `created_at`, label = gateway + reference),
  sorted by date then charges before credits; each line has a running balance.
- PDF via dompdf: Blade view `pdf.statement`, route `GET /statement/{user}/pdf`
  (`auth`, `2fa`), allowed when the requester is that user or an admin, else 403.
- Tenant Documents page: "Account statement" card (balance + Download PDF).
- Admin Tenants page: statement modal with the same lines and a PDF button.

## #7 Stall waitlist

- Model `StallWaitlist`. `Portal\StallBrowser`: stalls with status `occupied`/`maintenance`
  show "Notify me when available" → name + email form. Validation: required, email format;
  duplicate (stall, email) → "You're already on the list for this stall." Rate limit 5/hour
  per IP.
- `StallObserver::updated`: when `status` changed to `available`, email every waitlist entry
  for that stall with `notified_at` null (link to `/stalls`), then stamp `notified_at`.
  Mail failures are logged and do not block the stall update.
- Admin Stalls list shows a "Waitlist N" badge for stalls with un-notified entries.

## #8 Calendar

- Admin page `Admin\CalendarView` at `/admin/calendar` (sidebar entry), admin + 2FA.
  Public property `month` (`Y-m`, defaults to current; invalid → current). Prev/next buttons.
- Events for the visible month:
  - lease end (`stall_mappings.end_date`, active leases) → link to leases page filtered by
    search on the stall number;
  - invoice due (`invoices.due_date`, status `pending`), red when overdue → payments page;
  - renewal requested (`renewal_status = 'requested'`, plotted on `end_date`) → leases page
    with `status=renewal_requested`.
- Month grid: 7 columns Sun–Sat, max 3 chips per day + "+N more" (expands in place).
- Tenant dashboard: "Upcoming dates" list — the tenant's pending invoice due dates and active
  lease end dates in the next 60 days, max 5.

## #10 Global search

- Livewire component `Admin\GlobalSearch` embedded in the admin top bar; `/` focuses it
  (ignored while typing in an input), Esc clears.
- `query` with `wire:model.live.debounce.300ms`; under 2 chars → no results.
- Groups (max 5 each, parameter-bound `LIKE`): Tenants (name/email/phone, role user) →
  `/admin/tenants?search=`; Stalls (stall_number) → `/admin/stalls?search=`; Invoices
  (invoice_number) → `/admin/payments?search=`; Tickets (subject, or id when numeric) →
  `/admin/tickets?ticket={id}`. Target pages accept these query params (add `#[Url]` where
  missing).
- Rendered only for admins; the component's `mount` aborts 403 for non-admins.

## #9 Database backup

- `BackupService`:
  - `create(): string` — writes `storage/app/backups/backup-YYYY-MM-DD-His.sql`. MySQL:
    for each table, `SHOW CREATE TABLE` + `INSERT` statements in chunks of 500, wrapped in
    `SET FOREIGN_KEY_CHECKS=0/1`. SQLite: copies the database file as `.sqlite` (in-memory
    test DB: writes the `sqlite_master` schema + inserts).
  - `list()`, `path(name)`, `delete(name)`, `prune(keep = 14)`. Names are validated against
    `^backup-[0-9-]+\.(sql|sqlite)$` to block path traversal.
- Command `stallm:backup` (create + prune), scheduled daily at 02:00.
- System Settings "Backups" card: Create now, list with size/date, Download
  (`GET /admin/backups/{name}`, admin + 2FA), Delete (confirm), restore note. All actions
  audit-logged.

## Testing

Feature tests per item (PHPUnit, sqlite in-memory), including:
- termination: reason required; balance > 0 needs acknowledgement and leaves `past_due`;
  zero balance → `settled`; stall available; tenant notified.
- terms migration only replaces the untouched default.
- announcements reach only targeted tenants; email only when checked; dashboard card scoping.
- lookup: match shows status; wrong email / wrong reference / wrong year → same message;
  rate limit.
- deactivation: blocked at password, Google, Facebook sign-in and mid-session; admins can't
  be deactivated; resend link.
- statement: running balance with mixed charges, payments, cancelled invoice; PDF 403 for
  another tenant.
- waitlist: duplicate rejected; observer mails once, only on change to available.
- calendar: events land on the right days; invalid month falls back.
- search: grouped results; non-admin forbidden.
- backup: creates file, prune keeps 14, name validation, non-admin 403.

Full suite must stay green after every item.

## Out of scope

Late fees, partial payments, tenant-side full calendar, real-time (websocket) announcements,
backup restore from the UI.
