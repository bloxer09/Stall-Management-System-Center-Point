# Defense Sprint Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the audit's functional gaps (stall application portal, admin 2FA, lease renewals, typing indicators) and fix the remaining security issues before the Saturday 2026-10-10 defense.

**Architecture:** Laravel 13 + Livewire 3 monolith. New business rules go in `app/Services` (existing pattern: `LeaseService`, `InvoiceService`); Livewire components stay thin and call services. Five phases, each leaving `main` green and committed.

**Tech Stack:** PHP 8.2+, Laravel 13, Livewire 3, Alpine, Tailwind/DaisyUI, PHPUnit 11 (sqlite `:memory:`), Dompdf.

**Spec:** `docs/superpowers/specs/2026-10-06-defense-sprint-design.md`

## Global Constraints

- Roles are only `admin` and `user`. No `staff` anywhere in new code.
- 2FA applies to admins only.
- Run tests with `php artisan test`; the full suite must pass before every commit.
- Emails go through `MailService::sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool`; tests use `Mail::fake()` and assert `GenericNotificationMail` (public `subjectText`).
- SMS goes through `SmsService::sendSms(string $number, string $message): bool`; tests replace it with `$this->mock(SmsService::class)` so nothing writes to `storage/logs/sms-catcher.json`.
- In-app notifications: `App\Models\Notification::send(int $userId, string $type, string $title, string $message, ?string $link = null)`. Audit: `AuditLog::record(string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null, ?int $userId = null)`.
- Email and SMS are sent **after** the DB transaction commits; a send failure never rolls back the state change — it is logged and the admin sees a warning toast (`$this->dispatch('notify', type: 'warning', message: ...)`).
- Private files use the `local` disk via `FileUploadService`; downloads go through `FileController` with an authorization check.
- Livewire component tests use `Livewire::actingAs($user)->test(Component::class)` (existing idiom in `tests/Feature/ProfileModalTest.php`).
- Never stage these user-owned files unless the task says so: `app/Mail/GenericNotificationMail.php`, `app/Services/MailService.php`, `resources/views/livewire/auth/auth-modals.blade.php`, `tests/Feature/MailServiceTest.php`, `.phpunit.result.cache`. Stage files by explicit path, never `git add -A`/`.`.

## Review Focus

1. **Applicant email casing/whitespace** — `" Juan@Mail.com"` applying when `juan@mail.com` already has an account must link the existing account, not create a duplicate (users.email is unique; a duplicate would 500). Test in Task 9.
2. **Double approval** — two admins (or a double click) approving the same application must create at most one user and send one email; the second call is a no-op with an error toast. Test in Task 9.
3. **Set-password link reuse** — a signed link already used to set a password must not let someone set it again within the 72 hours. Test in Task 9.
4. **Admin removes phone while 2FA is on** — would lock them out at next login; profile save must refuse to clear the phone while 2FA is enabled. Test in Task 11.
5. **Login lockout bypass by email casing** — `Admin@x.com` and `admin@x.com` must share one lockout counter. Test in Task 1.

---

## Task 0: Pre-flight (gate — do not skip)

- [ ] **Step 1:** Run `git status --short`. If `app/Livewire/Auth/AuthModals.php` still shows as modified from the user's pending mail/OTP work, STOP and ask the user to commit or stash their mail/OTP changes first (Task 1 edits the same file). Do not commit their work yourself.
- [ ] **Step 2:** Run `php artisan test`. Expected: all tests pass (46 at plan time). Record the count.

---

# Phase 1 — Security fixes and cleanup

### Task 1: Login rate limiting (S1)

**Files:**
- Modify: `app/Livewire/Auth/AuthModals.php` (`processLogin`, ~line 173)
- Test: `tests/Feature/LoginThrottleTest.php` (create)

**Interfaces:**
- Produces: throttle key format `'login:' . Str::lower(trim($email)) . '|' . request()->ip()`.

- [ ] **Step 1: Write the failing tests**

```php
public function test_sixth_attempt_is_locked_even_with_correct_password(): void
{
    $user = User::factory()->create(['email' => 'admin@x.com', 'password' => Hash::make('right-pass'), 'role' => 'admin']);
    $c = Livewire::test(AuthModals::class);
    for ($i = 0; $i < 5; $i++) {
        $c->set('loginEmail', 'admin@x.com')->set('loginPassword', 'wrong')->call('processLogin');
    }
    $c->set('loginPassword', 'right-pass')->call('processLogin')->assertHasErrors('loginEmail');
    $this->assertGuest();
}

public function test_lockout_is_shared_across_email_casing(): void   // Review Focus 5
{
    User::factory()->create(['email' => 'admin@x.com', 'password' => Hash::make('right-pass')]);
    $c = Livewire::test(AuthModals::class);
    foreach (['Admin@x.com', 'ADMIN@X.COM', 'admin@x.com', 'Admin@X.com', ' admin@x.com'] as $e) {
        $c->set('loginEmail', $e)->set('loginPassword', 'wrong')->call('processLogin');
    }
    $c->set('loginEmail', 'admin@x.com')->set('loginPassword', 'right-pass')->call('processLogin');
    $this->assertGuest();
}

public function test_successful_login_clears_counter(): void
{
    $user = User::factory()->create(['email' => 'u@x.com', 'password' => Hash::make('p@ss')]);
    $c = Livewire::test(AuthModals::class);
    for ($i = 0; $i < 4; $i++) { $c->set('loginEmail', 'u@x.com')->set('loginPassword', 'bad')->call('processLogin'); }
    $c->set('loginPassword', 'p@ss')->call('processLogin');
    $this->assertAuthenticatedAs($user);
    $this->assertSame(0, RateLimiter::attempts('login:u@x.com|127.0.0.1'));
}
```

Note: the email validation rule is `required|email`; `' admin@x.com'` must be trimmed before validation (`$this->loginEmail = trim($this->loginEmail)` at the top of `processLogin`).

- [ ] **Step 2:** Run `php artisan test --filter=LoginThrottleTest`. Expected: FAIL (user gets logged in on 6th attempt).
- [ ] **Step 3: Implement in `processLogin`:** trim email; if `RateLimiter::tooManyAttempts($key, 5)` add error `"Too many login attempts. Please try again in {$seconds} seconds."` and return; on bad credentials `RateLimiter::hit($key, 60)`; on success `RateLimiter::clear($key)`.
- [ ] **Step 4:** Run the filter again, then `php artisan test`. Expected: all PASS.
- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Auth/AuthModals.php tests/Feature/LoginThrottleTest.php
git commit -m "fix(auth): rate-limit login attempts per email and IP"
```

### Task 2: Unguessable receipt verification links (S2)

**Files:**
- Create: `database/migrations/2026_10_06_000002_add_verification_token_to_invoices_table.php`
- Modify: `app/Models/Invoice.php` (fillable + `booted()` creating hook), `routes/web.php` (`/verify-receipt/{token}`), `app/Http/Controllers/ReceiptVerificationController.php` (`verify(string $token)`), `app/Services/PdfReceiptService.php:22`
- Test: `tests/Feature/ReceiptVerificationTest.php` (create)

**Interfaces:**
- Produces: `invoices.verification_token` (char 36, unique). `route('receipt.verify', ['token' => $invoice->verification_token])`.

- [ ] **Step 1: Write the failing tests** — helper creates a user, location, stall and an `Invoice` with `Invoice::create([...])`.
  - `test_new_invoice_gets_uuid_token`: `assertTrue(Str::isUuid($invoice->verification_token))`.
  - `test_verify_by_token_shows_receipt`: `get('/verify-receipt/'.$invoice->verification_token)->assertOk()->assertSee($invoice->invoice_number)`.
  - `test_verify_by_numeric_id_returns_404`: `get('/verify-receipt/'.$invoice->id)->assertNotFound()`.
  - `test_receipt_pdf_qr_points_to_token_url`: `app(PdfReceiptService::class)` — assert the verification URL it builds contains the token (expose via a small public `verificationUrl(Invoice $invoice): string` method on `PdfReceiptService` and use it internally).
- [ ] **Step 2:** Run `php artisan test --filter=ReceiptVerificationTest`. Expected: FAIL.
- [ ] **Step 3: Implement.** Migration: add nullable `uuid('verification_token')`, backfill each existing row with `Str::uuid()` in a chunked loop, then `->unique()` (and make non-null on MySQL only: `if (DB::getDriverName() === 'mysql')`). `Invoice::booted()`: `static::creating(fn ($i) => $i->verification_token ??= (string) Str::uuid())`. Controller `verify(string $token)` uses `Invoice::where('verification_token', $token)->firstOrFail()`; route constraint `->whereUuid('token')`.
- [ ] **Step 4:** Run the filter, then `php artisan migrate` (local MySQL) and `php artisan test`. Expected: all PASS, migration DONE.
- [ ] **Step 5: Commit** (explicit paths).

### Task 3: Restrict ticket stall to tenant's own leases (S3)

**Files:**
- Modify: `app/Livewire/User/TicketManager.php` (rules ~line 52 and ticket creation ~line 104)
- Test: `tests/Feature/TicketStallScopeTest.php` (create)

- [ ] **Step 1: Write the failing tests**
  - `test_tenant_cannot_open_ticket_on_stall_they_do_not_lease`: tenant A has active lease on S-1; set `stall_id` to S-2 (leased by B), call the create action → `assertHasErrors('stall_id')`, `assertDatabaseMissing('tickets', ['stall_id' => $s2->id])`.
  - `test_tenant_can_open_ticket_on_own_leased_stall`: same with S-1 → `assertHasNoErrors()`, ticket row exists with `stall_id = S-1`.
- [ ] **Step 2:** Run filter. Expected: first test FAILS.
- [ ] **Step 3:** Replace the rule with `['nullable', Rule::in($ownActiveStallIds)]` where `$ownActiveStallIds = StallMapping::where('user_id', auth()->id())->where('is_active', true)->pluck('stall_id')->all()`, built in a `rules(): array` method (remove the `stall_id` entry from the static `$rules` property). Error message: "You can only report issues for stalls you currently lease."
- [ ] **Step 4:** Filter, then full suite. Expected: PASS.
- [ ] **Step 5: Commit.**

### Task 4: Dependency advisories (S4)

**Files:** `composer.json` (only if a constraint must change), `composer.lock`

- [ ] **Step 1:** Run `composer audit --format=plain`. Record the affected package names.
- [ ] **Step 2:** Run `composer update <each affected package> --with-dependencies`. Do not update `laravel/framework` across a minor version.
- [ ] **Step 3:** Run `composer audit`. Expected: no advisories, or only ones requiring a framework upgrade — list those in `docs/technical-debt.md` under a new "Post-defense: dependency advisories" heading.
- [ ] **Step 4:** Run `php artisan test`. Expected: PASS.
- [ ] **Step 5: Commit** `composer.json composer.lock docs/technical-debt.md`.

### Task 5: Cleanup C1–C5

**Files:**
- Modify: `resources/views/layouts/app.blade.php` (sidebar), `app/Livewire/Admin/TicketCenter.php:264`, `app/Livewire/User/TicketManager.php:119,226`, `README.md`, `.gitignore`
- Delete: `app/Http/Controllers/Auth/VerifyEmailController.php`, `vite.config.js.timestamp-*.mjs`
- Test: `tests/Feature/NavigationLinksTest.php` (create)

- [ ] **Step 1: Write the failing tests**
  - `test_admin_sidebar_links_reports`: admin `get(route('admin.dashboard'))->assertSee(route('admin.reports'))`.
  - `test_tenant_sidebar_links_documents`: tenant `get(route('user.dashboard'))->assertSee(route('user.documents'))`.
- [ ] **Step 2:** Run filter. Expected: FAIL.
- [ ] **Step 3:** Add both links to the sidebar, matching neighboring link markup and icon style. Replace `whereIn('role', ['admin', 'staff'])` with `where('role', 'admin')` in the three places. Delete the controller (confirm `grep -rn VerifyEmailController app routes` returns nothing first). README: `stallm:generate-invoices` → `billing:generate-invoices`. Append to `.gitignore`: `/storage/framework/testing/`, `.phpunit.result.cache`, `vite.config.js.timestamp-*`. Run `git rm -r --cached storage/framework/testing .phpunit.result.cache` and delete the timestamp files.
- [ ] **Step 4:** Filter, then full suite. Expected: PASS.
- [ ] **Step 5: Commit** (explicit paths, including the `git rm --cached` removals): `chore: sidebar links, drop staff leftovers, repo hygiene`.

**Phase 1 checkpoint:** `php artisan test` all green; `git log --oneline -5` shows Tasks 1–5.

---

# Phase 2 — Stall application portal

### Task 6: Public stall browser and live home floor directory

**Files:**
- Create: `app/Livewire/Portal/StallBrowser.php`, `resources/views/livewire/portal/stall-browser.blade.php`, `resources/views/pages/stalls.blade.php`
- Modify: `routes/web.php` (public group), `resources/views/pages/home.blade.php:91-150` (floor directory), `resources/views/livewire/welcome/navigation.blade.php` (add "Browse stalls")
- Test: `tests/Feature/StallBrowserTest.php` (create)

**Interfaces:**
- Produces: route `stalls.browse` → `GET /stalls`. `StallBrowser` public props `?int $activeLocationId`, `?int $selectedStallId`; methods `selectLocation(int $id): void`, `selectStall(int $id): void`, `openApply(): void` (Task 7 fills the apply modal).

- [ ] **Step 1: Write the failing tests**
  - `test_guest_can_view_stall_browser`: `get('/stalls')->assertOk()->assertSee('S-101')`.
  - `test_only_available_stall_offers_apply`: `Livewire::test(StallBrowser::class)->call('selectStall', $available->id)->assertSee('Apply for this stall')`; then with an `occupied` stall → `assertDontSee('Apply for this stall')->assertSee('Occupied')`.
  - `test_stalls_without_or_outside_grid_position_are_listed_not_crashing`: location `grid_rows=2, grid_cols=2`; stall A has null position, stall B has `grid_row=5` → page OK, both stall numbers visible in the "Other stalls on this floor" list.
  - `test_home_shows_real_floor_counts`: location "Ground Floor" with 3 stalls, 2 available → `get('/')->assertSee('Ground Floor')->assertSee('2 of 3 available')->assertDontSee('Ground Floor Arcade')`.
- [ ] **Step 2:** Run filter. Expected: FAIL.
- [ ] **Step 3: Implement.** Render floors ordered by `sort_order`; default `activeLocationId` = first floor. Grid cell colors: available = success, reserved = warning, occupied = neutral, maintenance = error (DaisyUI badge/bg classes). A stall is "on the grid" only when both coords are non-null and `1 ≤ row ≤ grid_rows`, `1 ≤ col ≤ grid_cols`; others go in the list below. Page uses the guest/public layout the home page uses.
- [ ] **Step 4:** Filter, then full suite. Expected: PASS.
- [ ] **Step 5: Commit** `feat(portal): public stall browser and live floor directory`.

### Task 7: Application submission

**Files:**
- Create: `app/Livewire/Forms/StallApplicationForm.php`, `app/Services/StallApplicationService.php`
- Modify: `app/Livewire/Portal/StallBrowser.php` + view (apply modal), `app/Services/FileUploadService.php` (add `uploadApplicationDocument(UploadedFile $file): string` storing to `application_documents/` on `local`), `app/Models/StallApplication.php` (fillable/casts/relations `preferredStall()`, `reviewer()`, `user()` if missing)
- Test: `tests/Feature/StallApplicationSubmitTest.php` (create)

**Interfaces:**
- Produces: `StallApplicationService::submit(array $data, ?UploadedFile $document, string $ip): StallApplication` — throws `Illuminate\Validation\ValidationException` for business-rule failures (stall not available, duplicate pending, rate limit). `StallBrowser::submitApplication(): void`. Form fields exactly: `applicant_name, applicant_email, applicant_phone, business_name, business_type, message, document, website` (`website` is the honeypot).

- [ ] **Step 1: Write the failing tests** (all use `Mail::fake()`, `Storage::fake('local')`)
  - `test_valid_submission_creates_pending_application_and_notifies`: two admins exist; submit valid data + a `UploadedFile::fake()->create('permit.pdf', 200, 'application/pdf')` → row with `status=pending`, `preferred_stall_id`, `document` stored (`Storage::disk('local')->assertExists($app->document)`); `Mail::assertSent(GenericNotificationMail::class, fn ($m) => $m->hasTo('juan@mail.com'))`; `assertSame(2, Notification::where('type', 'application')->count())`.
  - `test_email_is_normalised`: submit `applicant_email = '  Juan@Mail.COM '` → stored as `juan@mail.com`.
  - `test_honeypot_discards_silently`: `website='http://spam'` → no errors, `assertDatabaseCount('stall_applications', 0)`, success message still shown.
  - `test_fourth_submission_in_an_hour_from_same_ip_is_rejected`: 3 valid submissions for 3 different stalls, 4th → `assertHasErrors('form.applicant_email')` with message containing "Too many applications".
  - `test_duplicate_pending_for_same_email_and_stall_rejected`.
  - `test_cannot_apply_for_non_available_stall`: stall flipped to `occupied` after selection → error, no row.
  - `test_document_type_and_size_validated`: `.exe` → error; 6 MB pdf → error.
- [ ] **Step 2:** Run filter. Expected: FAIL.
- [ ] **Step 3: Implement.** Form rules exactly per spec: name `required|string|max:100`, email `required|email|max:120`, phone `required|string|max:30`, business_name `required|string|max:150`, business_type `required|string|max:100`, message `nullable|string|max:1000`, document `nullable|file|mimes:pdf,jpg,jpeg,png|max:5120`. Rate limit key `'stall-apply:' . $ip`, 3 hits, decay 3600 s. Service lowercases/trims email, re-checks `Stall::status === 'available'`, checks duplicate pending, stores doc, creates row in a transaction; after commit sends confirmation email (subject: "We received your stall application") and `Notification::send($adminId, 'application', 'New stall application', "{$name} applied for Stall {$number}", route('admin.applications'))` for each admin. Route `admin.applications` is created in Task 8 — until then guard with a temporary `url('/admin/applications')`; Task 8 switches to `route()`.
- [ ] **Step 4:** Filter, then full suite. Expected: PASS.
- [ ] **Step 5: Commit** `feat(portal): online stall application submission`.

### Task 8: Admin application list, detail, document download, reject

**Files:**
- Create: `app/Livewire/Admin/ApplicationManager.php`, `resources/views/livewire/admin/application-manager.blade.php`, `resources/views/pages/admin/applications.blade.php`
- Modify: `routes/web.php` (admin group: `admin.applications`; auth group: `file.application-document` → `GET /files/application-document/{id}`), `app/Http/Controllers/FileController.php` (`downloadApplicationDocument(int $applicationId)`, admin only), `resources/views/layouts/app.blade.php` (sidebar link + pending badge), `app/Services/StallApplicationService.php` (`reject`), Task 7's notification link → `route('admin.applications')`
- Test: `tests/Feature/ApplicationReviewTest.php` (create)

**Interfaces:**
- Produces: `StallApplicationService::reject(StallApplication $app, User $admin, string $reason): StallApplication` (throws `DomainException('Application has already been reviewed.')` if not pending). `ApplicationManager` props `string $statusFilter = 'pending'`, `string $search = ''`, `?int $selectedId`, `string $rejectionReason = ''`; methods `openDetail(int $id)`, `reject()`, `approve()` (Task 9).

- [ ] **Step 1: Write the failing tests**
  - `test_admin_sees_pending_applications_by_default` / `test_search_matches_business_name_and_stall_number`.
  - `test_tenant_gets_403_on_applications_page_and_document`: tenant `get('/admin/applications')->assertForbidden()`; `get(route('file.application-document', $app->id))->assertForbidden()`.
  - `test_admin_can_download_application_document`.
  - `test_reject_requires_reason`: `call('reject')` with empty reason → `assertHasErrors('rejectionReason')`.
  - `test_reject_sets_fields_emails_applicant_and_audits`: `status=rejected`, `rejection_reason`, `reviewed_by=$admin->id`, `reviewed_at` not null; `Mail::assertSent(... hasTo(applicant))`; `assertDatabaseHas('audit_logs', ['entity_type' => 'StallApplication', 'entity_id' => $app->id])`.
  - `test_cannot_reject_already_reviewed`.
- [ ] **Step 2:** Run filter. Expected: FAIL.
- [ ] **Step 3: Implement.** Reason rule `required|string|max:500`. Badge count = `StallApplication::where('status','pending')->count()`, shown only when > 0. Rejection email subject: "Update on your stall application".
- [ ] **Step 4:** Filter, full suite. Expected: PASS.
- [ ] **Step 5: Commit** `feat(admin): application review list and rejection`.

### Task 9: Approve, set-password page, lease prefill

**Files:**
- Create: `app/Livewire/Auth/SetPassword.php`, `resources/views/livewire/auth/set-password.blade.php`, `resources/views/pages/auth/set-password-expired.blade.php`
- Modify: `app/Services/StallApplicationService.php` (`approve`, `setPasswordUrl`), `app/Livewire/Admin/ApplicationManager.php` + view (`approve()`, "Assign stall" link), `routes/web.php` (`account.set-password` → `GET /account/set-password/{user}`, middleware `signed`; invalid signature renders the expired page), `app/Livewire/Admin/LeaseMappingManager.php` (`mount()` reads `?user=&stall=` and opens the create modal pre-filled)
- Test: `tests/Feature/ApplicationApprovalTest.php` (create)

**Interfaces:**
- Consumes: `StallApplicationService` from Task 7/8.
- Produces: `StallApplicationService::approve(StallApplication $app, User $admin): array{application: StallApplication, user: User, created: bool}` — throws `DomainException('Application has already been reviewed.')` when not pending (row locked with `lockForUpdate()` inside the transaction). `StallApplicationService::setPasswordUrl(User $user): string` = `URL::temporarySignedRoute('account.set-password', now()->addHours(72), ['user' => $user->id, 'v' => substr(sha1($user->password), 0, 12)])`.

- [ ] **Step 1: Write the failing tests** (`Mail::fake()`)
  - `test_approve_new_email_creates_tenant_and_sends_set_password_link`: user with `role=user`, `email_verified_at` not null, name/phone copied; application `status=approved`, `user_id`, `reviewed_by`; mail sent whose `htmlContent` contains `/account/set-password/`; audit row.
  - `test_approve_existing_email_links_account_without_creating`: existing `juan@mail.com`; application email `Juan@Mail.com` (stored lowercased by Task 7, but insert raw here to simulate legacy data) → `assertDatabaseCount('users', $before)`, `user_id` = existing id, in-app notification to that user. *(Review Focus 1 — compare with `Str::lower(trim())`.)*
  - `test_double_approve_is_noop`: call `approve` twice → second throws/flags error toast; exactly one user created, one mail sent. *(Review Focus 2)*
  - `test_set_password_with_valid_link_sets_password_and_logs_in`: `get($url)->assertOk()`; Livewire set password `'N3w-Passw0rd!'` + confirmation → `Hash::check` true, `assertAuthenticatedAs`, redirect `user.dashboard`.
  - `test_expired_or_tampered_link_rejected`: `travel(73)->hours()` → response shows "This link has expired"; altered `user` param → same.
  - `test_link_cannot_be_reused_after_password_set`: use once, log out, `get($url)` again → expired page. *(Review Focus 3 — the `v` param no longer matches.)*
  - `test_assign_stall_prefills_lease_modal`: `Livewire::withQueryParams(['user' => $u->id, 'stall' => $s->id])->actingAs($admin)->test(LeaseMappingManager::class)->assertSet('user_id', $u->id)->assertSet('stall_id', $s->id)->assertSet('showModal', true)` (use the component's actual modal flag name).
- [ ] **Step 2:** Run filter. Expected: FAIL.
- [ ] **Step 3: Implement.** In `SetPassword::mount(User $user)` also verify `request('v') === substr(sha1($user->password), 0, 12)`, else show expired page. Password rules: reuse the rules from `app/Livewire/Auth/Register.php` / `UpdatePasswordForm`. Approval email subjects: new account "Your stall application was approved — set your password"; existing "Your stall application was approved". If an email fails, approval stands and the admin gets a warning toast.
- [ ] **Step 4:** Filter, full suite. Expected: PASS.
- [ ] **Step 5: Commit** `feat(admin): approve applications with tenant account creation`.

**Phase 2 checkpoint:** full suite green; manual: `/stalls` → apply → admin approves → open set-password link from Mailpit → tenant dashboard → admin "Assign stall".

---

# Phase 3 — Admin 2FA (SMS OTP)

### Task 10: TwoFactorService

**Files:**
- Create: `app/Services/TwoFactorService.php`
- Modify: `app/Models/TwoFactorCode.php` (fillable/casts if needed), `app/Models/User.php` (`two_factor_enabled` bool cast, `two_factor_confirmed_at` datetime cast, `hasTwoFactorEnabled(): bool` = admin && enabled)
- Test: `tests/Unit/TwoFactorServiceTest.php` (create)

**Interfaces:**
- Produces:
  - `sendCode(User $user): bool` — deletes old codes, stores `hash('sha256', $code)`, `expires_at = now()->addMinutes(10)`, sends exactly: `"Your STALLM verification code is {$code}. It expires in 10 minutes."`. Returns the SMS result. Never throws for throttling: returns `false` without sending when `RateLimiter::tooManyAttempts('2fa-send:'.$user->id, 1)`; otherwise hits that key with decay 60 s.
  - `secondsUntilResend(User $user): int`.
  - `verify(User $user, string $code): bool` — false when `RateLimiter::tooManyAttempts('2fa-verify:'.$user->id, 5)`; wrong code hits (decay 600); success deletes all codes and clears the limiter.
  - `attemptsRemaining(User $user): int`.

- [ ] **Step 1: Write the failing tests** (mock `SmsService`, capture the message with `->shouldReceive('sendSms')->andReturnUsing(function ($n, $m) use (&$sent) { $sent = $m; return true; })`)
  - `test_send_stores_hash_not_plain_code`: extract the 6 digits from `$sent` with `preg_match('/\d{6}/')`; `assertNotSame($code, $row->code)`; `assertSame(hash('sha256', $code), $row->code)`.
  - `test_verify_accepts_correct_code_once`: true, then same code → false.
  - `test_expired_code_fails`: `travel(11)->minutes()` → false.
  - `test_new_code_invalidates_old`: send, `travel(61)->seconds()`, send again → old code false.
  - `test_resend_throttled_for_60_seconds`: second immediate `sendCode` returns false and SMS called once.
  - `test_five_wrong_attempts_block_even_correct_code`.
  - `test_never_logs_the_code`: `Log::spy()`; after send+verify, `Log::shouldNotHaveReceived(...)` with an argument containing the code (check via `Log::spy()->shouldNotHaveReceived('info', [Mockery::on(fn ($m) => str_contains($m, $code))])` for `info`, `debug`, `warning`).
- [ ] **Step 2:** Run filter. Expected: FAIL.
- [ ] **Step 3:** Implement. Code = `str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT)`; compare with `hash_equals`.
- [ ] **Step 4:** Filter, full suite. Expected: PASS.
- [ ] **Step 5: Commit** `feat(2fa): OTP service with hashed single-use codes`.

### Task 11: Enrollment in ProfileModal

**Files:**
- Modify: `app/Livewire/Profile/ProfileModal.php`, `resources/views/livewire/profile/profile-modal.blade.php`
- Test: `tests/Feature/TwoFactorEnrollmentTest.php` (create)

**Interfaces:**
- Consumes: `TwoFactorService::sendCode/verify`.
- Produces: `ProfileModal` props `string $twoFactorCode = ''`, `string $twoFactorPassword = ''`, `bool $twoFactorCodeSent = false`; methods `sendTwoFactorEnableCode()`, `confirmTwoFactor()`, `disableTwoFactor()`.

- [ ] **Step 1: Write the failing tests** (mock `SmsService`)
  - `test_tenant_does_not_see_2fa_section`: `assertDontSee('Two-Factor Authentication')`.
  - `test_admin_without_phone_is_told_to_add_one`: `assertSee('Add a phone number first')`; `sendTwoFactorEnableCode` sends nothing.
  - `test_wrong_code_does_not_enable` / `test_correct_code_enables_and_audits`: `two_factor_enabled` true, `two_factor_confirmed_at` not null, audit row, `session('two_factor_verified')` true.
  - `test_disable_requires_correct_password`.
  - `test_cannot_clear_phone_while_2fa_enabled`: set `phone=''`, call the existing profile-save method → `assertHasErrors('phone')`, phone unchanged. *(Review Focus 4)*
- [ ] **Step 2:** Run filter. Expected: FAIL.
- [ ] **Step 3:** Implement. Phone rule becomes `required` (with message "Turn off two-factor authentication before removing your phone number.") when `hasTwoFactorEnabled()`.
- [ ] **Step 4:** Filter, full suite. Expected: PASS.
- [ ] **Step 5: Commit** `feat(2fa): admin enrollment in profile`.

### Task 12: RequiresTwoFactor middleware and challenge page

**Files:**
- Create: `app/Http/Middleware/RequiresTwoFactor.php`, `app/Livewire/Auth/TwoFactorChallenge.php`, `resources/views/livewire/auth/two-factor-challenge.blade.php`, `resources/views/pages/auth/two-factor.blade.php`
- Modify: `bootstrap/app.php` (alias `'2fa' => RequiresTwoFactor::class`), `routes/web.php` (admin group middleware `['auth', 'role:admin', '2fa']`; `GET /two-factor` named `two-factor.challenge` under `auth`)
- Test: `tests/Feature/TwoFactorChallengeTest.php` (create)

**Interfaces:**
- Consumes: `TwoFactorService` (Task 10), `User::hasTwoFactorEnabled()`.
- Produces: session key `two_factor_verified` (bool). Challenge methods `verify()`, `resend()`, `cancel()`; prop `string $code = ''`.

- [ ] **Step 1: Write the failing tests** (mock `SmsService`, capture code)
  - `test_admin_with_2fa_is_redirected_from_admin_routes`: `actingAs($admin)->get('/admin/dashboard')->assertRedirect(route('two-factor.challenge'))`.
  - `test_oauth_style_login_is_also_challenged`: `Auth::login($admin)` (no modal) then same assertion.
  - `test_admin_without_2fa_and_tenants_pass_through`.
  - `test_challenge_mount_sends_code_and_masks_phone`: phone `09171234321` → `assertSee('09•••••4321')`.
  - `test_correct_code_grants_access`: `call('verify')` → `assertRedirect(route('admin.dashboard'))`; then `get('/admin/dashboard')->assertOk()`.
  - `test_fifth_wrong_code_logs_out`: → `assertGuest()`, redirect home with flash "Too many attempts".
  - `test_cancel_logs_out`.
  - `test_session_flag_not_carried_to_next_login`: verify, `post('/logout')`, log in again → redirected to challenge.
- [ ] **Step 2:** Run filter. Expected: FAIL.
- [ ] **Step 3:** Implement. Middleware: `if ($user?->hasTwoFactorEnabled() && ! $request->session()->get('two_factor_verified')) { return redirect()->guest(route('two-factor.challenge')); }`. Mask = first 2 chars + `•` × (len − 6) + last 4. On success `session()->regenerate()` then put the flag, `redirect()->intended(route('admin.dashboard'))`. Send failure message: "We couldn't send the code. Please try Resend."
- [ ] **Step 4:** Filter, full suite. Expected: PASS.
- [ ] **Step 5: Commit** `feat(2fa): login challenge and admin route middleware`.

### Task 13: `stallm:2fa-reset` command

**Files:** Create `app/Console/Commands/ResetTwoFactor.php`; Test `tests/Feature/ResetTwoFactorCommandTest.php`

- [ ] **Step 1: Tests:** `artisan('stallm:2fa-reset', ['email' => $admin->email])->assertSuccessful()` → disabled, `two_factor_confirmed_at` null, codes deleted, audit row with details "console". Unknown email → `assertFailed()` with output "No admin found".
- [ ] **Step 2:** Run. Expected: FAIL.
- [ ] **Step 3:** Implement (signature `stallm:2fa-reset {email}`; only `role=admin`).
- [ ] **Step 4:** Filter, full suite. Expected: PASS.
- [ ] **Step 5: Commit** `feat(2fa): console reset for locked-out admins`.

**Phase 3 checkpoint:** full suite green; manual: enable 2FA in profile (code from Mailpit SMS preview), log out, log in → challenge → dashboard.

---

# Phase 4 — Lease renewal requests

### Task 14: Payments stop extending leases; renewal columns

**Files:**
- Create: `database/migrations/2026_10_06_000003_add_renewal_fields_to_stall_mappings_table.php` (`renewal_note` text nullable, `renewal_rejection_reason` text nullable)
- Modify: `app/Services/LeaseService.php:136` (rename to `markGoodStandingOnPayment(Invoice $invoice): ?StallMapping`, remove the `end_date` change, audit text "Lease #{id} marked Good Standing via Invoice #{number}"), `app/Services/InvoiceService.php:98`, `app/Models/StallMapping.php` (fillable)
- Modify test: `tests/Unit/LeaseServiceTest.php:87-110`

- [ ] **Step 1:** Rewrite `test_paying_monthly_rental_invoice_renews_and_maintains_good_standing` as `test_paying_monthly_invoice_marks_good_standing_without_changing_end_date`: assert `payment_status = good_standing` and `end_date->equalTo($oldEndDate)`.
- [ ] **Step 2:** Run filter. Expected: FAIL (end date still advances).
- [ ] **Step 3:** Implement rename + migration.
- [ ] **Step 4:** `php artisan migrate`, then full suite. Expected: PASS.
- [ ] **Step 5: Commit** `fix(lease): payments no longer extend lease end date`.

### Task 15: Renewal service methods

**Files:** Modify `app/Services/LeaseService.php`; Test `tests/Unit/LeaseRenewalTest.php` (create)

**Interfaces:**
- Produces (all throw `InvalidArgumentException` with a user-readable message on guard failure):
  - `requestRenewal(StallMapping $lease, User $tenant, Carbon $requestedEnd, ?string $note = null): StallMapping`
  - `approveRenewal(StallMapping $lease, User $admin, ?Carbon $overrideEnd = null): StallMapping`
  - `rejectRenewal(StallMapping $lease, User $admin, string $reason): StallMapping`
  - `isRenewalEligible(StallMapping $lease): bool` — `is_active && end_date <= today+60 days && renewal_status !== 'requested'`.

- [ ] **Step 1: Write the failing tests** (`Mail::fake()`, mock `SmsService`)
  - Request: succeeds on own active lease ending in 45 days → `renewal_status=requested`, `requested_end_date`, `renewal_note`, admin notifications, audit.
  - Request guards (one test each): other tenant's lease; inactive lease; ends in 90 days; already requested; requested date ≤ current end.
  - Request allowed again after `rejected` and after a previous `approved`.
  - Approve: `end_date` = requested; with override → override; `renewal_status=approved`; tenant gets in-app + mail + SMS; non-`requested` lease throws.
  - Reject: empty reason throws; sets `rejected` + reason; tenant in-app + mail.
- [ ] **Step 2:** Run filter. Expected: FAIL.
- [ ] **Step 3:** Implement; state changes in `DB::transaction`, sends after commit. Override must also be > current `end_date`.
- [ ] **Step 4:** Filter, full suite. Expected: PASS.
- [ ] **Step 5: Commit** `feat(lease): renewal request, approve and reject`.

### Task 16: Renewal UI (tenant and admin) + expiry command skip

**Files:**
- Modify: `app/Livewire/User/MyLeases.php` + `resources/views/livewire/user/my-leases.blade.php`; `app/Livewire/Admin/LeaseMappingManager.php` + view; `app/Console/Commands/NotifyExpiringLeases.php` (exclude `renewal_status = 'requested'`)
- Test: `tests/Feature/LeaseRenewalUiTest.php` (create); extend `tests/Feature/LeaseExpiryNotificationTest.php`

**Interfaces:**
- Consumes: Task 15 methods.
- Produces: `MyLeases` props `?int $renewalLeaseId`, `string $renewalOption = '6m'` (`'6m'|'1y'|'custom'`), `string $renewalCustomDate = ''`, `string $renewalNote = ''`; methods `openRenewal(int $id)`, `submitRenewal()`. `LeaseMappingManager` statusFilter value `'renewal_requested'`; props `string $renewalEndDate`, `string $renewalRejectReason`; methods `approveRenewal(int $id)`, `rejectRenewal(int $id)`.

- [ ] **Step 1: Write the failing tests**
  - Tenant sees "Request renewal" only on an eligible lease; `submitRenewal` with `'1y'` sets `requested_end_date = end_date + 1 year`; pending lease shows "Renewal requested — awaiting review"; rejected lease shows the reason.
  - Tenant calling `openRenewal` with another tenant's lease id → error, nothing changes.
  - Admin filter `renewal_requested` lists only requested leases; approve with edited date; reject without reason → `assertHasErrors('renewalRejectReason')`.
  - Expiry command: lease ending in exactly 30 days with `renewal_status=requested` → no notification row created.
- [ ] **Step 2:** Run filter. Expected: FAIL.
- [ ] **Step 3:** Implement; map `InvalidArgumentException` to `addError`.
- [ ] **Step 4:** Filter, full suite. Expected: PASS.
- [ ] **Step 5: Commit** `feat(lease): renewal UI for tenants and admins`.

**Phase 4 checkpoint:** full suite green; manual: tenant requests renewal → admin approves → tenant sees new end date.

---

# Phase 5 — Typing indicators

### Task 17: Typing indicators in ticket chat

**Files:**
- Modify: `app/Livewire/Admin/TicketCenter.php`, `app/Livewire/User/TicketManager.php`, both chat views (`resources/views/livewire/admin/ticket-center.blade.php`, `resources/views/livewire/user/ticket-manager.blade.php`)
- Test: `tests/Feature/TypingIndicatorTest.php` (create)

**Interfaces:**
- Produces: `typing(): void` on both components; computed list of typists exposed to the view as `$typingNames` (array of first names).

- [ ] **Step 1: Write the failing tests**
  - `test_typing_sets_only_callers_participant_row`: tenant with active ticket calls `typing()` → their `typing_at` within last 2 s; admin's row untouched.
  - `test_tenant_cannot_signal_on_foreign_ticket`: set `activeTicketId` to another tenant's ticket, call `typing()` → no participant row for that ticket/user.
  - `test_other_party_sees_indicator_within_5_seconds`: tenant types; admin's TicketCenter with that ticket open `assertSee('Juan is typing')`; `travel(6)->seconds()` → `assertDontSee('is typing')`.
  - `test_typer_does_not_see_own_indicator`.
  - `test_sending_message_clears_typing`.
- [ ] **Step 2:** Run filter. Expected: FAIL.
- [ ] **Step 3:** Implement. View input: `x-data="{ last: 0 }" @input="if (Date.now() - last > 2000) { last = Date.now(); $wire.typing() }"`. Indicator: three-dot bubble above the input using existing `animate-bounce` utility with staggered delays.
- [ ] **Step 4:** Filter, full suite. Expected: PASS.
- [ ] **Step 5: Commit** `feat(tickets): typing indicators`.

**Phase 5 checkpoint / sprint done:** full suite green; `php artisan migrate` clean on local MySQL; manual click-throughs from the spec's Definition of Done, including typing indicator between two browsers (one normal, one private window).
