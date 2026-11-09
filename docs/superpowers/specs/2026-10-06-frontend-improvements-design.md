# Front-end & Feature Improvements Design

**Date:** 2026-10-06
**Branch:** `defense-sprint` (continues after the defense-sprint commits)
**Status:** Approved in conversation; pending written-spec review

## Goal

Make every demo flow look complete and close the UX gaps found in the front-end audit: demo data, visitor and tenant guidance, actionable dashboards, overdue-invoice handling, polish, accessibility, grid drag-and-drop, and dead-code removal.

## Decisions already made

- Overdue invoices get **no late fee**. "Overdue" is computed (`status = pending` and `due_date < today`), never stored as an invoice status.
- The grid editor gets **real drag-and-drop**; click-to-place stays as the keyboard/touch fallback.
- All 12 audit findings are in scope. Roles remain `admin` and `user` only.

## 1. Demo data — `Database\Seeders\DemoSeeder`

- Run with `php artisan db:seed --class=DemoSeeder`. Not called from `DatabaseSeeder`.
- Throws and does nothing when `app()->environment('production')`.
- Idempotent: `updateOrCreate` keyed on floor name, stall number, user email, invoice number, application email+stall; never deletes.
- Content:
  - Floors: "Ground Floor" 6×8, "Second Floor" 5×6, "Food Court" 4×5, each with a one-line description, `sort_order` 1–3.
  - ~30 stalls (prefixes `G-`, `S-`, `F-`), placed on the grids; statuses mostly `available`, plus `occupied` for leased stalls, one `reserved`, one `maintenance`. Two Ground Floor stalls left unplaced (`grid_row`/`grid_col` null).
  - 4 tenants `tenant1@demo.test` … `tenant4@demo.test`, password `password`, `role = user`, distinct phones `0917000000{1-4}`, email verified.
  - One active `good_standing` lease per tenant on an occupied stall. tenant1's lease ends today+45 days (renewal eligible); tenant2's lease has `renewal_status = requested`, `requested_end_date = end_date + 1 year`.
  - Invoices: paid monthly invoices for each lease for the previous 6 months (with `paid_at` and a `PaymentTransaction`), one pending invoice due in 7 days (tenant3), one pending invoice due 10 days ago (tenant4, overdue).
  - Tickets: one open ticket (tenant1) with two messages, one closed ticket (tenant3).
  - Applications: two `pending` (new emails, different available stalls), one `rejected` with a reason.
- README gets a "Demo data & accounts" section listing the command and the accounts.

## 2. Home page

- Hero primary button: **Browse Available Stalls** → `route('stalls.browse')`. Secondary: **Sign In** (opens login modal). "Create Account" button removed from the hero (still in the navbar).
- Stat cards from live data: number of floors, available stalls, total stalls, and the existing static "PayMongo · GCash & Card Payments" card.

## 3. Tenant onboarding

- Tenant dashboard empty-lease state: heading "You don't have a stall yet", text "Browse available stalls and apply online — we'll email you when it's reviewed.", button **Browse available stalls** → `/stalls`.
- **My applications** card on the tenant dashboard, shown only when the tenant has applications: applications where `user_id = tenant id` OR `LOWER(applicant_email) = LOWER(tenant email)`, newest first, max 5, each with stall number, submitted date, status badge, and rejection reason when rejected.

## 4. Notifications open their link

- `NavbarNotificationBell::open(int $id)` marks the tenant's own notification read and, when `link` is non-empty and its host equals `parse_url(config('app.url'), PHP_URL_HOST)` or the current request host, redirects to it (`$this->redirect($link, navigate: true)`). Other links: mark read only.
- The dropdown items call `open` instead of `markAsRead`.

## 5. Admin "Needs attention" panel

- Top of the admin analytics dashboard: four cards — Pending applications (`StallApplication` pending), Renewal requests (active leases with `renewal_status = requested`), Overdue invoices (computed), Unassigned tickets (`status != closed` and `assigned_to` null).
- Each card links to the pre-filtered list: `/admin/applications` (default pending), `/admin/mappings?status=renewal_requested`, `/admin/payments?status=overdue`, `/admin/tickets?filter=unassigned`.
- `LeaseMappingManager`, `PaymentManager` and `TicketCenter` read those query parameters into their filters (Livewire `#[Url]` or `mount`). `PaymentManager` gains filter value `overdue`; `TicketCenter` gains filter `unassigned`.
- When all four counts are 0: a single line "All caught up ✓".

## 6. Tenant dashboard

- For each active lease: "Ends in N days" (or "Ended" / "Ends today"), the renewal state, and **Request renewal** when `LeaseService::isRenewalEligible` — reusing MyLeases' renewal modal logic (extracted so both components share it; see plan).
- **Next bill due:** earliest pending invoice — amount, due date, red "Overdue" badge when overdue, **Pay now** → `route('user.payments')`. Hidden when no pending invoices.

## 7. Overdue invoices

- `Invoice::isOverdue(): bool` and `Invoice::scopeOverdue($q)` (`status = pending` and `due_date < today()`).
- Red "Overdue" badge wherever invoices are listed: tenant Invoices & Payments, admin Payments, tenant Documents, tenant dashboard.
- Migration: `invoices.overdue_notified_at` timestamp nullable.
- Command `stallm:flag-overdue` (daily 08:30 in `routes/console.php`): for overdue invoices with `overdue_notified_at` null — set the matching active lease `payment_status = past_due`, send the tenant one notice (in-app type `warning`, email, SMS: "Your invoice {number} for Stall {n} was due on {date} and is now overdue. Please pay as soon as possible."), set `overdue_notified_at`; then one in-app notification per admin: "{count} invoice(s) became overdue today" linking to `/admin/payments?status=overdue`. No admin notice when count is 0.
- Paying an overdue invoice keeps the existing path (`markGoodStandingOnPayment` → `good_standing`); covered by a test.

## 8. Documents page

- Contract dates formatted `M d, Y`.
- **Billing statements** table: every invoice of the tenant — number, billing period, amount, status (Paid / Pending / Overdue). Paid → receipt PDF link (`route('receipt.pdf', id)`); unpaid → **Pay now** link.

## 9. Loading feedback

- Every submit/save/destructive button in admin and tenant components gets `wire:loading.attr="disabled"` plus a `loading-spinner` shown via `wire:target` for its action. Covered views: stall-manager, floor-manager, payment-manager, lease-mapping-manager, stall-grid-editor, system-settings, invoices-checkout, ticket-center, ticket-manager.
- List/dashboard content areas get `wire:loading.class="opacity-60"` scoped to their search/filter targets.

## 10. Accessibility

- `aria-label` on every icon-only button: modal close buttons, theme toggle, notification bell, chat attach/send, grid remove.
- Toast container gets `aria-live="polite"`.
- Modals: `@keydown.escape.window` calls the component's close method; first input gets `autofocus`/`x-init` focus on open.
- Revenue chart (advanced reports): "View as table" toggle rendering the same labels/values in a `<table>`.

## 11. Grid editor drag-and-drop

- Unplaced stall chips and placed stall cards are `draggable="true"` (Alpine `@dragstart` stores the stall id); empty cells handle `@dragover.prevent` / `@drop` and call `moveStall(stallId, row, col)`; drop target highlights while dragging over.
- `StallGridEditor::moveStall(int $stallId, int $row, int $col): void` and `placeStallOnGrid` share one validator:
  - `1 ≤ row ≤ gridRows`, `1 ≤ col ≤ gridCols`, else error toast "That cell is outside this floor's grid.";
  - stall's `location_id` is null or equals the selected floor, else error toast "That stall belongs to another floor.";
  - target cell empty (excluding the stall itself), else the existing "already occupied" toast. No swapping.
- Click-to-place unchanged as the fallback. README line updated to "drag-and-drop or click-to-place".

## 12. Dead code removal

Delete, after confirming no references outside themselves: `resources/views/welcome.blade.php`, `resources/views/dashboard.blade.php`, `resources/views/livewire/pages/auth/*`, `resources/views/livewire/auth/login.blade.php`, `resources/views/livewire/auth/register.blade.php`, `resources/views/livewire/profile/update-password-form.blade.php`, `resources/views/livewire/profile/update-profile-information-form.blade.php`, `resources/views/livewire/profile/delete-user-form.blade.php`, `resources/views/livewire/welcome/navigation.blade.php`, `resources/views/livewire/layout/navigation.blade.php`, `app/Livewire/Auth/Login.php`, `app/Livewire/Auth/Register.php`, `app/Livewire/Forms/LoginForm.php`, the matching `app/Livewire/Profile/{UpdatePasswordForm,UpdateProfileInformationForm,DeleteUserForm}.php`, and their imports in `routes/auth.php`. Any file found referenced stays and is listed in the final report.

## Testing

Feature/unit tests per item: seeder idempotency and production refusal; home CTA and live stats; tenant empty state and My applications matching (by user_id and by email casing); notification link redirect vs. foreign-host link; attention counts and query-param filters; tenant dashboard countdown/next bill; `isOverdue`/scope, flag-overdue command (one-time notices, lease past_due, admin summary, payment restores good standing); documents date format and statements; grid `moveStall` bounds/ownership/occupied checks; dead-code removal keeps the suite green. Loading/aria/escape changes are verified by rendered-markup assertions on representative views.

## Out of scope

Late fees, invoice status changes, stall swapping in the grid, touch drag polyfills, production deployment work.
