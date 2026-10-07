"""Builds docs/presentation.pptx from docs/ppt_template.pptx (never modified).

Every content slide is a copy of the template's content slide (banner, pill title, blue box, swoosh),
so the deck keeps the school design. Run from the repository root:  python docs/tools/build_pptx.py
"""
import copy
import json
import os

from PIL import Image
from pptx import Presentation
from pptx.enum.text import MSO_ANCHOR, PP_ALIGN
from pptx.util import Inches, Pt

HERE = os.path.dirname(os.path.abspath(__file__))
DOCS = os.path.dirname(HERE)
SRC = os.path.join(DOCS, 'ppt_template.pptx')
OUT = os.path.join(DOCS, 'presentation.pptx')
ASSETS = os.path.join(DOCS, 'assets')
FACTS = json.load(open(os.path.join(HERE, 'facts.json'), encoding='utf-8'))

TITLE = 'Web Based Stall Management System for Center Point'
RESEARCHERS = ['Angcap, Crisandra Mae B.', 'Malongo, Alvin A.', 'Pasiol, Jasper R.', 'Sagga, Mark G.', 'Else, Jhack L.', 'Fiel, Jay-ar T.']
T = FACTS['tests']

# (title, bullets, image or None)
SLIDES = [
    ('BACKGROUND OF THE STUDY', [
        'Center Point managed stalls with logbooks, paper receipts, and physical folders',
        'Payments were tracked by hand, causing errors and slow verification',
        'Tenants had no access to their own balances, receipts, or contracts',
        'No online way to apply for a stall, and no alerts for expiring leases',
        'Administrator accounts had no extra layer of security',
    ], None),
    ('GENERAL OBJECTIVE', [
        'To design and develop a web-based stall management system for Center Point, using Laravel 12 and Livewire 3, that improves efficiency, transparency, security, and access through automated billing, online payments, real-time communication, analytics, and tenant self-service.',
    ], None),
    ('SPECIFIC OBJECTIVES', [
        'Manage stalls on a visual floor map, leases, renewals, and terminations',
        'Automate monthly invoices, reminders, and lease expiry alerts',
        'Accept online payments through PayMongo with QR-verified receipts',
        'Give tenants a portal for leases, payments, statements, documents, and support',
        'Let the public apply for stalls, check status, and join a waitlist',
        'Protect data with 2FA, encrypted backups, and layered web security',
    ], None),
    ('SCOPE', [
        'Users: Administrator, Tenant, and visitors who are not signed in',
        'Stall, floor, lease, and tenant account management',
        'Automated billing, PayMongo payments, receipts, statements of account',
        'Support tickets with chat, announcements, notifications by email and SMS',
        'Reports, calendar, global search, audit logs, and encrypted backups',
    ], None),
    ('LIMITATION', [
        'Needs a stable internet connection for payments, email, and SMS',
        'No electricity/water billing, late-payment penalties, or partial payments',
        'Grid-based floor map; very irregular layouts may not fit exactly',
        'Two-Factor Authentication is for administrator accounts only',
        'Backups are stored on the same server unless copied elsewhere',
    ], None),
    ('SYSTEM ARCHITECTURE', [
        'Every request passes the security layer first',
        'Livewire 3 pages run on Laravel 12 services',
        'MySQL database with encrypted daily backups',
        'PayMongo, Mailjet, Semaphore, Google and Facebook',
    ], 'fig-architecture.png'),
    ('DATABASE DESIGN', [], 'fig-erd.png'),
    ('PUBLIC PORTAL', [
        'Browse available stalls floor by floor',
        'Apply online with supporting documents',
        'Check status with a reference number',
        'Join a waitlist for an occupied stall',
    ], '03-stall-apply.png'),
    ('ADMIN DASHBOARD', [
        'Needs-attention panel: applications, renewals, overdue, tickets',
        'Revenue trend, payment channels, occupancy',
        'Leases, renewals, and termination with a reason',
        'Monthly calendar and global search',
    ], '10-admin-dashboard.png'),
    ('BILLING & PAYMENTS', [
        'Monthly invoices generated automatically',
        'Online payment via GCash, Maya, and cards',
        'Receipts with QR verification',
        'Statement of account with running balance',
    ], '18-statement.png'),
    ('TENANT PORTAL', [
        'Announcements and upcoming due dates',
        'Leases, renewals, and invoices in one place',
        'Support tickets with real-time chat',
        'Documents, receipts, and statements',
    ], '30-tenant-dashboard.png'),
    ('ADMINISTRATION', [
        'Tenant accounts: deactivate, reactivate, resend link',
        'Announcements to all tenants or one floor',
        'Audit logs, including security events',
        'Encrypted backups and the SMS switch',
    ], '17-tenant-accounts.png'),
    ('SECURITY', [
        'Two-Factor Authentication (SMS code) for administrators',
        'Role, 2FA, and account checks on every action',
        'Content-Security-Policy and security headers block injected scripts',
        'Rate limits on sign-in, registration, password reset, and lookups',
        'Strong passwords, safe uploads, optional CAPTCHA',
        'Encrypted backups and security events in the audit log',
    ], None),
    ('RESULTS AND DISCUSSION', [
        f'{T["passed"]} automated tests — all passing',
        'Zero security-policy violations in the browser',
        'Code review findings fixed and tested',
        'One web platform replaced the logbooks',
    ], 'fig-flowchart-admin.png'),
    ('CONCLUSION', [
        'The system replaced manual stall management with a centralized platform',
        'Automated invoicing, reminders, and online payments reduced errors and delays',
        'Tenants and applicants gained self-service access and clear communication',
        'Layered security and encrypted backups protect tenant and financial data',
        'All general and specific objectives were achieved',
    ], None),
    ('RECOMMENDATION', [
        'Develop a mobile app for tenants',
        'Support multiple branches or locations',
        'Copy backups to off-site storage automatically',
        'Upgrade to Livewire 4 for a stricter security policy',
        'Offer Two-Factor Authentication to tenants',
    ], None),
]


def clone_slide(prs, source):
    """Copy a slide (shapes and their images) to the end of the deck."""
    new = prs.slides.add_slide(source.slide_layout)
    for ph in list(new.placeholders):
        ph._element.getparent().remove(ph._element)
    rid_map = {}
    for rel in source.part.rels.values():
        if 'image' in rel.reltype:
            rid_map[rel.rId] = new.part.relate_to(rel._target, rel.reltype)
    for shape in source.shapes:
        el = copy.deepcopy(shape._element)
        # Template shapes carry click-through links to other slides; their relationships are not
        # copied, so the links are removed (a dangling link makes PowerPoint refuse the file).
        for link in list(el.iter('{http://schemas.openxmlformats.org/drawingml/2006/main}hlinkClick')):
            link.getparent().remove(link)
        for blip in el.iter('{http://schemas.openxmlformats.org/drawingml/2006/main}blip'):
            rid = blip.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}embed')
            if rid in rid_map:
                blip.set('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}embed', rid_map[rid])
        new.shapes._spTree.append(el)
    return new


def shape_with_text(slide, text):
    for sh in slide.shapes:
        if sh.has_text_frame and sh.text_frame.text.strip().startswith(text):
            return sh
    raise LookupError(text)


def set_title(shape, text):
    tf = shape.text_frame
    run = tf.paragraphs[0].runs[0]
    run.text = text
    for extra in tf.paragraphs[0].runs[1:]:
        extra._r.getparent().remove(extra._r)
    run.font.size = Pt(28 if len(text) <= 22 else 24)


def set_bullets(shape, bullets, size):
    tf = shape.text_frame
    tf.word_wrap = True
    tf.vertical_anchor = MSO_ANCHOR.MIDDLE
    tf.margin_left = tf.margin_right = Inches(0.35)
    first = tf.paragraphs[0]
    template_run = copy.deepcopy(first.runs[0]._r) if first.runs else None
    for p in tf.paragraphs[1:]:
        p._p.getparent().remove(p._p)
    for r in list(first.runs):
        r._r.getparent().remove(r._r)
    single = len(bullets) == 1
    for i, text in enumerate(bullets):
        p = first if i == 0 else tf.add_paragraph()
        p.alignment = PP_ALIGN.CENTER if single else PP_ALIGN.LEFT
        p.space_after = Pt(10)
        p.space_before = Pt(0)
        p.line_spacing = 1.0 if not single else 1.25
        if template_run is not None:
            # Runs must come before the paragraph's end marker, or PowerPoint ignores them.
            end = p._p.find('{http://schemas.openxmlformats.org/drawingml/2006/main}endParaRPr')
            new_run = copy.deepcopy(template_run)
            if end is not None:
                end.addprevious(new_run)
            else:
                p._p.append(new_run)
            run = p.runs[-1]
        else:
            run = p.add_run()
        run.text = text if single else f'•  {text}'
        run.font.size = Pt(size)
        run.font.bold = False


def add_picture_fit(slide, image, left, top, width, height):
    with Image.open(os.path.join(ASSETS, image)) as im:
        w, h = im.size
    scale = min(width / w, height / h)
    pw, ph = int(w * scale), int(h * scale)
    slide.shapes.add_picture(os.path.join(ASSETS, image), left + (width - pw) // 2, top + (height - ph) // 2, pw, ph)


def main():
    prs = Presentation(SRC)
    title_slide, content, thanks = prs.slides[0], prs.slides[1], prs.slides[7]

    # Title slide: drop its links to other slides (the agenda boxes that used them are removed below)
    for rid, rel in list(title_slide.part.rels.items()):
        if rel.reltype.endswith('/slide'):
            for link in list(title_slide._element.iter('{http://schemas.openxmlformats.org/drawingml/2006/main}hlinkClick')):
                if link.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id') == rid:
                    link.getparent().remove(link)
            title_slide.part.drop_rel(rid)

    # Title slide: remove the leftover agenda boxes, fill title, researchers, adviser
    for label in ('Background of the Study', 'Chapter 2', 'Chapter 4', 'Chapter 5'):
        sh = shape_with_text(title_slide, label)
        sh._element.getparent().remove(sh._element)
    t = shape_with_text(title_slide, 'Insert')
    t.text_frame.paragraphs[0].runs[0].text = TITLE
    for r in t.text_frame.paragraphs[0].runs[1:]:
        r.text = ''
    t.text_frame.word_wrap = True
    t.left, t.width, t.top, t.height = Inches(0.9), Inches(11.5), Inches(1.6), Inches(1.0)
    t.text_frame.paragraphs[0].runs[0].font.size = Pt(26)
    t.text_frame.paragraphs[0].alignment = PP_ALIGN.CENTER
    people = shape_with_text(title_slide, 'Researcher 1')
    tf = people.text_frame
    template_p = copy.deepcopy(tf.paragraphs[0]._p)
    label_p = copy.deepcopy(tf.paragraphs[-1]._p)
    for p in list(tf.paragraphs):
        p._p.getparent().remove(p._p)
    for name in RESEARCHERS:
        el = copy.deepcopy(template_p)
        tf._txBody.append(el)
        tf.paragraphs[-1].runs[0].text = name
        for r in tf.paragraphs[-1].runs[1:]:
            r.text = ''
        tf.paragraphs[-1].runs[0].font.size = Pt(16)
    tf._txBody.append(label_p)
    people.top = Inches(2.85)
    people.height = Inches(2.6)
    adviser = shape_with_text(title_slide, 'Juan dela Cruz')
    adviser.text_frame.paragraphs[0].runs[0].text = '[Adviser’s Name]'
    for r in adviser.text_frame.paragraphs[0].runs[1:]:
        r.text = ''

    # Content slides: copies of the template content slide
    body_left, body_top, body_w, body_h = Inches(0.37), Inches(2.33), Inches(12.59), Inches(4.26)
    for title, bullets, image in SLIDES:
        s = clone_slide(prs, content)
        set_title(shape_with_text(s, 'GENERAL OBJECTIVES'), title)
        body = shape_with_text(s, 'Insert General Objectives')
        if image and not bullets:
            body._element.getparent().remove(body._element)
            add_picture_fit(s, image, Inches(0.4), Inches(2.25), Inches(12.5), Inches(4.45))
        elif image:
            body.width = Inches(4.4)
            set_bullets(body, bullets, 18)
            add_picture_fit(s, image, Inches(4.95), Inches(2.25), Inches(8.1), Inches(4.45))
        else:
            set_bullets(body, bullets, 24 if len(bullets) == 1 else 20)

    # Order: title, new content slides, thank-you; drop the template's own content slides
    ids = prs.slides._sldIdLst
    entries = list(ids)
    keep_first, old_content, new_slides = entries[0], entries[1:8], entries[8:]
    thanks_entry = entries[7]
    for e in entries[1:8]:
        ids.remove(e)
    for e in new_slides:
        ids.remove(e)
        ids.append(e)
    ids.remove(thanks_entry) if thanks_entry in list(ids) else None
    ids.append(thanks_entry)
    # drop the unused template slides' parts from the package
    for e in old_content:
        if e is thanks_entry:
            continue
        prs.part.drop_rel(e.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id'))
    prs.save(OUT)
    print('wrote', OUT, '| slides:', len(prs.slides))


if __name__ == '__main__':
    main()
