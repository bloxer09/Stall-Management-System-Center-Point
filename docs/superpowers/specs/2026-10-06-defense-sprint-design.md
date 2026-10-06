# Defense Sprint Design

**Date:** 2026-10-06
**Deadline:** Saturday 2026-10-10 (capstone defense, demoed on localhost)
**Status:** Approved in conversation; pending written-spec review

## Goal

Close the functional gaps found in the system audit and fix the remaining security issues, so the system is demo-ready on Saturday and on a sound footing for the post-defense production-readiness phase.

## Decisions already made

- The capstone paper (Chapter1–5.md) does **not** constrain this work; the group will revise it afterwards.
- **Utility billing is removed** (done before this spec: model, table and docs removed).
- **No staff role.** Only `admin` (management) and `user` (tenant) exist. 2FA applies to admins only.
- Demo runs on **localhost**. Deployment, backups, monitoring and other production hardening are a **separate spec after the defense**.
- Approving an application creates the tenant **account only**; the admin assigns the stall afterwards in Lease Mappings.
- Work is delivered in five phases on `main`. Each phase ends with the full test suite passing and its own commit, so an unfinished later phase never breaks an earlier one.

## Phase 1 — Security fixes and cleanup

### Security

| # | Problem (verified 2026-10-06) | Fix |
|---|---|---|
| S1 | `AuthModals::processLogin` has no rate limit — unlimited password guessing. | `RateLimiter` keyed on lowercase email + IP: 5 failed attempts, 60-second lockout with the remaining seconds shown in the error. Cleared on success. Same pattern as the forgot-password OTP in the same file. |
| S2 | `/verify-receipt/{id}` is public and uses sequential invoice IDs, exposing payer name and payment data to anyone who counts upward. | Migration adds `invoices.verification_token` (UUID, unique, not null after backfill) and backfills existing rows. `Invoice` generates one on `creating`. Route becomes `/verify-receipt/{token}`; `PdfReceiptService` QR code uses the token. Numeric IDs return 404. QR codes on receipts printed before this change stop resolving (accepted). |
| S3 | `User\TicketManager` validates `stall_id` only as `exists:stalls,id`, so a tenant can attach any stall to a ticket. | Validation restricts `stall_id` to stalls in the tenant's own active leases. |
| S4 | `composer audit` reports advisories (including phpseclib). | `composer update` limited to the affected packages (no framework major/minor jump). Re-run `composer audit` and the suite. Any advisory that cannot be resolved without a framework upgrade is listed in the post-defense spec instead. |

Already fixed and only re-verified by existing code (no work): webhook rejects requests when the secret is unset; ticket and invoice actions are owner-scoped; no unescaped `{!! !!}` output remains.

### Cleanup

- C1: Add **Reports** (`admin.reports`) to the admin sidebar and **Documents** (`user.documents`) to the tenant sidebar in `layouts/app.blade.php`.
- C2: Replace `whereIn('role', ['admin', 'staff'])` with `where('role', 'admin')` in `Admin\TicketCenter` and `User\TicketManager`.
- C3: Delete unused `app/Http/Controllers/Auth/VerifyEmailController.php` (no route references it).
- C4: README: invoice command is `billing:generate-invoices`.
- C5: `.gitignore` adds `storage/framework/testing/`, `.phpunit.result.cache`, `vite.config.js.timestamp-*`; those files are removed from the index (`git rm --cached`) and the stray timestamp files are deleted.

### Tests

- Login: 5 wrong passwords lock the 6th attempt even with the correct password; lock clears after the decay.
- Receipt: verifying by token shows the receipt; by numeric ID returns 404; new invoices get a token.
- Ticket: creating a ticket on a stall the tenant does not lease fails validation; on their own leased stall it succeeds.

## Phase 2 — Stall application portal

### Public side

- **Route** `GET /stalls` (`stalls.browse`), no auth. Livewire component `Portal\StallBrowser`.
- Floor tabs from `StallLocation` ordered by `sort_order`. Each floor renders a read-only grid using `grid_rows`/`grid_cols` and each stall's `grid_row`/`grid_col`, colored by status (available / reserved / occupied / maintenance). Stalls without a grid position are listed below the grid.
- Clicking an **available** stall opens a detail panel (number, floor, monthly price, description, image) with **Apply for this stall**. Non-available stalls show their status only.
- **Apply form** (modal inside `Portal\StallBrowser`, backed by the Livewire form object `App\Livewire\Forms\StallApplicationForm`): `applicant_name` (required, ≤100), `applicant_email` (required, email, ≤120), `applicant_phone` (required, ≤30), `business_name` (required, ≤150), `business_type` (required, ≤100), `message` (optional, ≤1000), `document` (optional, pdf/jpg/jpeg/png, ≤5 MB). `preferred_stall_id` comes from the clicked stall and is re-checked server-side to be `available`.
- **Abuse controls:** honeypot field (silently accepts and discards when filled); rate limit 3 submissions per IP per hour; reject a second **pending** application with the same email for the same stall.
- **On submit:** store the document via `FileUploadService` on the private `local` disk; create `stall_applications` row with `status = pending`; email the applicant a confirmation via `MailService`; `Notification::send` to every admin with a link to `/admin/applications`.
- Applying does **not** change the stall's status.
- **Home page:** replace the hardcoded "Building Zones & Floor Directory" cards with real data — each `StallLocation`'s name, description, and available/total stall counts — plus a **Browse available stalls** button linking to `/stalls`. Also add a "Browse stalls" link to the public navigation.

### Admin side

- **Route** `GET /admin/applications` (`admin.applications`) in the admin group. Livewire component `Admin\ApplicationManager`.
- Sidebar link with a badge showing the count of `pending` applications.
- Paginated list with status filter (pending / approved / rejected / all, default pending) and search (name, email, business name, stall number).
- Detail modal: all applicant fields, preferred stall (with current status), submitted date, reviewer and review date if reviewed, and the supporting document via a new `FileController` route `GET /files/application-document/{id}` (admin only, 403 otherwise).
- **Approve** (pending only):
  - In one DB transaction: find `User` by `applicant_email`. If none, create one with `role = user`, name/email/phone from the application, a random 40-char password, and `email_verified_at = now()`.
  - Set application `status = approved`, `user_id`, `reviewed_by`, `reviewed_at`.
  - `AuditLog::record` the approval.
  - After commit: for a **new** account, email a "Set your password" link — a temporary signed URL (`URL::temporarySignedRoute`, 72 hours) to `GET /account/set-password/{user}`; for an **existing** account, email an "application approved, log in to continue" message. Notify the applicant in-app if they already had an account.
  - The modal then shows **Assign stall**, linking to `/admin/mappings?user={id}&stall={preferred_stall_id}`; `LeaseMappingManager` reads those query parameters and opens its create modal pre-filled.
- **Reject** (pending only): `rejection_reason` required (≤500). Set `status = rejected`, `rejection_reason`, `reviewed_by`, `reviewed_at`; audit log; email the applicant the reason.
- **Set password page** `GET|POST /account/set-password/{user}` (`signed` middleware, guest or any): Livewire/Blade form with password + confirmation using the existing password rules and `<x-password-strength>`. On success: hash and save, log the user in, regenerate the session, redirect to `user.dashboard`. Expired or tampered links show an "This link has expired — use Forgot Password on the login screen" page.

### Tests

- Guest can view `/stalls`; only available stalls offer Apply.
- Valid submission creates a pending row, sends the confirmation email, and notifies every admin.
- Honeypot filled → no row; 4th submission in an hour from one IP → rejected; duplicate pending email+stall → rejected; applying for a non-available stall → rejected.
- Approve with a new email creates a `user` account and sends a signed set-password link; approve with an existing email links that user and creates no new account.
- Set-password: valid signature sets password and logs in; expired/tampered signature is rejected.
- Reject requires a reason and emails the applicant.
- Tenant gets 403 on `/admin/applications` and on the application document route.

## Phase 3 — Admin two-factor authentication (SMS OTP)

### Data

Existing: `users.two_factor_enabled`, `users.two_factor_confirmed_at`, `users.phone`, `two_factor_codes (user_id, code varchar(64), expires_at)`. No schema change needed; `code` stores a SHA-256 hex hash.

### `TwoFactorService` (new, `app/Services`)

- `sendCode(User $user): bool` — delete the user's existing codes, generate `random_int` 6-digit code, store `hash('sha256', $code)` with `expires_at = now()+10 min`, send via `SmsService::sendSms` ("Your STALLM verification code is 123456. It expires in 10 minutes."). Returns the SMS result. Never logs the code.
- `verify(User $user, string $code): bool` — match hash and unexpired; on success delete all the user's codes.
- Attempt counting and resend throttling use `RateLimiter` keys per user: verify `2fa-verify:{id}` (5 attempts / 10 min), resend `2fa-send:{id}` (1 per 60 s).

### Enrollment (ProfileModal, admins only)

- New "Two-Factor Authentication" section, rendered only when `auth()->user()->isAdmin()`.
- Requires a saved phone number; otherwise shows "Add a phone number first".
- **Enable:** "Send code" → `sendCode`; enter code → on `verify` success set `two_factor_enabled = true`, `two_factor_confirmed_at = now()`, mark the current session verified, audit log.
- **Disable:** requires current password (`Hash::check`); sets `two_factor_enabled = false`, `two_factor_confirmed_at = null`, audit log.

### Login challenge

- **Middleware** `RequiresTwoFactor`, alias `2fa`, added to the admin route group after `role:admin`. If the user is an admin with `two_factor_enabled` and the session lacks `two_factor_verified = true`, redirect to `two-factor.challenge` (store intended URL). Applies to every login path (password modal, Google, Facebook) because it guards the routes, not the login form.
- **Route** `GET /two-factor` (`two-factor.challenge`, `auth` middleware). Livewire `Auth\TwoFactorChallenge`:
  - On mount, if already verified → redirect to admin dashboard; else send a code (respecting the resend throttle).
  - Shows masked phone (first 2 + last 4 digits visible), 6-digit input, **Resend** (60 s cooldown with countdown), **Cancel** (logs out).
  - Correct code → `session(['two_factor_verified' => true])`, `session()->regenerate()`, redirect to intended or `admin.dashboard`.
  - Wrong code → error with attempts remaining; 5th wrong attempt → log out, invalidate session, redirect home with "Too many attempts".
  - SMS send failure → error message with Resend; no email fallback.
- On logout the session is invalidated, so verification is required at every new login.
- Login redirect for admins in `AuthModals::processLogin` and `OAuthController` stays `admin.dashboard`; the middleware handles the detour.

### Recovery

- Artisan command `stallm:2fa-reset {email}` — disables 2FA for that admin, deletes their codes, writes an audit log entry (actor recorded as "console").

### Tests

- Enable: wrong code does not enable; correct code enables and sets `two_factor_confirmed_at`.
- Disable: wrong password rejected; correct password disables.
- Admin with 2FA enabled is redirected from `/admin/dashboard` to `/two-factor`, including after an OAuth-style `Auth::login`.
- Correct code grants access; expired code fails; a used code cannot be reused; 5 wrong codes log the user out.
- Stored code is not the plain 6 digits (hash check).
- Tenant routes never redirect to the challenge; tenants do not see the 2FA section.
- `stallm:2fa-reset` disables 2FA.

## Phase 4 — Lease renewal requests

### Behavior change

`LeaseService::renewLeaseOnPayment` stops modifying `end_date`. Paying a monthly invoice only sets `payment_status = good_standing` (and keeps its audit entry, reworded). Lease end dates change only through an approved renewal. Rename the method to `markGoodStandingOnPayment` and update its caller in `InvoiceService`.

### Data

Existing columns on `stall_mappings`: `renewal_status` enum(none, requested, approved, rejected), `requested_end_date`. Migration adds `renewal_note` (text, nullable) and `renewal_rejection_reason` (text, nullable).

### `LeaseService` additions

- `requestRenewal(StallMapping $lease, User $tenant, Carbon $requestedEnd, ?string $note)` — guards: lease belongs to tenant, `is_active`, end date within 60 days (or past but lease still active), `renewal_status` not `requested`, `requestedEnd > end_date`. Sets `renewal_status = requested`, `requested_end_date`, `renewal_note`; notifies all admins in-app; audit log.
- `approveRenewal(StallMapping $lease, User $admin, ?Carbon $overrideEnd)` — requires `requested`; sets `end_date` to override or requested date, `renewal_status = approved`, clears `renewal_rejection_reason`; notifies tenant (in-app, email, SMS); audit log.
- `rejectRenewal(StallMapping $lease, User $admin, string $reason)` — requires `requested`; sets `renewal_status = rejected`, `renewal_rejection_reason`; notifies tenant (in-app, email); audit log.

### Tenant UI (`User\MyLeases`)

- On an eligible lease: **Request renewal** opens a modal with options +6 months, +1 year, or custom date (must be after current end), plus optional note.
- `requested` → badge "Renewal requested — awaiting review" with the requested date.
- `rejected` → shows the reason and allows a new request.
- `approved` → badge "Renewed until {date}" until the next renewal window.

### Admin UI (`Admin\LeaseMappingManager`)

- Status filter option "Renewal requested" and a badge on matching rows.
- Detail modal shows requested date and tenant note, an editable end date (defaults to requested), **Approve** and **Reject** (reason required).

### Expiry notifications

`stallm:notify-expiring-leases` skips leases with `renewal_status = requested`. Approved renewals already have a new `end_date`, so they are naturally excluded until the new 60/30-day marks.

When a lease's renewal window opens again after a previous approval, `renewal_status = approved` does not block a new request (only `requested` does).

### Tests

- Tenant can request on own eligible lease; cannot on another tenant's lease, an inactive lease, a lease ending in >60 days, or when a request is already pending; requested date must be after current end.
- Approve sets `end_date`; override date is respected; reject requires reason; both notify the tenant.
- Paying an invoice no longer changes `end_date` (update existing tests that assumed it did).
- Expiry command skips a lease with a pending request.

## Phase 5 — Typing indicators

- `TicketParticipant.typing_at` already exists.
- New Livewire method `typing()` on `Admin\TicketCenter` and `User\TicketManager`: resolves the active ticket with the component's existing access scoping, then `updateOrCreate`s the caller's participant row with `typing_at = now()`. No-op when no active ticket.
- Message input: Alpine `@input` handler calls `$wire.typing()` throttled to once per 2 seconds.
- `sendMessage` sets the sender's `typing_at = null`.
- Rendering (existing `wire:poll.3s` cycle): find other participants on the active ticket with `typing_at >= now() - 5 seconds`; show "{first name} is typing…" with an animated three-dot bubble above the input.

### Tests

- `typing()` sets only the caller's own `typing_at` on an accessible ticket; a tenant calling it with another tenant's ticket active changes nothing.
- The other party's render shows the indicator within 5 seconds; the typer's own view does not.
- Sending a message clears `typing_at`.

## Error handling (all phases)

- User-facing failures (SMS/email send failure, validation, throttling) show inline Livewire errors or toasts; they never expose stack traces.
- Unexpected exceptions in new services are reported through the existing `ErrorLogService` pattern.
- Email and SMS sends happen after DB transactions commit; a send failure does not roll back the approval/renewal, but is logged and surfaced to the admin as a warning.

## Out of scope (post-defense production spec)

Deployment target and process, queue workers, backups and restore testing, monitoring and alerting, security headers/CSP, admin user-management page, overdue/late-fee handling, any advisory requiring a framework upgrade, and updating Chapter1–5.md.

## Definition of done

- Each phase's tests pass and the full suite passes at every phase commit.
- `php artisan migrate` succeeds on the existing local MySQL database.
- A manual click-through of each phase's flow on localhost works (stall apply → admin approve → set password → assign stall; admin 2FA enable → logout → login with OTP; renewal request → approve; typing indicator between two browsers).
