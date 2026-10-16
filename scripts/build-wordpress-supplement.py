#!/usr/bin/env python3
"""Prepare additive WordPress content; private source data stays outside web/."""
import argparse
import hashlib
import html
import importlib.util
import json
from pathlib import Path
import re
from urllib.parse import quote, unquote, urlsplit

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('legacy_builder', ROOT / 'web/modules/custom/dyne_migrate/scripts/legacy/build-fixture.py')
builder = importlib.util.module_from_spec(spec)
spec.loader.exec_module(builder)


def build(source):
    records = []
    existing = json.loads((ROOT / 'web/modules/custom/dyne_migrate/data/legacy-content.json').read_text())
    aliases = {p['alias'] for p in existing['records']} | {'/fshd-day', '/our-focus', '/community-involvement'}

    def rewrite(value):
        value = builder.navigation.canonical_embedded_urls(value)
        value = value.replace('/wp-content/themes/dynetxdev2020/', '/themes/custom/dynetx/')
        def media(match):
            path = unquote(match[1])
            if '..' in Path(path).parts or path.startswith('/'):
                raise ValueError('Unsafe media path')
            return '/sites/default/files/legacy/' + quote(path, safe='/@-._~')
        return re.sub(r'/wp-content/uploads/([^\"\'<>\)\?#]+)', media, value)

    def clean(value):
        parser = builder.SafeHTML(rewrite, aliases)
        parser.feed(value or '')
        return ''.join(parser.output).strip()

    for item in source['records']:
        if item['classification'] not in {'unmigrated_editorial', 'unpublished_editorial', 'legacy_unrelated_feed'}:
            continue
        source_id = item['id']
        page = item['type'] in {'page', 'documentation', 'popupbuilder', 'qr'}
        published = source_id in {1124, 1211} and item['status'] == 'publish' and not item['password_protected']
        alias = '/' + item['slug'] if item['type'] == 'page' else f'/legacy-archive/{item["type"]}/{source_id}'
        title = html.unescape(re.sub(r'<[^>]*>', '', item['title'])) or f'Untitled WordPress record {source_id}'
        body = clean(item['content'])
        meta = item['meta']
        reason = ('Restored published source page.' if published else
                  'Preserved as unpublished: ' + item['disposition'])
        if source_id == 1124:
            # The legacy page body is blank; its historical ACF fields hold the
            # original title/video. Restore those exact values, not new copy.
            body = '<section class="container-fluid section page-intro mt-5"><div class="container"><h1>' + html.escape(meta['flexible_content_0_title']) + '</h1><div class="ratio ratio-16x9">' + clean(meta['flexible_content_1_content']) + '</div></div></section>'
            # A nested paragraph prevents Bootstrap's ratio child sizing.
            body = re.sub(r'<p\b[^>]*>(<iframe[\s\S]*?</iframe>)</p>', r'\1', body)
            body = body.replace('<iframe ', '<iframe title="' + html.escape(meta['flexible_content_0_title'], quote=True) + '" ', 1)
            body = body.replace('</div></div></section>', '</div><p><a href="https://vimeo.com/430827122" target="_blank" rel="noopener noreferrer">Watch the FSHD Day video on Vimeo</a></p></div></section>')
            reason = 'Published page restored from archived ACF title and Vimeo video; source post_content is empty.'
        elif source_id == 1211:
            body = '<section class="container-fluid section page-intro mt-5"><div class="container"><h1>' + html.escape(title) + '</h1></div></section>'
            reason = 'Published empty source page restored with its original title; archive contains no body copy.'
        record = {'source_id': source_id, 'source_type': item['type'], 'source_status': item['status'],
                  'title': title, 'bundle': 'dyne_page' if page else 'news', 'published': published,
                  'alias': alias, 'body': body, 'date': item['date'], 'modified': item['modified'],
                  'reason': reason, 'source_data': item}
        if page:
            record['components'] = [
                {'type': 'dyne_hero', 'fields': {'field_title_line1': clean(meta.get('first_line', '')), 'field_title_line2': clean(meta.get('second_line', ''))}},
                {'type': 'dyne_rich_text', 'fields': {'field_style': 'legacy-markup', 'field_body_text': body}},
            ]
        records.append(record)
    return {'source_sha256': source['source_sha256'], 'records': records,
            'policy': 'Additive only. Existing 344 public nodes are never rewritten. Private/draft/test/foreign-feed content stays unpublished.'}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--source', type=Path, default=ROOT / 'content-archive/wordpress-records.json')
    parser.add_argument('--output', type=Path, default=ROOT / 'content-archive/wordpress-supplement.json')
    args = parser.parse_args()
    result = build(json.loads(args.source.read_text()))
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(result, ensure_ascii=False, indent=2) + '\n')
    print(json.dumps({'records': len(result['records']), 'published': sum(r['published'] for r in result['records']), 'output': str(args.output)}))


if __name__ == '__main__':
    main()
