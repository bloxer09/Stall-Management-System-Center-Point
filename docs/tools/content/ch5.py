"""Chapter V becomes RESULTS AND DISCUSSIONS (reference structure), with the system figures."""
import json
import os

FACTS = json.load(open(os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'facts.json'), encoding='utf-8'))


def apply(b):
    tests = FACTS['tests']
    b.set(b.find('SUMMARY, CONCLUSIONS, AND RECOMMENDATIONS'), 'RESULTS AND DISCUSSIONS')

    section_like = b.find('Summary of the Study')
    body_like = b.find('This study presented the design')

    # Remove the old Summary section (heading through the paragraph before "Conclusions"),
    # keeping one body paragraph as the formatting template until the new sections exist.
    conclusions = b.find('Conclusions')
    template_section = section_like
    template_body = body_like
    el = section_like._p
    doomed = []
    while el is not None and el is not conclusions._p:
        doomed.append(el)
        el = el.getnext()

    anchor = b.find('RESULTS AND DISCUSSIONS')
    last = b.after(anchor, 'This chapter presents the results of developing, testing, and implementing the Web Based Stall '
                           'Management System for Center Point, discusses how the system answers the problems identified in '
                           'Chapter I, and shows the system flowcharts, use case diagram, content diagram, and entity '
                           'relationship diagram.', like=template_body)

    sections = [
        ('Functionality and Testing Results', [
            f'All functional requirements listed in Chapter III were implemented and verified. The automated test suite '
            f'contains {tests["passed"]} tests with {tests["assertions"]} assertions in {len(tests["by_file"])} test files, and '
            f'every test passes (Table 3). The tests run against a fresh database each time and cover sign-in and Two-Factor '
            f'Authentication, stalls and the floor map, stall applications and status lookup, leases including renewal and '
            f'termination, invoices, payments and statements, support tickets, notifications and announcements, '
            f'administration tools, and the security measures.',
            'Beyond the automated tests, every page was opened in a web browser with the Content-Security-Policy enforced, '
            'including page changes without a full reload, and no policy violations were reported. An independent code review '
            'of the final version found no critical problems; every important finding was fixed and covered by a new test '
            'before release.',
        ]),
        ('Results of System Implementation', [
            'The system replaced the logbooks and paper receipts at Center Point with a single web platform. Administrators '
            'see what needs attention on the dashboard (pending applications, renewal requests, overdue invoices, and '
            'unassigned tickets), manage stalls on a visual floor map, and track every lease, invoice, and payment. '
            'Invoices are generated automatically each month, reminders and overdue notices are sent by email and SMS, and '
            'tenants pay online through PayMongo and download official receipts with a QR code for verification.',
            'Tenants now have their own portal for leases, invoices, statements of account, documents, announcements, and '
            'support conversations, which removes most of the routine inquiries to the office. Prospective tenants apply '
            'online, check their application status with a reference number, and can ask to be notified when an occupied '
            'stall becomes available.',
        ]),
        ('Implementation Flow', [
            'A visitor browses the available stalls on the public portal and submits an application. The administrator '
            'reviews it; approval creates the tenant account and emails a link for setting the password. The administrator '
            'then assigns the stall through a lease, which issues the first invoice; once it is paid online or at the '
            'office, the lease becomes active and the stall occupied.',
            'From then on, the scheduler issues monthly invoices, sends payment reminders and lease expiry alerts, flags '
            'overdue invoices, and backs up the database every night. Tenants pay, request renewals, and raise concerns '
            'through support tickets; administrators approve renewals, post announcements, and terminate leases with a '
            'recorded reason when needed. Every important action is written to the audit log.',
        ]),
        ('Problems Encountered During Implementation', [
            'Scope changes: billing for electricity and water readings and a separate office-employee role were removed during '
            'development to keep the system focused on what Center Point needs; every page, permission, and record that '
            'referred to them was updated.',
            'Security review findings: a review of the system found that some administrator actions could be replayed by '
            'other users and that the password reset form revealed which emails were registered. Both were fixed, and the '
            'role and account checks now run on every interactive action.',
            'Content-Security-Policy: Livewire and Alpine.js run code written inside page attributes, so 31 inline event '
            'handlers had to be rewritten and the per-request security token was changed to a per-session token so pages '
            'that load without a full refresh keep working.',
            'Stale interface styles: new pages initially appeared partly unstyled because the compiled style sheet was out '
            'of date; rebuilding the front-end assets fixed it, and the deployment checklist now includes this step.',
            'Backups without database tools: the server may not have the mysqldump program installed, so backups are '
            'produced by the system itself and encrypted with the application key.',
        ]),
    ]
    for title, paragraphs in sections:
        last = b.heading_after(last, title, like=template_section)
        for text in paragraphs:
            last = b.after(last, text, like=template_body)

    for title, image, caption, intro in [
        ('System Flowchart for Admin', 'fig-flowchart-admin.png', 'System flowchart for the administrator',
         'The administrator signs in (with Two-Factor Authentication when enabled), reaches the dashboard, and works in one module at a time until signing out.'),
        ('System Flowchart for Tenant', 'fig-flowchart-tenant.png', 'System flowchart for the tenant',
         'A visitor without an account applies for a stall first; a tenant signs in and uses the portal modules until signing out.'),
        ('Use Case Diagram', 'fig-use-case.png', 'Use case diagram',
         'The use case diagram shows what each actor can do in the system, including the external PayMongo gateway and the scheduler.'),
        ('Content Diagram', 'fig-content-diagram.png', 'Content diagram (site map)',
         'The content diagram shows every page of the public site, the tenant portal, and the administrator panel.'),
        ('Entity Relationship Diagram', 'fig-erd.png', 'Entity relationship diagram',
         'The entity relationship diagram shows the database tables, their columns, and how they relate to each other.'),
    ]:
        last = b.heading_after(last, title, like=template_section)
        last = b.after(last, intro, like=template_body)
        last = b.picture(last, image, caption, like=template_body)

    for el in doomed:
        el.getparent().remove(el)

    # --- Conclusions
    b.replace_everywhere([
        ('The three-tier role-based access control system (Administrator, Staff, Tenant, and unauthenticated Guest)',
         'The role-based access control (Administrator, Tenant, and visitors who are not signed in)'),
    ], mark=True)
    overall = b.find('Overall, the Web Based Stall Management System for Center Point successfully achieved')
    numbered = b.find('The system successfully replaced the manual stall management processes')
    prev = overall._p.getprevious()
    from docx.text.paragraph import Paragraph
    anchor = Paragraph(prev, overall._parent)
    b.after_many(anchor, [
        'The announcements, application status lookup, stall waitlist, and tenant account management closed the remaining communication gaps between the office, tenants, and applicants.',
        'Statements of account, the administrative calendar, and the global search gave both tenants and administrators faster, clearer access to balances, dates, and records.',
        'Encrypted daily backups and the security measures — Content-Security-Policy, rate limiting, a strong password policy, security event logging, and safe upload handling — protect the system’s data against loss and common web attacks.',
    ], like=numbered)

    # --- Recommendations
    last_rec = b.find('Integration with Local Government Regulatory Systems')
    b.after_many(last_rec, [
        'Off-site Backup Storage — Automatically copy the encrypted daily backups to cloud storage or another server so a copy survives a server failure.',
        'Livewire 4 Upgrade — Upgrading to Livewire 4 would allow the Content-Security-Policy to drop its remaining allowance for runtime-evaluated code.',
        'Tenant Two-Factor Authentication — Offer optional Two-Factor Authentication to tenants as well as administrators.',
        'Partial Payments and Electricity/Water Billing — If Center Point later requires them, support partial invoice payments and charges for electricity and water readings as separate modules.',
    ])
