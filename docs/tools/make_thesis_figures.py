"""Thesis figures as draw.io pages: Admin and Tenant system flowcharts, Use Case Diagram, Content Diagram.

Output: docs/diagrams/thesis-figures.drawio (one page per figure). Rerun after changing the lists below.
"""
import os
from xml.sax.saxutils import escape

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, 'diagrams', 'thesis-figures.drawio')

STYLE = {
    'term': 'ellipse;whiteSpace=wrap;html=1;fillColor=#D5E8D4;strokeColor=#82B366;fontStyle=1;fontSize=13;',
    'proc': 'rounded=1;whiteSpace=wrap;html=1;fillColor=#DAE8FC;strokeColor=#6C8EBF;fontSize=13;',
    'io': 'shape=parallelogram;perimeter=parallelogramPerimeter;whiteSpace=wrap;html=1;fixedSize=1;fillColor=#FFF2CC;strokeColor=#D6B656;fontSize=13;',
    'dec': 'rhombus;whiteSpace=wrap;html=1;fillColor=#FFE6CC;strokeColor=#D79B00;fontSize=12;',
    'auto': 'shape=process;whiteSpace=wrap;html=1;backgroundOutline=1;fillColor=#E1D5E7;strokeColor=#9673A6;fontSize=12;',
    'edge': 'edgeStyle=orthogonalEdgeStyle;rounded=1;html=1;endArrow=block;endFill=1;strokeColor=#444444;fontSize=11;labelBackgroundColor=#FFFFFF;',
    'actor': 'shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;outlineConnect=0;fontStyle=1;fontSize=13;',
    'uc': 'ellipse;whiteSpace=wrap;html=1;fillColor=#FFFFFF;strokeColor=#333333;fontSize=12;',
    'boundary': 'rounded=0;whiteSpace=wrap;html=1;fillColor=none;strokeColor=#333333;verticalAlign=top;fontStyle=1;fontSize=15;',
    'line': 'endArrow=none;html=1;strokeColor=#555555;',
    'tree': 'edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;endArrow=none;strokeColor=#555555;',
    'page_root': 'rounded=1;whiteSpace=wrap;html=1;fillColor=#1E3A8A;fontColor=#FFFFFF;strokeColor=#1E3A8A;fontStyle=1;fontSize=14;',
    'page_group': 'rounded=1;whiteSpace=wrap;html=1;fillColor=#6366F1;fontColor=#FFFFFF;strokeColor=#4F46E5;fontStyle=1;fontSize=13;',
    'page_leaf': 'rounded=1;whiteSpace=wrap;html=1;fillColor=#EEF2FF;strokeColor=#6366F1;fontSize=12;',
}


class Page:
    def __init__(self, name):
        self.name = name
        self.cells = []
        self.n = 1

    def node(self, kind, text, x, y, w=190, h=56):
        self.n += 1
        nid = f'n{self.n}'
        label = escape(text).replace('\n', '&lt;br&gt;')  # html=1 labels break lines on <br>, not newlines
        self.cells.append(f'<mxCell id="{nid}" value="{label}" style="{STYLE[kind]}" vertex="1" parent="1">'
                          f'<mxGeometry x="{x}" y="{y}" width="{w}" height="{h}" as="geometry"/></mxCell>')
        return nid

    def edge(self, a, b, text='', kind='edge', points=None, extra=''):
        self.n += 1
        pts = ''
        if points:
            pts = '<Array as="points">' + ''.join(f'<mxPoint x="{px}" y="{py}"/>' for px, py in points) + '</Array>'
        self.cells.append(f'<mxCell id="e{self.n}" value="{escape(text)}" style="{STYLE[kind]}{extra}" edge="1" parent="1" source="{a}" target="{b}">'
                          f'<mxGeometry relative="1" as="geometry">{pts}</mxGeometry></mxCell>')

    def xml(self, index):
        return (f'<diagram id="t{index}" name="{escape(self.name)}"><mxGraphModel dx="1400" dy="900" grid="0" gridSize="10" '
                f'guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="0" pageScale="1" math="0" shadow="0">'
                f'<root><mxCell id="0"/><mxCell id="1" parent="0"/>{"".join(self.cells)}</root></mxGraphModel></diagram>')


def role_flowchart(name, sign_in_steps, dashboard, modules, x0=420):
    """Vertical flowchart: start → sign-in checks → dashboard → module branch → back → logout → end."""
    p = Page(name)
    cx = x0
    y = 20
    prev = p.node('term', 'Start', cx + 10, y, 170, 50)
    y += 90
    first_input = None
    for kind, text, no_text in sign_in_steps:
        h = 90 if kind == 'dec' else 56
        cur = p.node(kind, text, cx - 10 if kind == 'dec' else cx, y, 210 if kind == 'dec' else 190, h)
        p.edge(prev, cur, 'Yes' if prev.startswith('n') and getattr(p, '_last_dec', False) else '')
        p._last_dec = kind == 'dec'
        if kind == 'io' and first_input is None:
            first_input = (cur, y)
        if kind == 'dec' and no_text:
            err = p.node('io', no_text, cx + 300, y + 17, 230, 70)
            p.edge(cur, err, 'No')
            if first_input and first_input[1] < y:
                p.edge(err, first_input[0], 'Try again', extra='exitX=1;exitY=0.5;entryX=1;entryY=0.5;',
                       points=[(cx + 570, y + 52), (cx + 570, first_input[1] + 28)])
        prev = cur
        y += h + 40
    dash = p.node('proc', dashboard, cx, y, 190, 56)
    p.edge(prev, dash, 'Yes' if getattr(p, '_last_dec', False) else '')
    y += 96
    choose = p.node('dec', 'Choose a module', cx - 10, y, 210, 90)
    p.edge(dash, choose)
    y += 150
    # modules laid out in a row of columns under the decision
    count = len(modules)
    spacing = 230
    left = cx + 95 - (count - 1) * spacing / 2
    bottoms = []
    for i, (title, action) in enumerate(modules):
        mx = left + i * spacing - 100
        m = p.node('proc', title, mx, y, 200, 64)
        p.edge(choose, m, '')
        a = p.node('auto', action, mx, y + 104, 200, 74)
        p.edge(m, a)
        bottoms.append(a)
    merge_y = y + 104 + 74 + 30
    y += 104 + 74 + 70
    more = p.node('dec', 'Continue working?', cx - 10, y, 210, 90)
    for i, b in enumerate(bottoms):
        bx = left + i * spacing
        p.edge(b, more, extra='exitX=0.5;exitY=1;entryX=0.5;entryY=0;', points=[(bx, merge_y), (cx + 95, merge_y)])
    p.edge(more, dash, 'Yes', points=[(left - 170, y + 45), (left - 170, dash_y(p, dash))])
    y += 130
    out = p.node('proc', 'Log out', cx, y, 190, 56)
    p.edge(more, out, 'No')
    y += 96
    end = p.node('term', 'End', cx + 10, y, 170, 50)
    p.edge(out, end)
    return p


def dash_y(page, node_id):
    for c in page.cells:
        if f'id="{node_id}"' in c:
            y = float(c.split('y="')[1].split('"')[0])
            return y + 28
    return 0


admin = role_flowchart(
    'System Flowchart for Admin',
    [('io', 'Enter email and password\n(or Google / Facebook)', None),
     ('dec', 'Credentials valid and\naccount active?', 'Show error message'),
     ('dec', 'Two-factor code\ncorrect? (if enabled)', 'Show attempts left;\nsign out after 5'),
     ],
    'Admin Dashboard\n(needs-attention panel)',
    [('Stalls, Floors\n& Floor Map', 'Add / edit stalls;\nplace them on the floor grid'),
     ('Applications', 'Approve (account created,\nset-password link) or reject'),
     ('Leases &\nTenant Accounts', 'Create, renew or terminate\nleases; deactivate accounts'),
     ('Payments\n& Reports', 'Record cash payments;\nexport reports and statements'),
     ('Tickets &\nAnnouncements', 'Reply to tenants;\npost notices'),
     ('Calendar\n& Search', 'View due dates and\nlease ends; find records'),
     ('Settings, Backups\n& Audit Logs', 'Update settings, SMS switch;\nback up; review activity'),
     ],
    x0=720,
)

tenant = role_flowchart(
    'System Flowchart for Tenant',
    [('dec', 'Has an account?', 'Browse stalls → apply →\ncheck status → set password\nfrom approval email'),
     ('io', 'Enter email and password\n(or Google / Facebook)', None),
     ('dec', 'Credentials valid and\naccount active?', 'Show error message'),
     ],
    'Tenant Dashboard\n(announcements, upcoming dates)',
    [('My Leases', 'View lease details;\nrequest a renewal'),
     ('Invoices &\nPayments', 'Pay online via PayMongo;\nwebhook marks invoice paid'),
     ('Support Tickets', 'Open a ticket;\nchat with the office'),
     ('Documents', 'Download contract, receipts\nand statement of account'),
     ('Profile', 'Update details\nand password'),
     ],
    x0=560,
)

# ---------------------------------------------------------------- Use case
uc = Page('Use Case Diagram')
uc.node('boundary', 'Web-Based Stall Management System for Center Point', 260, 20, 900, 970)
actors = {
    'Visitor': (60, 120), 'Tenant': (60, 520), 'Administrator': (1290, 360),
    'PayMongo': (60, 900), 'Scheduler': (1290, 900),
}
actor_ids = {name: uc.node('actor', name, x, y, 50, 90) for name, (x, y) in actors.items()}
cases = {
    'Visitor': ['Browse available stalls', 'Apply for a stall', 'Check application status', 'Join stall waitlist', 'Register / sign in'],
    'Tenant': ['View dashboard and announcements', 'View leases / request renewal', 'Pay invoices online', 'Download receipts and statement', 'Submit support tickets and chat', 'Update profile and password'],
    'Administrator': ['Manage stalls, floors and floor map', 'Review stall applications', 'Manage leases and terminations', 'Manage tenant accounts', 'Record payments and view reports', 'Post announcements', 'Handle support tickets', 'View calendar and search records', 'Manage settings, backups and audit logs'],
    'PayMongo': ['Process online payment'],
    'Scheduler': ['Generate invoices, reminders, overdue flags and backups'],
}
positions = {
    'Visitor': [(330, 70 + i * 70) for i in range(5)],
    'Tenant': [(330, 450 + i * 70) for i in range(6)],
    'Administrator': [(760, 70 + i * 82) for i in range(9)],
    'PayMongo': [(330, 900)],
    'Scheduler': [(760, 900)],
}
for actor, items in cases.items():
    for text, (x, y) in zip(items, positions[actor]):
        u = uc.node('uc', text, x, y, 320, 54)
        side = 'entryX=1;entryY=0.5;' if actor in ('Administrator', 'Scheduler') else 'entryX=0;entryY=0.5;'
        uc.edge(actor_ids[actor], u, kind='line', extra=side)
pay_case = [c for c in uc.cells if 'Pay invoices online' in c][0].split('id="')[1].split('"')[0]
process_case = [c for c in uc.cells if 'Process online payment' in c][0].split('id="')[1].split('"')[0]
# Routed through the gap between the use-case columns so it crosses no other use case.
pay_y = 450 + 2 * 70 + 27
uc.edge(pay_case, process_case, '«include»', kind='edge', extra='exitX=1;exitY=0.5;entryX=1;entryY=0.5;',
        points=[(700, pay_y), (700, 927)])

# ---------------------------------------------------------------- Content diagram (site map)
cd = Page('Content Diagram')
root = cd.node('page_root', 'Center Point Stall Management System', 620, 20, 360, 60)
groups = [
    ('Public Pages', ['Home', 'Browse Stalls', 'Apply for a Stall', 'Application Status', 'Sign in / Register', 'Forgot Password', 'Verify Receipt (QR)', 'Privacy Policy & Terms']),
    ('Tenant Portal', ['Dashboard', 'My Leases', 'Invoices & Payments', 'Support Tickets', 'Documents', 'Profile', 'Notifications']),
    ('Admin Panel', ['Dashboard', 'Stalls', 'Floors', 'Floor Map', 'Leases & Tenants', 'Tenant Accounts', 'Applications', 'Payments', 'Calendar', 'Reports', 'Support Tickets', 'Announcements', 'Audit Logs', 'Error Logs', 'System Settings', 'Global Search', 'Two-Factor Challenge']),
]
gx = [80, 620, 1160]
for (title, leaves), x in zip(groups, gx):
    g = cd.node('page_group', title, x, 150, 300, 56)
    cd.edge(root, g, kind='tree')
    for i, leaf in enumerate(leaves):
        lf = cd.node('page_leaf', leaf, x + 60, 240 + i * 62, 240, 44)
        cd.edge(g, lf, kind='tree', points=[(x + 30, 263 + i * 62)])

pages = [admin, tenant, uc, cd]
xml = '<mxfile host="app.diagrams.net" type="device">' + ''.join(p.xml(i) for i, p in enumerate(pages)) + '</mxfile>'
open(OUT, 'w', encoding='utf-8').write(xml)
print('wrote', OUT, [p.name for p in pages])
