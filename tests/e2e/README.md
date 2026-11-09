# Browser QA suite

Playwright tests run the real Laravel/Livewire application on `http://127.0.0.1:8877` using page objects and a dedicated SQLite database. Tests fail on unmet expectations; known application defects are not marked as expected failures.

## Run

Requires Node.js 22 or newer, PHP 8.2 or newer with PDO SQLite, Composer dependencies, and built Vite assets.

```sh
composer install
npm ci
npm run build
npx playwright install chromium
npm run test:e2e:types
npm run test:e2e
```

If PHP is outside PATH, set `E2E_PHP` to its executable path. Stop Vite before running: the harness refuses `public/hot` so the browser uses built assets. Port 8877 must be free.

```sh
# Interactive browser run
npm run test:e2e -- --headed

# Repeat the resilience scenarios ten times
npm run test:e2e:stress -- resilience.spec.ts

# Run every scenario ten times
npm run test:e2e:stress

# All configured browsers and mobile Chromium
npx playwright install firefox webkit
npm run test:e2e:all

# Open failure details
npm run test:e2e:report
```

Use `--grep` or a spec filename to select scenarios. The default run uses desktop Chromium. Firefox, WebKit, and Pixel 7 Chromium are configured separately; a Chromium pass does not certify those projects.

## Coverage

| Spec | Checks |
| --- | --- |
| `journeys.spec.ts` | Registration and redirect; tenant login, ticket creation, replies and logout; admin announcement and tenant notification; stall assignment, rent invoice, office cash payment, and tenant lease visibility |
| `validation.spec.ts` | Native and server required-field validation, malformed email, duplicates, mismatched passwords, length limits, ticket minimum length, escaped script markup, invalid lease dates |
| `access-control.spec.ts` | Guest redirects for dashboards, admin endpoints and downloads; tenant/admin role separation; admin two-factor URL guard; another tenant's ticket URL |
| `resilience.spec.ts` | Rapid clicks, pending and committed submission refreshes, back/forward navigation, simultaneous writes from two tabs |
| `error-boundaries.spec.ts` | Action-specific HTTP 400/401/404/419/500 responses and connection failure; visible error, preserved draft, zero rejected writes, manual retry; actual session expiry and separate sign-in tab; persistent guest-dialog alerts, keyboard dismissal, narrow light/dark dialogs, page-level alerts and navigation without duplicate alerts |
| `portal-coverage.spec.ts` | Every admin and tenant page, public pages, sidebar links, history and refresh; floor/stall creation and edit cancellation; calendar controls; XLSX/PDF exports; application submission, approval and status lookup; floor-map placement/removal; renewal request, approval, cash completion, invoice placement and receipt PDF |
| `admin-action-modals.spec.ts`, `announcements-layout.spec.ts` | Action dialogs, keyboard dismissal, account actions, ticket claiming, announcement posting/deletion and mobile recovery |
| `otp-countdown.spec.ts`, `phone-inputs.spec.ts`, `invoice-selection.spec.ts` | OTP deadline persistence and expiry, Philippine mobile validation and prefix display, individual/bulk invoice selection in both themes and desktop/mobile layouts |

Every Livewire action assertion matches the action name in the request body and verifies its status and JSON content type. Actions that remain on the page also verify the response components array. Authentication actions verify the redirect URL instead of reading a response body that Chromium may discard during immediate navigation. Polling requests cannot satisfy those assertions. Navigation checks assert URLs and visible page elements. Database reads verify users, tickets, messages, notifications, leases, stall state, invoices, invoice items, transactions, and audit records.

Malformed dates cannot be entered into a native date input. Those scenarios change its DOM type to text before submitting to exercise the server rules. Empty server-validation scenarios set `noValidate` on the browser form. Neither changes application source or server validation.

## Isolation

The server and PHP CLI helper share `tests/e2e/.runtime/e2e.sqlite`. The helper refuses mutations unless the application environment, connection, and exact database path match the dedicated test configuration. Application `.env` and existing database files are not changed. Laravel storage, sessions, cached configuration, views, and logs live under `.runtime`.

PHP starts with `variables_order=EGPCS` so the HTTP process exposes the storage override to Laravel on Windows. The test router refuses requests unless its database and storage environment match the isolated paths. The fixture checks that HTTP sessions were actually written there.

Migrations run once at server startup. Each test clears only that database and its sessions/cache, then seeds deterministic tenant/admin accounts, stalls, a lease, and a private ticket owned by another tenant. Workers are fixed at one and sharding is rejected because all tests share this database. Do not run multiple suites simultaneously; the server configuration rejects an occupied port instead of reusing another application.

Fixtures use `Defense!2026` for the disposable accounts `tenant@example.test`, `other@example.test`, `admin@example.test`, and `secure@example.test`. `secure` has two-factor enabled; the normal admin fixture does not. Authentication runs through the real login form, and route middleware remains active. Browser requests use Laravel's normal CSRF/session flow.

The guarded `renewal-fixture` helper moves only the isolated tenant's seeded lease into the renewal window. Application snapshots also include isolated application records so approval assertions verify persisted state.

Email uses the array mailer; SMS is disabled in seeded settings; payment and CAPTCHA credentials are empty. Browser requests outside the test origin are blocked. No real payment gateway, SMS delivery, OAuth provider, or CAPTCHA integration is certified by this suite.

The built-in PHP development server serializes backend requests. Two tabs provide overlapping browser submissions, but this setup is not a production load benchmark and cannot prove MySQL locking behavior. Run separate load tests against an isolated production-equivalent deployment for throughput or database contention measurements.

## Failure evidence

`playwright-report/index.html` includes the HTML report. `test-results/e2e-results.json` and `test-results/e2e-junit.xml` support automation. Failed scenarios retain screenshots, video, and a Playwright trace under `test-results/e2e/`.

The fixture also attaches console warnings/errors, uncaught browser errors, failed requests, HTTP failures, the current URL, assertion errors, database state without password hashes, and the Laravel log segment for that test. It screenshots every tab still open on failure. Uncaught JavaScript exceptions fail the test even when its explicit assertions pass.

An injected error must produce a persistent application alert; Livewire's default debug iframe does not count. Error scenarios capture a screenshot before recovery and assert preserved input, restored controls, and exactly one ticket after manual retry without reloading. A separate scenario clears browser cookies to exercise real CSRF/session expiry, opens sign-in in another tab, and verifies that the original draft survives. After sign-in, the old page still has a stale CSRF token: the alert instructs users to copy unfinished input before refreshing. That scenario reloads and restores its known draft before resubmitting.

Playwright references: [fixtures and attachments](https://playwright.dev/docs/test-fixtures), [network interception](https://playwright.dev/docs/network), [trace viewer](https://playwright.dev/docs/trace-viewer).
