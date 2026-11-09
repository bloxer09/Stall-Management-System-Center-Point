# Browser QA results, 10 October 2026

Latest results across the completed runs: **87 passed and 3 failed out of 90 distinct scenario/browser combinations**. All three remaining failures reproduce one application defect during admin sidebar navigation. The complete PHP suite passed: **437 tests, 2,035 assertions**. TypeScript, PHP helper syntax, and scoped whitespace checks passed.

These are aggregated latest results per scenario and browser, rather than a single 90-test invocation. The original 62 Chromium scenarios ran together; seven feature scenarios were added, then executed on desktop Chromium, mobile Chromium, Firefox, and WebKit. Failed harness checks were corrected and selectively rerun. No product defect was marked as an expected failure or skipped.

| Browser/configuration | Scope | Passed | Failed |
| --- | --- | ---: | ---: |
| Desktop Chromium | Original 62 scenarios plus seven added feature scenarios | 68 | 1 |
| Mobile Chromium, Pixel 7 | Seven added feature scenarios | 6 | 1 |
| Firefox | Seven added feature scenarios | 6 | 1 |
| WebKit | Seven added feature scenarios | 7 | 0 |
| Total | Latest outcome per scenario/configuration | 87 | 3 |

## Confirmed application finding

**QA-NAV-01: uncaught JavaScript errors during repeated admin sidebar navigation.** All 15 admin pages returned successful responses and rendered their expected headings, and sidebar destinations changed the URL correctly. During navigation, however, Alpine evaluated the expression `open` and raised uncaught exceptions. Chromium reports `Illegal invocation`; Firefox reports `'open' called on an object that does not implement interface Window.` The desktop Chromium rerun recorded exceptions while the current URL was `/admin/payments` and `/admin/error-logs`.

Reproduction: sign in as the isolated admin, visit every admin page, return to the dashboard, and follow each sidebar link in order. The automated reproduction is the first test in `portal-coverage.spec.ts`. It reproduced on desktop Chromium, mobile Chromium, and Firefox; this WebKit run did not reproduce it. The evidence points to Alpine state/lifecycle handling during Livewire navigation, but an exact application fix has not been implemented. Page rendering succeeds; the zero-uncaught-errors assertion correctly remains red. Suggested priority: P2, to resolve before the defense.

## Coverage executed

- All 15 admin pages, all five tenant pages, authenticated application-status lookup, public stall browsing, privacy, terms, the analytics redirect, and every admin sidebar destination. Browser back/forward and refresh were checked.
- Floor and stall creation, Manage and Edit dialogs, keyboard dismissal, and cancelling edits without saving. Existing tests also exercise tenant action modals, statements, account activation/deactivation, ticket actions, announcement dialogs, profile settings, and authentication dialogs.
- Application submission, persisted ownership, admin approval, tenant notification, and reference/email status lookup. Floor-map placement and removal persist after refresh.
- Calendar month controls, XLSX/PDF report downloads, lease assignment, invoice generation, cash activation, invoice selection, and the full renewal request/approval/cash-payment/receipt journey. Renewal payment links appear in View Details rather than the lease table.
- Login/logout, registration, validation and length boundaries, special-character escaping, restricted-route guards, two-factor guarding, OTP expiry/countdowns, Philippine mobile validation, and phone-prefix handling.
- Rapid clicks, interrupted submissions, competing tabs, request failures for HTTP 400/401/404/419/500 and connection loss, preserved drafts, explicit recovery, and separate-tab session renewal.
- PHP tests supplement browser coverage for cancellation deadlines, repeated cancellation, payment/cancellation ordering, reassignment, late payment review, renewal billing, reports, authorization, and other backend services.

## Test maintenance and reruns

The original session-recovery browser test exceeded its 30-second test budget. Its allowance is now 90 seconds, with all draft, response, redirect, and database assertions retained; the rerun passed. New test corrections use the calendar month label instead of comparing all hidden/visible page text, return to the portal before logout, recognize Livewire property updates separately from action calls, open the mobile sidebar before logout, and use mobile invoice cards when desktop rows are hidden. The corrected scenarios passed.

Four PHP checks were stale after prior changes: descriptive close-button labels and dialog-level Escape handlers, a short invalid phone fixture, overdue badges rendered separately for desktop/mobile, and announcement previews located inside the create modal. Their assertions/fixtures were updated to the current behavior; the subsequent complete PHP run passed.

## Evidence

All run reports and failure artifacts were preserved before subsequent runs overwrote Playwright's default output. Screenshots, video, traces, browser diagnostics, isolated database snapshots, Laravel log excerpts, JSON/JUnit output, and successful report downloads remain under `test-results/qa-2026-10-10`.

- [Original Chromium run](<C:/Users/John Lloyd Ramos/Desktop/Projects/Stall-Management-System-Center-Point/test-results/qa-2026-10-10/initial-chromium/report/index.html>)
- [Initial added coverage and session-recovery rerun](<C:/Users/John Lloyd Ramos/Desktop/Projects/Stall-Management-System-Center-Point/test-results/qa-2026-10-10/expanded-initial/report/index.html>)
- [Corrected desktop checks and navigation reproduction](<C:/Users/John Lloyd Ramos/Desktop/Projects/Stall-Management-System-Center-Point/test-results/qa-2026-10-10/expanded-verified/report/index.html>)
- [Mobile Chromium, Firefox, and WebKit reports](<C:/Users/John Lloyd Ramos/Desktop/Projects/Stall-Management-System-Center-Point/test-results/qa-2026-10-10/cross-browser/report/index.html>)
- [Mobile application rerun](<C:/Users/John Lloyd Ramos/Desktop/Projects/Stall-Management-System-Center-Point/test-results/qa-2026-10-10/mobile-verified/report/index.html>)
- [Final mobile renewal rerun](<C:/Users/John Lloyd Ramos/Desktop/Projects/Stall-Management-System-Center-Point/test-results/qa-2026-10-10/mobile-final/report/index.html>)
- [Machine-readable aggregate and complete run history](<C:/Users/John Lloyd Ramos/Desktop/Projects/Stall-Management-System-Center-Point/test-results/qa-2026-10-10/summary.json>)

Run the expanded Chromium suite with `npm run test:e2e`. Reproduce the navigation finding with `npx playwright test portal-coverage.spec.ts --project=chromium --grep "all admin"`. Run the seven added scenarios on all configured browsers with `npx playwright test portal-coverage.spec.ts`.

## Scope limits

The runs used the isolated SQLite database and runtime storage on port 8877. Existing application data was not modified. Email used the array mailer, SMS delivery was disabled, payment/CAPTCHA credentials were empty, and outside browser requests were blocked. Live PayMongo, email/SMS delivery, OAuth, production database backup/restore, and external CAPTCHA integration remain unverified. These runs are functional regression checks, not production load measurements; SQLite and the development server do not prove simultaneous MySQL transaction locking. The original 62-scenario suite was run on desktop Chromium only; the other browser projects ran the seven added scenarios.

## Historical results, 9 October 2026

The final desktop Chromium run completed with **51 passed and 0 failed** out of 51 scenarios. No tests were skipped, retried, or reported as flaky. Runtime was about 6 minutes 21 seconds.

Command: `npm run test:e2e`.

| Coverage | Passed | Failed |
| --- | ---: | ---: |
| Registration, tenant tickets, announcements, lease assignment and cash activation | 4 | 0 |
| Form validation, length boundaries, special characters and malformed dates | 24 | 0 |
| Guest redirects, role separation, admin two-factor and private ticket access | 5 | 0 |
| Rapid clicks, interrupted submissions, navigation and competing tab writes | 7 | 0 |
| HTTP/connection feedback, session renewal, keyboard recovery, mobile dialogs and page alerts | 11 | 0 |

## Defect resolved

The previous run exposed five missing-alert failures for ticket submission after HTTP 400, 401, 404, or 500, or a failed connection. The application now uses Livewire's request failure hook to replace the raw-response popup with a persistent, accessible alert. Error messages do not expose backend response contents, and failed requests never trigger an automatic retry.

The original five scenarios and an additional HTTP 419 scenario verify the response status or failed request, a visible alert, retained draft, unchanged URL, enabled submission controls, no debug popup, and zero rejected writes. After removing each simulated fault, manually resubmitting without reloading creates exactly one ticket. Error-state screenshots are attached to the successful results.

Real session expiry is exercised by clearing browser cookies. The alert opens sign-in in a separate tab while preserving the original draft. The original page still holds an expired CSRF token after sign-in; its alert instructs users to copy unfinished input before refreshing. The test reloads, restores its known draft, and confirms one ticket persists.

Additional scenarios verify a guest login alert remains past the existing notification timeout, keyboard dismissal restores focus, the login button supports manual retry, cached navigation does not duplicate alerts, narrow light/dark dialogs keep recovery controls reachable, and a page-level alert allows recovery without obstructing the New Ticket button. The corresponding [antislop audit](../anti-slop/audit-005-2026-10-09.md) closes finding 1 under R-27.

## Verified interactions

| Interaction | Observed result |
| --- | --- |
| Register a new tenant | Dashboard redirect; one user persisted with the expected tenant role |
| Sign in and sign out | Role-specific dashboard; logout redirects home; restricted navigation returns to sign-in |
| Submit a support ticket | One ticket and initial message persisted; admin notification created; conversation opened |
| Reply and refresh a ticket | Reply persisted once; opening the ticket URL and refreshing retained its visible messages |
| Post an announcement | Announcement, audit record and tenant notification persisted; tenant dashboard displayed it |
| Assign a stall | One pending lease and rent invoice created; stall changed to reserved |
| Record office cash payment | Invoice paid; successful transaction persisted; lease active and stall occupied; tenant could see the lease after refresh |
| Empty, malformed and boundary inputs | Expected native or server validation appeared; invalid submissions made no unwanted database writes |
| Guest and wrong-role direct URLs | Sign-in redirect or HTTP 403, with no restricted dashboard access |
| Two-factor-enabled admin | Direct admin URLs continued to require the challenge |
| Another tenant's ticket URL | Private ticket/message contents and reply form remained inaccessible |
| Rapid multi-clicks | One create request and one stored record for ticket and announcement submission |
| Refresh before a submission reached the backend | No phantom write; manual retry created one ticket |
| Refresh after an announcement committed | One announcement survived even while its response was delayed |
| Browser back and forward | Pages remained usable; the submitted ticket was not replayed |
| Two tabs submitting separate tickets | Both tickets and their initial messages persisted |
| Two tabs assigning the same stall | One lease and invoice persisted; the competing assignment showed a rejection |
| HTTP 400/401/404/419/500 and connection failure | Persistent alert, preserved draft, restored controls, no debug popup; manual retry stored exactly one ticket |
| Actual expired session | Sign-in opened separately; original draft survived; stale-page warning appeared before reload and recovery |
| Guest login failure | Email/password stayed in the form; alert persisted; keyboard dismissal restored focus; manual retry redirected to the tenant dashboard |
| Navigation after a failed submission | Restored form remained usable; repeated failures showed one alert; successful retry removed it |
| Narrow light/dark dialogs | Error text and controls remained inside the viewport; sign-in target was at least 44 pixels high; dismissal preserved the draft |
| Failure outside a dialog | Page alert appeared above the content; New Ticket remained usable and opened after recovery |

## Evidence and checks

Open the [HTML report](../playwright-report/index.html), [JSON results](../test-results/e2e-results.json), or [JUnit results](../test-results/e2e-junit.xml). This passing run retains explicit error-state screenshots in the report. On failure, the harness retains videos, traces, screenshots and page snapshots under `test-results/e2e/`, plus browser diagnostics, database snapshots without user password hashes, and Laravel log attachments.

`npm run test:e2e:types`, PHP lint for the database helper and test router, Node syntax checks for support scripts, and whitespace checks on the changed tracked files passed. A GitHub Actions workflow runs Chromium and retains failure artifacts; that hosted workflow has not been executed in this workspace.

For this fix, `npm run build`, TypeScript checking, JavaScript syntax checking, targeted recovery tests, and the full 51-scenario Chromium run passed. Application assets were rebuilt, and Tailwind now scans JavaScript so the alert's layout and control-size classes are included.

An early Windows harness run used normal application file sessions/cache because PHP did not expose the storage override to Laravel. This was corrected and guarded before the final run. The test-created registration throttle was backed up and removed. Every final scenario checked that HTTP sessions used the dedicated runtime. Database writes remained confined to `tests/e2e/.runtime/e2e.sqlite`; the application's `.env` and existing database were not changed.

## Scope

Desktop Chromium was executed. Mobile Chromium, Firefox and WebKit are configured but were not executed. The repeated stress command is provided in the [run guide](../tests/e2e/README.md); the final results above describe a single standard run, not a repeated soak test.

The new tests resize desktop Chromium to 390 and 375 pixels to check light/dark dialog layouts. These viewport checks do not certify the separate mobile-device project. Screenshots of the mobile and page-level alerts were visually inspected.

During investigation, cached Livewire history emitted inline-script CSP console errors. The exercised navigation and alert recovery passed; CSP policy and existing layout scripts are outside this fix. The harness fails on uncaught JavaScript exceptions, but does not treat every console error as a test failure because injected HTTP/network faults also produce expected console entries.

The PHP development server serializes backend requests. The two-tab scenarios verify overlapping browser submissions and write outcomes, not production throughput or MySQL locking guarantees. Real payment gateways, SMS/email delivery, OAuth and CAPTCHA are outside this isolated run. The suite covers the listed journeys; it does not certify every application workflow or provide a full accessibility audit.
# Announcement layout follow-up (October 9, 2026)

After converting announcement creation to a modal and posted notices to a table, the nine announcement-related Chromium scenarios passed (zero failures, skips, or flaky results). This targeted run includes two new scenarios for dialog focus, draft retention, table metadata, deletion, and narrow-screen request-error recovery. Existing announcement posting, length validation, rapid clicks, and refresh-after-commit scenarios remain passing. TypeScript checks, PHP syntax, Blade compilation, and the production asset build passed. The full-suite results below describe the earlier request-error verification; the full suite was not rerun for this layout change.

Screenshots are retained as `announcements-table` and `announcements-mobile-error` attachments in the latest Playwright report. Tests continue using the isolated database and runtime storage.

