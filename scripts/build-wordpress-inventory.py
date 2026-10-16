#!/usr/bin/env python3
"""Build a standalone, escaped WordPress inventory from local reconciliation reports.

The output belongs outside the Drupal document root: it lists source drafts,
private titles and revision excerpts. Source HTML is only rendered as plain text.
"""
from __future__ import annotations
import argparse
from collections import Counter
from datetime import datetime
from html import escape, unescape
from html.parser import HTMLParser
import json
from pathlib import Path
import re
from urllib.parse import urlsplit
from zoneinfo import ZoneInfo

ROOT = Path(__file__).resolve().parents[1]
AUDIT = ROOT / 'output/content-audit'
CATEGORY_LABELS = {
    'migrated_public_content': 'Original public content',
    'unmigrated_editorial': 'Additional editorial content',
    'unpublished_editorial': 'Draft / private editorial content',
    'legacy_unrelated_feed': 'Historical external news feed',
    'embedded_public_content': 'Content embedded in pages',
    'media': 'Media attachment',
    'navigation': 'Navigation item',
    'revision_history': 'Historical revision',
    'plugin_configuration': 'Plugin / field configuration',
    'operational_log': 'Operational / accessibility log',
    'legacy_redirect': 'Legacy redirect configuration',
}
ADDITIONAL_LABELS = {
    'cached_social_post': 'Cached social posts',
    'legacy_map': 'Historical maps',
    'legacy_map_marker': 'Historical map markers',
    'legacy_qr_definition': 'Historical QR definitions',
    'historical_footer_link': 'Historical footer links',
    'historical_social_link': 'Historical social links',
    'historical_widget': 'Historical widget copy',
}

class PlainText(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.parts = []
        self.hidden = 0
    def handle_starttag(self, tag, attrs):
        if tag in {'script', 'style'}:
            self.hidden += 1
        elif tag in {'p', 'div', 'li', 'br', 'h1', 'h2', 'h3', 'h4', 'tr'}:
            self.parts.append(' ')
    def handle_endtag(self, tag):
        if tag in {'script', 'style'}:
            self.hidden = max(0, self.hidden - 1)
        elif tag in {'p', 'div', 'li', 'h1', 'h2', 'h3', 'h4'}:
            self.parts.append(' ')
    def handle_data(self, data):
        if not self.hidden:
            self.parts.append(data)


def plain(value, limit=None):
    p = PlainText()
    p.feed(str(value or ''))
    # Flush trailing text containing a literal ampersand (for example R&D or a
    # query string). HTMLParser otherwise buffers it as a possible entity.
    p.close()
    result = re.sub(r'\s+', ' ', ''.join(p.parts)).strip()
    return result[:limit].rstrip() + ('…' if limit and len(result) > limit else '') if limit else result


def read(path, fallback=None):
    return json.loads(path.read_text()) if path.is_file() else (fallback if fallback is not None else {})


def by_id(values):
    if isinstance(values, dict):
        return {str(k): v for k, v in values.items()}
    return {str(x.get('source_id', x.get('id', x.get('attachment_id')))): x for x in values or []}


def public_url(value):
    """Permit inert public HTTP(S) links; reject credentials and active schemes."""
    value = str(value or '').strip()
    try:
        parsed = urlsplit(value)
        return value if parsed.scheme in {'http', 'https'} and parsed.hostname and not parsed.username and not parsed.password else ''
    except ValueError:
        return ''


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--source', type=Path, default=AUDIT/'wordpress-records.json')
    parser.add_argument('--media', type=Path, default=AUDIT/'wordpress-media.json')
    parser.add_argument('--completion', type=Path, default=AUDIT/'content-completion.json')
    parser.add_argument('--coverage', type=Path, default=AUDIT/'drupal-coverage-before.json')
    parser.add_argument('--output', type=Path, default=ROOT/'output/wordpress-content-inventory.html')
    args = parser.parse_args()
    if args.output.resolve().is_relative_to((ROOT/'web').resolve()):
        parser.error('The inventory includes private/draft source details and must remain outside web/.')
    source = read(args.source)
    if not source.get('records'):
        parser.error('The WordPress source inventory is missing or empty.')
    media = read(args.media)
    media_summary = media.get('summary', {})
    theme_content = read(AUDIT/'theme-content.json').get('records', [])
    additional_source = read(ROOT/'content-archive/wordpress-additional-content.json')
    additional_rows = []
    for item in additional_source.get('records', []):
        additional_rows.append({
            'id': str(item.get('id', item.get('source_id', ''))),
            'title': plain(item.get('title', item.get('name', ''))) or 'Archived source record',
            'category': ADDITIONAL_LABELS.get(item.get('type'), plain(item.get('category', item.get('type', 'Additional archive content')))),
            'table': plain(item.get('source_table', item.get('table', ''))),
            'date': plain(item.get('date', item.get('created', ''))),
            'modified': plain(item.get('modified', '')),
            'text': plain(item.get('text', item.get('content', item.get('body', '')))),
            'permalink': public_url(item.get('source_url', item.get('permalink', item.get('public_url', item.get('url', ''))))),
            'sourceTarget': plain(item.get('source_url', '')),
            'fields': json.dumps(item.get('fields', {}), ensure_ascii=False, indent=2),
            'disposition': plain(item.get('disposition', 'Preserved in the local archive; not published as an active Drupal feed or feature.')),
        })
    completion = read(args.completion)
    coverage = read(args.coverage)
    legacy_audit = read(AUDIT/'wordpress-audit.json')
    fixture = read(ROOT/'web/modules/custom/dyne_migrate/data/legacy-content.json')
    originals = {str(x['source_id']): x for x in coverage.get('records', [])}
    new_records = by_id(completion.get('records', {}))
    media_records = by_id(media.get('attachments', media.get('records', [])))
    old_media = by_id(legacy_audit.get('attachments', []))
    managed_media = by_id(completion.get('media', completion.get('attachments', {})))
    fixture_menu = by_id(fixture.get('menus', []))
    menu_lookup = {}
    for source_id, menu in fixture_menu.items():
        expected = menu['url']
        expected = 'internal:' + expected if expected.startswith('/') else expected
        hits = [m for m in coverage.get('menus', []) if m['enabled'] and m['menu'] == 'main' and m['uri'] == expected and plain(m['title']).casefold() == plain(menu['title']).casefold()]
        if len(hits) == 1:
            menu_lookup[source_id] = hits[0]

    rows = []
    media_rows = []
    for source_record in source['records']:
        r = source_record
        sid = str(r['id'])
        category = r['classification']
        original = originals.get(sid)
        supplement = new_records.get(sid)
        m = media_records.get(sid, old_media.get(sid, {}))
        managed = managed_media.get(sid, {})
        state = 'Pending'
        disposition = plain(r.get('disposition', ''))
        destination = ''
        nid = None
        alias = ''
        published = None
        if supplement:
            disposition = plain(supplement.get('disposition', disposition))
            nid = supplement.get('nid')
            alias = supplement.get('alias') or ''
            published = supplement.get('published')
            if nid is not None:
                state = 'Drupal node'
                destination = f"Node {nid} · {supplement.get('bundle', '')} · {'published' if published else 'unpublished'}"
            elif supplement.get('menu_link_id'):
                state = 'Drupal navigation'
                destination = f"Archived mobile menu · link {supplement['menu_link_id']} · disabled"
            elif supplement.get('preserved', False) or supplement.get('complete', False):
                state = 'Preserved'
                destination = plain(supplement.get('destination', supplement.get('archive_path', '')))
        elif original and not original.get('issues') and original.get('nid'):
            state = 'Drupal node'
            nid = original['nid']
            alias = original['alias']
            published = original['published']
            destination = f"Node {nid} · {original['bundle']} · {'published' if published else 'unpublished'}"
            disposition = 'Verified against the original migration fixture: title, body, fields, components, alias and source hash match.'
        elif category == 'navigation' and sid in menu_lookup:
            state = 'Drupal navigation'
            destination = f"Main menu · link {menu_lookup[sid]['id']}"
            disposition = 'Source primary-navigation item is present as an enabled Drupal main-menu link.'
        elif category == 'embedded_public_content':
            # These were explicitly expanded by the public fixture, not omitted.
            target = '/privacy-policy' if r['type'] == 'wb_privacy_policy' else '/careers-culture'
            node = next((n for n in originals.values() if n['alias'] == target and not n['issues']), None)
            if node:
                state = 'Embedded component'
                destination = f"Page {target} · node {node['nid']}"
                alias = target
        if category == 'media':
            path = m.get('relative_path', m.get('path', m.get('source_path', '')))
            uri = managed.get('uri', m.get('local_uri', m.get('drupal_uri', m.get('uri', ''))))
            fid = managed.get('fid', m.get('fid'))
            local = m.get('local', {}).get('exists', m.get('local_exists', m.get('drupal_file_exists', m.get('destination_exists'))))
            exact = m.get('hash_match', m.get('sha256_match', m.get('exact_hash', m.get('exact_size'))))
            source_info = m.get('source', {})
            local_info = m.get('local', {})
            if source_info.get('sha256') and local_info.get('sha256'):
                exact = source_info['sha256'] == local_info['sha256']
            if path and not Path(str(path)).is_absolute() and '..' not in Path(str(path)).parts:
                local_path = ROOT/'web/sites/default/files/legacy'/path
                local = local_path.is_file()
                if local and not uri:
                    uri = 'public://legacy/' + path
            if fid:
                state = 'Managed media'
                destination = f"Managed file {fid} · {uri}"
                disposition = plain(managed.get('disposition', 'Original WordPress attachment registered as a permanent Drupal managed file.'))
            elif local:
                state = 'Local file'
                destination = str(uri or path)
                disposition = 'File exists in Drupal uploads. Managed attachment registration is pending or not recorded.'
            else:
                state = 'Missing file'
                destination = str(path or r.get('asset_url', ''))
                disposition = plain(m.get('reason', m.get('disposition', 'Missing from the supplied archive and Drupal uploads; attempted public-origin recovery did not return the original file.')))
            media_rows.append({'id':r['id'], 'title':plain(r['title']), 'mime':r['mime'], 'path':str(path), 'bytes':local_info.get('size', source_info.get('size', m.get('bytes',m.get('size',0)))), 'source':source_info.get('exists',m.get('source_exists',m.get('archive_exists'))), 'local':bool(local), 'verified':exact, 'fid':fid, 'state':state, 'destination':destination, 'disposition':disposition})
        if state == 'Pending' and category in {'plugin_configuration','operational_log'}:
            state = 'Inventory only'
            disposition += ' Listed for complete source accounting; this is not an authored Drupal content page.'
        if state == 'Pending' and category == 'revision_history' and completion.get('archive_path'):
            state = 'Preserved'
            destination = str(completion['archive_path'])
            disposition = 'Historical revision retained in the local preservation archive outside the public document root; parent ID links it to its source record.'
        if state == 'Pending' and category == 'legacy_redirect':
            state = 'Inventory only'
        if sid == '1124' and nid is not None:
            disposition += ' The source video embed is restored. The video provider currently requires sign-in; unauthenticated playback is not verified.'
        text = plain(r.get('excerpt') or r.get('content'), 700)
        if not text and r.get('content_suppressed'):
            text = 'Operational or configuration payload intentionally not rendered in this inventory.'
        elif not text:
            text = 'No text in the WordPress post body. Content may come from template code, custom fields, a media file or another referenced record.'
        terms = ', '.join(plain(t.get('name', t.get('term',{}).get('name',''))) for t in r.get('terms', []))
        rows.append({'id':r['id'],'title':plain(r.get('title')) or '(untitled)', 'type':r['type'],'status':r['status'], 'category':CATEGORY_LABELS.get(category,category), 'category_key':category,'slug':r.get('slug',''),'parent':r.get('parent',0),'date':r.get('date',''),'modified':r.get('modified',''),'state':state,'disposition':disposition,'destination':destination,'nid':nid,'alias':alias,'published':published,'excerpt':text,'body_chars':r.get('source_content_characters',0),'terms':terms,'protected':bool(r.get('password_protected'))})
    assert len(rows) == source['total_records'] == len({r['id'] for r in rows}), 'Incomplete or duplicated WordPress inventory'
    type_counts = Counter((r['type'],r['status']) for r in rows)
    category_counts = Counter(r['category'] for r in rows)
    state_counts = Counter(r['state'] for r in rows)
    nodes = {str(r['nid']) for r in rows if r['nid'] is not None}
    supplements = sum(1 for sid,r in new_records.items() if r.get('nid') and sid not in originals)
    upload_files = media.get('source_upload_inventory', media.get('upload_files', legacy_audit.get('upload_files', [])))
    if isinstance(upload_files, dict): upload_files=list(upload_files.values())
    physical_count = media_summary.get('source_upload_files') or len(upload_files) or media.get('upload_count') or media.get('total_files') or 0
    local_uploads = 0
    upload_bytes = 0
    for f in upload_files:
        path = f.get('path', f.get('relative_path',''))
        if path and not Path(str(path)).is_absolute() and '..' not in Path(str(path)).parts:
            local_uploads += (ROOT/'web/sites/default/files/legacy'/path).is_file()
        upload_bytes += int(f.get('source',{}).get('size',f.get('bytes',f.get('size',0))) or 0)
    problems = [r for r in rows if r['state'] in {'Pending','Missing file'}]
    now = datetime.now(ZoneInfo('Asia/Kolkata')).strftime('%d %B %Y, %H:%M IST')
    data = {'rows':rows,'media':media_rows,'additionalRows':additional_rows,'additionalNotes':[plain(v) for v in additional_source.get('privacy_notes', [])],'taxonomies':source.get('taxonomies',[]),'themeContent':[{**{k:plain(v) for k,v in r.items() if k!='content'},'excerpt':plain(r.get('content'),700)} for r in theme_content],'typeCounts':[{'type':t,'status':s,'count':n} for (t,s),n in sorted(type_counts.items())], 'categoryCounts':dict(sorted(category_counts.items())), 'stateCounts':dict(sorted(state_counts.items())), 'summary':{'source':len(rows),'originalNodes':len(originals),'newNodes':supplements,'mappedNodes':len(nodes),'reportedNodeTotal':completion.get('node_total',len(nodes)),'attachments':len(media_rows),'managedAttachments':sum(bool(m['fid']) for m in media_rows),'localAttachments':sum(m['local'] for m in media_rows),'uploadFiles':physical_count,'localUploadFiles':local_uploads,'safeUploadFiles':media_summary.get('safe_source_upload_files',physical_count),'uploadBytes':upload_bytes,'uploadOriginals':media_summary.get('safe_source_attachment_original_files',0),'uploadDerivatives':media_summary.get('safe_source_attachment_derivative_files',0),'additionalUploads':media_summary.get('safe_source_additional_upload_files',0),'excludedUploads':media_summary.get('excluded_upload_files',0),'totalLocalFiles':media_summary.get('local_upload_files',local_uploads),'totalLocalBytes':media_summary.get('local_upload_bytes',0),'priorPublicMediaExpected':media_summary.get('previously_recovered_public_media_expected',0),'priorPublicMediaVerified':media_summary.get('previously_recovered_public_media_verified',0),'additionalArchiveRecords':len(additional_rows),'additionalArchiveCounts':dict(Counter(r['category'] for r in additional_rows)),'revisionCount':sum(r['type']=='revision' for r in rows),'taxonomies':len(source.get('taxonomies',[])),'pending':len(problems),'pendingMappings':sum(r['state']=='Pending' for r in rows),'missingFiles':sum(r['state']=='Missing file' for r in rows),'complete':bool(completion.get('complete'))}, 'generated':now, 'sourceSha256':source.get('source_sha256',''), 'sourcePath':source.get('source',''), 'archivePath':completion.get('archive_path',''), 'privacyNotes':source.get('privacy_notes',[]), 'completionPresent':args.completion.is_file()}
    # Escape every HTML-sensitive character in the JSON script body. Rendering
    # below uses textContent, not innerHTML; archived markup is never executed.
    encoded=json.dumps(data,ensure_ascii=False,separators=(',',':')).replace('&','\\u0026').replace('<','\\u003c').replace('>','\\u003e').replace('\u2028','\\u2028').replace('\u2029','\\u2029')
    document=TEMPLATE.replace('__DATA__',encoded).replace('__GENERATED__',escape(now))
    args.output.parent.mkdir(parents=True,exist_ok=True)
    args.output.write_text(document)
    (AUDIT/'html-inventory-summary.json').write_text(json.dumps(data['summary']|{'state_counts':dict(state_counts),'generated':now,'html':str(args.output)},indent=2))
    print(json.dumps({'output':str(args.output),'bytes':args.output.stat().st_size,**data['summary']},indent=2))


TEMPLATE=r'''<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><link rel="icon" href="data:,"><title>Dyne Therapeutics · WordPress content inventory</title>
<style>
:root{--navy:#142840;--teal:#007b83;--pale:#e8f5f3;--text:#20354a;--muted:#5b6e80;--line:#d8e1e8;--bg:#f4f7fa;--orange:#9a4a00}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:15px/1.5 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}a{color:#006d80;text-underline-offset:3px}button,input,select{font:inherit}button{cursor:pointer}button:disabled{cursor:default;opacity:.45}button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible,summary:focus-visible{outline:3px solid #e3a321;outline-offset:3px}.skip{position:absolute;left:-999px}.skip:focus{left:16px;top:10px;background:white;padding:10px;z-index:3}.wrap{max-width:1440px;margin:auto;padding:0 32px}header{background:var(--navy);color:white;padding:42px 0 34px;border-bottom:5px solid #19b1af}.eyebrow{text-transform:uppercase;font-size:12px;font-weight:750;letter-spacing:.16em;color:#9ee3df}h1{font-size:clamp(28px,3vw,44px);line-height:1.12;margin:12px 0}header p{max-width:870px;color:#d5e3ed;margin:12px 0}.meta{font-size:12px;color:#c1d2df}.header-actions{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}.print{border:1px solid #728ea4;color:white;background:none;padding:9px 15px;border-radius:6px;white-space:nowrap}main{padding-top:26px;padding-bottom:50px}.cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:0 0 22px}.card{background:white;border:1px solid var(--line);border-radius:9px;padding:18px 20px}.card strong{font-size:33px;line-height:1.2;display:block;color:var(--navy);font-weight:750}.card span{display:block;font-size:13px;color:var(--muted);margin-top:5px}.notice{border-left:4px solid var(--teal);background:var(--pale);padding:15px 19px;border-radius:0 7px 7px 0;margin:16px 0}.notice.pending{border-left-color:#c47617;background:#fff4e1}.notice p{margin:0}.notice p+p{margin-top:7px}.section-title{font-size:20px;margin:0 0 12px}.explain{margin-bottom:24px;padding:21px;background:white;border:1px solid var(--line);border-radius:9px}.explain p{margin:7px 0;color:var(--muted)}.accounting{display:grid;grid-template-columns:1fr 1fr;gap:24px}.count-list{margin:10px 0 0;padding:0;list-style:none}.count-list li{display:flex;justify-content:space-between;gap:16px;padding:5px 0;border-bottom:1px solid #edf1f4;font-size:13px}.count-list strong{font-variant-numeric:tabular-nums}details>summary{cursor:pointer;font-weight:650}.tabs{display:flex;gap:6px;flex-wrap:wrap;border-bottom:1px solid var(--line);margin-bottom:18px}.tab{background:none;border:0;border-bottom:3px solid transparent;padding:12px 16px;font-weight:650;color:var(--muted)}.tab[aria-selected=true]{color:var(--teal);border-bottom-color:var(--teal)}[hidden]{display:none!important}.filters{display:grid;grid-template-columns:minmax(240px,2fr) repeat(4,minmax(120px,1fr));gap:12px;align-items:end}.filter label{display:block;font-size:12px;font-weight:700;margin-bottom:5px}.filter input,.filter select{width:100%;background:white;border:1px solid #b7c7d3;border-radius:5px;min-height:42px;padding:8px;color:var(--text)}.table-toolbar{display:flex;justify-content:space-between;align-items:center;gap:15px;margin:16px 0;color:var(--muted);font-size:13px}.clear{border:0;background:none;color:var(--teal);text-decoration:underline;padding:3px}.table-wrap{background:white;border:1px solid var(--line);border-radius:8px;overflow:auto}table{width:100%;border-collapse:collapse;table-layout:fixed}th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.06em;background:#edf3f7;padding:12px 14px;color:#425b71}td{padding:14px;vertical-align:top;border-top:1px solid #e1e8ed;font-size:13px;overflow-wrap:anywhere}tbody tr:nth-child(even){background:#fcfdfe}th:nth-child(1){width:8%}th:nth-child(2){width:31%}th:nth-child(3){width:15%}th:nth-child(4){width:32%}th:nth-child(5){width:14%}.record-title{font-size:14px;font-weight:700;line-height:1.4;display:block;margin-bottom:6px}.muted{color:var(--muted)}.small{font-size:12px}.pill{display:inline-block;font-size:11px;font-weight:650;line-height:1.4;padding:3px 7px;border-radius:4px;background:#edf1f5;color:#40566c;margin:0 4px 5px 0}.pill.ok{background:#e5f4ef;color:#19634e}.pill.warn{background:#fff1dc;color:#87500e}.cell-line{display:block;margin-top:4px}.excerpt{margin-top:9px}.excerpt summary{font-size:12px;font-weight:500;color:var(--teal)}.excerpt p{margin:7px 0;font-size:12px;color:#4d6070}.pager{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:18px 0}.pager-controls{display:flex;gap:8px;align-items:center}.pager button{padding:7px 13px;background:white;border:1px solid #b7c7d3;border-radius:5px;color:var(--text)}.pager select{padding:7px;background:white;border:1px solid #b7c7d3;border-radius:5px}.empty{padding:40px;text-align:center;color:var(--muted)}.source-block{font-family:ui-monospace,SFMono-Regular,monospace;font-size:12px;overflow-wrap:anywhere;white-space:pre-wrap;background:#f4f7fa;padding:12px;border-radius:6px}.notes-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}.notes-grid article{background:white;border:1px solid var(--line);padding:22px;border-radius:8px}.notes-grid h3{margin-top:0;font-size:17px}.notes-grid p,.notes-grid li{font-size:14px}.taxonomy-list{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:16px 0}.taxonomy-item{padding:10px 12px;background:white;border:1px solid var(--line);border-radius:5px}.table-compact th:nth-child(n){width:auto}.table-compact{margin-top:15px}.filepath{font-family:ui-monospace,monospace;font-size:11px}footer{border-top:1px solid var(--line);margin-top:30px;padding-top:18px;font-size:12px;color:var(--muted)}@media(max-width:1100px){.filters{grid-template-columns:repeat(3,1fr)}.filter:first-child{grid-column:span 2}.cards{grid-template-columns:repeat(2,1fr)}}@media(max-width:760px){.wrap{padding:0 16px}main.wrap{padding-top:20px}header{padding:28px 0}.header-actions{display:block}.print{margin-top:12px}.accounting,.notes-grid{grid-template-columns:1fr}.filters{grid-template-columns:1fr 1fr}.filter:first-child{grid-column:span 2}.cards{gap:9px}.card{padding:15px}.card strong{font-size:28px}.table-wrap{border:0;background:none;overflow:visible}table,tbody,tr,td{display:block}thead{display:none}tbody tr{background:white!important;border:1px solid var(--line);border-radius:7px;margin-bottom:12px;padding:9px}td{padding:7px;border:0;font-size:13px}td::before{content:attr(data-label);display:block;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);font-size:10px;margin-bottom:3px}.pager{align-items:flex-start;flex-direction:column}.taxonomy-list{grid-template-columns:1fr 1fr}.table-toolbar{align-items:flex-start}.tabs{gap:0}.tab{padding:11px;font-size:13px}}@media print{@page{size:landscape;margin:12mm}body{background:white;font-size:10px}.wrap{padding:0;max-width:none}header{padding:0 0 15px;background:white;color:var(--navy);border-bottom:2px solid var(--teal)}header p,.meta,.eyebrow{color:var(--muted)}h1{font-size:25px}.print,.tabs,.filters,.pager,.clear,.table-toolbar,details.excerpt{display:none!important}.cards{grid-template-columns:repeat(4,1fr);gap:8px;margin-top:15px}.card{padding:10px}.card strong{font-size:23px}.notice,.explain{padding:12px;break-inside:avoid}.accounting{grid-template-columns:1fr 1fr}.table-wrap{overflow:visible;border:0}table{font-size:10px}th,td{padding:7px;font-size:10px}tr{break-inside:avoid}.record-title{font-size:11px}a{color:inherit;text-decoration:none}.pill{font-size:9px}.notes-grid{display:block}.notes-grid article{margin:12px 0}footer{font-size:10px}}
</style></head><body>
<a class="skip" href="#inventory">Skip to content inventory</a>
<header><div class="wrap"><div class="header-actions"><div><div class="eyebrow">Dyne Therapeutics · Migration evidence</div><h1>WordPress content inventory</h1><p>A complete index of the records found in the legacy WordPress database, with each record’s Drupal destination or preservation status.</p><div class="meta">Generated __GENERATED__ · Local standalone report</div></div><button class="print" id="print-button" type="button">Print / save PDF</button></div></div></header>
<main class="wrap"><div class="cards" id="metrics"></div><div class="notice" id="completion-note"></div>
<section class="explain" aria-labelledby="why-title"><h2 class="section-title" id="why-title">Why the WordPress and Drupal counts differ</h2><p>WordPress stores pages, posts, attachments, menus, revisions, field definitions and plugin logs in one <code>wp_posts</code> table. Drupal lists authored pages under Content and stores files, menus and page components separately. Comparing those totals directly overstates the content gap.</p><div class="accounting"><div><h3 class="small">All WordPress records, by purpose</h3><ul class="count-list" id="category-counts"></ul></div><div><h3 class="small">Current reconciliation, by destination</h3><ul class="count-list" id="state-counts"></ul><p class="small" id="node-explanation"></p><p class="small">Drupal’s administrative Content list displays 50 rows per page by default. Use the pager and clear content-type/status filters to see the complete list.</p></div></div></section>
<div class="tabs" role="tablist" aria-label="Inventory sections"><button class="tab" type="button" role="tab" id="tab-records" aria-selected="true" aria-controls="panel-records" data-tab="records">All WordPress records</button><button class="tab" type="button" role="tab" id="tab-media" aria-selected="false" aria-controls="panel-media" tabindex="-1" data-tab="media">Media library</button><button class="tab" type="button" role="tab" id="tab-additional" aria-selected="false" aria-controls="panel-additional" tabindex="-1" data-tab="additional">Additional archive content</button><button class="tab" type="button" role="tab" id="tab-notes" aria-selected="false" aria-controls="panel-notes" tabindex="-1" data-tab="notes">Coverage & source notes</button></div>
<section id="panel-records" role="tabpanel" aria-labelledby="tab-records"><h2 id="inventory" class="section-title" tabindex="-1">Complete record list</h2><div class="filters"><div class="filter"><label for="search">Search title, ID, path, excerpt or disposition</label><input id="search" type="search" placeholder="Try Community Involvement, privacy or 2826" autocomplete="off"></div><div class="filter"><label for="type-filter">WordPress type</label><select id="type-filter"><option value="">All types</option></select></div><div class="filter"><label for="status-filter">WordPress status</label><select id="status-filter"><option value="">All statuses</option></select></div><div class="filter"><label for="category-filter">Record purpose</label><select id="category-filter"><option value="">All purposes</option></select></div><div class="filter"><label for="state-filter">Drupal / preservation</label><select id="state-filter"><option value="">All destinations</option></select></div></div><div class="table-toolbar"><span id="results-count" role="status" aria-live="polite"></span><button type="button" class="clear" id="clear-filters">Clear filters</button></div><div class="table-wrap"><table><caption class="skip">Every WordPress database record and its reconciliation status</caption><thead><tr><th>WP ID</th><th>Content / source</th><th>Type / status</th><th>Drupal destination / disposition</th><th>Source dates</th></tr></thead><tbody id="record-rows"></tbody></table><div class="empty" id="records-empty" hidden>No matching records. Clear a filter or try another search.</div></div><div class="pager"><div class="small muted" id="page-status"></div><div class="pager-controls"><label class="small" for="page-size">Rows</label><select id="page-size"><option>25</option><option selected>50</option><option>100</option></select><button type="button" id="previous">Previous</button><button type="button" id="next">Next</button></div></div></section>
<section id="panel-media" role="tabpanel" aria-labelledby="tab-media" hidden><h2 class="section-title">Media attachment inventory</h2><div class="notice"><p id="media-explanation"></p><p class="small">Attachment records identify original library items. Resized images, thumbnails, converted formats and other upload files are additional physical files, not extra editorial pages.</p></div><div class="filters"><div class="filter"><label for="media-search">Search attachment title, ID, MIME type or file path</label><input type="search" id="media-search" placeholder="Search media library" autocomplete="off"></div><div class="filter"><label for="media-state">Availability</label><select id="media-state"><option value="">All attachments</option><option value="managed">Registered managed files</option><option value="local">Local files</option><option value="missing">Missing local files</option></select></div></div><div class="table-toolbar"><span id="media-results" role="status" aria-live="polite"></span></div><div class="table-wrap"><table><caption class="skip">Original WordPress media attachment records</caption><thead><tr><th>WP ID</th><th>Media title / file</th><th>Format / size</th><th>Drupal destination</th><th>Availability</th></tr></thead><tbody id="media-rows"></tbody></table><div id="media-empty" class="empty" hidden>No matching media attachments.</div></div><div class="pager"><span id="media-page-status" class="small muted"></span><div class="pager-controls"><button type="button" id="media-previous">Previous</button><button type="button" id="media-next">Next</button></div></div></section>
<section id="panel-additional" role="tabpanel" aria-labelledby="tab-additional" hidden>
<h2 class="section-title">Additional archive content</h2>
<div class="notice"><p id="additional-explanation"></p><p class="small">These items come from separate WordPress plugin tables and selected public-content options. They are additional to the 1,747 <code>wp_posts</code> records, and do not change the 458 Drupal node count. Cached social posts are dated historical copies, not a live feed. Map and QR definitions that were unused on the current source website remain archive references.</p></div>
<ul id="additional-notes" class="small muted"></ul>
<div class="filters"><div class="filter"><label for="additional-search">Search archived text, source ID, title or permalink</label><input id="additional-search" type="search" placeholder="Search social posts, maps or historical copy" autocomplete="off"></div><div class="filter"><label for="additional-category">Archive category</label><select id="additional-category"><option value="">All additional content</option></select></div></div>
<div class="table-toolbar"><span id="additional-results" role="status" aria-live="polite"></span></div>
<div class="table-wrap"><table><caption class="skip">Authored content preserved from other WordPress database tables and public options</caption><thead><tr><th>Source ID</th><th>Archived content / text</th><th>Category / source</th><th>Disposition / public reference</th><th>Source dates</th></tr></thead><tbody id="additional-rows"></tbody></table><div class="empty" id="additional-empty" hidden>No matching additional archive content.</div></div>
<div class="pager"><span id="additional-page-status" class="small muted"></span><div class="pager-controls"><button type="button" id="additional-previous">Previous</button><button type="button" id="additional-next">Next</button></div></div>
</section>
<section id="panel-notes" role="tabpanel" aria-labelledby="tab-notes" hidden><h2 class="section-title">Coverage and source notes</h2><div class="notes-grid"><article><h3>What this report includes</h3><p>Every row of the source <code>wp_posts</code> table is listed once in the main inventory, including unpublished pages and historical records. The media tab is a second view of the same attachment rows.</p><p>Excerpts are plain-text previews, limited to 700 characters. Full preserved editorial content remains in Drupal or the local preservation dataset. A blank post body may be populated by theme templates or custom fields on the original site.</p><p>Source dates are WordPress database timestamps, reproduced without assuming a timezone. The report generation time is shown in India Standard Time.</p></article><article><h3>Private and historical material</h3><p>This file is stored outside Drupal’s public <code>web/</code> directory. It can include private titles, drafts and revision excerpts and is intended for local review.</p><ul id="privacy-notes"></ul><p>WordPress private/draft status is shown separately from Drupal publication status. Historical feed/test content is not evidence of a missing public page.</p><p id="archive-note"></p></article><article><h3>Verification boundaries</h3><p>The original 344 migrated nodes were checked against their source fixture, including aliases, bodies, custom fields, component values and source hashes. New coverage is drawn from the additive import’s completion report.</p><p>“Inventory only” identifies operational logs or WordPress implementation configuration. These are accounted for without recreating obsolete WordPress plugin behavior in Drupal. “Pending” or “Missing file” means the report has no completed destination evidence.</p><p>Primary navigation and embedded privacy/quote content can have Drupal menu or component destinations instead of standalone nodes.</p></article><article><h3>Source provenance</h3><p class="small">Legacy archive</p><div id="source-path" class="source-block"></div><p class="small">SQL source SHA-256</p><div id="source-sha" class="source-block"></div><p class="small">This report is self-contained. It loads no external libraries, fonts or scripts. Source markup is escaped and never inserted as executable HTML.</p></article></div><h3>Content written in theme templates</h3><p class="small muted">These source sections are separate from the 1,747 database records. Active sections are implemented in the Drupal theme; dormant banners remain historical references.</p><div id="theme-content-list" class="notes-grid"></div><h3>Source taxonomy terms</h3><p class="small muted">Categories and navigation/team groups are separate classification records, not additional <code>wp_posts</code> entries.</p><div class="taxonomy-list" id="taxonomy-list"></div><details><summary>Counts by original WordPress type and status</summary><div class="table-wrap"><table class="table-compact"><thead><tr><th>WordPress type</th><th>Source status</th><th>Records</th></tr></thead><tbody id="type-counts"></tbody></table></div></details></section>
<footer>Local migration inventory · Counts describe the supplied archive snapshot, not changes made later to the live website. The full dataset remains embedded in this file; filtering and pagination change only the visible rows.</footer></main>
<script type="application/json" id="inventory-data">__DATA__</script>
<script>
'use strict';
const D=JSON.parse(document.getElementById('inventory-data').textContent), $=id=>document.getElementById(id), S=D.summary;
const el=(tag,text,cls)=>{const n=document.createElement(tag);if(text!==undefined&&text!==null)n.textContent=String(text);if(cls)n.className=cls;return n;};
const addLine=(p,text,cls='cell-line')=>{if(text)p.append(el('span',text,cls));};
const number=n=>Number(n||0).toLocaleString('en-US');
const size=n=>n?((n>=1073741824?(n/1073741824).toFixed(2)+' GB':n>=1048576?(n/1048576).toFixed(1)+' MB':n>=1024?(n/1024).toFixed(1)+' KB':n+' B')):'—';
const good=new Set(['Drupal node','Drupal navigation','Embedded component','Managed media','Preserved']);
function pill(text,state){return el('span',text,'pill '+(good.has(state)?'ok':['Pending','Missing file','Local file'].includes(state)?'warn':''));}
function td(label){const n=el('td');n.dataset.label=label;return n;}
function link(alias,nid,published){const a=el('a',alias||'Open Drupal record');const path=alias||(nid?'/node/'+nid:'');if(!path.startsWith('/')||path.startsWith('//')||/[\r\n]/.test(path))return el('span',path);a.href='http://dyne-tx-master.ddev.site'+path;a.target='_blank';a.rel='noopener noreferrer';if(published===false)a.title='This unpublished record requires Drupal permission to view.';return a;}
[[S.source,'WordPress database records'],[S.reportedNodeTotal,'Mapped Drupal content nodes'],[S.attachments,'Original media attachment records'],[S.revisionCount,'Historical source revisions']].forEach(([n,label])=>{const card=el('div',null,'card');card.append(el('strong',number(n)),el('span',label));$('metrics').append(card);});
function countList(id,counts){Object.entries(counts).forEach(([label,count])=>{const li=el('li');li.append(el('span',label),el('strong',number(count)));$(id).append(li);});}
countList('category-counts',D.categoryCounts);countList('state-counts',D.stateCounts);
$('node-explanation').textContent=number(S.originalNodes)+' original migrated nodes + '+number(S.newNodes)+' additional node records mapped by the reconciliation report. Files, navigation, embedded components and source revision history are counted separately.';
const complete=S.complete&&S.pending===0;
$('completion-note').classList.toggle('pending',!complete);
const headline=complete?'Reconciliation recorded: every source record has a destination or an explicit preservation / non-content disposition.':D.completionPresent?(S.pendingMappings?number(S.pendingMappings)+' records still need destination evidence. ':'')+(S.missingFiles?number(S.missingFiles)+' original attachment files are missing from the supplied archive and could not be recovered from the public origin. Their records remain listed under Missing file.':'Reconciliation verification has not yet been marked complete.'):'Reconciliation in progress: the original migration is verified; additional imports and attachment registration are pending final evidence.';
$('completion-note').append(el('p',headline));
$('media-explanation').textContent=number(S.localAttachments)+' of '+number(S.attachments)+' source attachment files are present locally; '+number(S.managedAttachments)+' are registered as managed files. All '+number(S.localUploadFiles)+' verified local archive uploads are listed against '+number(S.safeUploadFiles)+' eligible files: '+number(S.uploadOriginals)+' original attachment files, '+number(S.uploadDerivatives)+' generated derivatives and '+number(S.additionalUploads)+' other media uploads. '+number(S.excludedUploads)+' WordPress cache/code/log files are excluded from the archive total of '+number(S.uploadFiles)+'. Drupal has '+number(S.totalLocalFiles)+' local media files ('+number(S.totalLocalBytes)+' bytes), including recovered public videos. '+number(S.priorPublicMediaVerified)+' of '+number(S.priorPublicMediaExpected)+' previously recovered public videos were reverified, alongside the newly recovered FORCE platform video.';
D.privacyNotes.forEach(n=>$('privacy-notes').append(el('li',n)));
$('archive-note').textContent=D.archivePath?'Preservation archive: '+D.archivePath:'The final archival destination has not yet been recorded in the completion report.';
$('source-path').textContent=D.sourcePath;$('source-sha').textContent=D.sourceSha256;
D.taxonomies.forEach(t=>{const x=el('div',null,'taxonomy-item');x.append(el('strong',t.name||'', 'small'));addLine(x,(t.taxonomy||'')+' · ID '+t.id,'muted small cell-line');$('taxonomy-list').append(x);});
D.themeContent.forEach(t=>{const x=el('article');x.append(el('h3',t.title),el('p',t.disposition),el('p',t.source_file,'filepath'));const detail=el('details',null,'excerpt');detail.append(el('summary','Source text excerpt'),el('p',t.excerpt));x.append(detail);$('theme-content-list').append(x);});
D.typeCounts.forEach(t=>{const tr=el('tr');['type','status','count'].forEach(k=>{const c=td(k);c.textContent=t[k];tr.append(c)});$('type-counts').append(tr);});
for(const [id,key] of [['type-filter','type'],['status-filter','status'],['category-filter','category'],['state-filter','state']]){[...new Set(D.rows.map(r=>r[key]))].sort().forEach(v=>{const o=el('option',v);o.value=v;$(id).append(o);});}
let page=0,mediaPage=0,additionalPage=0,printing=false,currentTab='records';
function filteredRecords(){const q=$('search').value.trim().toLowerCase();return D.rows.filter(r=>(!$('type-filter').value||r.type===$('type-filter').value)&&(!$('status-filter').value||r.status===$('status-filter').value)&&(!$('category-filter').value||r.category===$('category-filter').value)&&(!$('state-filter').value||r.state===$('state-filter').value)&&(!q||[r.id,r.title,r.slug,r.type,r.excerpt,r.disposition,r.destination,r.alias,r.terms].join(' ').toLowerCase().includes(q)));}
function renderRecords(){const all=filteredRecords(),per=Number($('page-size').value),pages=Math.max(1,Math.ceil(all.length/per));page=Math.min(page,pages-1);const visible=printing?all:all.slice(page*per,(page+1)*per);$('record-rows').replaceChildren();const fragment=document.createDocumentFragment();visible.forEach(r=>{const tr=el('tr'),id=td('WP ID'),content=td('Content / source'),kind=td('Type / status'),dest=td('Drupal destination / disposition'),dates=td('Source dates');id.append(el('strong','#'+r.id));if(r.parent)addLine(id,'Parent #'+r.parent,'small muted cell-line');content.append(el('span',r.title,'record-title'));addLine(content,r.slug,'small muted cell-line');content.append(pill(r.category));const details=el('details',null,'excerpt');details.append(el('summary','Source excerpt and details'),el('p',r.excerpt));if(r.terms)details.append(el('p','Source taxonomy: '+r.terms));details.append(el('p','Source body length: '+number(r.body_chars)+' characters.'));content.append(details);kind.append(el('strong',r.type));addLine(kind,r.status,'cell-line');if(r.protected)addLine(kind,'Password protected','small cell-line');dest.append(pill(r.state,r.state));addLine(dest,r.destination,'small cell-line');if(r.nid||r.alias){const line=el('div',null,'cell-line');line.append(link(r.alias,r.nid,r.published));dest.append(line);}addLine(dest,r.disposition,'small muted cell-line');addLine(dates,'Created '+(r.date||'—'),'small cell-line');addLine(dates,'Modified '+(r.modified||'—'),'small muted cell-line');tr.append(id,content,kind,dest,dates);fragment.append(tr);});$('record-rows').append(fragment);$('records-empty').hidden=all.length>0;$('results-count').textContent=number(all.length)+' of '+number(D.rows.length)+' records match';$('page-status').textContent='Page '+(page+1)+' of '+pages+' · '+(all.length?number(page*per+1):0)+'–'+number(Math.min((page+1)*per,all.length))+' shown';$('previous').disabled=page===0;$('next').disabled=page>=pages-1;}
function filteredMedia(){const q=$('media-search').value.trim().toLowerCase(),s=$('media-state').value;return D.media.filter(r=>(!q||[r.id,r.title,r.mime,r.path,r.destination].join(' ').toLowerCase().includes(q))&&(!s||(s==='managed'&&r.fid)||(s==='local'&&r.local)||(s==='missing'&&!r.local)));}
function renderMedia(){const all=filteredMedia(),per=50,pages=Math.max(1,Math.ceil(all.length/per));mediaPage=Math.min(mediaPage,pages-1);const visible=printing?all:all.slice(mediaPage*per,(mediaPage+1)*per);$('media-rows').replaceChildren();const frag=document.createDocumentFragment();visible.forEach(r=>{const tr=el('tr'),id=td('WP ID'),name=td('Media title / file'),format=td('Format / size'),dest=td('Drupal destination'),available=td('Availability');id.textContent='#'+r.id;name.append(el('span',r.title,'record-title'));addLine(name,r.path,'filepath cell-line');format.textContent=r.mime||'—';addLine(format,size(r.bytes),'small muted cell-line');dest.append(el('span',r.destination,'small'));addLine(dest,r.disposition,'small muted cell-line');available.append(pill(r.state,r.state));addLine(available,'In source archive: '+(r.source===true?'Yes':r.source===false?'No':'Not recorded'),'small cell-line');addLine(available,'In Drupal uploads: '+(r.local?'Yes':'No'),'small cell-line');if(r.verified===true)addLine(available,'File comparison verified','small cell-line');tr.append(id,name,format,dest,available);frag.append(tr);});$('media-rows').append(frag);$('media-empty').hidden=all.length>0;$('media-results').textContent=number(all.length)+' of '+number(D.media.length)+' attachments match';$('media-page-status').textContent='Page '+(mediaPage+1)+' of '+pages;$('media-previous').disabled=mediaPage===0;$('media-next').disabled=mediaPage>=pages-1;}
for(const id of ['search','type-filter','status-filter','category-filter','state-filter','page-size'])$(id).addEventListener(id==='search'?'input':'change',()=>{page=0;renderRecords();});
$('clear-filters').addEventListener('click',()=>{for(const id of ['search','type-filter','status-filter','category-filter','state-filter'])$(id).value='';page=0;renderRecords();});
$('previous').addEventListener('click',()=>{page--;renderRecords();});$('next').addEventListener('click',()=>{page++;renderRecords();});
$('media-search').addEventListener('input',()=>{mediaPage=0;renderMedia();});$('media-state').addEventListener('change',()=>{mediaPage=0;renderMedia();});$('media-previous').addEventListener('click',()=>{mediaPage--;renderMedia();});$('media-next').addEventListener('click',()=>{mediaPage++;renderMedia();});

$('additional-explanation').textContent=number(D.additionalRows.length)+' additional source records are preserved in content-archive/wordpress-additional-content.json. '+Object.entries(S.additionalArchiveCounts).map(([category,n])=>number(n)+' '+category.toLowerCase()).join(' · ')+'.';
D.additionalNotes.forEach(text=>$('additional-notes').append(el('li',text)));
[...new Set(D.additionalRows.map(r=>r.category))].sort().forEach(category=>{const option=el('option',category);option.value=category;$('additional-category').append(option);});
function filteredAdditional(){const q=$('additional-search').value.trim().toLowerCase(),category=$('additional-category').value;return D.additionalRows.filter(r=>(!category||r.category===category)&&(!q||[r.id,r.title,r.category,r.table,r.date,r.text,r.permalink,r.sourceTarget,r.fields,r.disposition].join(' ').toLowerCase().includes(q)));}
function renderAdditional(){
 const all=filteredAdditional(),per=25,pages=Math.max(1,Math.ceil(all.length/per));additionalPage=Math.min(additionalPage,pages-1);const visible=printing?all:all.slice(additionalPage*per,(additionalPage+1)*per);$('additional-rows').replaceChildren();const fragment=document.createDocumentFragment();
 visible.forEach(r=>{const tr=el('tr'),id=td('Source ID'),content=td('Archived content / text'),kind=td('Category / source'),disposition=td('Disposition / public reference'),dates=td('Source dates');id.textContent=r.id;content.append(el('span',r.title,'record-title'));content.append(el('p',r.text||'The source definition is recorded in the fields below.','small'));if(r.fields&&r.fields!=='{}'){const details=el('details',null,'excerpt');details.append(el('summary','Preserved source fields'),el('pre',r.fields,'source-block'));content.append(details);}if(r.sourceTarget&&!r.permalink)addLine(content,'Source target: '+r.sourceTarget,'filepath cell-line');kind.append(pill(r.category));addLine(kind,r.table,'filepath cell-line');disposition.append(el('span',r.disposition,'small'));if(r.permalink){try{const url=new URL(r.permalink);if(['http:','https:'].includes(url.protocol)&&!url.username&&!url.password){const a=el('a','Open original public reference');a.href=url.href;a.target='_blank';a.rel='noopener noreferrer';const line=el('div',null,'cell-line');line.append(a);disposition.append(line);addLine(disposition,r.permalink,'filepath cell-line');}}catch(error){/* Invalid links remain unlinked source data. */}}addLine(dates,r.date||'Not recorded','small cell-line');if(r.modified)addLine(dates,'Modified '+r.modified,'small muted cell-line');tr.append(id,content,kind,disposition,dates);fragment.append(tr);});
 $('additional-rows').append(fragment);$('additional-empty').hidden=all.length>0;$('additional-results').textContent=number(all.length)+' of '+number(D.additionalRows.length)+' additional archive records match';$('additional-page-status').textContent='Page '+(additionalPage+1)+' of '+pages;$('additional-previous').disabled=additionalPage===0;$('additional-next').disabled=additionalPage>=pages-1;
}
$('additional-search').addEventListener('input',()=>{additionalPage=0;renderAdditional();});$('additional-category').addEventListener('change',()=>{additionalPage=0;renderAdditional();});$('additional-previous').addEventListener('click',()=>{additionalPage--;renderAdditional();});$('additional-next').addEventListener('click',()=>{additionalPage++;renderAdditional();});

function selectTab(key){currentTab=key;document.querySelectorAll('[data-tab]').forEach(b=>{const active=b.dataset.tab===key;b.setAttribute('aria-selected',String(active));b.tabIndex=active?0:-1;$('panel-'+b.dataset.tab).hidden=!active;});}
const tabs=[...document.querySelectorAll('[data-tab]')];tabs.forEach((b,i)=>{b.addEventListener('click',()=>selectTab(b.dataset.tab));b.addEventListener('keydown',e=>{let target;if(e.key==='ArrowRight')target=(i+1)%tabs.length;else if(e.key==='ArrowLeft')target=(i+tabs.length-1)%tabs.length;else if(e.key==='Home')target=0;else if(e.key==='End')target=tabs.length-1;if(target!==undefined){e.preventDefault();selectTab(tabs[target].dataset.tab);tabs[target].focus();}});});
$('print-button').addEventListener('click',()=>window.print());window.addEventListener('beforeprint',()=>{printing=true;renderRecords();renderMedia();renderAdditional();});window.addEventListener('afterprint',()=>{printing=false;renderRecords();renderMedia();renderAdditional();});renderRecords();renderMedia();renderAdditional();
</script></body></html>'''

if __name__ == '__main__':
    main()
