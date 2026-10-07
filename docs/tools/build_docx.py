"""Builds docs/updated.docx from docs/current.docx (never modified).

New paragraphs are deep copies of existing ones, so styles, fonts and list numbering stay the
document's own. Rewritten or new text is highlighted yellow for the team to review.

Run from the repository root:  python docs/tools/build_docx.py
"""
import copy
import importlib
import os
import sys

from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK, WD_COLOR_INDEX
from docx.shared import Inches, Pt

HERE = os.path.dirname(os.path.abspath(__file__))
DOCS = os.path.dirname(HERE)
SRC = os.path.join(DOCS, 'current.docx')
OUT = os.path.join(DOCS, 'updated.docx')
ASSETS = os.path.join(DOCS, 'assets')
TEXT_WIDTH_IN = 6.0  # Letter page, 1.5" left + 1.0" right margins

sys.path.insert(0, HERE)


class Builder:
    def __init__(self, path):
        self.doc = Document(path)
        self.figure_no = 0
        self.table_no = 2  # the manuscript already has Table 1 and Table 2

    # ------------------------------------------------------------- finding
    def paragraphs(self):
        return list(self.doc.paragraphs)

    def find(self, prefix, after=None, required=True):
        """The first paragraph whose stripped text starts with prefix (optionally after another paragraph)."""
        started = after is None
        for p in self.paragraphs():
            if not started:
                started = p._p is after._p
                continue
            if p.text.strip().startswith(prefix):
                return p
        if required:
            raise LookupError(f'paragraph not found: {prefix!r}')
        return None

    # ------------------------------------------------------------- editing
    @staticmethod
    def mark(p):
        for r in p.runs:
            r.font.highlight_color = WD_COLOR_INDEX.YELLOW

    def set(self, p, text, mark=True):
        """Replace a paragraph's text, keeping its first run's formatting."""
        runs = p.runs
        if not runs:
            p.add_run(text)
        else:
            runs[0].text = text
            for r in runs[1:]:
                r._r.getparent().remove(r._r)
        if mark:
            self.mark(p)
        return p

    def delete(self, p):
        p._p.getparent().remove(p._p)

    def delete_prefix(self, prefix, after=None):
        self.delete(self.find(prefix, after))

    def after(self, anchor, text, like=None, mark=True, plain=False):
        """Insert a new paragraph after anchor, formatted like `like` (default: the anchor).

        plain=True drops list numbering (for typed step numbers that must restart at 1)."""
        src = like if like is not None else anchor
        new = copy.deepcopy(src._p)
        anchor._p.addnext(new)
        from docx.text.paragraph import Paragraph
        p = Paragraph(new, anchor._parent)
        if plain and p._p.pPr is not None and p._p.pPr.numPr is not None:
            p._p.pPr.remove(p._p.pPr.numPr)
        p.paragraph_format.page_break_before = False
        # drop pictures / page breaks copied from the template
        for r in list(p.runs):
            if r._r.findall('.//{http://schemas.openxmlformats.org/wordprocessingml/2006/main}drawing') or r._r.findall('.//{http://schemas.openxmlformats.org/wordprocessingml/2006/main}br'):
                r._r.getparent().remove(r._r)
        return self.set(p, text, mark)

    def after_many(self, anchor, texts, like=None, mark=True, plain=False):
        last = anchor
        for t in texts:
            last = self.after(last, t, like=like, mark=mark, plain=plain)
        return last

    def replace_everywhere(self, pairs, mark=True, include_tables=True):
        """Phrase replacements across all paragraphs (and table cells). Returns paragraphs changed."""
        changed = []
        targets = list(self.doc.paragraphs)
        if include_tables:
            for t in self.doc.tables:
                for row in t.rows:
                    for cell in row.cells:
                        targets.extend(cell.paragraphs)
        for p in targets:
            text = p.text
            new = text
            for old, rep in pairs:
                new = new.replace(old, rep)
            if new != text:
                # run-level replacement first (keeps mixed formatting); fall back to whole paragraph
                done = True
                for old, rep in pairs:
                    if old in text and not any(old in r.text for r in p.runs):
                        done = False
                if done:
                    for r in p.runs:
                        for old, rep in pairs:
                            r.text = r.text.replace(old, rep)
                    if mark:
                        self.mark(p)
                else:
                    self.set(p, new, mark)
                changed.append(p)
        return changed

    def page_break_before(self, p):
        p.paragraph_format.page_break_before = True

    def picture(self, anchor, image, caption, width_in=TEXT_WIDTH_IN, like=None):
        """Insert a centred picture and a 'Figure N. caption' line after anchor. Returns the caption paragraph."""
        holder = self.after(anchor, '', like=like or anchor, mark=False)
        for r in list(holder.runs):
            r._r.getparent().remove(r._r)
        pf = holder.paragraph_format
        pf.alignment = WD_ALIGN_PARAGRAPH.CENTER
        pf.first_line_indent = Inches(0)
        pf.left_indent = Inches(0)
        holder._p.get_or_add_pPr()
        num = holder._p.pPr.numPr
        if num is not None:
            holder._p.pPr.remove(num)
        holder.add_run().add_picture(os.path.join(ASSETS, image), width=Inches(width_in))
        self.figure_no += 1
        cap = self.after(holder, f'Figure {self.figure_no}. {caption}', like=holder, mark=False)
        cap.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
        for r in cap.runs:
            r.italic = True
            r.font.size = Pt(10)
        return cap

    def heading_after(self, anchor, text, like, page_break=False, mark=True):
        h = self.after(anchor, text, like=like, mark=mark, plain=True)
        h.paragraph_format.page_break_before = page_break
        return h

    def table_after(self, anchor, header, rows, caption, like_table=None, mark=True):
        """A simple bordered table after anchor, plus an italic 'Table N.' caption."""
        table = self.doc.add_table(rows=1 + len(rows), cols=len(header))
        if like_table is not None:
            table.style = like_table.style
        else:
            table.style = 'Table Grid'
        for i, h in enumerate(header):
            cell = table.rows[0].cells[i]
            cell.text = h
            for r in cell.paragraphs[0].runs:
                r.bold = True
        for ri, row in enumerate(rows, start=1):
            for ci, val in enumerate(row):
                table.rows[ri].cells[ci].text = str(val)
                if mark:
                    for r in table.rows[ri].cells[ci].paragraphs[0].runs:
                        r.font.highlight_color = WD_COLOR_INDEX.YELLOW
        anchor._p.addnext(table._tbl)
        self.table_no += 1
        from docx.text.paragraph import Paragraph
        cap_el = copy.deepcopy(anchor._p)
        table._tbl.addnext(cap_el)
        cap = Paragraph(cap_el, anchor._parent)
        self.set(cap, f'Table {self.table_no}. {caption}', mark=False)
        cap.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER
        num = cap._p.pPr.numPr if cap._p.pPr is not None else None
        if num is not None:
            cap._p.pPr.remove(num)
        for r in cap.runs:
            r.italic = True
            r.bold = False
        return cap

    # ------------------------------------------------------------- tables
    def term_table(self):
        return self.doc.tables[0]

    @staticmethod
    def delete_row(table, first_cell_prefix):
        for row in table.rows:
            if row.cells[0].text.strip().startswith(first_cell_prefix):
                row._tr.getparent().remove(row._tr)
                return
        raise LookupError(first_cell_prefix)

    def add_row(self, table, values, mark=True):
        new = copy.deepcopy(table.rows[-1]._tr)
        table._tbl.append(new)
        row = table.rows[-1]
        for cell, val in zip(row.cells, values):
            ps = cell.paragraphs
            self.set(ps[0], val, mark)
            for extra in ps[1:]:
                extra._p.getparent().remove(extra._p)
        return row

    def set_row(self, table, first_cell_prefix, values, mark=True):
        for row in table.rows:
            if row.cells[0].text.strip().startswith(first_cell_prefix):
                for cell, val in zip(row.cells, values):
                    if val is not None:
                        self.set(cell.paragraphs[0], val, mark)
                return row
        raise LookupError(first_cell_prefix)

    def save(self):
        self.doc.save(OUT)


def main():
    b = Builder(SRC)
    for name in ['content.ch1', 'content.ch2', 'content.ch3', 'content.ch4', 'content.ch5', 'content.backmatter']:
        try:
            module = importlib.import_module(name)
        except ModuleNotFoundError as e:
            if e.name == name:
                continue  # chapter not written yet
            raise
        module.apply(b)
        print('applied', name)
    # Original figures wider than the text area are scaled down to fit the margins.
    limit = Inches(TEXT_WIDTH_IN)
    for shape in b.doc.inline_shapes:
        if shape.width > limit:
            ratio = limit / shape.width
            shape.height = int(shape.height * ratio)
            shape.width = limit
    b.save()
    print('wrote', OUT, '| figures:', b.figure_no)


if __name__ == '__main__':
    main()
