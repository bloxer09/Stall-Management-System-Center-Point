# Minor Fixes & Security Hardening — Design

Date: 2026-10-07 · Branch: `defense-sprint` · Defense: Saturday 2026-10-10 (localhost demo)

## Goal

Close the 7 minors deferred by the feature-improvements review, then harden the system for
production. Hard constraint: the Saturday **localhost** demo keeps working — same demo accounts
and passwords, no HTTPS needed, no internet-only dependency switched on by default.

## Order

A (all) → B1–B5 → B6–B9 → C4 → C2 → C1 → C3. One commit per item group, full suite green before
each commit. C1 and C3 sit behind `.env` switches so they can be turned off on demo day.

## Part A — deferred minors

- **A1 Re-deactivation.** `TenantManager::deactivate` on an already-deactivated tenant: no write,
  no email, toast `"{name} is already deactivated."` (type `info`).
- **A2 Exact stall link.** `LeaseMappingManager` gains `#[Url] public string $stall = ''` (stall
  number, exact match, combined with other filters). Calendar lease-end URL becomes
  `/admin/mappings?stall={number}`.
- **A3 Shared LIKE helper.** New `App\Support\LikeSearch::contains(Builder $q, string $column, string $term, string $boolean = 'and'): Builder`
  (escape char `!`). Used by `GlobalSearch` and `TenantManager`.
- **A4 Backup consistency.** Rows ordered by the table's primary key (fallback: all columns);
  MySQL dump wrapped in `START TRANSACTION WITH CONSISTENT SNAPSHOT` on the reading connection
  (committed after).
- **A5 Bulk email off the request.** New job `App\Jobs\SendBulkEmail` (recipient list, subject,
  html, text) dispatched with `->afterResponse()` by `AnnouncementService` and `StallObserver`
  (waitlist stamps `notified_at` per recipient only when that send returns true). No worker
  needed; the HTTP response returns before the emails are sent.
- **A6 Reference length.** `findByReference` accepts `^APP-(\d{4})-(\d{6,})$`.
- **A7 Real Livewire test.** Deactivation test posts a real `/livewire/update` with a snapshot
  taken from the tenant dashboard and asserts the redirect + logout.

## Part B — security

- **B1 Upload names.** Every `FileUploadService` method stores with a server-chosen name:
  `{prefix}_{random40}.{$file->extension()}` (extension guessed from content, never the client
  name). Plus `storage/app/public/.htaccess` denying script execution
  (`php`, `phtml`, `phar`, `cgi`, `pl`, `py`, `sh`) and `Options -ExecCGI`.
- **B2 Reply IDOR.** `TicketManager::sendMessage` and `TicketCenter::sendMessage` set
  `reply_to_message_id` only when that message belongs to the same ticket; otherwise null.
  `setReplyTo`-style actions (whatever sets `replyToMessageId`) validate the same way.
- **B3 Forgot password.** One message for every outcome:
  `"If an account exists for that email, we sent a 6-digit code."`; the step advances to code
  entry either way (unknown emails get no code). Rate limit: 3 requests / 10 min per
  `email|ip`. Verification step unchanged (already limited).
- **B4 Registration limit.** 5 successful or failed registrations / hour per IP.
- **B5 Password rule.** `Password::defaults(fn () => Password::min(8)->letters()->numbers())`
  in `AppServiceProvider`; used by register, forgot-password reset, set-password, profile
  password change. Existing passwords unaffected.
- **B6 Security headers.** Middleware `SecurityHeaders` appended to `web`:
  `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`,
  `Referrer-Policy: strict-origin-when-cross-origin`,
  `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()`,
  `Strict-Transport-Security: max-age=31536000; includeSubDomains` only when the request is HTTPS.
  Also on the PayMongo webhook response (harmless).
- **B7 Production HTTPS.** `URL::forceScheme('https')` when `app()->isProduction()`.
  `.env.example` production block: `APP_ENV=production`, `APP_DEBUG=false`,
  `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`. `config/session.php` `encrypt` reads
  `env('SESSION_ENCRYPT', false)`.
- **B8 Security events in Audit Logs** (entity type `Security`): login succeeded, login failed
  (email recorded, never the password), login locked out, sign-in refused (deactivated),
  password reset completed, 2FA code failed, 2FA lockout. Failed-login records have
  `user_id` = the matching account if one exists, else null.
- **B9 OAuth phone.** Accounts created by Google/Facebook get `phone = null` (no random number).

## Part C — extras

- **C1 Content-Security-Policy.** Config `security.csp_mode` from `CSP_MODE`
  (`enforce` default, `report`, `off`). Per-request nonce (`Vite::useCspNonce()`); the
  `SecurityHeaders` middleware sends `Content-Security-Policy` (or `-Report-Only`):
  `default-src 'self'; script-src 'self' 'nonce-{n}' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob: https:; connect-src 'self'; frame-src 'self' https://challenges.cloudflare.com; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'`
  plus `https://challenges.cloudflare.com` in `script-src` when Turnstile is enabled.
  `'unsafe-eval'` stays until a Livewire 4 upgrade (Alpine evaluates expressions at runtime).
  Groundwork: the 31 inline `on*=` handlers in 13 views become Alpine/`wire:` equivalents; the
  inline `<script>` blocks get `nonce="{{ Vite::cspNonce() }}"`; `@livewireScripts` /
  `@livewireStyles` get the nonce. Every page is loaded in the browser pane with zero CSP
  console violations before commit.
- **C2 Backup encryption.** New backups are written as `backup-…sql.enc` / `.sqlite.enc`:
  the plain file is encrypted with `Crypt::encryptString` (APP_KEY) and the plain file deleted.
  `list()` shows both encrypted and legacy plain files; download of `.enc` decrypts on the fly
  and serves `backup-….sql` / `.sqlite`. Name pattern extended to the `.enc` suffix.
  Settings card notes: "Encrypted with the app key — keep a copy of APP_KEY to restore."
- **C3 CAPTCHA (Cloudflare Turnstile).** Config `services.turnstile.site_key` / `secret_key`.
  Enabled only when both are set. `TurnstileService::verify(?string $token, string $ip): bool`
  posts to `https://challenges.cloudflare.com/turnstile/v0/siteverify` (2 s timeout; network
  failure → false). Widget + check on: Register, Forgot-password request, Stall application,
  Waitlist sign-up, Application status lookup. Failure message:
  `"Please complete the security check."` Disabled → widget not rendered, check skipped.
- **C4 SMS switch.** Setting `sms_enabled` (default on). System Settings toggle
  "Send SMS notifications" with help text "Admin two-factor codes are always sent."
  `SmsService::sendSms(string $number, string $message, bool $essential = false)` returns false
  without sending when disabled and not essential; 2FA code sends pass `essential: true`.
  Changing it is audit-logged.

## Testing

A test that fails first for every item, including: upload stored name ends with the detected
extension for a `.php`-named PNG; reply to another ticket's message saved as null; forgot-password
identical response for known/unknown email and 4th request blocked; 6th registration blocked;
`password1` accepted / `password` rejected on all four forms; headers present (HSTS only on
HTTPS); security events written; OAuth-created user has null phone; CSP header carries the
nonce used by rendered scripts and switches to report-only/off per `CSP_MODE`; `.enc` backup is
not plain text and downloads decrypted; Turnstile enabled → missing/failed token rejected,
disabled → no check; SMS off → non-essential not sent, 2FA still sent. Full suite green.

## Out of scope

Livewire 4 upgrade (to drop `'unsafe-eval'`), off-site backup storage, a CSP violation
report endpoint, MFA for tenants.
