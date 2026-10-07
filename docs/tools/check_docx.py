"""Checks on docs/updated.docx. Exit code 1 on any failure.

  python docs/tools/check_docx.py            all checks
  python docs/tools/check_docx.py --upto ch2 outdated-term scan limited to chapters I-II
"""
import re
import sys

from docx import Document
from docx.shared import Inches

DOC = 'docs/updated.docx'
OUTDATED = re.compile(r'\b(utility|utilities|meter(ed)?|staff|late fee|surcharge|laravel 13)\b', re.I)
TEXT_WIDTH = Inches(6.0)

d = Document(DOC)
upto = sys.argv[sys.argv.index('--upto') + 1] if '--upto' in sys.argv else None
stop_at = {'ch2': 'CHAPTER III', 'ch4': 'CHAPTER V'}.get(upto)

failures = []
in_refs = False
for p in d.paragraphs:
    t = p.text.strip()
    if stop_at and t == stop_at:
        break
    if t in ('REFERENCES',):
        in_refs = True
    elif t.startswith('CHAPTER') or t in ('USER GUI GUIDE', 'SYSTEM USER GUIDE MANUAL'):
        in_refs = False
    if not in_refs and OUTDATED.search(t):
        failures.append(f'outdated term: {t[:110]}')
for ti, table in enumerate(d.tables):
    for row in table.rows:
        for cell in row.cells:
            if OUTDATED.search(cell.text):
                failures.append(f'outdated term in table {ti}: {cell.text[:80]}')

if not upto:
    for shape in d.inline_shapes:
        if shape.width > TEXT_WIDTH + Inches(0.01):
            failures.append(f'image wider than text width: {shape.width.inches:.2f} in')
    captions = [int(m.group(1)) for p in d.paragraphs for m in [re.match(r'Figure (\d+)\. ', p.text.strip())] if m]
    if captions != list(range(1, len(captions) + 1)):
        failures.append(f'figure numbers not consecutive: {captions}')
    order = ['CHAPTER I', 'CHAPTER II', 'CHAPTER III', 'CHAPTER IV', 'CHAPTER V', 'RESULTS AND DISCUSSIONS',
             'TRANSMITTAL LETTER', 'PHOTO DOCUMENTATION', 'USER GUI GUIDE', 'SYSTEM USER GUIDE MANUAL', 'REFERENCES',
             'PERSONAL INFORMATION']
    texts = [p.text.strip() for p in d.paragraphs]
    pos = -1
    for h in order:
        try:
            i = texts.index(h, pos + 1)
        except ValueError:
            failures.append(f'heading missing or out of order: {h}')
            continue
        pos = i

for f in failures:
    print('FAIL', f)
print('checks:', 'FAILED' if failures else 'OK', f'({len(failures)} problems)')
sys.exit(1 if failures else 0)
