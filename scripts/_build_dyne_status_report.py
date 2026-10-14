from html import escape
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

OUTPUT = Path('/home/ubuntu/dyne-tx/reports/Dyne-Drupal-Implementation-Status-2026-10-01.docx')
W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
XML = 'http://www.w3.org/XML/1998/namespace'


def run(text, bold=False, color=None, size=None):
    props = []
    if bold:
        props.append('<w:b/>')
    if color:
        props.append(f'<w:color w:val="{color}"/>')
    if size:
        props.append(f'<w:sz w:val="{size}"/><w:szCs w:val="{size}"/>')
    rpr = f'<w:rPr>{"".join(props)}</w:rPr>' if props else ''
    return f'<w:r>{rpr}<w:t xml:space="preserve">{escape(str(text))}</w:t></w:r>'


def para(text='', style='Normal', bold=False, after=100):
    ppr = f'<w:pPr><w:pStyle w:val="{style}"/><w:spacing w:after="{after}"/></w:pPr>'
    return f'<w:p>{ppr}{run(text, bold=bold)}</w:p>'


def cell(text, width, header=False):
    fill = '<w:shd w:fill="17324D"/>' if header else ''
    tcpr = f'<w:tcPr><w:tcW w:w="{width}" w:type="dxa"/>{fill}<w:vAlign w:val="center"/></w:tcPr>'
    ppr = '<w:pPr><w:spacing w:after="40"/></w:pPr>'
    return f'<w:tc>{tcpr}<w:p>{ppr}{run(text, bold=header, color="FFFFFF" if header else None, size=19 if header else 18)}</w:p></w:tc>'


def table(headers, rows, widths):
    borders = '<w:tblBorders><w:top w:val="single" w:sz="5" w:color="B8C4CE"/><w:left w:val="single" w:sz="5" w:color="B8C4CE"/><w:bottom w:val="single" w:sz="5" w:color="B8C4CE"/><w:right w:val="single" w:sz="5" w:color="B8C4CE"/><w:insideH w:val="single" w:sz="4" w:color="D7DEE5"/><w:insideV w:val="single" w:sz="4" w:color="D7DEE5"/></w:tblBorders>'
    tblpr = f'<w:tblPr><w:tblW w:w="9360" w:type="dxa"/><w:tblLayout w:type="fixed"/>{borders}</w:tblPr>'
    grid = '<w:tblGrid>' + ''.join(f'<w:gridCol w:w="{width}"/>' for width in widths) + '</w:tblGrid>'
    all_rows = [headers] + rows
    row_xml = []
    for row_index, row in enumerate(all_rows):
        is_header = row_index == 0
        row_xml.append('<w:tr>' + ''.join(cell(value, widths[index], is_header) for index, value in enumerate(row)) + '</w:tr>')
    return f'<w:tbl>{tblpr}{grid}{"".join(row_xml)}</w:tbl>'


parts = []
parts.append(para('Local Drupal Implementation', 'Title', after=70))
parts.append(para('Current statistics, pending work, and recommended delivery approach', 'Subtitle', after=80))
parts.append(para('Prepared for management discussion | Snapshot date: October 1, 2026', 'Normal', after=40))
parts.append(para('Source: WordPress at dyne-tx.com | Target: local Drupal at dyne-tx.ddev.site', 'Normal', after=220))

parts.append(para('Executive Summary', 'Heading1'))
parts.append(para('The local Drupal site is a functioning page-composition prototype, not a completed or repeatable migration. The current runtime contains 22 component-built pages and 100 Paragraphs, but all four configured migrations have imported zero rows. Important menu destinations return 404, uploaded files and redirects are absent, and migration mappings do not consistently match the installed schema.'))
parts.append(para('Recommendation: retain Paragraphs for curated marketing-page composition, use structured Drupal content entities for reusable records, reconcile schema and mappings before running imports, and make a verified full content/URL/media comparison an acceptance gate.'))

parts.append(para('Verified Statistics', 'Heading1'))
stat_rows = [
    ['Drupal runtime', 'Drupal 11.4.6; PHP 8.3.30; database connected', 'Local site is running'],
    ['Published content', '22 nodes; all are dyne_page', 'No job, leader, perspective, news, or pipeline-program nodes'],
    ['Page components', '100 Paragraph entities', 'Pages are assembled from component sections'],
    ['Content model', '6 intended node bundles; 1 populated', 'The populated bundle is dyne_page'],
    ['Migration execution', '4 migrations; 0 imported; 48 unprocessed rows', 'All migrations remain Idle'],
    ['Sample CSV scope', '22 pages; 8 jobs; 11 leaders; 7 perspectives', 'Samples total 48 rows; not a full source inventory'],
    ['Taxonomy', '24 terms', 'Supporting terms are present'],
    ['Aliases and redirects', '30 aliases; 0 redirects', 'URL-map CSV is not imported as redirects'],
    ['Managed files', '0 file entities', 'Theme assets exist, but uploaded media is not migrated'],
]
parts.append(table(['Measure', 'Verified current state', 'Meaning'], stat_rows, [2050, 3650, 3660]))
parts.append(para('Statistics were rechecked against the active DDEV database and Drush migration status on October 1, 2026. The 48 unprocessed rows are the totals reported by the four registered migration sources, not a count of all WordPress content.', 'Caption', after=180))

parts.append(para('What Works Today', 'Heading1'))
for text in [
    'The local DDEV Drupal runtime and custom dynetx theme are active.',
    'The home, about, FORCE platform, pipeline, clinical trials, disease overview, careers/culture, perspectives, and publications routes render.',
    'The site has Paragraph-based page composition, taxonomy vocabularies, a contact Webform at /form/contact, and static theme assets.',
    'The current 22-page inventory is represented by component-built dyne_page nodes rather than imported standard page nodes.',
]:
    parts.append(para('- ' + text))

parts.append(para('Pending Work and Risks', 'Heading1'))
work_rows = [
    ['P0 - Migration blocker', 'dyne_pages.yml targets the page bundle, but node.type.page is absent. Current content uses dyne_page. The page migration also maps the title as body content.'],
    ['P0 - Schema alignment', 'Jobs mapping targets field_department and field_location; installed schema has text variants instead. Other planned fields are absent. Imports have not been run.'],
    ['P1 - Missing content', 'No reusable job, leader, perspective, news, or pipeline-program records are populated. There are no dedicated news or pipeline migrations.'],
    ['P1 - Broken routes', 'Checked menu destinations including /jobs, /news, /leadership, /about-us/contact-us, /physicians, /patients, and /benefits return 404. Contact only works at /form/contact.'],
    ['P1 - URLs and media', 'There are no redirect records or managed files. The URL-map CSV is only a sample and has no corresponding import.'],
    ['P1 - Reproducibility', 'Drush config status reports key custom configuration as Only in DB; it is not fully exported as deployable configuration.'],
    ['P2 - Content quality', 'Visible text contains encoding damage (for example, Ả in dates), missing spaces, blank pipeline links, and duplicate H1 headings.'],
    ['P2 - Editorial maintenance', 'News and pipeline rows are hard-coded in JSON/PHP. Re-running the page builder clears and reconstructs Paragraphs, risking loss of editor changes.'],
]
parts.append(table(['Priority', 'Finding'], work_rows, [2050, 7310]))

parts.append(para('Recommended Delivery Approach', 'Heading1'))
phases = [
    ('1. Stabilize the model', 'Choose dyne_page as the canonical marketing-page bundle or deliberately migrate to another bundle. Export the active configuration. Define fields and bundles for jobs, leaders, perspectives, news, and pipeline programs, then make migration destinations and field names match exactly.'),
    ('2. Build complete source extracts', 'Replace sample CSVs with complete, approved exports. Include full body/summary content, taxonomies, dates, source identifiers, canonical paths, files/media references, and required relationships. Record agreed source totals before import.'),
    ('3. Make content reusable', 'Keep Paragraphs for page-specific composition. Represent jobs, people, stories, news, and pipeline programs as structured content entities; connect page sections to those entities rather than embedding key records in JSON blobs.'),
    ('4. Migrate and reconcile', 'Run migrations in dependency order in a staging copy. Review migration messages, imported/unprocessed counts, field completeness, aliases, redirects, and file usage. Make reruns idempotent and avoid overwriting editorial changes.'),
    ('5. Validate and release', 'Test every menu and source URL, representative content on desktop/mobile, form submission, media rendering, accessibility basics, metadata, and sitemap behavior. Obtain content-owner sign-off before production cutover.'),
]
for heading, text in phases:
    parts.append(para(heading, 'Heading2', after=50))
    parts.append(para(text, after=130))

parts.append(para('Acceptance Criteria', 'Heading1'))
for text in [
    'All agreed WordPress content totals reconcile with imported Drupal records; migration status has no unexplained unprocessed rows or errors.',
    'All primary navigation destinations return the expected page; legacy URLs redirect to the approved canonical route.',
    'Required images/files are managed by Drupal and render correctly; no blank or broken links remain in key journeys.',
    'Editors can update reusable records and compose pages without changing migration scripts or hard-coded JSON.',
    'A clean local/staging rebuild from repository configuration reproduces the approved content model and theme.',
]:
    parts.append(para('- ' + text))

parts.append(para('Manager Discussion Summary', 'Heading1'))
parts.append(para('We have demonstrated the Drupal presentation approach: 22 pages are composed from Paragraphs and the local site is running. We have not yet completed the migration: all configured imports remain unrun, core content types are unpopulated, some migrations do not match the installed schema, and navigation, media, redirects, and data quality need work. The next milestone should be schema/mapping alignment followed by a complete staged import and measurable acceptance testing.'))

parts.append(para('Evidence and Scope', 'Heading1'))
parts.append(para('Verified using the local DDEV/Drush runtime, Drupal database counts, migration status, and public-route checks. Repository evidence includes web/modules/custom/dyne_migrate/README.md, dyne_migrate.install, migrations/*.yml, data/*.csv, scripts/create-pages.php, and the custom dynetx theme templates. This report does not claim exhaustive WordPress page-by-page parity, authenticated editor testing, form submission testing, or a complete responsive/accessibility audit.'))

body = ''.join(parts)
section = '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1080" w:right="1440" w:bottom="1080" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/></w:sectPr>'
document = f'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="{W}" xmlns:r="{R}"><w:body>{body}{section}</w:body></w:document>'
styles = f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="{W}">
<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/><w:sz w:val="20"/><w:color w:val="263746"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="100" w:line="276" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>
<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>
<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:before="100" w:after="80"/></w:pPr><w:rPr><w:rFonts w:ascii="Aptos Display" w:hAnsi="Aptos Display"/><w:b/><w:color w:val="17324D"/><w:sz w:val="40"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:basedOn w:val="Normal"/><w:rPr><w:color w:val="39708B"/><w:sz w:val="24"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="280" w:after="100"/></w:pPr><w:rPr><w:b/><w:color w:val="17324D"/><w:sz w:val="28"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="140" w:after="50"/></w:pPr><w:rPr><w:b/><w:color w:val="39708B"/><w:sz w:val="22"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Caption"><w:name w:val="caption"/><w:basedOn w:val="Normal"/><w:rPr><w:i/><w:color w:val="5B6770"/><w:sz w:val="17"/></w:rPr></w:style>
</w:styles>'''
content_types = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>'''
root_rels = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>'''
doc_rels = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>'''

OUTPUT.parent.mkdir(parents=True, exist_ok=True)
with ZipFile(OUTPUT, 'w', ZIP_DEFLATED) as docx:
    docx.writestr('[Content_Types].xml', content_types)
    docx.writestr('_rels/.rels', root_rels)
    docx.writestr('word/document.xml', document)
    docx.writestr('word/styles.xml', styles)
    docx.writestr('word/_rels/document.xml.rels', doc_rels)
print(OUTPUT)
