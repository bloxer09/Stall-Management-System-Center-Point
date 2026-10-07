"""Back matter in the reference order: Transmittal Letter, Photo Documentation, User GUI Guide,
System User Guide Manual, References (moved here), Personal Information."""

MEMBERS = ['Angcap, Crisandra Mae B.', 'Malongo, Alvin A.', 'Pasiol, Jasper R.', 'Sagga, Mark G.', 'Else, Jhack L.', 'Fiel, Jay-ar T.']

GUI = [
    ('Home Page', '01-home.png', 'The public landing page with live stall counts, the floor directory, and links to browse stalls, sign in, register, and check an application.'),
    ('Browse Stalls', '02-browse-stalls.png', 'Visitors pick a floor and see every stall on the floor map, coloured by status.'),
    ('Stall Application Form', '03-stall-apply.png', 'Selecting an available stall shows its details and the online application form.'),
    ('Application Status', '04-application-status.png', 'Applicants enter their reference number and email to see the status of their application.'),
    ('Sign In', '05-sign-in.png', 'Sign in with email and password, or with Google or Facebook.'),
    ('Register', '06-register.png', 'New users create a tenant account; passwords need 8 or more characters with letters and numbers.'),
    ('Forgot Password', '07-forgot-password.png', 'Request a 6-digit reset code by email and SMS.'),
    ('Administrator Dashboard', '10-admin-dashboard.png', 'Items that need attention, key figures, the revenue trend, payment channels, and floor occupancy.'),
    ('Stall Applications', '11-applications.png', 'Review applications from the public portal and approve or reject them.'),
    ('Stalls', '12-stalls.png', 'Add, edit, and delete stalls; a waitlist badge shows visitors waiting for a stall.'),
    ('Floors', '13-floors.png', 'Manage floor levels and their grid sizes.'),
    ('Floor Map', '14-floor-map.png', 'Drag stalls onto the floor grid to match the building layout.'),
    ('Leases and Tenants', '15-leases.png', 'Every lease with its period and status; create leases and review renewal requests.'),
    ('Terminate Lease', '16-terminate-lease.png', 'Terminating a lease requires a reason and shows any unpaid balance.'),
    ('Tenant Accounts', '17-tenant-accounts.png', 'Tenants with their active leases, unpaid balances, and account status.'),
    ('Statement of Account', '18-statement.png', 'Every charge and payment of a tenant with a running balance, downloadable as a PDF.'),
    ('Payments', '19-payments.png', 'All invoices with their status; record office payments and export to Excel.'),
    ('Calendar', '20-calendar.png', 'Lease end dates, invoice due dates, and renewal requests by month.'),
    ('Announcements', '21-announcements.png', 'Post notices to all tenants or one floor, optionally by email.'),
    ('Support Tickets (Administrator)', '22-admin-tickets.png', 'Reply to tenant concerns in a chat window, claim and close tickets.'),
    ('Financial Reports', '23-reports.png', 'Revenue trend, occupancy, and overdue aging, exportable to CSV and PDF.'),
    ('Audit Logs', '24-audit-logs.png', 'Every recorded action, including sign-ins and security events.'),
    ('System Settings and Backups', '25-settings-backups.png', 'Platform details, the SMS switch, and encrypted database backups.'),
    ('Global Search', '26-global-search.png', 'Find tenants, stalls, invoices, and tickets from any administrator page.'),
    ('Tenant Dashboard', '30-tenant-dashboard.png', 'Announcements, upcoming dates, leases, the next bill, and open tickets.'),
    ('My Leases', '31-my-leases.png', 'Lease details and renewal requests.'),
    ('Invoices and Payments', '32-invoices.png', 'Select invoices and pay online through PayMongo.'),
    ('Support Tickets (Tenant)', '33-tenant-tickets.png', 'Open a ticket and chat with the office.'),
    ('Documents', '34-documents.png', 'The account statement, lease contracts, and billing statements with receipts.'),
]

MANUAL = [
    ('1. Introduction', [
        'This manual explains how to use the Web Based Stall Management System for Center Point. It is written for the administrators who run the system and for tenants who use the tenant portal. Visitors who are not signed in can browse stalls, apply, check an application, and join a stall waitlist.',
    ]),
    ('2. System Requirements', [
        'Any up-to-date web browser (Google Chrome, Microsoft Edge, Mozilla Firefox, or Safari) on a computer, tablet, or smartphone, and a stable internet connection. No software needs to be installed.',
    ]),
    ('3. Signing In', [
        '1. Open the system and click Sign In.',
        '2. Enter your email and password, or click Continue with Google or Continue with Facebook.',
        '3. Administrators with Two-Factor Authentication enabled enter the 6-digit code sent to their phone.',
        '4. Forgot your password? Click Forgot password, enter your email, and use the 6-digit code you receive to set a new password.',
        'After five wrong passwords, sign-in pauses for a minute. A deactivated account cannot sign in; contact the office.',
    ]),
    ('4. Administrator Procedures', []),
    ('4.1 Stalls, Floors and the Floor Map', [
        '1. Open Floors to add each floor level and its grid size.',
        '2. Open Stalls and click Add Stall; enter the stall number, floor, monthly price, status, and an optional photo.',
        '3. Open Floor Map, choose a floor, and drag each stall onto its place on the grid.',
    ]),
    ('4.2 Stall Applications', [
        '1. Open Applications and click an application to see the details and attached document.',
        '2. Click Approve to create the tenant account (an email with a set-password link is sent) or Reject and enter a reason.',
        '3. After approving, click Assign stall to create the lease.',
    ]),
    ('4.3 Leases, Renewals and Termination', [
        '1. In Leases & Tenants, click New Lease, choose the tenant, an available stall, and the contract dates, and attach the signed contract.',
        '2. The first invoice is created; when the tenant pays it (online or at the office), the lease becomes active.',
        '3. To approve a renewal request, open the lease, check the new end date, and click Approve renewal.',
        '4. To end a lease, open it, click Terminate Lease, enter the reason, and confirm any unpaid balance.',
    ]),
    ('4.4 Tenant Accounts and Statements', [
        '1. Open Tenant Accounts to see every tenant with their leases and unpaid balance.',
        '2. Use Actions to view the statement of account, resend a set-password link, or deactivate and reactivate the account.',
    ]),
    ('4.5 Payments and Reports', [
        '1. Open Payments to see invoices; use the filter for paid, pending, or overdue.',
        '2. Click Record Cash Payment for payments received at the office, or Mark as Paid on an invoice.',
        '3. Open Reports for the revenue trend, occupancy, and overdue aging, and export them to CSV or PDF.',
    ]),
    ('4.6 Announcements, Calendar and Search', [
        '1. Open Announcements, enter a title and message, choose all tenants or one floor, tick Also email tenants if needed, and click Post announcement.',
        '2. Open Calendar to see lease end dates, due dates, and renewal requests for the month.',
        '3. Use the search box at the top of any administrator page (or press /) to find a tenant, stall, invoice, or ticket.',
    ]),
    ('4.7 Support Tickets', [
        '1. Open Support Tickets and click a ticket to open the chat.',
        '2. Click Claim to take the ticket, reply to the tenant, and change the status to resolved or closed when done.',
    ]),
    ('4.8 Settings, Backups and Audit Logs', [
        '1. Open System Settings to update the platform details, policies, landing image, and the Send SMS notifications switch.',
        '2. In Database Backups, click Create backup now, or download and delete existing backups. Keep downloaded backups and a copy of the APP_KEY in a safe place.',
        '3. Open Audit Logs to review recorded actions and security events, and Error Logs to review system errors.',
        '4. Turn on Two-Factor Authentication from your profile (avatar menu → Profile → Password & Security).',
    ]),
    ('5. Tenant Procedures', []),
    ('5.1 Pay Invoices Online', [
        '1. Open Invoices & Payments, tick the invoices to pay (or Select all pending), and click Pay with PayMongo.',
        '2. Complete the payment with GCash, Maya, or a card on the PayMongo page; you return to the system automatically.',
        '3. The invoice is marked paid within moments, and the receipt can be downloaded from Documents.',
    ]),
    ('5.2 Leases and Renewals', [
        '1. Open My Leases to see each lease and its end date.',
        '2. When a lease is within 60 days of ending, click Request renewal, choose the new end date, and send the request.',
    ]),
    ('5.3 Documents and Statement of Account', [
        'Open Documents to download your account statement, lease contracts, and receipts for paid bills.',
    ]),
    ('5.4 Support Tickets', [
        '1. Open Support Tickets and click New Ticket; describe the concern and choose the stall.',
        '2. Chat with the office in the ticket window; you can attach photos or files.',
    ]),
    ('5.5 Profile and Password', [
        'Open the avatar menu → Profile to update your name, phone, photo, and password.',
    ]),
    ('6. Visitor Procedures', [
        '1. Open Browse Stalls, choose a floor, and click an available (green) stall.',
        '2. Click Apply for this stall, fill in the form, and submit. Keep the reference number shown and emailed to you.',
        '3. Open Check application status, enter the reference number and your email to see the result.',
        '4. For an occupied stall, click Notify me when available to be emailed when it opens up.',
    ]),
]

TROUBLESHOOTING = [
    ('Cannot sign in', 'Wrong email or password, too many attempts, or the account is deactivated', 'Check the details, wait one minute after five failed tries, or contact the office if the account is deactivated.'),
    ('Did not receive the 2FA or reset code', 'SMS delay or an old phone number on the account', 'Wait one minute and click Resend; confirm the phone number in your profile.'),
    ('Invoice still unpaid after paying online', 'The payment confirmation from PayMongo is still on its way', 'Refresh after a few minutes; if it remains unpaid, open a support ticket with the PayMongo reference.'),
    ('"Please complete the security check"', 'The CAPTCHA was not completed or expired', 'Complete the check again and resubmit the form.'),
    ('Page looks unstyled', 'Front-end assets were not rebuilt after an update', 'The administrator runs npm run build on the server.'),
    ('No backups listed', 'The scheduler is not running', 'Make sure the server cron job runs php artisan schedule:run every minute.'),
]

FAQ = [
    ('Can I pay at the office instead of online?', 'Yes. The administrator records the payment and it appears in your invoices and statement.'),
    ('How do I know my application was approved?', 'You receive an email; you can also check it with your reference number on the Application Status page.'),
    ('Why is my password rejected?', 'New passwords need at least 8 characters with both letters and numbers.'),
    ('Can someone else see my invoices or documents?', 'No. Each tenant can only open their own records; administrators can see all records.'),
]


def apply(b):
    title_like = b.find('CHAPTER V')
    section_like = b.find('Conclusions')
    body_like = b.find('Based on the results of the development, testing, and implementation')
    last = b.find('Partial Payments and Electricity/Water Billing')

    # --- Transmittal Letter
    last = b.heading_after(last, 'TRANSMITTAL LETTER', like=title_like, page_break=True)
    last = b.after_many(last, [
        '[Date]',
        '[Name of Recipient]',
        '[Position], Center Point Commercial Complex',
        'Pagadian City, Zamboanga del Sur',
        'Dear [Sir / Ma’am]:',
        'We are pleased to formally turn over the Web Based Stall Management System for Center Point, developed as our capstone project in partial fulfillment of the requirements for the degree Bachelor of Science in Information Technology at West Prime Horizon Institute, Inc.',
        'The system includes the public stall portal, the tenant portal, and the administrator panel, together with this manuscript, the User GUI Guide, and the System User Guide Manual. We thank you for your cooperation throughout the study.',
        'Respectfully yours,',
        '[Signatures and names of the researchers]',
        'Received by: ____________________   Date: ____________',
    ], like=body_like, plain=True)

    # --- Photo Documentation
    last = b.heading_after(last, 'PHOTO DOCUMENTATION', like=title_like, page_break=True)
    last = b.after(last, '[Insert photos of the interviews, data gathering, system testing, and turnover at Center Point, each with a one-line caption.]', like=body_like, plain=True)

    # --- User GUI Guide
    last = b.heading_after(last, 'USER GUI GUIDE', like=title_like, page_break=True)
    last = b.after(last, 'The following screens show the main interfaces of the system using demonstration data.', like=body_like, plain=True)
    for i, (title, image, description) in enumerate(GUI, start=1):
        last = b.heading_after(last, f'{i}. {title} Interface', like=section_like)
        last = b.after(last, description, like=body_like, plain=True)
        last = b.picture(last, image, f'{title} interface', like=body_like)

    # --- System User Guide Manual
    last = b.heading_after(last, 'SYSTEM USER GUIDE MANUAL', like=title_like, page_break=True)
    last = b.after(last, 'Web Based Stall Management System for Center Point', like=body_like, plain=True)
    for heading, lines in MANUAL:
        last = b.heading_after(last, heading, like=section_like)
        for line in lines:
            last = b.after(last, line, like=body_like, plain=True)
        if heading == '2. System Requirements':
            last = b.table_after(last, ['Requirement', 'Recommended'], [
                ('Device', 'Computer, tablet, or smartphone'),
                ('Browser', 'Latest Chrome, Edge, Firefox, or Safari'),
                ('Internet', 'Stable broadband or mobile data'),
                ('Account', 'Administrator or tenant account (not needed to browse or apply)'),
            ], 'Requirements for using the system.', like_table=b.doc.tables[2])
    last = b.heading_after(last, '7. Troubleshooting Guide', like=section_like)
    last = b.table_after(last, ['Problem', 'Likely cause', 'Solution'], TROUBLESHOOTING, 'Troubleshooting guide.', like_table=b.doc.tables[2])
    last = b.heading_after(last, '8. Frequently Asked Questions', like=section_like)
    for q, a in FAQ:
        last = b.after(last, f'Q: {q}', like=body_like, plain=True)
        last = b.after(last, f'A: {a}', like=body_like, plain=True)
    last = b.heading_after(last, '9. Contact and Support Information', like=section_like)
    last = b.after_many(last, [
        'System Administrator: [Name]',
        'Email: [Email address]',
        'Contact Number: [Phone number]',
        'Support Hours: [Days and hours]',
    ], like=body_like, plain=True)
    last = b.heading_after(last, 'END OF USER GUIDE MANUAL', like=title_like)

    # --- References: moved from after Chapter II to here (content unchanged)
    refs = b.find('REFERENCES')
    chapter3 = b.find('CHAPTER III')
    moving = []
    el = refs._p
    while el is not None and el is not chapter3._p:
        moving.append(el)
        el = el.getnext()
    anchor_el = last._p
    for el in moving:
        el.getparent().remove(el)
        anchor_el.addnext(el)
        anchor_el = el
    from docx.text.paragraph import Paragraph
    refs = Paragraph(moving[0], last._parent)
    refs.paragraph_format.page_break_before = True
    last = Paragraph(anchor_el, last._parent)
    chapter3.paragraph_format.page_break_before = True

    # --- Personal Information (placeholders)
    for member in MEMBERS:
        last = b.heading_after(last, 'PERSONAL INFORMATION', like=title_like, page_break=True)
        last = b.after_many(last, [
            '[2x2 photo]',
            f'Name: {member}',
            'Address: [Address]',
            'Date of Birth: [Date]',
            'Email: [Email]',
            'Contact Number: [Number]',
        ], like=body_like, plain=True)
        last = b.table_after(last, ['Educational Attainment', 'School Name', 'Year Graduated'], [
            ('Tertiary', 'West Prime Horizon Institute, Inc.', '[Year]'),
            ('Secondary', '[School]', '[Year]'),
            ('Primary', '[School]', '[Year]'),
        ], f"Educational background of {member.rstrip('.')}.", like_table=b.doc.tables[2])
