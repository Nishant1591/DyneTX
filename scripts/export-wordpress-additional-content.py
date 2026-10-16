#!/usr/bin/env python3
"""Archive authored website copy stored outside WordPress posts, without secrets.

Read the source SQL without executing it. Export public social text/media URLs,
legacy maps/QR destination definitions and allowlisted footer/social/widget copy.
Caches, credentials, arbitrary options, form entries and operational logs are not
exported. Output stays outside Drupal's public web directory.
"""
import argparse
import collections
import html
import importlib.util
import json
import pathlib
import re
from urllib.parse import urlsplit, urlunsplit, parse_qsl, urlencode

ROOT = pathlib.Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('archive', type=pathlib.Path)
parser.add_argument('--output', type=pathlib.Path, default=ROOT / 'content-archive/wordpress-additional-content.json')
args = parser.parse_args()
source = args.archive.resolve()
sql = source / 'wp-content/mysql.sql'
if not sql.is_file(): parser.error('archive must contain wp-content/mysql.sql')
if args.output.resolve().is_relative_to(ROOT / 'web'): parser.error('output must remain outside the public web directory')
spec = importlib.util.spec_from_file_location('extract', ROOT / 'web/modules/custom/dyne_migrate/scripts/legacy/extract-archive.py')
extract = importlib.util.module_from_spec(spec)
spec.loader.exec_module(extract)


def plain(value):
    value = re.sub(r'<(script|style)\b[^>]*>.*?</\1>', '', str(value or ''), flags=re.S | re.I)
    return re.sub(r'\s+', ' ', html.unescape(re.sub(r'<[^>]+>', ' ', value))).strip()


def public_url(value):
    value = html.unescape(str(value or '')).strip()
    if not value or '[encode]' in value: return ''
    parsed = urlsplit(value)
    if parsed.scheme and parsed.scheme.lower() not in {'https', 'http', 'mailto', 'tel'}: return ''
    if parsed.username or parsed.password: return ''
    query = [(k, v) for k, v in parse_qsl(parsed.query, keep_blank_values=True)
             if not re.search(r'token|secret|password|credential|api.?key|signature|authorization', k, re.I)]
    return urlunsplit((parsed.scheme, parsed.netloc, parsed.path, urlencode(query), parsed.fragment))


def unserialize(value):
    """Decode scalar/array PHP serialization only; objects are never supported."""
    data = value.encode('utf-8'); index = 0
    def read():
        nonlocal index
        kind = chr(data[index]); index += 2
        if kind == 'N': return None
        if kind in 'bid':
            end = data.index(b';', index); token = data[index:end]; index = end + 1
            return token == b'1' if kind == 'b' else int(token) if kind == 'i' else float(token)
        if kind == 's':
            end = data.index(b':', index); size = int(data[index:end]); index = end + 2
            result = data[index:index+size].decode('utf-8'); index += size + 2
            return result
        if kind == 'a':
            end = data.index(b':', index); count = int(data[index:end]); index = end + 2
            result = {}
            for _ in range(count):
                key = read(); result[key] = read()
            if data[index:index+1] != b'}': raise ValueError('Malformed array')
            index += 1
            return result
        raise ValueError('Unsupported serialized value')
    return read()

records = []
def add(kind, sid, title, text='', date='', url='', table='', fields=None, reason=''):
    records.append({'id': kind + ':' + str(sid), 'source_id': sid, 'type': kind,
                    'title': plain(title), 'text': plain(text), 'date': str(date or ''),
                    'source_url': public_url(url), 'source_table': table,
                    'fields': fields or {}, 'disposition': reason})

for row in extract.rows(sql, 'wp_ctf_posts'):
    payload = json.loads(row[5])
    author = payload.get('user', {}).get('screen_name', '')
    tweet_id = str(payload.get('id_str', row[1]))
    text = payload.get('full_text', payload.get('text', ''))
    media, links = [], []
    for group in ('entities', 'extended_entities'):
        entity = payload.get(group) or {}
        for item in entity.get('urls', []):
            dest = public_url(item.get('expanded_url', item.get('url', '')))
            if dest and dest not in links: links.append(dest)
        for item in entity.get('media', []):
            dest = public_url(item.get('media_url_https', item.get('media_url', '')))
            if dest and dest not in media: media.append(dest)
    add('cached_social_post', row[0], plain(text)[:140], text, payload.get('created_at', ''),
        'https://twitter.com/' + author + '/status/' + tweet_id, 'wp_ctf_posts',
        {'author': author, 'social_post_id': tweet_id, 'links': links, 'media': media},
        'Historical cached social-feed content referenced by the legacy Perspectives page; preserved for reference, not republished as current Drupal news or a live social feed.')

for row in extract.rows(sql, 'wp_wpgmza_maps'):
    add('legacy_map', row[0], row[1], table='wp_wpgmza_maps',
        fields={'latitude': row[4], 'longitude': row[5]},
        reason='Legacy map definition without a reference in the active theme or editorial content; archived without enabling an obsolete map.')
for row in extract.rows(sql, 'wp_wpgmza'):
    add('legacy_map_marker', row[0], row[10] or row[2], row[3], url=row[5], table='wp_wpgmza',
        fields={'map_id': row[1], 'address': plain(row[2]), 'image_url': public_url(row[4]), 'latitude': row[7], 'longitude': row[8]},
        reason='Legacy map marker without an active page reference; retained as source content, not published.')
for row in extract.rows(sql, 'wp_sos_dqc_qrcodes'):
    add('legacy_qr_definition', row[0], row[3], date=row[22] if len(row)>22 else '', url=row[4], table='wp_sos_dqc_qrcodes',
        fields={'inactive_url': public_url(row[5]), 'expired_url': public_url(row[6]), 'date_from': row[7], 'date_to': row[8], 'finished_url': public_url(row[14])},
        reason='Historical QR campaign destination; retained for reference without restoring redirect behavior or exporting access-control fields.')

option_allow = {'widget_text','widget_custom_html','widget_block','widget_black-studio-tinymce','widget_media_image','widget_media_audio','widget_media_video','widget_media_gallery','widget_rss'}
options = {}
for row in extract.rows(sql, 'wp_options'):
    if row[1] in option_allow or re.fullmatch(r'options_(footer_links|social_media_links)_\d+_(label|url|open_in_new_tab|name|icon)', row[1]):
        options[row[1]] = (row[0], row[2])
for group, kind, title_field in [('footer_links','historical_footer_link','label'), ('social_media_links','historical_social_link','name')]:
    indices = sorted({int(re.match(r'options_' + group + r'_(\d+)_', key)[1]) for key in options if key.startswith('options_' + group + '_')})
    for index in indices:
        base = 'options_' + group + '_' + str(index) + '_'
        title = options.get(base+title_field, (None,''))[1]
        sid = options.get(base+title_field, (base,''))[0]
        add(kind, sid, title, url=options.get(base+'url', (None,''))[1], table='wp_options',
            fields={'source_option_prefix':base, 'icon': options.get(base+'icon', (None,''))[1]},
            reason='Historical theme option copy; the active source theme hardcodes its footer/header, so this is preserved without changing current navigation.')
for name in sorted(option_allow):
    if name not in options: continue
    option_id, serialized = options[name]
    widgets = unserialize(serialized)
    for instance, widget in widgets.items():
        if not isinstance(widget, dict) or not widget: continue
        title = plain(widget.get('title', ''))
        body = plain(widget.get('text', widget.get('content', widget.get('caption',''))))
        # Ignore empty widget placeholders and non-editorial settings.
        if not title and not body and not widget.get('url'): continue
        urls = [public_url(url) for url in re.findall(r'(?:src|href)=[\"\x27]([^\"\x27]+)', str(widget.get('text',widget.get('content',''))))]
        urls = list(dict.fromkeys(url for url in urls if url))
        add('historical_widget', str(option_id) + ':' + str(instance), title or name, body,
            url=widget.get('url',''), table='wp_options',
            fields={'source_option':name,'instance':instance,'links':urls,'alt':plain(widget.get('alt',''))},
            reason='Historical sidebar widget copy. No dynamic_sidebar/the_widget calls exist in the active source theme; archived without publishing former contact information.')

result = {'source':str(source),'record_count':len(records),'counts':dict(collections.Counter(r['type'] for r in records)),
          'scope':'Additional authored content outside wp_posts; counts are separate from the 1,747 WordPress post rows.',
          'privacy_notes':['No arbitrary option values, credentials, integration settings, form submissions, operational logs, full feed-cache payloads or social user-profile objects are exported.','These records are archival and are not published automatically.'],
          'records':records}
args.output.parent.mkdir(parents=True,exist_ok=True)
args.output.write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'output':str(args.output),'record_count':len(records),'counts':result['counts']},indent=2))
