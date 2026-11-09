# Front-end & Feature Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the 12 front-end/feature audit findings: demo data, visitor/tenant guidance, actionable dashboards, overdue invoices, documents, loading/accessibility polish, grid drag-and-drop, dead-code removal.

**Architecture:** Laravel 12 + Livewire 3 + Alpine. Business rules live in models, services and one console command (`Invoice` overdue scope, `stallm:flag-overdue`); Livewire components stay thin; shared renewal UI is extracted to a trait + Blade partial so MyLeases and UserDashboard reuse it.

**Tech Stack:** PHP 8.2, Laravel 12, Livewire 3, Alpine, Tailwind/DaisyUI, Chart.js, PHPUnit (sqlite in-memory).

**Spec:** `docs/superpowers/specs/2026-10-06-frontend-improvements-design.md`

## Global Constraints

- Work on branch `defense-sprint`; one commit per task; full suite (`vendor/bin/phpunit`) green before every commit; stage files by explicit path.
- Overdue = `status = 'pending'` AND `due_date < today()` (strict: due today is not overdue). Never write `status = 'overdue'`.
- No late fees. Roles only `admin` / `user`.
- Tests mock `SmsService` (`$this->mock(SmsService::class)`) and use `Mail::fake()`.
- Notifications: `Notification::send(int $userId, string $type, string $title, string $message, ?string $link = null)`.
- Lease renewal logic stays in `LeaseService` (`isRenewalEligible`, `requestRenewal`); UI must not duplicate rules.

## Review Focus

1. **Notification link pointing off-site** (`https://evil.example/x`, `//evil.example`, `javascript:alert(1)`) — must only mark read, never redirect. Test in Task 6.
2. **Running `DemoSeeder` twice** — no duplicate rows and no unique-constraint crash (invoices are unique on `stall_id + billing_period`). Test in Task 4.
3. **Running `stallm:flag-overdue` twice on the same day** — exactly one tenant notice and one admin summary. Test in Task 2.
4. **Invoice due today** — not overdue; no badge, not flagged. Test in Task 1.
5. **Forged grid call** (`moveStall` with a stall from another floor, or row/col outside the grid) — rejected, stall unchanged. Test in Task 10.

---

### Task 1: Invoice overdue model support

**Files:**
- Create: `database/migrations/2026_10_06_000004_add_overdue_notified_at_to_invoices_table.php`
- Modify: `app/Models/Invoice.php`
- Test: `tests/Unit/InvoiceOverdueTest.php`

**Interfaces:**
- Produces: `Invoice::isOverdue(): bool`; `Invoice::scopeOverdue(Builder $q): Builder`; column `invoices.overdue_notified_at` (nullable timestamp, cast datetime, fillable).

- [ ] **Step 1: Write failing tests** — `test_pending_past_due_is_overdue` (due yesterday, pending → `isOverdue()` true, included in `Invoice::overdue()`); `test_due_today_is_not_overdue` (Review Focus 4); `test_paid_past_due_is_not_overdue`.
- [ ] **Step 2:** `php artisan test --filter=InvoiceOverdueTest` → FAIL (method missing).
- [ ] **Step 3:** Implement migration + model methods (`whereDate('due_date', '<', today())`).
- [ ] **Step 4:** Filter, `php artisan migrate`, full suite → PASS.
- [ ] **Step 5:** Commit `feat(billing): computed overdue invoices`.

### Task 2: `stallm:flag-overdue` command

**Files:**
- Create: `app/Console/Commands/FlagOverdueInvoices.php`
- Modify: `routes/console.php` (`Schedule::command('stallm:flag-overdue')->dailyAt('08:30');`)
- Test: `tests/Feature/FlagOverdueInvoicesTest.php`

**Interfaces:**
- Consumes: `Invoice::overdue()`, `overdue_notified_at` (Task 1).
- Produces: command `stallm:flag-overdue` (exit 0, prints "Flagged N overdue invoice(s).").

- [ ] **Step 1: Write failing tests**
  - `test_flags_overdue_invoice_once`: tenant with active lease + invoice due 3 days ago → after run: lease `payment_status = past_due`; `overdue_notified_at` set; tenant has 1 notification type `warning` whose message is exactly `"Your invoice {number} for Stall {stall_number} was due on {M d, Y} and is now overdue. Please pay as soon as possible."`; 1 mail to tenant; each admin has 1 notification with message `"1 invoice(s) became overdue today"` and link `url('/admin/payments?status=overdue')`.
  - `test_running_twice_sends_one_notice` (Review Focus 3): run twice → counts unchanged after second run; second run prints "Flagged 0".
  - `test_no_admin_notice_when_nothing_overdue`.
  - `test_paying_overdue_invoice_restores_good_standing`: after flag, `app(InvoiceService::class)->markAsPaid($invoice, 'office_cash')` → lease `good_standing`.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement: query `Invoice::overdue()->whereNull('overdue_notified_at')->with(['user','stall'])`; per invoice update active lease (`user_id` + `stall_id`, `is_active`) to `past_due`, send in-app + `MailService::sendEmail` + `SmsService::sendSms` (only if phone), set `overdue_notified_at = now()`; then admin summary if count > 0.
- [ ] **Step 4:** Filter + full suite → PASS.
- [ ] **Step 5:** Commit `feat(billing): daily overdue invoice notices`.

### Task 3: Overdue badges and Payments overdue filter

**Files:**
- Modify: `app/Livewire/Admin/PaymentManager.php` (+ view), `resources/views/livewire/user/invoices-checkout.blade.php`
- Test: `tests/Feature/OverdueDisplayTest.php`

**Interfaces:**
- Produces: `PaymentManager::$statusFilter` accepts `'overdue'` (applies `Invoice::overdue()`); `PaymentManager::mount()` reads `request('status')` into `$statusFilter` when it is one of the filter options. Badge markup text: `Overdue` (DaisyUI `badge-error`).

- [ ] **Step 1: Failing tests** — admin `Livewire::withQueryParams(['status' => 'overdue'])->test(PaymentManager::class)` sees overdue invoice number, not the due-in-7-days one, and `assertSee('Overdue')`; tenant `InvoicesCheckout` shows `Overdue` for the past-due invoice only.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement filter option "Overdue" in the select, badge next to the status badge where `$invoice->isOverdue()`.
- [ ] **Step 4:** Filter + suite → PASS.
- [ ] **Step 5:** Commit `feat(billing): overdue badges and payments filter`.

### Task 4: DemoSeeder

**Files:**
- Create: `database/seeders/DemoSeeder.php`
- Modify: `README.md` (new "Demo data & accounts" section)
- Test: `tests/Feature/DemoSeederTest.php`

**Interfaces:**
- Consumes: `Invoice` overdue (Task 1) for the overdue invoice.
- Produces: floors "Ground Floor" (6×8), "Second Floor" (5×6), "Food Court" (4×5); tenants `tenant1@demo.test`…`tenant4@demo.test` / `password`; data exactly as spec §1.

- [ ] **Step 1: Failing tests**
  - `test_seeds_demo_dataset`: after `$this->seed(DemoSeeder::class)`: 3 floors; ≥ 28 stalls; 2 stalls with null `grid_row`; 4 users ending `@demo.test`; tenant1 lease `isRenewalEligible` true; tenant2 lease `renewal_status = requested`; exactly 1 `Invoice::overdue()`; ≥ 24 paid invoices; 2 pending + 1 rejected applications; 1 open + 1 closed ticket; every placed stall's coords inside its floor's grid.
  - `test_seeding_twice_creates_no_duplicates` (Review Focus 2): seed twice → all counts equal after second run, no exception.
  - `test_refuses_in_production`: `app()->detectEnvironment(fn () => 'production')` → seeding throws `RuntimeException`, no floors created.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement with `updateOrCreate`; invoices keyed on `stall_id + billing_period`; dates relative to `now()`; uses `admin@stallm.com` as reviewer/handler (create via `firstOrCreate` with role admin if missing).
- [ ] **Step 4:** Filter + suite → PASS. Also run `php artisan db:seed --class=DemoSeeder` against local MySQL → no error.
- [ ] **Step 5:** Commit `feat(demo): demo data seeder`.

### Task 5: Home page CTA and live stats

**Files:** Modify `resources/views/pages/home.blade.php`; Test `tests/Feature/HomePageTest.php`

- [ ] **Step 1: Failing tests** — with 2 floors / 5 stalls (3 available): `get('/')` sees `Browse Available Stalls` linking `route('stalls.browse')` in the hero, sees `2` floors and `3` available and `5` total in the stat cards (assert text `"3 Available Stalls"` and `"2 Floors"`), does not see `3 Building Levels`, does not see `Create Account` inside the hero (assert the hero section markup via `assertDontSee('Create Account</button>', false)` or equivalent unique markup).
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement per spec §2 (stat labels: `"{n} Floors"`, `"{n} Available Stalls"`, `"{n} Total Stalls"`, `PayMongo · GCash & Card Payments`).
- [ ] **Step 4:** Filter + suite → PASS.
- [ ] **Step 5:** Commit `feat(home): browse-stalls CTA and live stats`.

### Task 6: Notifications open their link

**Files:** Modify `app/Livewire/NavbarNotificationBell.php`, `resources/views/livewire/navbar-notification-bell.blade.php`; Test `tests/Feature/NotificationLinkTest.php`

**Interfaces:**
- Produces: `NavbarNotificationBell::open(int $id): void`; helper `isInternalLink(?string $link): bool` (private).

- [ ] **Step 1: Failing tests** — own notification with `url('/user/leases')` → `call('open', $id)` marks read and `assertRedirect(url('/user/leases'))`; another user's notification id → nothing changes, no redirect; links `https://evil.example/x`, `//evil.example/x`, `javascript:alert(1)` → marked read, `assertNoRedirect()` (Review Focus 1); null link → marked read, no redirect.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement: internal when `parse_url` scheme is `http`/`https` and host equals `parse_url(config('app.url'), PHP_URL_HOST)` or `request()->getHost()`, or the link is a path starting with a single `/` (not `//`). View items call `open`.
- [ ] **Step 4:** Filter + suite → PASS.
- [ ] **Step 5:** Commit `feat(notifications): clicking a notification opens its page`.

### Task 7: Tenant dashboard — onboarding, applications, countdown, next bill

**Files:**
- Create: `app/Livewire/Concerns/RequestsLeaseRenewal.php` (trait), `resources/views/livewire/partials/renewal-modal.blade.php`
- Modify: `app/Livewire/User/MyLeases.php` + view (use trait + partial, behavior unchanged), `app/Livewire/User/UserDashboard.php` + view
- Test: `tests/Feature/TenantDashboardTest.php`

**Interfaces:**
- Produces: trait `RequestsLeaseRenewal` with properties `?int $renewalLeaseId`, `string $renewalOption = '6m'`, `string $renewalCustomDate`, `string $renewalNote` and methods `openRenewal(int $id, LeaseService $leases)`, `closeRenewal()`, `submitRenewal(LeaseService $leases)`, `renewalLease(): ?StallMapping` (exact moves of the current MyLeases code). Partial expects `$renewalLease` and the trait properties.

- [ ] **Step 1: Failing tests**
  - `test_tenant_without_lease_sees_browse_cta`: `assertSee("You don't have a stall yet")` and `assertSee(route('stalls.browse'))`.
  - `test_my_applications_matches_user_id_and_email_case_insensitively`: application A with `user_id = tenant`, application B with `applicant_email = strtoupper(tenant email)`, application C of another email → sees A and B business names, not C; rejected one shows its reason.
  - `test_lease_countdown_and_renew_button`: lease ending in 45 days → `assertSee('Ends in 45 days')` and `Request renewal`; `call('openRenewal', id)->set('renewalOption','6m')->call('submitRenewal')` → lease `renewal_status = requested`.
  - `test_next_bill_due_with_overdue_badge`: pending invoice due 2 days ago → sees invoice amount, `Overdue`, and `route('user.payments')`.
  - Existing `LeaseRenewalUiTest` must stay green (proves the trait extraction kept MyLeases behavior).
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement per spec §3 and §6 (countdown text: `"Ends in {n} days"`, `"Ends today"`, `"Ended"`; use `now()->startOfDay()->diffInDays($lease->end_date, false)`).
- [ ] **Step 4:** Filter + `LeaseRenewalUiTest` + suite → PASS.
- [ ] **Step 5:** Commit `feat(tenant): dashboard onboarding, applications, countdown and next bill`.

### Task 8: Admin "Needs attention" panel and pre-filtered lists

**Files:** Modify `app/Livewire/Admin/AnalyticsDashboard.php` + view, `app/Livewire/Admin/LeaseMappingManager.php` (`mount` reads `status`), `app/Livewire/Admin/TicketCenter.php` + view (filter `unassigned`, `mount` reads `filter`); Test `tests/Feature/AdminAttentionPanelTest.php`

**Interfaces:**
- Consumes: `Invoice::overdue()` (Task 1), PaymentManager `status` param (Task 3).
- Produces: `TicketCenter::$statusFilter` value `'unassigned'` (`status != 'closed'` and `assigned_to` null).

- [ ] **Step 1: Failing tests** — with 1 pending application, 1 renewal request, 1 overdue invoice, 1 unassigned open ticket: dashboard shows the four labels with count `1` and links `/admin/applications`, `/admin/mappings?status=renewal_requested`, `/admin/payments?status=overdue`, `/admin/tickets?filter=unassigned`; with none → `assertSee('All caught up')`; `Livewire::withQueryParams(['status'=>'renewal_requested'])->test(LeaseMappingManager::class)->assertSet('statusFilter','renewal_requested')`; `withQueryParams(['filter'=>'unassigned'])->test(TicketCenter::class)` lists only the unassigned ticket.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement. Note `LeaseMappingManager::mount` already handles `user`/`stall`; add `status` handling there.
- [ ] **Step 4:** Filter + suite → PASS.
- [ ] **Step 5:** Commit `feat(admin): needs-attention panel with filtered links`.

### Task 9: Documents page

**Files:** Modify `app/Livewire/User/DocumentRepository.php` + view; Test `tests/Feature/DocumentRepositoryTest.php`

- [ ] **Step 1: Failing tests** — contract row shows `Jan 01, 2026`-style dates (`$lease->start_date->format('M d, Y')`) and not `00:00:00`; Billing statements lists the tenant's paid invoice with `route('receipt.pdf', id)`, a pending one with `Pay now`, an overdue one with `Overdue`; another tenant's invoice number not shown.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement per spec §8.
- [ ] **Step 4:** Filter + suite → PASS.
- [ ] **Step 5:** Commit `feat(tenant): billing statements and formatted dates in documents`.

### Task 10: Grid editor drag-and-drop with server-side validation

**Files:** Modify `app/Livewire/Admin/StallGridEditor.php` + view, `README.md` (grid line); Test `tests/Feature/StallGridEditorTest.php`

**Interfaces:**
- Produces: `moveStall(int $stallId, int $row, int $col): void`; private `validatePlacement(Stall $stall, int $row, int $col): ?string` returning the error message or null; `placeStallOnGrid` uses it.

- [ ] **Step 1: Failing tests** — move placed stall to empty cell → coords updated; drop unplaced stall → placed with `location_id` set; occupied cell → unchanged + error notify containing "already occupied"; row `gridRows+1` or col `0` → unchanged + "outside this floor's grid" (Review Focus 5); stall from another floor → unchanged + "belongs to another floor" (Review Focus 5); `placeStallOnGrid` out of bounds also rejected.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement server side, then view: `draggable="true"` + `@dragstart="$event.dataTransfer.setData('text/plain', '{{ $id }}')"` on chips/cards; empty cells `@dragover.prevent="over = true"`, `@dragleave="over = false"`, `@drop.prevent="over = false; $wire.moveStall(parseInt($event.dataTransfer.getData('text/plain')), {{ $r }}, {{ $c }})"` with highlight class bound to `over`. Click-to-place unchanged. README: "drag-and-drop or click-to-place".
- [ ] **Step 4:** Filter + suite → PASS.
- [ ] **Step 5:** Commit `feat(admin): drag-and-drop floor grid editor`.

### Task 11: Loading feedback

**Files:** Modify views listed in spec §9; Test `tests/Feature/LoadingStatesTest.php`

- [ ] **Step 1: Failing tests** — one data-provider test: for each (component, method) pair — StallManager `saveStall`, FloorManager (its save method), PaymentManager `recordManualPayment`, LeaseMappingManager `saveMapping`, SystemSettings `saveSettings`, InvoicesCheckout (its checkout method) — render as the right role and assert the HTML contains `wire:target="{method}"` and `wire:loading.attr="disabled"`.
- [ ] **Step 2:** Run → FAIL for the views missing it.
- [ ] **Step 3:** Add `wire:loading.attr="disabled"` + `<span wire:loading wire:target="{method}" class="loading loading-spinner loading-xs"></span>` to every submit/save/destructive button in the spec §9 views; add `wire:loading.class="opacity-60" wire:target="search,statusFilter"` (matching each component's filter props) on list containers.
- [ ] **Step 4:** Filter + suite → PASS.
- [ ] **Step 5:** Commit `feat(ui): consistent loading feedback`.

### Task 12: Accessibility

**Files:** Modify `resources/views/layouts/app.blade.php` (toast `aria-live`), `resources/views/layouts/navigation.blade.php` (theme toggle), `resources/views/livewire/navbar-notification-bell.blade.php`, modal views with icon-only close buttons, chat views (attach/send), `stall-grid-editor` (remove), `advanced-reports` (chart table toggle); Test `tests/Feature/AccessibilityMarkupTest.php`

- [ ] **Step 1: Failing tests** — rendered markup assertions: toast container has `aria-live="polite"`; bell button has `aria-label="Notifications"`; theme toggle has `aria-label="Toggle dark mode"`; ticket chat send button `aria-label="Send message"` and attach `aria-label="Attach files"`; advanced reports contains `View as table` and a `<table` with the month labels; ApplicationManager/StallBrowser modal root has `@keydown.escape.window`.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement: `aria-label` on all icon-only buttons (close buttons `aria-label="Close"`); every modal root gets `@keydown.escape.window="$wire.<closeMethod>()"` and its first input `autofocus`; chart toggle via Alpine `x-data="{ asTable: false }"`.
- [ ] **Step 4:** Filter + suite → PASS.
- [ ] **Step 5:** Commit `feat(a11y): labels, escape-to-close, chart table`.

### Task 13: Dead code removal

**Files:** Delete the list in spec §12; Modify `routes/auth.php` imports.

- [ ] **Step 1:** For each file, `grep -rn` its view name / class name across `app routes resources tests bootstrap config`; record any reference outside the file itself. Referenced files stay (ledger ruling).
- [ ] **Step 2:** `git rm` the unreferenced files; remove now-unused `use` lines in `routes/auth.php`.
- [ ] **Step 3:** `vendor/bin/phpunit` → PASS; `php artisan view:cache && php artisan view:clear` → no errors; `php artisan route:list` → no errors.
- [ ] **Step 4:** Commit `chore: remove unused Breeze scaffolding`.

**Done:** full suite green; `php artisan migrate` and `db:seed --class=DemoSeeder` succeed on local MySQL; manual click-through on demo data (home → stalls → apply; tenant1 dashboard renew; admin attention panel links; grid drag).
