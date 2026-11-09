"""Corrections from the final accuracy review, checked against the code. Runs last."""
import io
import os
import re

from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.shared import Inches, Pt
from PIL import Image

W = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'
A = '{http://schemas.openxmlformats.org/drawingml/2006/main}'
R = '{http://schemas.openxmlformats.org/officeDocument/2006/relationships}'
WP = '{http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing}'

PHRASES = [
    # invoicing (GenerateMonthlyInvoices.php:17, InvoiceService.php:46)
    ('stallm:generate-invoices', 'billing:generate-invoices'),
    ('produces exactly one invoice per active mapping per billing month without duplication', 'produces exactly one invoice per stall per billing period without duplication'),
    # stall statuses include Reserved
    ('(Available, Occupied, Maintenance)', '(Available, Reserved, Occupied, Maintenance)'),
    ('(available, occupied, maintenance)', '(available, reserved — assigned and awaiting the first payment — occupied, maintenance)'),
    # audit log is append-only, not tamper-evident
    ('Tamper-evident record', 'Append-only record (no edit or delete in the interface)'),
    ('complete and tamper-evident record', 'complete, append-only record'),
    ('A tamper-evident record', 'An append-only record'),
    ('tamper-evident record', 'append-only record'),
    # payment / transaction statuses
    ('Manages payment statuses (pending, paid, failed).', 'Invoice statuses are pending, paid, and cancelled; payment transactions are pending, successful, or failed.'),
    # receipts are generated on request
    ('using Dompdf upon payment confirmation', 'using Dompdf on request once the invoice is paid'),
    # Mailjet runs through Laravel Mail over SMTP; no SDK
    ('(Laravel framework, Livewire 3, Socialite, Dompdf, Mailjet SDK)', '(Laravel framework, Livewire 3, Socialite, Dompdf, PhpSpreadsheet)'),
    (' and the Mailjet SDK)', ')'),
    ('the Mailjet API for transactional email', "Mailjet for transactional email (sent through Laravel's mailer over the Mailjet SMTP relay)"),
    # notifications that exist
    ('delivering real-time in-app notifications for new invoices, payment confirmations, ticket replies, lease events, lease expiry warnings, and application status updates',
     'delivering in-app notifications, refreshed every 10 seconds, for payment reminders, overdue notices, ticket replies, lease events, lease expiry warnings, announcements, and application status updates'),
    ('Delivers real-time in-app notifications through a Livewire-powered notification bell. Notifies users of new invoices, payment confirmations, ticket replies, lease events, lease expiry warnings, and application status updates.',
     'Delivers in-app notifications through a Livewire-powered notification bell refreshed every 10 seconds. Notifies users of payment reminders, overdue notices, ticket replies, lease events, lease expiry warnings, announcements, and application status updates.'),
    # live chat is Livewire polling
    ('Support Ticket System with Real-Time Chat', 'Support Ticket System with Live Chat'),
    ('Real-time notification bell with unread badge counters', 'Notification bell with unread badge counters, refreshed every 10 seconds'),
    ('a real-time support ticket chat system', 'a live support ticket chat system'),
    ('real-time threaded chat', 'live threaded chat (refreshed every 3 seconds)'),
    ('real-time unread badge counters', 'automatically refreshed unread badge counters'),
    ('real-time threaded messaging', 'live threaded messaging (Livewire polling every 3 seconds)'),
    ('and real-time typing status indicators', 'and typing indicators'),
    ('threaded real-time chat', 'threaded live chat'),
    ('Support Ticket and Real-Time Chat System', 'Support Ticket and Live Chat System'),
    ('Support Tickets and Real-Time Chat:', 'Support Tickets and Live Chat:'),
    ('support ticket and real-time chat module', 'support ticket and live chat module'),
    ('a threaded real-time chat interface', 'a threaded live chat interface'),
    ('updates invoice statuses in real time upon payment confirmation', 'updates the invoice status as soon as PayMongo confirms the payment'),
    ('accurate and real-time invoice status updates', 'accurate and prompt invoice status updates'),
    ('providing a subject and initial message. The system automatically creates participant records for both the submitting tenant and the assigned administrator.',
     'describing the issue and the related stall. A participant record is created when each user opens or types in the conversation, recording when they last read it.'),
    # verification claims
    ('including profile picture, phone number, and email verification.', 'including profile picture and phone number.'),
    ('password reset, email verification, and Google', 'password reset, and Google'),
    ('CSRF protection, email and phone verification, and', 'CSRF protection, and'),
    # environment
    ('Apache, PHP 8.2, and MySQL', 'Apache, PHP 8.3, and MySQL'),
    ('Laragon (Apache, PHP 8.2, MySQL)', 'Laragon (Apache, PHP 8.3, MySQL)'),
    ('PHPUnit 11 integrated with Laravel Test Bench', "PHPUnit 11 with Laravel's built-in testing utilities"),
    ('system settings including 2FA and the SMS switch.', 'system settings including the SMS switch; each administrator turns on 2FA from their own profile.'),
    # installation
    ('PayMongo API keys (PAYMONGO_PUBLIC_KEY, PAYMONGO_SECRET_KEY, PAYMONGO_WEBHOOK_SECRET)', 'PayMongo keys (PAYMONGO_SECRET_KEY, PAYMONGO_WEBHOOK_TOKEN)'),
    ('Mailjet API credentials (MAILJET_APIKEY, MAILJET_APISECRET)', 'Mailjet SMTP credentials (MAILJET_API_KEY, MAILJET_SECRET_KEY)'),
    ('Semaphore SMS API key (SEMAPHORE_API_KEY)', 'Semaphore SMS settings (SEMAPHORE_API_KEY, SEMAPHORE_SENDER_NAME)'),
    ('Google OAuth credentials (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET)', 'Google OAuth credentials (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI)'),
    ('Database Seeding: Run the database seeder to generate default administrator and tenant accounts:',
     'Database Seeding: Run the database seeder to create the default administrator account and system settings (the separate DemoSeeder adds demonstration tenants for presentations only):'),
    # exports
    ('Produces the Excel (.xlsx) exports for payments, analytics, audit logs, and error logs.', 'Produces the Excel (.xlsx) exports for payments, the analytics dashboard, audit logs, and error logs.'),
    ('Excel (.xlsx) exports of payments, reports, and logs', 'Excel (.xlsx) exports of payments, analytics, and logs'),
    # user manual button
    ('click New Lease, choose the tenant', 'click Assign Stall to Tenant, choose the tenant'),
]

REWRITES = {
    'Mailjet API: Dispatches transactional emails':
        "Mailjet: Transactional email service, used through Laravel's built-in mailer over the Mailjet SMTP relay, for payment reminders, overdue notices, lease expiry alerts, application updates, announcements, and account emails.",
    'mailjet/mailjet-apiv3-php:':
        "Mailjet SMTP relay: Laravel's built-in mailer sends all transactional email through Mailjet's SMTP service; no separate Mailjet package is required.",
    'Mailjet Test Mode for capturing':
        'Mailpit, a local mail catcher, for capturing and checking emails during development instead of sending them.',
    'Semaphore Sandbox API for testing SMS':
        'Laravel SMS Catcher for capturing SMS messages and 2FA codes locally during development instead of sending them.',
    'Lease Expiry Automated Notifications: Automatically notifies both administrators and tenants':
        'Lease Expiry Automated Notifications: At 60 and 30 days before a lease ends, notifies the tenant by in-app notification, email, and SMS, and the administrators by in-app notification (skipped when a renewal request is already pending).',
    'Automatically notify administrators and tenants via Mailjet email and Semaphore SMS at 60 and 30 days':
        'Automatically notify the tenant by in-app notification, Mailjet email, and Semaphore SMS, and the administrators by in-app notification, 60 and 30 days before a lease ends, unless a renewal request is already pending.',
    'Lease Expiry Notification Module — A scheduled Artisan command':
        'Lease Expiry Notification Module — A scheduled Artisan command (stallm:notify-expiring-leases) that runs daily at 07:00, finds active leases ending in exactly 60 and 30 days without a pending renewal request, and notifies the tenant by in-app notification, Mailjet email, and Semaphore SMS, and the administrators by in-app notification.',
    'Lease Expiry Notification: A dedicated Artisan console command':
        'Lease Expiry Notification: A dedicated Artisan console command (stallm:notify-expiring-leases) scheduled daily at 07:00. It finds active leases ending in exactly 60 and 30 days without a pending renewal request and notifies the tenant by in-app notification, Mailjet email, and Semaphore SMS, and the administrators by in-app notification.',
    'The system dispatches automated lease expiry notifications through a separate scheduled Artisan command':
        'The system dispatches automated lease expiry notifications through a separate scheduled Artisan command (stallm:notify-expiring-leases), which runs daily at 07:00. It queries the stall_mappings table for active leases whose end_date falls exactly 60 or 30 days from the current date and that have no pending renewal request. For each matching lease, the tenant receives an in-app notification, a Mailjet email, and a Semaphore SMS prompting a renewal request if needed, and every administrator receives an in-app notification. This eliminates the risk of unmanaged lease expirations and reduces administrative oversight requirements.',
    'Lease Expiry Notifications: Verified that the stallm:notify-expiring-leases command':
        'Lease Expiry Notifications: Verified that the stallm:notify-expiring-leases command finds the correct leases at 60 and 30 days, notifies the tenant by email, SMS, and in-app notification and the administrators in-app, and skips leases with a pending renewal request.',
    'The lease expiry automated notification system, powered by a dedicated daily Artisan command':
        'The lease expiry automated notification system, powered by a dedicated daily Artisan command, proactively informed tenants by email, SMS, and in-app notification, and administrators in-app, of approaching lease contract expiration dates at 60 and 30 days in advance. This eliminated the risk of unmanaged contract expirations and gave tenants sufficient notice to submit renewal requests.',
    'The system generates monthly rental invoices automatically through a dedicated Laravel Artisan console command':
        'The system generates monthly rental invoices automatically through a dedicated Laravel Artisan console command (billing:generate-invoices) that the scheduler runs on the first day of every month at 00:00. For each active lease, it creates a pending invoice for that billing period, due on the 6th day of the month, and skips any stall that already has an invoice for the same billing period.',
    'PDF Receipt Generation: Following payment confirmation':
        'PDF Receipt Generation: Once the invoice is paid, the tenant (or an administrator) can download an official A4 PDF receipt, generated on request using Dompdf, with an embedded QR code linking to the online receipt verification page.',
    'To evaluate the system’s usability, functionality, and performance through user acceptance testing.':
        'To evaluate the system’s functionality, security, and performance through automated testing, end-to-end testing, and an independent code review.',
    "To evaluate the system's usability, functionality, and performance through user acceptance testing.":
        "To evaluate the system's functionality, security, and performance through automated testing, end-to-end testing, and an independent code review.",
    'In addition, users records when an account was deactivated':
        'In addition, users records when an account was deactivated; stall_mappings stores the lease payment status (pending_initial, good_standing, past_due, settled), the initial invoice, and the termination reason and date; stall_applications links an approved applicant to their user account and stores the rejection reason; and two_factor_codes stores only a SHA-256 hash of each code, never the code itself.',
}

TERM_ROWS = {
    'Lease Expiry Notification': 'An automated alert 60 and 30 days before a lease ends: the tenant receives an in-app notification, a Mailjet email, and a Semaphore SMS; administrators receive an in-app notification.',
    'Mailjet': "A transactional email service, used through Laravel's mailer over its SMTP relay, for payment reminders, overdue notices, lease expiry alerts, application updates, and announcements.",
}

ORIGINAL_FIGURES = [
    # (paragraph before the image, caption)
    ('To build a reliable platform', 'Waterfall model used in the development of the system'),
    ('To support online rental payments via GCash, Maya', 'PayMongo online payment sequence'),
    ('The system implements optional Two-Factor Authentication', 'Two-Factor Authentication sign-in flow'),
]
MAX_FIGURE_HEIGHT = Inches(7.0)


def _sizes(drawing):
    """The size elements of a picture (wp:extent and a:ext with cx/cy; extension-list a:ext have none)."""
    return [e for e in list(drawing.iter(WP + 'extent')) + list(drawing.iter(A + 'ext')) if e.get('cx') is not None]


def _drawing_after(p):
    el = p._p.getnext()
    while el is not None:
        if el.findall('.//' + W + 'drawing'):
            return el
        if el.tag == W + 'p' and ''.join(t.text or '' for t in el.iter(W + 't')).strip():
            return None
        el = el.getnext()
    return None


def apply(b):
    b.replace_everywhere(PHRASES, mark=True)
    for prefix, text in REWRITES.items():
        p = b.find(prefix, required=False)
        if p is not None:
            b.set(p, text)
    t = b.term_table()
    for term, meaning in TERM_ROWS.items():
        b.set_row(t, term, [None, meaning])
    sw = b.doc.tables[2]
    b.set_row(sw, 'Mailjet', ['Mailjet', 'Transactional email through Laravel Mail', 'Mailjet SMTP relay'])

    # Duplicate "Technical Background" text: keep the second, fuller copy.
    first_heading = b.find('Technical Background and System Architecture')
    for prefix in ('Reactive user interface components are powered by Livewire 3', 'The backend handles business logic including authentication'):
        dup = b.find(prefix, after=first_heading)
        second = b.find(prefix, after=dup, required=False)
        if second is not None:
            b.delete(dup)

    # PayMongo sequence: steps 9-10 (receipt e-mail/SMS on payment) do not happen; use the corrected image.
    anchor = b.find('To support online rental payments via GCash, Maya')
    drawing = _drawing_after(anchor)
    blip = drawing.find('.//' + A + 'blip')
    part = b.doc.part.related_parts[blip.get(R + 'embed')]
    new_png = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))), 'assets', 'fig-paymongo-sequence.png')
    with open(new_png, 'rb') as fh:
        part._blob = fh.read()
    with Image.open(new_png) as im:
        ratio = im.height / im.width
    for e in _sizes(drawing):
        e.set('cy', str(int(int(e.get('cx')) * ratio)))

    # Captions for the original figures; very tall ones scaled so they don't take a page each.
    for prefix, caption in ORIGINAL_FIGURES:
        p = b.find(prefix)
        drawing = _drawing_after(p)
        if drawing is None:
            continue
        for ext in _sizes(drawing):
            cx, cy = int(ext.get('cx')), int(ext.get('cy'))
            if cy > MAX_FIGURE_HEIGHT:
                scale = MAX_FIGURE_HEIGHT / cy
                ext.set('cx', str(int(cx * scale)))
                ext.set('cy', str(int(MAX_FIGURE_HEIGHT)))
        from docx.text.paragraph import Paragraph
        holder = Paragraph(drawing, p._parent)
        holder.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
        cap = b.after(holder, f'Figure 0. {caption}', like=p, mark=False, plain=True)
        cap.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
        cap.paragraph_format.first_line_indent = Inches(0)
        for r in cap.runs:
            r.italic = True
            r.font.size = Pt(10)

    # Renumber every figure caption in document order.
    n = 0
    for p in b.paragraphs():
        m = re.match(r'Figure \d+\. ', p.text.strip())
        if m:
            n += 1
            first = p.runs[0]
            first.text = re.sub(r'^(\s*)Figure \d+\. ', rf'\1Figure {n}. ', first.text)
