"""Chapter III updates: requirements, ERD figure, software table, modules, benefits."""


def apply(b):
    b.replace_everywhere([
        ('All nineteen functional modules', 'All functional modules'),
        ('the absence of structured utility billing and lease expiry notifications', 'the absence of lease expiry notifications'),
        ('administrators, staff, tenants, and prospective tenant applicants', 'administrators, tenants, and prospective tenant applicants'),
        ('administrator and staff routes', 'administrator routes'),
        ('Access to admin and staff areas', 'Access to administrator areas'),
        ('administrator, staff, and tenant roles', 'administrator and tenant roles'),
        ('administrator and staff users', 'administrator users'),
        ('all administrator and staff route groups', 'all administrator route groups'),
        ('Administrators and staff can then review', 'Administrators can then review'),
        ('the assigned staff member', 'the assigned administrator'),
    ], mark=True)

    # --- Functional requirements
    b.delete_prefix('Record metered utility readings per tenant stall')
    anchor = b.find('Record all significant administrative actions in an audit log')
    b.after_many(anchor, [
        'Allow administrators to terminate a lease with a recorded reason, showing the unpaid balance for that stall and notifying the tenant; an unpaid reservation’s first invoice is cancelled instead of left as debt.',
        'Allow administrators to post announcements to all tenants or to the tenants of one floor, with optional email delivery.',
        'Allow applicants to check the status of an application using its reference number and email address.',
        'Allow visitors to join a waitlist for an occupied stall and email them once when it becomes available.',
        'Allow administrators to deactivate or reactivate tenant accounts and resend set-password links; deactivated accounts cannot sign in by any method.',
        'Provide tenants and administrators with a statement of account showing every charge and payment with a running balance, downloadable as a PDF.',
        'Provide administrators with a monthly calendar of lease end dates, invoice due dates, and renewal requests, and a global search across tenants, stalls, invoices, and tickets.',
        'Create encrypted database backups on demand and daily at 2:00 AM, keeping the latest 14, with download and delete from System Settings.',
        'Allow administrators to switch SMS notifications off, while Two-Factor Authentication codes are always sent.',
    ])

    # --- Non-functional requirements
    anchor = b.find('Ensure data security through Laravel authentication middleware')
    b.after_many(anchor, [
        'Send security response headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, and HSTS over HTTPS) and a nonce-based Content-Security-Policy that blocks injected scripts.',
        'Limit repeated attempts: sign-in (5 per minute per email and connection), password reset requests (3 per 10 minutes), new accounts (5 per hour per connection), application status lookups (10 per minute), and waitlist sign-ups (5 per hour).',
        'Require new passwords to have at least 8 characters with both letters and numbers, and never reveal through the password reset form whether an email address is registered.',
        'Record security events — sign-ins, failed sign-ins, lockouts, password resets, and 2FA failures — in the audit log.',
        'Store uploaded files under server-chosen names whose extension is detected from the file content, and refuse to execute scripts from public storage.',
        'Re-check the user’s role, 2FA status, and account status on every interactive (Livewire) action, not only on page loads.',
    ])

    # --- Current architecture replaces the old diagram (it still showed an "Administrator & Staff" user group)
    anchor = b.find('The following architectural diagram illustrates')
    b.set(anchor, 'The following architectural diagram illustrates the layers of the system: the users, the security layer that every request passes through, the Livewire presentation layer, the Laravel service layer, and the data and external services:')
    old = anchor._p.getnext()
    if old is not None and old.findall('.//{http://schemas.openxmlformats.org/wordprocessingml/2006/main}drawing'):
        old.getparent().remove(old)
    b.picture(anchor, 'fig-architecture.png', 'System architecture of the Web Based Stall Management System for Center Point')

    # --- ERD replaces the old database figure
    anchor = b.find('The relational database schema is structured around the following core entities and their relationships')
    old = anchor._p.getnext()
    if old is not None and old.findall('.//{http://schemas.openxmlformats.org/wordprocessingml/2006/main}drawing'):
        old.getparent().remove(old)
    b.picture(anchor, 'fig-erd.png', 'Entity Relationship Diagram of the Web Based Stall Management System for Center Point')

    # --- Programming environment
    p = b.find('Frontend: Blade templating engine with Tailwind CSS')
    b.set(p, 'Frontend: Blade templating engine with Tailwind CSS 3 and DaisyUI 4, compiled with Vite 5, for a responsive and modern user interface; Alpine.js (bundled with Livewire) for small interactions; Chart.js 4 for interactive financial charts.')

    # --- Software specification table (Table 2)
    t = b.doc.tables[2]
    b.set_row(t, 'PHP', [None, None, 'PHP 8.2+ (developed and tested on PHP 8.3)'])
    b.set_row(t, 'Laravel', [None, None, 'Laravel 12'], mark=False)
    b.set_row(t, 'Tailwind CSS', ['Tailwind CSS and DaisyUI', 'Responsive interface styling and components', 'Tailwind CSS 3.4, DaisyUI 4'])
    b.set_row(t, 'Chart.js', [None, None, 'Chart.js 4.5'])
    for row in [
        ('Vite', 'Front-end asset build tool', 'Vite 5'),
        ('PhpSpreadsheet', 'Excel (.xlsx) exports of payments, reports, and logs', 'PhpSpreadsheet 5'),
        ('Cloudflare Turnstile', 'Optional CAPTCHA for public forms', 'Turnstile (enabled by configuration)'),
    ]:
        b.add_row(t, row)

    # --- Modules described
    b.delete_prefix('Utility Billing Module —')
    anchor = b.find('Analytics Dashboard Module —')
    b.after_many(anchor, [
        'Lease Termination Module — Ends a lease with a required reason, shows the unpaid balance for that stall, keeps unpaid invoices on record (or cancels an unpaid reservation’s first invoice), frees the stall, and notifies the tenant.',
        'Announcements Module — Lets administrators post notices to all tenants or one floor, delivered through the notification bell, optional email, and the tenant dashboard.',
        'Application Status and Waitlist Module — Gives every application a reference number that applicants use to check its status, and lets visitors join a waitlist that emails them once when an occupied stall becomes available.',
        'Tenant Account Management Module — Lists tenants with active leases and unpaid balances, and lets administrators deactivate or reactivate accounts and resend set-password links.',
        'Statement of Account Module — Builds each tenant’s ledger of charges and payments with a running balance, shown on screen and exported as a PDF.',
        'Calendar and Global Search Module — A monthly calendar of lease ends, invoice due dates, and renewal requests, and a search box that finds tenants, stalls, invoices, and tickets.',
        'Database Backup Module — Creates encrypted backups on demand and every day at 2:00 AM, keeps the latest 14, and lets administrators download or delete them.',
        'Security Module — Content-Security-Policy and security headers, rate limiting, a strong password rule, security event logging, safe upload handling, optional Cloudflare Turnstile CAPTCHA, and an SMS on/off switch.',
    ])

    # --- Benefits
    anchor = b.find('Ninth, the audit log system enhances operational accountability')
    b.after(anchor, 'Tenth, encrypted daily database backups and layered web security measures — a Content-Security-Policy, rate limiting, a strong password policy, and security event logging — protect tenant and financial data against loss and common attacks.')
