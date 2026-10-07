# Minor Fixes & Security Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the 7 deferred minors and harden uploads, auth flows, headers, CSP, backups, CAPTCHA and SMS for production without breaking the localhost demo.

**Architecture:** Small, test-first changes to existing services and Livewire components; cross-cutting concerns go into one new `SecurityHeaders` middleware (headers + CSP) and small services (`LikeSearch`, `SecurityLog`, `TurnstileService`). Risky items (CSP, CAPTCHA) are switchable from `.env`.

**Tech Stack:** Laravel 12, Livewire 3.8, Alpine, PHPUnit (sqlite in-memory).

**Spec:** `docs/superpowers/specs/2026-10-07-hardening-design.md`

## Global Constraints

- Localhost demo keeps working: demo accounts/passwords unchanged, no HTTPS required, CAPTCHA off unless keys set, `CSP_MODE` switchable (`enforce` default, `report`, `off`).
- One commit per task; `php artisan test` fully green before each commit.
- Neutral forgot-password copy exactly: `If an account exists for that email, we sent a 6-digit code.`
- CAPTCHA failure copy exactly: `Please complete the security check.`
- SMS toggle help text exactly: `Admin two-factor codes are always sent.`
- Security audit entries use entity type `Security`; never record passwords or codes.

## Review Focus

- A `.php`-named file that is a real PNG uploaded as avatar/stall photo/contract/attachment: stored name must end in the detected extension (Task 3 `test_uploads_use_detected_extension`, all five upload methods).
- Forgot-password with an unknown email: user sees the same message and the same next step as a known email, and no email/SMS is sent (Task 4 `test_forgot_password_response_identical`).
- CSP enforce mode on a real page: every inline `<script>` the page renders carries the header's nonce (Task 9 `test_rendered_scripts_carry_csp_nonce`).
- Encrypted backup downloaded: the file served is the original SQL, byte-identical (Task 8 `test_encrypted_backup_downloads_original_sql`).
- SMS switched off: 2FA code still sent (Task 7 `test_two_factor_sms_sent_when_sms_disabled`).

---

### Task 1: Deferred minors A1, A2, A3, A6

**Files:**
- Create: `app/Support/LikeSearch.php`
- Modify: `app/Livewire/Admin/TenantManager.php` (deactivate guard; search via helper), `app/Livewire/Admin/GlobalSearch.php` (use helper, remove private `contains`), `app/Livewire/Admin/LeaseMappingManager.php` (`#[Url] public string $stall = ''`, exact filter), `app/Services/CalendarService.php` (lease url `?stall=`), `app/Models/StallApplication.php` (`\d{6,}`)
- Test: `tests/Feature/HardeningMinorsTest.php`

**Interfaces:**
- Produces: `App\Support\LikeSearch::contains(Builder $q, string $column, string $term, string $boolean = 'and'): Builder` (escape char `!`).

- [ ] **Step 1: Failing tests** — `test_redeactivate_is_noop` (deactivated tenant, call `deactivate` → `assertDispatched('notify', type: 'info')`, `Mail::assertNothingSent()`, `deactivated_at` unchanged), `test_tenant_search_treats_wildcards_literally` (`search='%%'` → no tenants listed), `test_exact_stall_filter` (leases on S-1 and S-10; `withQueryParams(['stall' => 'S-1'])` → sees S-1 tenant, not S-10 tenant), `test_calendar_lease_link_uses_exact_stall` (url contains `stall=S-101`), `test_seven_digit_reference_resolves` (forceFill id 1234567 → `findByReference('APP-'.year.'-1234567')` finds it).
- [ ] **Step 2: Run** `php artisan test --filter=HardeningMinorsTest` — FAIL.
- [ ] **Step 3: Implement.** Toast copy `"{name} is already deactivated."`.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `fix: re-deactivation no-op, exact stall filter, shared LIKE helper, longer references`

### Task 2: Deferred minors A4, A5, A7

**Files:**
- Create: `app/Jobs/SendBulkEmail.php`
- Modify: `app/Services/BackupService.php` (order by PK; MySQL consistent snapshot), `app/Services/AnnouncementService.php`, `app/Observers/StallObserver.php`, `tests/Feature/TenantAccountsTest.php` (real `/livewire/update` test), existing waitlist/announcement tests that assert mail counts
- Test: `tests/Feature/HardeningMinorsTest.php`

**Interfaces:**
- Produces: `SendBulkEmail(array $recipients /* list<array{email:string,name:string,waitlist_id?:int}> */, string $subject, string $html /* contains {name} placeholder */, string $text)`; `handle(MailService $mailer): void` sends each, replacing `{name}` with `e(name)` in html and raw name in text; when `waitlist_id` present and send returned true, stamps that `StallWaitlist` row's `notified_at`.

- [ ] **Step 1: Failing tests** — `test_announcement_email_dispatched_after_response` (`Bus::fake()`; post with email → `Bus::assertDispatchedAfterResponse(SendBulkEmail::class)`), `test_bulk_email_stamps_only_delivered_waitlist_rows` (run job with MailService mock returning true then false → first row stamped, second null), `test_backup_rows_ordered_by_primary_key` (insert users out of id order via forceFill ids 5, 2 → SQL `INSERT` lists id 2 before 5), and in `TenantAccountsTest` replace the GET-with-header test body with a real `/livewire/update` post (snapshot scraped from `/user/dashboard` as the tenant, deactivate, post → assert redirect and `assertGuest()`).
- [ ] **Step 2: Run** — FAIL.
- [ ] **Step 3: Implement.** PK via `Schema::getIndexes($table)` where `primary` true → its columns; fallback all columns. MySQL: `DB::statement('START TRANSACTION WITH CONSISTENT SNAPSHOT')` before tables, `DB::commit()`-equivalent `DB::statement('COMMIT')` after (only when driver is mysql). Dispatch with `SendBulkEmail::dispatch(...)->afterResponse()`. Update existing tests that counted mails synchronously: with `QUEUE_CONNECTION=sync` in phpunit, after-response jobs run on terminate — where a test needs the mails, call `app()->terminate()` or assert the dispatch instead.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `fix: bulk emails after response, consistent backups, real Livewire deactivation test`

### Task 3: Upload names (B1) and reply IDOR (B2)

**Files:**
- Modify: `app/Services/FileUploadService.php` (all 5 single-file methods), `app/Livewire/User/TicketManager.php` + `app/Livewire/Admin/TicketCenter.php` (`sendMessage`)
- Create: `storage/app/public/.htaccess` (force-add in git; storage is ignored)
- Test: `tests/Feature/UploadAndReplySecurityTest.php`

**Interfaces:**
- Produces: private `FileUploadService::storedName(UploadedFile $file, string $prefix): string` → `"{$prefix}_" . Str::random(40) . '.' . ($file->extension() ?: 'bin')`.

- [ ] **Step 1: Failing tests** — `test_uploads_use_detected_extension` (for each of contract/application/ticket/avatar/stall: `UploadedFile::fake()->createWithContent('shell.php', <1x1 PNG bytes>)` → stored path ends `.png`, not `.php`), `test_public_storage_blocks_scripts` (file `storage/app/public/.htaccess` contains `php` deny rule and `-ExecCGI`), `test_tenant_reply_to_foreign_message_dropped` (message M in another tenant's ticket; set `replyToMessageId=M`, send → saved `reply_to_message_id` null), `test_admin_reply_to_foreign_message_dropped`, `test_reply_within_same_ticket_kept`.
- [ ] **Step 2: Run** — FAIL.
- [ ] **Step 3: Implement.** `.htaccess`:
  ```
  Options -ExecCGI -Indexes
  <FilesMatch "\.(php|phtml|phar|cgi|pl|py|sh)$">
      Require all denied
  </FilesMatch>
  ```
  Reply check: `TicketMessage::where('id', $this->replyToMessageId)->where('ticket_id', $ticketId)->exists()` else null.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `fix(security): server-chosen upload names, no script execution in public storage, reply IDOR`

### Task 4: Auth flows — forgot password, registration limit, password rule, OAuth phone (B3, B4, B5, B9)

**Files:**
- Modify: `app/Livewire/Auth/AuthModals.php`, `app/Livewire/Auth/SetPassword.php`, `app/Livewire/Profile/ProfileModal.php`, `app/Providers/AppServiceProvider.php` (`Password::defaults`), `app/Http/Controllers/OAuthController.php` (phone null), auth-modals view (neutral copy)
- Test: `tests/Feature/AuthHardeningTest.php`

- [ ] **Step 1: Failing tests** — `test_forgot_password_response_identical` (known vs unknown email: both `assertHasNoErrors`, `forgotStep === 'verify'`, see neutral copy; unknown → `Mail::assertNothingSent()`), `test_forgot_password_rate_limited` (4th request in 10 min for same email → error on `forgotEmail`, no 4th mail), `test_registration_rate_limited` (6th register from same IP within an hour → error on `regEmail`), `test_password_rule_on_all_forms` (`password` rejected, `password1` accepted on register, forgot reset, SetPassword, ProfileModal), `test_oauth_created_user_has_no_phone` (Google callback for new email → `phone` null).
- [ ] **Step 2: Run** — FAIL.
- [ ] **Step 3: Implement.** Forgot rate key `forgot:{lower email}|{ip}`, 3 per 600 s, hit on every request; drop the `exists` rule; unknown email sets `forgotStep = 'verify'` without creating a code. Register key `register:{ip}`, 5 per 3600 s, hit on every attempt (before validation result). Replace `min:8` with `Password::defaults()` in the four forms (keep `confirmed`/`same`). Existing tests that set passwords like `password` on these forms: update to `password1`.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `fix(security): neutral forgot-password, throttled registration, stronger passwords, no fake OAuth phones`

### Task 5: Security headers and production HTTPS (B6, B7)

**Files:**
- Create: `app/Http/Middleware/SecurityHeaders.php`, `config/security.php` (`'csp_mode' => env('CSP_MODE', 'enforce')`)
- Modify: `bootstrap/app.php` (append to `web`; also `api`), `app/Providers/AppServiceProvider.php` (`URL::forceScheme('https')` when production), `config/session.php` (`'encrypt' => env('SESSION_ENCRYPT', false)`), `.env.example` (production block comment)
- Test: `tests/Feature/SecurityHeadersTest.php`

**Interfaces:**
- Produces: `SecurityHeaders::handle()` — Task 9 adds CSP inside this same middleware.

- [ ] **Step 1: Failing tests** — `test_security_headers_present` (GET `/` → the four headers with spec values), `test_hsts_only_on_https` (`/` over http → no HSTS; `$this->get('https://localhost/')` → HSTS `max-age=31536000; includeSubDomains`), `test_production_forces_https_urls` (set env production in test, re-run provider boot or `URL::forceScheme` check via `url('/')` starting `https://`), `test_session_encrypt_reads_env`.
- [ ] **Step 2: Run** — FAIL.
- [ ] **Step 3: Implement** per spec values.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(security): security headers, HTTPS in production, session encryption option`

### Task 6: Security events in Audit Logs (B8)

**Files:**
- Create: `app/Services/SecurityLog.php`
- Modify: `AuthModals::processLogin` (success, failure, lockout, deactivated), `OAuthController` (deactivated refusal), `AuthModals::verifyForgotPasswordOtp` (reset completed), `TwoFactorChallenge::verify` (failed code, lockout)
- Test: `tests/Feature/SecurityLogTest.php`

**Interfaces:**
- Produces: `SecurityLog::record(string $event, ?int $userId, string $detail = ''): void` → `AuditLog::record("Security: {$event}", 'Security', null, $detail, $userId)`. Events: `login succeeded`, `login failed`, `login locked out`, `sign-in refused (deactivated)`, `password reset`, `2FA code failed`, `2FA lockout`.

- [ ] **Step 1: Failing tests** — one per event asserting an `audit_logs` row (`entity_type` `Security`, action contains the event); `test_failed_login_records_email_not_password` (details contain the email, not the typed password).
- [ ] **Step 2: Run** — FAIL.
- [ ] **Step 3: Implement.** Failed-login `userId` = matching account id or null; detail = `email: {email}`.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(security): record sign-in, reset and 2FA events in audit logs`

### Task 7: SMS switch (C4)

**Files:**
- Modify: `app/Services/SmsService.php` (`sendSms(string $number, string $message, bool $essential = false): bool`), `app/Services/TwoFactorService.php:42` (`essential: true`), `app/Livewire/Admin/SystemSettings.php` + view (toggle `sms_enabled`)
- Test: `tests/Feature/SmsSwitchTest.php`

- [ ] **Step 1: Failing tests** — `test_non_essential_sms_skipped_when_disabled` (setting `sms_enabled`='0' → `sendSms` returns false, nothing dispatched to the SMS transport — assert via the existing sms catcher/fake used in other tests), `test_two_factor_sms_sent_when_sms_disabled`, `test_toggle_saved_and_audited`.
- [ ] **Step 2: Run** — FAIL.
- [ ] **Step 3: Implement.** Setting default `'1'`; toggle saved by `saveSettings`; audit `"Turned SMS notifications on|off"` only when changed.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(settings): switch to turn off non-essential SMS`

### Task 8: Backup encryption (C2)

**Files:**
- Modify: `app/Services/BackupService.php`, `app/Http/Controllers/BackupController.php`, settings view note
- Test: `tests/Feature/BackupTest.php` (extend)

**Interfaces:**
- Produces: `BackupService::create(): string` now returns `backup-….sql.enc` / `.sqlite.enc`; `BackupService::read(string $name): ?string` (decrypted contents for `.enc`, raw for legacy); `BackupService::plainName(string $name): string` (strips `.enc`). Name pattern `^backup-[0-9-]+\.(sql|sqlite)(\.enc)?\z`.

- [ ] **Step 1: Failing tests** — `test_backup_encrypted_at_rest` (created name ends `.enc`; file contents lack `CREATE TABLE`), `test_encrypted_backup_downloads_original_sql` (download → `assertDownload('backup-…sql')`, streamed content contains `CREATE TABLE` and equals `read()`), `test_legacy_plain_backup_still_downloads`, update existing backup tests for the new suffix.
- [ ] **Step 2: Run** — FAIL.
- [ ] **Step 3: Implement.** Write plain file as now, then `File::put($path.'.enc', Crypt::encryptString(File::get($path)))`, delete plain (in the same try/cleanup). Download: `response()->streamDownload(fn () => print($backups->read($name)), $backups->plainName($name))`, audit as before. Note copy: `Encrypted with the app key — keep a copy of APP_KEY to restore.`
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(backup): encrypt backups at rest`

### Task 9: Content-Security-Policy (C1)

**Files:**
- Modify: `app/Http/Middleware/SecurityHeaders.php` (CSP + nonce), `resources/views/layouts/app.blade.php` + `guest.blade.php` (`@livewireStyles(['nonce' => Vite::cspNonce()])`, `@livewireScripts(['nonce' => ...])`, nonce on inline scripts), the 13 views with inline `on*=` handlers, `resources/views/livewire/admin/advanced-reports.blade.php` + `analytics-dashboard.blade.php` (nonce on scripts), `.env.example` (`CSP_MODE=enforce`)
- Test: `tests/Feature/ContentSecurityPolicyTest.php`

**Interfaces:**
- Consumes: Task 5 middleware, `config('security.csp_mode')`; Task 10 adds Turnstile origin when `TurnstileService::enabled()`.

- [ ] **Step 1: Failing tests** — `test_csp_enforced_with_nonce` (GET `/` → `Content-Security-Policy` contains `script-src 'self' 'nonce-` and `frame-ancestors 'self'`), `test_rendered_scripts_carry_csp_nonce` (as admin GET `/admin/dashboard` and as guest GET `/`: every `<script` tag in the HTML has `nonce="{n}"` matching the header), `test_report_only_mode` (`CSP_MODE=report` → `Content-Security-Policy-Report-Only`, no enforcing header), `test_off_mode` (no CSP header), `test_no_inline_event_handlers` (grep `resources/views` for ` on(click|change|submit|input|load|keydown)=` → zero).
- [ ] **Step 2: Run** — FAIL.
- [ ] **Step 3: Implement.** Call `Vite::useCspNonce()` in the middleware before `$next`; header value exactly as spec. Convert each `onclick="X"` to `x-on:click="X"` (or `@click`) inside an `x-data` scope, rewriting `document.getElementById('id').showModal()` as `$refs`/`document.getElementById(...)` within Alpine (Alpine expressions may call DOM APIs).
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Browser check.** Start the app (`.claude/launch.json` dev server), log in as demo admin and demo tenant, load every page (home, stalls, application status, all user pages, all admin pages incl. charts), open each modal touched; `read_console_messages` must show zero `Content Security Policy` violations. Fix any, re-run tests.
- [ ] **Step 6: Commit** `feat(security): nonce-based Content-Security-Policy, inline handlers moved to Alpine`

### Task 10: CAPTCHA — Cloudflare Turnstile (C3)

**Files:**
- Create: `app/Services/TurnstileService.php`, `resources/views/components/turnstile.blade.php`
- Modify: `config/services.php` (`turnstile.site_key`, `turnstile.secret_key` from `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`), `AuthModals` (register + forgot request), `StallBrowser` (submitApplication + joinWaitlist), `ApplicationStatus::lookup`, their views, `SecurityHeaders` (CSP origin), `.env.example`
- Test: `tests/Feature/TurnstileTest.php`

**Interfaces:**
- Produces: `TurnstileService::enabled(): bool`; `TurnstileService::verify(?string $token, string $ip): bool` (`Http::asForm()->timeout(2)->post(...)`, `success` true → true; any exception → false). Components expose public `string $captchaToken = ''` set by the widget callback via `$wire.set('captchaToken', token)`.

- [ ] **Step 1: Failing tests** — `test_disabled_by_default_no_check` (no keys → register works without token; widget markup absent), `test_enabled_rejects_missing_token` (keys set, `Http::fake(['*' => Http::response(['success' => false])])` → each of the 5 actions errors with `Please complete the security check.`), `test_enabled_accepts_valid_token` (fake success → actions proceed), `test_network_failure_rejects` (`Http::fake` throwing `ConnectionException` → rejected), `test_csp_allows_turnstile_when_enabled`.
- [ ] **Step 2: Run** — FAIL.
- [ ] **Step 3: Implement.** Widget component renders `<div class="cf-turnstile" data-sitekey=… data-callback=…>` and the nonce'd `https://challenges.cloudflare.com/turnstile/v0/api.js` script, only when enabled. Error key per form: `regEmail`, `forgotEmail`, `form.applicant_email`, `waitlistEmail`, `reference`.
- [ ] **Step 4: Run** `php artisan test` — PASS.
- [ ] **Step 5: Commit** `feat(security): optional Cloudflare Turnstile on public forms`

### Task 11: Diagrams

**Files:**
- Modify: scratchpad `system_flow_data.py` (forgot-password neutral step + rate limit; registration limit; SMS toggle; CAPTCHA decision on the five public actions when enabled; security events note on login), regenerate `docs/diagrams/system-flowchart.drawio`
- [ ] **Step 1:** Regenerate; coverage check `ok`.
- [ ] **Step 2:** `php artisan test` — PASS.
- [ ] **Step 3: Commit** `docs: flowchart updated for security hardening`
