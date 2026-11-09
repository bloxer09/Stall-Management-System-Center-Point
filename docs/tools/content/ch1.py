"""Chapter I updates: current feature set, Administrator/Tenant roles only, no utility billing."""

# Phrase fixes applied to the whole manuscript (all chapters and tables).
GLOBAL_FACTS = [
    ('Laravel 13.x', 'Laravel 12'),
]
GLOBAL_REWRITES = [
    ('Staff members have limited access for transaction recording and tenant support. ', ''),
    ('administrator and staff accounts', 'administrator accounts'),
    ('administrative and staff accounts', 'administrator accounts'),
    ('An administrator or staff member', 'An administrator'),
    ('an administrator or staff member', 'an administrator'),
    ('administrators, staff, and tenants', 'administrators, tenants, and prospective tenants'),
    ('administrators and staff', 'administrators'),
    ('administrators or staff', 'administrators'),
    ('administrative staff', 'the administration'),
    ('(administrator, staff, and tenant)', '(administrator and tenant)'),
    ('admin, staff, and tenant', 'admin and tenant'),
    ('(admin, staff, user)', '(admin, user)'),
    ('for both tenant and staff users', 'for both tenant and administrator users'),
    ('Staff relied heavily', 'Administrators relied heavily'),
    ('dependency on staff', 'dependency on the office'),
    ('relying entirely on staff', 'relying entirely on the office'),
    ('staff had to manually', 'the office had to manually'),
    ('default administrator, staff, and tenant accounts', 'default administrator and tenant accounts'),
    ('administrators and staff of Center Point', 'administrators of Center Point'),
    ('inquiries to office staff', 'inquiries to the office'),
    ('(Administrator, Staff, or Tenant)', '(Administrator or Tenant)'),
    ('Administrative and staff access', 'Administrator access'),
    ('payment, utility, support ticket', 'payment, support ticket, announcement, waitlist'),
    ('online payment processing, utility billing, support ticket', 'online payment processing, support ticket'),
    ('payment management, utility billing, support ticket', 'payment management, support ticket'),
    ('tenant profiles, lease contracts, and utility billing', 'tenant profiles, and lease contracts'),
    ('invoice status, utility charges, and', 'invoice status, statement of account, and'),
    ('a utility billing module; ', ''),
    ('utility billing, ', ''),
    (', utility billing', ''),
]

NEW_FEATURES = [
    'Announcements: Administrators post notices to all tenants or to one floor; tenants receive them through the notification bell, optional email, and their dashboard.',
    'Application Status Lookup: Applicants check the status of a stall application with their reference number (for example, APP-2026-000123) and email address, without signing in.',
    'Stall Waitlist: Visitors can ask to be emailed when an occupied stall becomes available again.',
    'Tenant Account Management: Administrators list tenants with their active leases and unpaid balances, deactivate or reactivate accounts, and resend set-password links.',
    'Statement of Account: Every charge and payment for a tenant with a running balance, viewable on screen and downloadable as a PDF.',
    'Administrative Calendar: A monthly calendar of lease end dates, invoice due dates (overdue in red), and renewal requests.',
    'Global Search: A search box on every administrator page that finds tenants, stalls, invoices, and support tickets.',
    'Encrypted Database Backups: One-click and scheduled daily backups, encrypted with the application key; the latest 14 are kept.',
    'Security Hardening: A Content-Security-Policy, security response headers, rate limiting on sign-in, registration and password reset, a strong password rule, security event logging, and optional Cloudflare Turnstile CAPTCHA.',
]


def apply(b):
    b.replace_everywhere(GLOBAL_FACTS, mark=False)
    b.replace_everywhere(GLOBAL_REWRITES, mark=True)

    # --- Background of the Study
    p = b.find('To address these challenges, this study presents')
    b.set(p, 'To address these challenges, this study presents the design and development of the Web Based Stall '
             'Management System for Center Point. The system digitizes and streamlines all stall management processes '
             'through a web-based platform developed using Laravel 12 and Livewire 3. It provides a tenant portal for '
             'self-service account access, an online payment gateway via PayMongo, automated monthly invoicing with '
             'multi-stage SMS and email reminders, a real-time support ticket chat system, a tenant document repository '
             'with statements of account, advanced financial reporting and analytics, a public stall application portal '
             'with application status lookup and a waitlist, announcements, lease expiry notifications, Google and '
             'Facebook social login, Two-Factor Authentication (2FA) for administrator accounts, encrypted database '
             'backups, and layered web security protections.')

    p = b.find('To address all of these problems, the proposed system')
    b.set(p, 'To address all of these problems, the proposed system introduces a comprehensive digital solution built on '
             'Laravel 12 and Livewire 3. Beyond the core tenant portal and PayMongo payment integration, the system '
             'incorporates advanced financial reporting with exportable summaries, automated lease expiry notifications '
             'sent through Mailjet email and Semaphore SMS 60 and 30 days before contract end dates, Google and Facebook '
             'social login, a public stall application portal, a tenant document repository with downloadable statements '
             'of account, announcements, an administrative calendar and global search, encrypted database backups, and '
             'optional Two-Factor Authentication via SMS OTP for administrator accounts.')

    # --- Software and Technologies Used: add the front-end stack and optional CAPTCHA
    anchor = b.find('Chart.js: A JavaScript charting library')
    b.after_many(anchor, [
        'Tailwind CSS 3 and DaisyUI 4: A CSS framework of small reusable classes and a component library, used for the responsive interface and compiled with Vite 5.',
        'Alpine.js: A lightweight JavaScript framework, bundled with Livewire, used for small interactions such as modals, dropdowns, and drag-and-drop on the floor plan.',
        'Cloudflare Turnstile (optional): A privacy-friendly CAPTCHA that protects public forms when its keys are configured.',
    ])

    # --- Purpose and Description: feature list
    b.delete_prefix('Utility Billing Module:')
    anchor = b.find('Audit Log System: Tamper-evident record')
    b.after_many(anchor, NEW_FEATURES)

    # --- Specific Objectives
    p = b.find('To implement a utility billing module')
    b.set(p, 'To implement announcements, an application status lookup, a stall waitlist, and tenant account management that keep tenants, applicants, and administrators informed.')
    anchor = b.find('To implement an audit logging system that records administrative actions')
    b.after_many(anchor, [
        'To provide statements of account, an administrative calendar, and a global search for faster access to records.',
        'To protect system data through encrypted scheduled database backups, a Content-Security-Policy, rate limiting, a strong password policy, and security event logging.',
    ])

    # --- Significance: Staff group removed (only Administrator and Tenant accounts exist)
    b.delete_prefix('Administrative and Office Staff')

    # --- Scope and Limitations
    p = b.find('This study covers the design, development, testing')
    b.set(p, 'This study covers the design, development, testing, and deployment of the Web Based Stall Management System '
             'for Center Point in Pagadian City, covering tenant registration, stall assignment, lease contract management '
             'and termination, automated billing, online payment processing, statements of account, support ticket '
             'communication, announcements, advanced financial reporting, the public stall application portal with status '
             'lookup and waitlist, tenant document repository, lease expiry notifications, social authentication, '
             'Two-Factor Authentication, database backups, and audit logging.')
    p = b.find('The system includes three types of users')
    b.set(p, 'The system has two types of user accounts with distinct access levels, plus public visitors:')
    b.delete_prefix('Staff — Limited access')
    p = b.find('Administrator — Full system control')
    b.set(p, 'Administrator — Full system control including stall and floor management, lease allocation and termination, '
             'tenant account management, payment management, statements of account, support tickets, announcements, '
             'advanced financial reports, the calendar, global search, stall application management, audit and error '
             'logs, database backups, and system settings including 2FA and the SMS switch.')
    p = b.find('Guest (Unauthenticated)')
    b.set(p, 'Visitor (not signed in) — Can browse available stalls, submit a rental application, check its status with a '
             'reference number, and join a stall waitlist.')
    p = b.find('However, the system has certain limitations')
    b.set(p, 'However, the system has certain limitations. It requires a stable internet connection for online payment '
             'processing, webhook handling, email and SMS dispatching, and real-time notification updates. Service '
             'disruptions from PayMongo, Mailjet, or Semaphore may temporarily affect the corresponding features. The system '
             'does not compute electricity or water charges, late-payment penalties, or partial payments, and does not handle advanced '
             'accounting functions such as tax computation or multi-currency support. It is limited to stall rental '
             'management and does not include inventory tracking for individual tenant businesses. The floor plan grid '
             'editor is designed for structured grid-based layouts and may not accommodate highly irregular physical floor '
             'configurations. Two-Factor Authentication is available for administrator accounts only and relies on SMS '
             'delivery by the Semaphore gateway. Database backups are stored on the same server and must be copied '
             'elsewhere by the administrator for off-site safekeeping.')

    # --- Definition of Terms
    t = b.term_table()
    b.delete_row(t, 'Utility Billing')
    b.delete_row(t, 'Surcharge')
    b.set_row(t, 'Laravel', ['Laravel 12', None], mark=False)
    b.set_row(t, 'Online Stall Reservation', ['Online Stall Application', None])
    for term, meaning in [
        ('Announcement', 'A notice posted by an administrator to all tenants or to the tenants of one floor, delivered through the notification bell, optional email, and the tenant dashboard.'),
        ('Application Reference Number', 'A code such as APP-2026-000123 given to each stall application, used with the applicant’s email to check its status.'),
        ('Stall Waitlist', 'A list of visitors who asked to be emailed when an occupied stall becomes available.'),
        ('Statement of Account', 'A record of every charge and payment of a tenant with a running balance, downloadable as a PDF.'),
        ('Account Deactivation', 'An administrator action that prevents a tenant account from signing in while keeping its records.'),
        ('Global Search', 'A search box on administrator pages that finds tenants, stalls, invoices, and support tickets.'),
        ('Encrypted Backup', 'A copy of the database encrypted with the application key, created on demand or daily by the scheduler.'),
        ('Content-Security-Policy (CSP)', 'A browser security rule sent by the system that only allows scripts carrying a secret per-session token to run, blocking injected scripts.'),
        ('Rate Limiting', 'A limit on how many times an action (sign-in, registration, password reset, lookups) can be attempted in a period of time, to stop guessing and abuse.'),
        ('Cloudflare Turnstile', 'An optional CAPTCHA service that verifies a human is submitting a public form.'),
    ]:
        b.add_row(t, [term, meaning])
