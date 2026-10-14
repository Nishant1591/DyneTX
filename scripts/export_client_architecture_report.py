"""Export a client report to DOCX using Python's standard library."""

from pathlib import Path
import re
import sys
import xml.etree.ElementTree as ET
from zipfile import ZIP_DEFLATED, ZipFile


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / (sys.argv[1] if len(sys.argv) > 1 else "reports/Dyne-Project-Architecture-and-Approach-2026-10-07.md")
OUTPUT = SOURCE.with_suffix(".docx")
WORD = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
RELATIONSHIPS = "http://schemas.openxmlformats.org/package/2006/relationships"
CONTENT_TYPES = "http://schemas.openxmlformats.org/package/2006/content-types"
XML = "http://www.w3.org/XML/1998/namespace"
ET.register_namespace("w", WORD)


def word_element(parent, name, **attributes):
    return ET.SubElement(
        parent,
        f"{{{WORD}}}{name}",
        {f"{{{WORD}}}{key}": str(value) for key, value in attributes.items()},
    )


def add_text(paragraph, text, bold=False, code=False):
    run = word_element(paragraph, "r")
    if bold or code:
        properties = word_element(run, "rPr")
        if bold:
            word_element(properties, "b")
        if code:
            word_element(properties, "rFonts", ascii="Consolas", hAnsi="Consolas")
            word_element(properties, "sz", val=18)
    content = word_element(run, "t")
    content.set(f"{{{XML}}}space", "preserve")
    content.text = text


def add_inline(paragraph, text):
    pattern = r"(\*\*[^*]+\*\*|`[^`]+`|\[[^\]]+\]\([^)]+\))"
    for part in re.split(pattern, text):
        if part.startswith("**") and part.endswith("**"):
            add_text(paragraph, part[2:-2], bold=True)
        elif part.startswith("`") and part.endswith("`"):
            add_text(paragraph, part[1:-1], code=True)
        else:
            link = re.fullmatch(r"\[([^\]]+)\]\(([^)]+)\)", part)
            if link:
                add_text(paragraph, f"{link.group(1)} ({link.group(2)})")
            else:
                add_text(paragraph, part)


def add_paragraph(parent, text, style="Normal"):
    paragraph = word_element(parent, "p")
    properties = word_element(paragraph, "pPr")
    word_element(properties, "pStyle", val=style)
    if style == "Heading1" and text.startswith("1. Executive"):
        word_element(properties, "pageBreakBefore")
    add_inline(paragraph, text)
    return paragraph


def add_table(parent, rows):
    column_count = len(rows[0])
    assert all(len(row) == column_count for row in rows), "Unequal table columns"
    table = word_element(parent, "tbl")
    properties = word_element(table, "tblPr")
    word_element(properties, "tblW", w=10080, type="dxa")
    word_element(properties, "tblLayout", type="fixed")
    borders = word_element(properties, "tblBorders")
    for edge in ["top", "left", "bottom", "right", "insideH", "insideV"]:
        word_element(borders, edge, val="single", sz=4, color="CBD5DE")
    width = 10080 // column_count
    grid = word_element(table, "tblGrid")
    for column_index in range(column_count):
        word_element(grid, "gridCol", w=width)
    for row_index, row in enumerate(rows):
        table_row = word_element(table, "tr")
        row_properties = word_element(table_row, "trPr")
        word_element(row_properties, "cantSplit")
        if row_index == 0:
            word_element(row_properties, "tblHeader")
        for value in row:
            cell = word_element(table_row, "tc")
            cell_properties = word_element(cell, "tcPr")
            word_element(cell_properties, "tcW", w=width, type="dxa")
            if row_index == 0:
                word_element(cell_properties, "shd", fill="E5EDF3")
            paragraph = add_paragraph(cell, value, "TableText")
            if row_index == 0:
                for run in paragraph.findall(f"{{{WORD}}}r"):
                    run_properties = run.find(f"{{{WORD}}}rPr")
                    if run_properties is None:
                        run_properties = ET.Element(f"{{{WORD}}}rPr")
                        run.insert(0, run_properties)
                    word_element(run_properties, "b")
    add_paragraph(parent, "")


def styles_document():
    styles = ET.Element(f"{{{WORD}}}styles")
    definitions = [
        ("Normal", "Normal", 21, "263746", False),
        ("Title", "Title", 42, "17324D", True),
        ("Subtitle", "Subtitle", 27, "39708B", False),
        ("Heading1", "heading 1", 28, "17324D", True),
        ("Heading2", "heading 2", 23, "39708B", True),
        ("Heading3", "heading 3", 21, "39708B", True),
        ("TableText", "Table Text", 18, "263746", False),
    ]
    for style_id, label, size, color, bold in definitions:
        style = word_element(styles, "style", type="paragraph", styleId=style_id)
        if style_id == "Normal":
            style.set(f"{{{WORD}}}default", "1")
        word_element(style, "name", val=label)
        if style_id != "Normal":
            word_element(style, "basedOn", val="Normal")
        properties = word_element(style, "pPr")
        word_element(properties, "spacing", after=100, line=264, lineRule="auto")
        if style_id.startswith("Heading"):
            word_element(properties, "keepNext")
            word_element(properties, "outlineLvl", val=int(style_id[-1]) - 1)
        run_properties = word_element(style, "rPr")
        word_element(run_properties, "rFonts", ascii="Aptos", hAnsi="Aptos")
        word_element(run_properties, "sz", val=size)
        word_element(run_properties, "color", val=color)
        if bold:
            word_element(run_properties, "b")
    return styles


def xml_bytes(root):
    return ET.tostring(root, encoding="utf-8", xml_declaration=True)


def main():
    lines = SOURCE.read_text(encoding="utf-8").splitlines()
    document = ET.Element(f"{{{WORD}}}document")
    body = word_element(document, "body")
    diagrams = [
        [
            ["Architecture flow", "Relationship"],
            ["Source and planned imports", "WordPress reference -> discovery/CSV extracts -> Migrate mappings -> Drupal content storage (imports pending)"],
            ["Current content population", "PHP page-building scripts -> marketing nodes and Paragraphs; install/update hooks -> content model and configuration"],
            ["Visitor presentation", "Drupal content -> ordered Paragraph sections -> dynetx preprocessing/Twig -> Bootstrap5/CSS/JavaScript/assets -> browser"],
            ["Editorial access", "Drupal editor/administrator interface -> content storage"],
        ],
        [
            ["Local runtime flow", "Relationship"],
            ["Web request", "Browser -> DDEV Traefik -> Apache-FPM -> PHP 8.3 / Drupal 11"],
            ["Persistence", "Drupal -> MariaDB 11.4 and theme/file storage"],
            ["Development email", "Drupal -> Mailpit; not production email delivery"],
        ],
    ]
    diagram_index = 0
    line_index = 0
    while line_index < len(lines):
        line = lines[line_index].strip()
        if not line:
            line_index += 1
            continue
        if line == "```mermaid":
            add_paragraph(body, "Architecture flow (text equivalent of the Markdown diagram)")
            add_table(body, diagrams[diagram_index])
            diagram_index += 1
            line_index += 1
            while line_index < len(lines) and lines[line_index].strip() != "```":
                line_index += 1
        elif line.startswith("|"):
            rows = []
            while line_index < len(lines) and lines[line_index].strip().startswith("|"):
                row = [value.strip() for value in lines[line_index].strip().strip("|").split("|")]
                if not all(re.fullmatch(r":?-{3,}:?", value) for value in row):
                    rows.append(row)
                line_index += 1
            add_table(body, rows)
            continue
        elif line.startswith("#"):
            level = len(line) - len(line.lstrip("#"))
            text = line[level:].strip()
            if line_index == 0:
                style = "Title"
            elif line_index == 1:
                style = "Subtitle"
            else:
                style = f"Heading{min(level - 1, 3)}"
            add_paragraph(body, text, style)
        else:
            add_paragraph(body, line)
        line_index += 1
    assert diagram_index == sum(line.strip() == "```mermaid" for line in lines), "Diagram export incomplete"
    section = word_element(body, "sectPr")
    word_element(section, "pgSz", w=12240, h=15840)
    word_element(section, "pgMar", top=1080, right=1080, bottom=1080, left=1080)
    content_types = ET.Element(f"{{{CONTENT_TYPES}}}Types")
    ET.SubElement(content_types, f"{{{CONTENT_TYPES}}}Default", Extension="rels", ContentType="application/vnd.openxmlformats-package.relationships+xml")
    ET.SubElement(content_types, f"{{{CONTENT_TYPES}}}Default", Extension="xml", ContentType="application/xml")
    for part, content_type in [
        ("/word/document.xml", "application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"),
        ("/word/styles.xml", "application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"),
    ]:
        ET.SubElement(content_types, f"{{{CONTENT_TYPES}}}Override", PartName=part, ContentType=content_type)
    root_relationships = ET.Element(f"{{{RELATIONSHIPS}}}Relationships")
    ET.SubElement(root_relationships, f"{{{RELATIONSHIPS}}}Relationship", Id="rId1", Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument", Target="word/document.xml")
    document_relationships = ET.Element(f"{{{RELATIONSHIPS}}}Relationships")
    ET.SubElement(document_relationships, f"{{{RELATIONSHIPS}}}Relationship", Id="rId1", Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles", Target="styles.xml")
    with ZipFile(OUTPUT, "w", ZIP_DEFLATED) as package:
        for name, root in [
            ("[Content_Types].xml", content_types),
            ("_rels/.rels", root_relationships),
            ("word/document.xml", document),
            ("word/styles.xml", styles_document()),
            ("word/_rels/document.xml.rels", document_relationships),
        ]:
            package.writestr(name, xml_bytes(root))
    with ZipFile(OUTPUT) as package:
        assert package.testzip() is None, "Invalid DOCX ZIP package"
        for name in package.namelist():
            ET.fromstring(package.read(name))
        content = ET.fromstring(package.read("word/document.xml"))
        text = " ".join(content.itertext())
        for required in [line[3:].strip() for line in lines if line.startswith("## ")]:
            assert required in text, f"Missing exported report content: {required}"
    print(f"PASS: Exported and validated {OUTPUT.name}")
    print(f"Paragraphs: {len(document.findall('.//' + '{' + WORD + '}p'))}; tables: {len(document.findall('.//' + '{' + WORD + '}tbl'))}")


if __name__ == "__main__":
    main()