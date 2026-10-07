"""Chapter IV updates: modules and tables, new feature subsections, testing results, deployment."""
import json
import os

FACTS = json.load(open(os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'facts.json'), encoding='utf-8'))

TEST_AREAS = [
    ('Sign-in, accounts and Two-Factor Authentication', ['AuthTest', 'AuthHardeningTest', 'LoginThrottleTest', 'TwoFactorChallengeTest', 'TwoFactorEnrollmentTest', 'TwoFactorServiceTest', 'ResetTwoFactorCommandTest', 'TenantAccountsTest', 'ProfileModalTest', 'SettingAndUserTest', 'SecurityLogTest']),
    ('Stalls, floors and the floor map', ['StallSystemTest', 'StallGridEditorTest', 'StallBrowserTest', 'StallWaitlistTest']),
    ('Stall applications and status lookup', ['ApplicationApprovalTest', 'ApplicationReviewTest', 'ApplicationStatusTest', 'StallApplicationSubmitTest']),
    ('Leases, renewals and termination', ['LeaseServiceTest', 'LeaseRenewalTest', 'LeaseRenewalUiTest', 'LeaseTerminationTest', 'LeaseExpiryNotificationTest']),
    ('Billing, payments and statements', ['InvoiceServiceTest', 'InvoiceOverdueTest', 'FlagOverdueInvoicesTest', 'OverdueDisplayTest', 'LegacyOverdueStatusMigrationTest', 'TransactionServiceTest', 'ReceiptVerificationTest', 'StatementTest', 'DocumentRepositoryTest', 'PaymentChannelsCardTest']),
    ('Support tickets, notifications and announcements', ['TicketStallScopeTest', 'TypingIndicatorTest', 'NotificationLinkTest', 'AnnouncementTest', 'MailServiceTest', 'SmsSwitchTest']),
    ('Administration tools (dashboard, calendar, search, settings, backups)', ['AdminAttentionPanelTest', 'CalendarTest', 'GlobalSearchTest', 'SystemSettingsTest', 'BackupTest', 'TermsWordingTest', 'ErrorLogServiceTest', 'ServicesTest']),
    ('Security hardening (headers, CSP, uploads, CAPTCHA, review fixes)', ['SecurityHeadersTest', 'ContentSecurityPolicyTest', 'UploadAndReplySecurityTest', 'TurnstileTest', 'FinalReviewFixesTest', 'HardeningMinorsTest', 'HardeningReviewFixesTest', 'ReviewFixesTest']),
    ('Interface, navigation and demo data', ['AccessibilityMarkupTest', 'LoadingStatesTest', 'MarkupCleanupTest', 'NavigationLinksTest', 'FrontendReviewFixesTest', 'HomePageTest', 'TenantDashboardTest', 'DemoSeederTest', 'DemoSeederSafetyTest']),
]


def apply(b):
    by_file = FACTS['tests']['by_file']
    covered = {f for _, files in TEST_AREAS for f in files}
    missing = set(by_file) - covered
    assert not missing, f'test files not grouped: {missing}'

    # --- Analysis and Design: module list
    b.delete_prefix('Utility Billing: Recording metered utility readings')
    anchor = b.find('Audit Logging and Security Monitoring:')
    b.after_many(anchor, [
        'Lease Termination: Ending a lease with a required reason and the unpaid balance shown, keeping unpaid invoices on record, and notifying the tenant.',
        'Announcements: Notices to all tenants or one floor through the notification bell, optional email, and the tenant dashboard.',
        'Application Status and Waitlist: Reference-number status lookup for applicants and email alerts for visitors waiting on an occupied stall.',
        'Tenant Accounts and Statements: Account deactivation and set-password resend for administrators, and statements of account for tenants and administrators.',
        'Calendar, Global Search and Backups: An administrative calendar, a system-wide search box, and encrypted scheduled database backups.',
        'Security Hardening: Content-Security-Policy, security headers, rate limiting, password policy, security event logging, safe uploads, and optional CAPTCHA.',
    ])

    # --- Database tables (payments were moved to invoices; utility readings removed)
    p = b.find('payments: Tracks invoice records')
    b.set(p, 'invoices, invoice_items, payment_transactions: Track monthly and initial invoices with their line items, due dates and statuses (pending, paid, cancelled), and each online or office payment transaction with its gateway, reference, amount, and status.')
    b.delete_prefix('utility_readings: Records metered utility readings')
    anchor = b.find('settings: Stores global system configuration')
    b.after_many(anchor, [
        'announcements: Stores notices posted by administrators, the target floor (or all tenants), whether they were emailed, and who posted them.',
        'stall_waitlist: Stores visitors waiting for an occupied stall and when they were notified.',
        'In addition, users records when an account was deactivated, and stall_mappings records the termination reason and date of ended leases.',
    ])

    # --- Coding: libraries
    anchor = b.find('mailjet/mailjet-apiv3-php:')
    b.after(anchor, 'phpoffice/phpspreadsheet: Produces the Excel (.xlsx) exports for payments, analytics, audit logs, and error logs.')

    # --- New feature subsections (after Audit Trail Logging)
    heading_like = b.find('Audit Trail Logging')
    body_like = b.find('The system records all significant administrative actions in the audit_logs table')
    last = body_like
    for title, body in [
        ('Lease Termination with Reason and Balance Check',
         'Terminating a lease opens a form that requires a reason of 10 to 500 characters and shows the tenant’s unpaid balance for that stall. When a balance exists, the administrator must confirm that it remains owed; unpaid invoices stay on record and the lease is marked past due, otherwise it is settled. For a reservation that was never paid, the first invoice is cancelled instead. The stall becomes available, the reason and date are saved, and the tenant is notified in-app and by email. A lease that has already ended cannot be terminated again.'),
        ('Announcements',
         'Administrators post a title and message to all tenants or to the tenants of one floor. Every tenant with an active lease on the target floor receives a notification once, deactivated accounts are skipped, and an optional email is sent after the page responds so posting stays fast. Tenants see the latest three announcements that apply to them on their dashboard.'),
        ('Application Status Lookup and Stall Waitlist',
         'Each stall application receives a reference number such as APP-2026-000123, shown after submitting and in the confirmation email. The public Application Status page shows the status, stall, dates, and rejection reason when the reference and email match, and the same message for any mismatch so it never reveals who has applied. On an occupied stall, visitors can join a waitlist; when the stall becomes available (by termination or an administrator edit) everyone waiting is emailed once, and only delivered emails are marked as sent.'),
        ('Tenant Account Management and Statement of Account',
         'The Tenant Accounts page lists every tenant with their active leases and unpaid balance. Administrators can deactivate an account (the tenant is signed out on the next request and cannot sign in by password, Google, or Facebook), reactivate it, or resend a set-password link. The statement of account lists every non-cancelled invoice as a charge and every successful payment as a credit, in date order with a running balance, and downloads as a PDF for the tenant or an administrator.'),
        ('Administrative Calendar and Global Search',
         'The calendar shows a month grid of active lease end dates, pending invoice due dates (red when overdue), and renewal requests; each item links to the matching record. The global search box on every administrator page (press / to focus) returns up to five matches each for tenants, stalls, invoices, and tickets; typed % and _ characters are treated literally.'),
        ('Encrypted Database Backups',
         'A backup service writes the whole database to a file using PHP alone, reading every table at one consistent moment, then encrypts it with the application key and deletes the unencrypted copy. Backups are created from System Settings or daily at 2:00 AM by the scheduler, the latest 14 are kept, and downloads are decrypted on the fly into a normal restorable .sql file. File names are strictly validated, so a crafted name can never read files outside the backup folder.'),
        ('Security Hardening',
         'Every response carries security headers and a Content-Security-Policy that only runs scripts carrying a secret per-session token; all inline event handlers were moved to Alpine.js for this. Sign-in, registration, password reset, status lookups, and waitlist sign-ups are rate limited, and the password reset form never reveals whether an email is registered. New passwords need 8 or more characters with letters and numbers. Uploads are stored under random names with an extension taken from the file content, and public storage refuses to run scripts. The administrator role, 2FA, and account status are re-checked on every Livewire action, and security events appear in the audit log. Cloudflare Turnstile CAPTCHA can be switched on for public forms by adding its keys, and SMS notifications can be switched off in System Settings while 2FA codes are always sent.'),
    ]:
        last = b.heading_after(last, title, like=heading_like)
        last = b.after(last, body, like=body_like)

    # --- Component testing: new areas
    anchor = b.find('Support Ticket System: Confirmed that ticket creation, message threading')
    last = b.after_many(anchor, [
        'Lease Termination: Verified that a reason is required, that only the unpaid balance for the same tenant and stall counts, that unpaid reservations cancel their first invoice, and that the tenant is notified.',
        'Announcements, Waitlist and Status Lookup: Verified that announcements reach only the targeted tenants, that waitlist emails are sent once and only on a change to available, and that status lookups reveal nothing on a mismatch.',
        'Accounts, Statements and Backups: Verified deactivation at every sign-in path and mid-session, running balances in statements, PDF access rules, encrypted backups that download as the original SQL, and rejection of crafted backup names.',
        'Security: Verified security headers, the Content-Security-Policy nonce on every script, rate limits, the password rule, identical responses for known and unknown emails, upload naming, the reply-to check in ticket chats, and that a tenant cannot replay an administrator action.',
    ])
    total = FACTS['tests']['passed']
    intro = b.after(last, f'In total, the automated suite contains {total} tests ({FACTS["tests"]["assertions"]} assertions) in {len(by_file)} test files, and all of them pass. Table 3 groups them by area.', like=b.find('Each system module was verified against its functional requirements'))
    rows = [(area, sum(by_file.get(f, 0) for f in files), len([f for f in files if f in by_file])) for area, files in TEST_AREAS]
    rows.append(('Total', total, len(by_file)))
    b.table_after(intro, ['Area', 'Tests', 'Test files'], rows, 'Automated test results by area (PHPUnit 11, all passing).', like_table=b.doc.tables[2])

    # --- System testing: security assessment wording
    anchor = b.find('Payment Gateway Testing: PayMongo checkout flows')
    b.after(anchor, 'Content-Security-Policy Check: Every guest, tenant, and administrator page was loaded in a browser, including page changes without a full reload, with zero policy violations in the browser console.')

    # --- Installation checklist
    anchor = b.find('Application URL (APP_URL)')
    b.after_many(anchor, [
        'Production security settings (APP_ENV=production, APP_DEBUG=false, SESSION_SECURE_COOKIE=true, SESSION_ENCRYPT=true, CSP_MODE=enforce)',
        'Optional CAPTCHA keys (TURNSTILE_SITE_KEY, TURNSTILE_SECRET_KEY)',
    ])
    anchor = b.find('Cron Scheduler Configuration: Configure a server cron job')
    b.after_many(anchor, [
        'Front-end Assets: Build the interface styles and scripts:',
        'npm ci && npm run build',
        'Backups and Keys: Keep a secure copy of the APP_KEY value; it is required to restore the encrypted database backups. Copy downloaded backups to separate storage regularly.',
    ])

    # --- Training
    b.delete_prefix('Utility billing: Recording utility meter readings')
    anchor = b.find('Reviewing audit logs: Monitoring the administrative audit trail')
    b.after_many(anchor, [
        'Tenant accounts: Deactivating and reactivating accounts, resending set-password links, and viewing statements of account.',
        'Announcements, calendar and search: Posting notices, reading the monthly calendar, and finding records with the global search.',
        'Backups: Creating, downloading, and safely storing encrypted backups.',
    ])

    # --- Maintenance
    p = b.find('Database Backups: The MySQL database should be backed up')
    b.set(p, 'Database Backups: The system creates an encrypted backup every day at 2:00 AM and keeps the latest 14. Administrators should download a copy at least weekly, store it off the server together with a secure copy of APP_KEY, and test a restore occasionally.')
    anchor = b.find('Two-Factor Authentication Review:')
    b.after(anchor, 'Interface Assets: After any change to the interface templates, rebuild the front-end assets (npm run build) so new styles are included.')
