# Security Audit

## Method and baseline

Reviewed routes, middleware, Livewire actions, models, services, migrations, Apache rules, package locks, and configuration. Eloquent is used throughout (no raw SQL found), standard Blade escaping is used in most views, Livewire/HTML forms use CSRF protections, and model `$fillable` lists reduce mass-assignment exposure. File download endpoints contain owner/staff checks. No CORS configuration or security-header middleware was found. Production secrets and server headers were out of scope because the real `.env` and host configuration are not versioned.

## Findings

| Severity | Finding and evidence | Recommendation |
| --- | --- | --- |
| High | **Broken object authorization.** `User/TicketManager` accepts arbitrary `activeTicketId` for `selectTicket`, `markAsRead`, and `sendMessage`; ticket creation accepts any existing `stall_id`. `InvoicesCheckout` calculates and updates selected payment IDs without applying the owner/status scope to every query. | Apply policies/scoped queries at every action; derive selectable stalls and payments server-side. |
| High | **Webhook fails open when its secret is absent.** `PayMongoWebhookController` validates a signature only when the configuration value is non-empty, then marks payments paid. | Require a non-empty secret at boot/deploy and reject unsigned, stale, malformed, duplicate, and unmatched events. |
| High | **Stored cross-role XSS.** `analytics-dashboard.blade.php` inserts `json_encode($locationLabels)` via `{!! !!}` into a script. A staff member can create a location name; staff access `/admin/floors` and `/admin/analytics`. | Use `@js`/JSON_HEX flags and a strict CSP; test a script-breaking location name. |
| High | **Known framework advisories and unreproducible dependency state.** Composer audit reports Laravel advisories, including a high CRLF injection advisory; lock has 11.55.0 while manifest requires `^13.0`. | Resolve intentionally to a patched supported version, commit lockfile, enable dependency audit in CI. |
| Medium | **OTP and login hardening gaps.** Route login bypasses the unused rate-limited `LoginForm`; 2FA codes are plaintext, no attempt limit is present, and the alternative modal logs the OTP. | Use Laravel rate limiting, hashed expiring single-use OTPs, attempt counters, and remove OTP logging/duplicate flows. |
| Medium | **Sensitive data is public or overly broad.** Numeric public receipt verification reveals payer/payment data; application documents are stored on the public disk; staff can view and clear raw application logs. | Use opaque verification tokens/minimal public data, private documents with authorized download, and restrictive log access with retention. |
| Medium | **Configuration and error handling risks.** `config/app.php` supplies a fallback app key; OAuth error text exposes exception details; no HTTPS/header policy is versioned. | Fail startup without `APP_KEY`, log opaque correlation IDs, enforce TLS/HSTS/CSP/frame/referrer/content-type headers at edge. |
| Low | **External remote content.** QR rendering calls a third-party service and Dompdf remote access is enabled; chart data loads from a CDN without SRI. | Generate QR locally, disable remote PDF access unless needed, bundle/pin assets with CSP. |

## OWASP disposition

Injection is mitigated for SQL by Eloquent, but the raw analytics script enables XSS. Broken access control is confirmed for ticket/payment flows. Authentication/session handling is partial. Security misconfiguration, vulnerable components, sensitive-data exposure, logging risks, and SSRF-adjacent remote PDF behavior need remediation. No unsafe deserialization or raw command execution was observed.

Every remediation's why, impact, difficulty, risk, priority, expected improvement, effort, and rollout safety is specified in [roadmap.md](roadmap.md).
