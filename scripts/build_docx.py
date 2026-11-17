"""Convert the deployment runbook (README.md) into a styled .docx.

Usage:  python scripts/build_docx.py [output.docx]
Requires: pip install python-docx
"""
import os
import re
import sys

from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Pt, RGBColor

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SRC = os.path.join(ROOT, "README.md")
OUT = sys.argv[1] if len(sys.argv) > 1 else os.path.join(ROOT, "restaurant-deploy-guide.docx")

ACCENT = RGBColor(0x1E, 0x40, 0xAF)
MUTED = RGBColor(0x47, 0x55, 0x69)


def shade(cell, hexcolor):
    el = OxmlElement("w:shd")
    el.set(qn("w:val"), "clear")
    el.set(qn("w:fill"), hexcolor)
    cell._tc.get_or_add_tcPr().append(el)


def add_code(doc, text):
    """Render a fenced block as a shaded monospace paragraph."""
    p = doc.add_paragraph()
    p.paragraph_format.left_indent = Pt(12)
    p.paragraph_format.space_before = Pt(4)
    p.paragraph_format.space_after = Pt(8)
    pPr = p._p.get_or_add_pPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:fill"), "F1F5F9")
    pPr.append(shd)
    run = p.add_run(text.rstrip("\n"))
    run.font.name = "Consolas"
    run.font.size = Pt(9)
    run._element.rPr.rFonts.set(qn("w:eastAsia"), "Consolas")


INLINE = re.compile(r"(<https?://[^>]+>|`[^`]+`|\*\*[^*]+\*\*|\[[^\]]+\]\([^)]+\))")


def add_inline(paragraph, text, base_bold=False):
    for part in INLINE.split(text):
        if not part:
            continue
        if part.startswith("<http") and part.endswith(">"):
            r = paragraph.add_run(part[1:-1])
            r.font.color.rgb = ACCENT
            r.underline = True
            r.font.size = Pt(9.5)
        elif part.startswith("`") and part.endswith("`") and len(part) > 2:
            r = paragraph.add_run(part[1:-1])
            r.font.name = "Consolas"
            r.font.size = Pt(9.5)
        elif part.startswith("**") and part.endswith("**") and len(part) > 4:
            r = paragraph.add_run(part[2:-2])
            r.bold = True
            r.bold = True
        elif part.startswith("[") and "](" in part:
            label = part[1:part.index("]")]
            url = part[part.index("](") + 2:-1]
            r = paragraph.add_run(label)
            r.font.color.rgb = ACCENT
            r.underline = True
            r.font.size = Pt(10)
            paragraph.add_run(" ")
            u = paragraph.add_run(f"({url})")
            u.font.size = Pt(8.5)
            u.font.color.rgb = MUTED
        else:
            r = paragraph.add_run(part)
            r.bold = base_bold


def build():
    with open(SRC, encoding="utf-8") as fh:
        lines = fh.read().splitlines()

    doc = Document()
    style = doc.styles["Normal"]
    style.font.name = "Calibri"
    style.font.size = Pt(10.5)
    style.paragraph_format.space_after = Pt(6)
    style.paragraph_format.line_spacing = 1.15

    i = 0
    in_code = False
    code_buf = []
    table_buf = []

    def flush_code():
        nonlocal code_buf
        if code_buf:
            add_code(doc, "\n".join(code_buf))
            code_buf = []

    def flush_table():
        nonlocal table_buf
        if not table_buf:
            return
        rows = []
        for raw in table_buf:
            cells = [c.strip() for c in raw.strip().strip("|").split("|")]
            if all(re.fullmatch(r":?-{2,}:?", c) for c in cells if c):
                continue
            rows.append(cells)
        table_buf = []
        if not rows:
            return
        width = max(len(r) for r in rows)
        table = doc.add_table(rows=0, cols=width)
        table.style = "Light Grid Accent 1"
        for ridx, row in enumerate(rows):
            cells = table.add_row().cells
            for cidx in range(width):
                text = row[cidx] if cidx < len(row) else ""
                para = cells[cidx].paragraphs[0]
                add_inline(para, text, base_bold=(ridx == 0))
                for run in para.runs:
                    run.font.size = Pt(9)
                if ridx == 0:
                    shade(cells[cidx], "DBEAFE")
        doc.add_paragraph()

    while i < len(lines):
        line = lines[i]

        if line.strip().startswith("```"):
            if in_code:
                flush_code()
                in_code = False
            else:
                flush_table()
                in_code = True
            i += 1
            continue

        if in_code:
            code_buf.append(line)
            i += 1
            continue

        if line.strip().startswith("|"):
            table_buf.append(line)
            i += 1
            continue
        flush_table()

        if re.fullmatch(r"-{3,}", line.strip()):
            p = doc.add_paragraph()
            pPr = p._p.get_or_add_pPr()
            pbdr = OxmlElement("w:pBdr")
            bottom = OxmlElement("w:bottom")
            bottom.set(qn("w:val"), "single")
            bottom.set(qn("w:sz"), "6")
            bottom.set(qn("w:color"), "CBD5E1")
            pbdr.append(bottom)
            pPr.append(pbdr)
            i += 1
            continue

        m = re.match(r"^(#{1,4})\s+(.*)$", line)
        if m:
            level, text = len(m.group(1)), m.group(2)
            if level == 1:
                h = doc.add_heading(level=0)
                add_inline(h, text)
            else:
                h = doc.add_heading(level=min(level, 4))
                add_inline(h, text)
            i += 1
            continue

        m = re.match(r"^\s*[-*]\s+(.*)$", line)
        if m:
            p = doc.add_paragraph(style="List Bullet")
            add_inline(p, m.group(1))
            i += 1
            continue

        m = re.match(r"^\s*\d+\.\s+(.*)$", line)
        if m:
            p = doc.add_paragraph(style="List Number")
            add_inline(p, m.group(1))
            i += 1
            continue

        m = re.match(r"^>\s?(.*)$", line)
        if m:
            p = doc.add_paragraph()
            p.paragraph_format.left_indent = Pt(14)
            r = p.add_run(m.group(1))
            r.italic = True
            r.font.color.rgb = MUTED
            i += 1
            continue

        if line.strip() == "":
            i += 1
            continue

        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        add_inline(p, line.strip())
        i += 1

    flush_table()
    flush_code()

    doc.save(OUT)
    print(f"wrote {OUT}")


if __name__ == "__main__":
    build()