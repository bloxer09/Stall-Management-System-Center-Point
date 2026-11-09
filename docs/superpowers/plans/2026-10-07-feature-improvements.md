# Feature Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix lease termination and stale Terms text, and add announcements, application status lookup, tenant accounts, statements, waitlist, calendar, global search and backups.

**Architecture:** Each feature is a Livewire page (or an addition to one) backed by a small service or model method, following the existing `app/Livewire/Admin/*` + `resources/views/pages/admin/*.blade.php` route-closure pattern. Cross-cutting behaviour goes in middleware (`EnsureAccountActive`) or a model observer (`StallObserver`). Every task ends with a green full suite and its own commit.

**Tech Stack:** Laravel 12, Livewire 3, Alpine.js, DaisyUI/Tailwind, dompdf, PHPUnit (sqlite in-memory).

**Spec:** `docs/superpowers/specs/2026-10-07-feature-improvements-design.md`

## Global Constraints

- Branch `defense-sprint`; one commit per task; `php artisan test` fully green before each commit.
- Admin pages: route inside the existing `['auth', 'role:admin', '2fa']` group in `routes/web.php`, view `resources/views/pages/admin/<name>.blade.php` wrapping `<livewire:admin.<name> />`, sidebar link in `resources/views/layouts/app.blade.php` using the existing link classes.
- All admin-side mutations call `AuditLog::record(action, entity, id)`.
- Email via `MailService::sendEmail($toEmail, $toName, $subject, $html, $text)`; bell via `Notification::send($userId, $type, $title, $message, $link)`. External sends happen after DB commits.
- Toasts via `$this->dispatch('notify', type: ..., message: ...)`.
- Escape all user text in emails with `e()`.
- Money shown as `₱` + `number_format($x, 2)`.
- Tests: `tests/Feature/<Name>Test.php`, `RefreshDatabase`, `Mail::fake()` where mail is sent; admins via `User::factory()->create(['role' => 'admin'])`.

## Review Focus

- Terminating a lease whose tenant has unpaid invoices on *another* stall: only invoices for that tenant + that stall count toward the balance (Task 1 test `test_balance_counts_only_this_stall`).
- Deactivated tenant with an open Livewire page: their next Livewire action must also be logged out, not only full page loads (Task 5 test `test_deactivated_user_livewire_request_is_logged_out`).
- Application lookup with extra whitespace / different case in the email or reference: should still match (Task 4 test `test_lookup_ignores_case_and_whitespace`).
- Stall saved with status unchanged (e.g. price edit while `available`): waitlist must not be re-emailed (Task 7 test `test_no_email_when_status_unchanged`).
- Backup download with a crafted name like `../.env`: must 404, never serve the file (Task 10 test `test_download_rejects_traversal_name`).

---

### Task 1: Lease termination with reason and balance check

**Files:**
- Create: `database/migrations/2026_10_07_000002_add_termination_fields_to_stall_mappings_table.php`
- Modify: `app/Models/StallMapping.php` (fillable + `terminated_at` datetime cast), `app/Services/LeaseService.php:319` (`terminateLease`), `app/Livewire/Admin/LeaseMappingManager.php:188`, `resources/views/livewire/admin/lease-mapping-manager.blade.php:314` (button → modal; detail shows reason/date when terminated)
- Test: `tests/Feature/LeaseTerminationTest.php`

**Interfaces:**
- Produces: `LeaseService::unpaidBalance(StallMapping $lease): float` (sum of `total_amount` of `pending` invoices with same `user_id` + `stall_id`); `LeaseService::terminateLease(StallMapping $lease, string $reason): StallMapping`.
- Livewire: `openTerminate(int $id)`, properties `terminateId`, `terminationReason`, `acknowledgeBalance` (bool), `terminateLease()`, `closeTerminate()`.

- [ ] **Step 1: Write failing tests** — `test_reason_is_required` (`terminationReason` = 'short' → `assertHasErrors(['terminationReason' => 'min'])`), `test_unpaid_balance_requires_acknowledgement` (pending invoice ₱4500, no ack → error on `acknowledgeBalance`, lease still active), `test_termination_with_balance_leaves_past_due` (ack → `is_active` false, `payment_status` 'past_due', reason + `terminated_at` saved, invoice still 'pending', stall 'available'), `test_termination_without_balance_is_settled`, `test_tenant_notified_with_reason` (Notification row for tenant containing reason; Mail sent), `test_balance_counts_only_this_stall`.
- [ ] **Step 2: Run** `php artisan test --filter=LeaseTerminationTest` — Expected: FAIL.
- [ ] **Step 3: Implement** migration (`termination_reason` text nullable, `terminated_at` timestamp nullable, after `renewal_status` or at end), service methods, Livewire validation (`terminationReason` required|string|min:10|max:500; `acknowledgeBalance` `accepted` only when balance > 0, message "Confirm that the unpaid balance stays owed by the tenant."), modal showing "Unpaid balance: ₱X" and the checkbox only when X > 0. Notification type `lease`, title "Lease terminated", link `route('user.leases')`. Update `tests` that call the old one-arg `terminateLease` (grep `terminateLease`).
- [ ] **Step 4: Run** `php artisan test` — Expected: all PASS.
- [ ] **Step 5: Commit** `fix(leases): termination requires a reason, keeps unpaid balance owed, notifies tenant`

### Task 2: Terms wording

**Files:**
- Create: `database/migrations/2026_10_07_000003_replace_utility_clause_in_terms_setting.php`
- Modify: `app/Livewire/Admin/SystemSettings.php:35`
- Test: `tests/Feature/TermsWordingTest.php`

**Interfaces:**
- Produces: `SystemSettings::DEFAULT_TERMS` (public const string) used by both the component and the migration (migration hard-codes the *old* text as a local constant).

- [ ] **Step 1: Write failing tests** — `test_default_terms_have_no_utility_clause` (`DEFAULT_TERMS` contains "2. Payments" and not "Utility"), `test_migration_replaces_untouched_default` (set setting to old text, run migration `up()` via `(include path)->up()`, value equals `DEFAULT_TERMS`), `test_migration_keeps_edited_terms`.
- [ ] **Step 2: Run** `php artisan test --filter=TermsWordingTest` — FAIL.
- [ ] **Step 3: Implement.** Clause text exactly: `2. Payments\nRent is billed monthly. Invoices not paid by their due date are flagged as overdue.`
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `fix(settings): replace utility metering clause in default terms`

### Task 3: Announcements

**Files:**
- Create: migration `2026_10_07_000004_create_announcements_table.php`, `app/Models/Announcement.php`, `app/Services/AnnouncementService.php`, `app/Livewire/Admin/AnnouncementManager.php`, `resources/views/livewire/admin/announcement-manager.blade.php`, `resources/views/pages/admin/announcements.blade.php`
- Modify: `routes/web.php` (`/announcements` → `admin.announcements`), `layouts/app.blade.php` sidebar, `app/Livewire/User/UserDashboard.php` + `resources/views/livewire/user/user-dashboard.blade.php` (card)
- Test: `tests/Feature/AnnouncementTest.php`

**Interfaces:**
- Produces: `Announcement` (fillable `title, body, stall_location_id, emailed, posted_by`; `location()`, `poster()`; scope `visibleTo(User $user)` = null location OR location in floors where user has an active lease). `AnnouncementService::post(array $data, User $admin): Announcement` returns after sending; `AnnouncementService::recipients(?int $locationId): Collection<User>`.

- [ ] **Step 1: Write failing tests** — `test_post_notifies_all_active_tenants` (2 tenants with active leases + 1 without → 2 notifications type 'announcement'), `test_floor_audience_limits_recipients`, `test_email_only_when_checked` (Mail count 0 vs 2), `test_tenant_with_two_stalls_notified_once`, `test_validation` (title required/max:150, body required/max:2000), `test_dashboard_shows_latest_three_visible` (4 visible + 1 other-floor → 3 shown, other-floor absent), `test_delete_removes_announcement`, `test_non_admin_forbidden` (GET `/admin/announcements` as tenant → 403).
- [ ] **Step 2: Run** `php artisan test --filter=AnnouncementTest` — FAIL.
- [ ] **Step 3: Implement.** Admin component: form fields `title, body, locationId (nullable), sendEmail`, `post()`, `delete(int $id)` with `wire:confirm`, paginated list (10) showing audience ("All tenants" / floor name), poster, date, "Emailed" badge. Notification title = announcement title, message = `Str::limit(body, 140)`, link `route('user.dashboard')`. Dashboard passes `announcements` = `Announcement::visibleTo($user)->latest()->limit(3)->get()`; card hidden when empty.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(announcements): admins post notices to all tenants or one floor`

### Task 4: Applicant status lookup

**Files:**
- Create: `app/Livewire/Portal/ApplicationStatus.php`, `resources/views/livewire/portal/application-status.blade.php`, `resources/views/pages/application-status.blade.php`
- Modify: `app/Models/StallApplication.php`, `app/Services/StallApplicationService.php:76` (email shows reference), `app/Livewire/Portal/StallBrowser.php` + view (success message shows reference), `routes/web.php` (`GET /application-status` → `application.status`, no auth), public navbar + footer in `resources/views/pages/home.blade.php` / `layouts/guest.blade.php` (wherever the public nav lives)
- Test: `tests/Feature/ApplicationStatusTest.php`

**Interfaces:**
- Produces: `StallApplication::reference(): string` → `sprintf('APP-%s-%06d', $this->created_at->year, $this->id)`; `static StallApplication::findByReference(string $ref): ?self` (trim, uppercase, regex `^APP-(\d{4})-(\d{6})$`, year must equal `created_at->year`).
- Livewire: properties `reference`, `email`, `result` (?array), `lookup()`.

- [ ] **Step 1: Write failing tests** — `test_reference_format` (`APP-{year}-000001` for id 1), `test_lookup_shows_status_and_reason` (rejected app → sees "Rejected" + reason), `test_wrong_email_wrong_reference_wrong_year_same_message` (all three → `assertSee('No application matches that reference and email.')`, no status shown), `test_lookup_ignores_case_and_whitespace`, `test_rate_limited_after_ten` (11th → error "Too many lookups. Please try again in a minute."), `test_confirmation_email_contains_reference`, `test_page_is_public` (GET `/application-status` 200 as guest).
- [ ] **Step 2: Run** `php artisan test --filter=ApplicationStatusTest` — FAIL.
- [ ] **Step 3: Implement.** Rate limiter key `app-status:` + request IP, 10 per 60s, hit on every lookup. Result shows status badge, stall number, submitted date, reviewed date, rejection reason when rejected, and for approved "Check your email for your account set-up link."
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(portal): applicants can check their application status by reference`

### Task 5: Tenant accounts and deactivation

**Files:**
- Create: migration `2026_10_07_000005_add_deactivated_at_to_users_table.php`, `app/Http/Middleware/EnsureAccountActive.php`, `app/Livewire/Admin/TenantManager.php`, `resources/views/livewire/admin/tenant-manager.blade.php`, `resources/views/pages/admin/tenants.blade.php`
- Modify: `app/Models/User.php` (fillable/cast, `isDeactivated(): bool`), `bootstrap/app.php` (append `EnsureAccountActive` to `web`), `app/Providers/AppServiceProvider.php` (add to `Livewire::addPersistentMiddleware`), `app/Livewire/Auth/AuthModals.php` (~line 195 before `Auth::login`), `app/Http/Controllers/OAuthController.php` (lines 68 and 134), `app/Services/StallApplicationService.php` (extract set-password email to public `sendSetPasswordLink(User $user): bool`), `routes/web.php`, sidebar
- Test: `tests/Feature/TenantAccountsTest.php`

**Interfaces:**
- Produces: `User::isDeactivated()`; `StallApplicationService::sendSetPasswordLink(User $user): bool`; `TenantManager` with `#[Url] $search`, `$statusFilter` ('' | 'active' | 'deactivated'), `deactivate(int $id)`, `reactivate(int $id)`, `resendSetPassword(int $id)`, `openStatement(int $id)` (stub that sets `statementUserId`; filled in Task 6).
- Message constant `EnsureAccountActive::MESSAGE = 'This account is deactivated. Please contact the office.'` used by all sign-in refusals.

- [ ] **Step 1: Write failing tests** — `test_list_shows_tenants_with_balance`, `test_search_filters`, `test_deactivate_and_reactivate` (audit rows exist), `test_cannot_deactivate_admin` (target admin → error toast, `deactivated_at` null), `test_password_login_refused` (AuthModals login → `assertHasErrors('loginEmail')`, guest), `test_google_login_refused` (mock Socialite user per existing OAuth tests or `Socialite::shouldReceive`), `test_facebook_login_refused`, `test_deactivated_user_page_request_is_logged_out` (actingAs → GET `/user/dashboard` → redirect `/`, guest, session flash message), `test_deactivated_user_livewire_request_is_logged_out`, `test_resend_set_password_sends_mail`.
- [ ] **Step 2: Run** `php artisan test --filter=TenantAccountsTest` — FAIL.
- [ ] **Step 3: Implement.** Middleware: if `auth()->user()?->isDeactivated()` → `Auth::logout()`, invalidate + regenerate token, `redirect('/')->with('error', MESSAGE)` (Livewire header requests: same redirect; Livewire follows it). Ensure the home page renders `session('error')` (check existing flash display; add a toast if absent). Table columns per spec; unpaid balance = sum of `pending` invoices; paginate 15. Deactivation email subject "Your account has been deactivated".
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(tenants): admin tenant list with deactivation and set-password resend`

### Task 6: Statement of account

**Files:**
- Create: `app/Services/StatementService.php`, `resources/views/pdf/statement.blade.php`, `resources/views/livewire/partials/statement-table.blade.php`, controller method `StatementController@pdf` in `app/Http/Controllers/StatementController.php`
- Modify: `routes/web.php` (in the `['auth','2fa']` group: `GET /statement/{user}/pdf` → `statement.pdf`), `app/Livewire/User/DocumentRepository.php` + view (card), `TenantManager` + view (modal)
- Test: `tests/Feature/StatementTest.php`

**Interfaces:**
- Produces: `StatementService::forUser(User $user): array{lines: list<array{date: Carbon, label: string, charge: float, credit: float, balance: float}>, charges: float, payments: float, balance: float}`; `StatementService::pdf(User $user): string` (dompdf output, same setup as `PdfReceiptService`).

- [ ] **Step 1: Write failing tests** — `test_running_balance` (invoice ₱4500 day 1, payment ₱4500 day 2, invoice ₱4500 day 3, cancelled invoice ₱999 → 3 lines, balances 4500/0/4500, charges 9000, payments 4500, balance 4500), `test_same_day_charge_before_credit`, `test_failed_transactions_ignored`, `test_tenant_downloads_own_pdf` (200, `application/pdf`), `test_tenant_cannot_download_other` (403), `test_admin_can_download_any`, `test_documents_page_shows_balance`, `test_admin_statement_modal_shows_lines`.
- [ ] **Step 2: Run** `php artisan test --filter=StatementTest` — FAIL.
- [ ] **Step 3: Implement.** Sort key: date, then charge (0) before credit (1). PDF header: tenant name/email, generated date, totals; filename `statement_{user id}_{Y-m-d}.pdf`, `inline`.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(statements): tenant statement of account with running balance and PDF`

### Task 7: Stall waitlist

**Files:**
- Create: migration `2026_10_07_000006_create_stall_waitlist_table.php`, `app/Models/StallWaitlist.php` (table `stall_waitlist`), `app/Observers/StallObserver.php`
- Modify: `app/Models/Stall.php` (`#[ObservedBy(StallObserver::class)]`, `waitlist()` hasMany), `app/Livewire/Portal/StallBrowser.php` + view, `app/Livewire/Admin/StallManager.php` view (badge via `withCount(['waitlist as waiting_count' => fn ($q) => $q->whereNull('notified_at')])`)
- Test: `tests/Feature/StallWaitlistTest.php`

**Interfaces:**
- Livewire (`StallBrowser`): `openWaitlist()`, `waitlistName`, `waitlistEmail`, `joinWaitlist()`.

- [ ] **Step 1: Write failing tests** — `test_join_waitlist_for_occupied_stall`, `test_cannot_join_for_available_stall` (error "This stall is open — apply instead."), `test_duplicate_rejected` (message "You're already on the list for this stall."; email compared lowercase), `test_rate_limited` (6th in an hour fails), `test_emails_once_when_stall_becomes_available` (2 entries → 2 mails, `notified_at` set; set occupied then available again → no new mails), `test_no_email_when_status_unchanged`, `test_termination_triggers_waitlist_email` (via `LeaseService::terminateLease` — note the service uses `Stall::where()->update()`; change it to load the model and `update()` so the observer fires), `test_admin_sees_waitlist_badge`.
- [ ] **Step 2: Run** `php artisan test --filter=StallWaitlistTest` — FAIL.
- [ ] **Step 3: Implement.** Observer `updated(Stall $stall)`: `if ($stall->wasChanged('status') && $stall->status === 'available')`, mail each un-notified entry (subject "Stall {number} is now available", link `url('/stalls')`), wrap each send in try/catch + `report()`, stamp `notified_at`. Grep for other `Stall::where(...)->update(['status' => 'available'])` mass updates and convert those that represent a stall becoming free.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(waitlist): visitors get emailed when an occupied stall opens up`

### Task 8: Calendar

**Files:**
- Create: `app/Services/CalendarService.php`, `app/Livewire/Admin/CalendarView.php`, `resources/views/livewire/admin/calendar-view.blade.php`, `resources/views/pages/admin/calendar.blade.php`
- Modify: `routes/web.php`, sidebar, `UserDashboard` + view ("Upcoming dates")
- Test: `tests/Feature/CalendarTest.php`

**Interfaces:**
- Produces: `CalendarService::eventsForMonth(Carbon $month): array<string, list<array{type: 'lease_end'|'invoice_due'|'renewal', label: string, url: string, overdue: bool}>>` keyed `Y-m-d`; `CalendarService::upcomingFor(User $user, int $days = 60, int $limit = 5): Collection`.
- Livewire: `#[Url] public string $month`, `previousMonth()`, `nextMonth()`.

- [ ] **Step 1: Write failing tests** — `test_events_land_on_correct_days` (lease end 2026-10-15, pending invoice due 2026-10-05 with today 2026-10-07 via `Carbon::setTestNow` → overdue true, renewal request on end date), `test_paid_and_cancelled_invoices_excluded`, `test_invalid_month_falls_back` (`?month=garbage` → current month), `test_prev_next`, `test_tenant_upcoming_dates_limited_to_own`, `test_non_admin_forbidden`.
- [ ] **Step 2: Run** `php artisan test --filter=CalendarTest` — FAIL.
- [ ] **Step 3: Implement.** URLs: lease → `/admin/mappings?search={stall_number}`, invoice → `/admin/payments?search={invoice_number}`, renewal → `/admin/mappings?status=renewal_requested`. Grid: leading blanks for the first weekday (Sunday start), 3 chips then Alpine-toggled "+N more". Chip colours: lease info, invoice warning/error when overdue, renewal accent. Ensure `LeaseMappingManager` and `PaymentManager` read `search` from the query (`#[Url]`).
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(calendar): admin month calendar of lease ends, due dates and renewals`

### Task 9: Global search

**Files:**
- Create: `app/Livewire/Admin/GlobalSearch.php`, `resources/views/livewire/admin/global-search.blade.php`
- Modify: `layouts/app.blade.php` (admin top bar, admins only), `TenantManager`/`StallManager`/`PaymentManager` (`#[Url]` on `search`), `TicketCenter` (`#[Url(as: 'ticket')]` on `selectedTicketId` or mount reads `request('ticket')`)
- Test: `tests/Feature/GlobalSearchTest.php`

**Interfaces:**
- Produces: `GlobalSearch::results(): array{tenants: Collection, stalls: Collection, invoices: Collection, tickets: Collection}` (computed, empty under 2 chars).

- [ ] **Step 1: Write failing tests** — `test_short_query_returns_nothing`, `test_groups_results` ('S-10' matches stalls; tenant by phone; invoice by number; ticket by subject and by numeric id), `test_max_five_per_group`, `test_like_wildcards_are_literal` (query `%` matches nothing extra — escape `%` and `_`), `test_non_admin_forbidden` (tenant mounting component → 403), `test_target_pages_accept_query` (`Livewire::withQueryParams(['search' => 'S-101'])->test(StallManager::class)->assertSet('search', 'S-101')`).
- [ ] **Step 2: Run** `php artisan test --filter=GlobalSearchTest` — FAIL.
- [ ] **Step 3: Implement.** `wire:model.live.debounce.300ms="query"`; dropdown with group headers; Alpine `@keydown.slash.window` focuses unless `$event.target` is an input/textarea/contenteditable; Esc clears and blurs.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(search): admin global search across tenants, stalls, invoices and tickets`

### Task 10: Database backup

**Files:**
- Create: `app/Services/BackupService.php`, `app/Console/Commands/BackupDatabase.php` (`stallm:backup`), `app/Http/Controllers/BackupController.php`
- Modify: `routes/console.php` (`Schedule::command('stallm:backup')->dailyAt('02:00')`), `routes/web.php` (admin group: `GET /backups/{name}` → `admin.backups.download`), `app/Livewire/Admin/SystemSettings.php` + view ("Backups" card: `createBackup()`, `deleteBackup(string $name)`)
- Test: `tests/Feature/BackupTest.php`

**Interfaces:**
- Produces: `BackupService::create(): string` (file name), `list(): array<int, array{name: string, size: int, created: Carbon}>` newest first, `path(string $name): ?string` (null when name fails `^backup-[0-9-]+\.(sql|sqlite)$` or file missing), `delete(string $name): bool`, `prune(int $keep = 14): int`. Directory `storage_path('app/backups')`.

- [ ] **Step 1: Write failing tests** (use a temp dir: bind `BackupService` with a directory override constructor arg `?string $dir = null`) — `test_create_writes_sql_with_tables_and_rows` (in-memory sqlite: file contains `CREATE TABLE` and an `INSERT INTO` for a seeded user), `test_prune_keeps_fourteen` (16 files → 14 remain, oldest removed), `test_download_rejects_traversal_name` (GET `/admin/backups/..%2F.env` → 404), `test_admin_downloads_backup` (200), `test_non_admin_forbidden` (403), `test_command_creates_and_prunes`, `test_settings_create_and_delete_are_audited`.
- [ ] **Step 2: Run** `php artisan test --filter=BackupTest` — FAIL.
- [ ] **Step 3: Implement.** MySQL: `SET FOREIGN_KEY_CHECKS=0;`, per table from `Schema::getTableListing()`: `DROP TABLE IF EXISTS`, `SHOW CREATE TABLE` output, rows via `DB::table($t)->orderBy(first column)->chunk(500)` as multi-row `INSERT` with values quoted by `DB::getPdo()->quote()` (null → `NULL`), then `SET FOREIGN_KEY_CHECKS=1;`. SQLite file DB: `copy()` to `.sqlite`; `:memory:`: schema from `sqlite_master` + same INSERT writer. Settings card restore note: "Restore with: mysql -u USER -p DATABASE < backup-file.sql".
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(backup): one-click and daily database backups for admins`

### Task 11: Diagrams and demo seed

**Files:**
- Modify: scratchpad generators `make_diagrams.py` (ERD: add `announcements`, `stall_waitlist`, new columns) and `system_flow_data.py` (new screens: Announcements, Tenants, Calendar, Application Status; new actions on Leases, Stalls Browser, Documents, Settings, Tenant Dashboard; top-bar Global Search; scheduled `stallm:backup`), `database/seeders/DemoSeeder.php` (2 announcements, 1 waitlist entry, keeps the `@demo.test` safety rules)
- Output: `docs/diagrams/erd.drawio`, `docs/diagrams/system-flowchart.drawio`

- [ ] **Step 1:** Regenerate both diagrams; coverage check reports 0 uncharted actions/routes; XML parses.
- [ ] **Step 2:** `php artisan test` — PASS (DemoSeeder tests included).
- [ ] **Step 3: Commit** `docs: diagrams for new features; demo data for announcements and waitlist` (includes `docs/diagrams/`).
