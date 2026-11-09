"""Builds docs/presentation-short.pptx: the defense deck cut to 8 slides (title, 6 content, thank-you).

Same template and layout code as build_pptx.py. Run from the repository root:  python docs/tools/build_pptx_short.py
"""
import os

from build_pptx import DOCS, T, main

OUT = os.path.join(DOCS, 'presentation-short.pptx')

SLIDES = [
    ('BACKGROUND & OBJECTIVE', [
        'Center Point tracked stalls, leases, and payments in logbooks and paper folders',
        'Manual records led to payment errors, slow checking, and missed lease expirations',
        'Tenants could not see their own balances, receipts, or contracts',
        'Objective: a Laravel 12 and Livewire 3 web system with automated billing, online payments, and tenant self-service',
    ], None),
    ('SCOPE & LIMITATIONS', [
        'Users: the administrator, tenants, and visitors who are not signed in',
        'Covers stalls, the floor map, leases, billing, payments, tickets, reports, and backups',
        'Needs a stable internet connection for payments, email, and SMS',
        'No utility billing, late-payment penalties, or partial payments',
        'Two-factor authentication covers administrator accounts only',
    ], None),
    ('SYSTEM ARCHITECTURE', [
        'Every request passes the security layer first',
        'Livewire 3 pages run on Laravel 12 services',
        'MySQL database with encrypted daily backups',
        'PayMongo, Mailjet, Semaphore, Google and Facebook',
    ], 'fig-architecture.png'),
    ('KEY FEATURES', [
        'Public: browse stalls, apply online, track the application',
        'Admin: floor map, leases, renewals, calendar, reports',
        'Billing: monthly invoices, PayMongo, QR-verified receipts',
        'Tenant: invoices, documents, support tickets',
    ], '10-admin-dashboard.png'),
    ('SECURITY & RESULTS', [
        'SMS code two-factor authentication for administrators',
        'Role and account checks on every action',
        'Content Security Policy, rate limits, and safe file uploads',
        'Encrypted backups and an audit log of key actions',
        f'{T["passed"]} automated tests, all passing',
    ], None),
    ('CONCLUSION & RECOMMENDATION', [
        'One web platform replaced the logbooks and paper receipts',
        'Automated invoices, reminders, and online payments reduced errors and delays',
        'All general and specific objectives were achieved',
        'Next steps: a tenant mobile app, more branches, off-site backups, and 2FA for tenants',
    ], None),
]

if __name__ == '__main__':
    main(SLIDES, OUT)
