#!/usr/bin/env python3
"""Build portable, public Drupal content fixtures and copy referenced media only."""
import argparse
import collections
import datetime
import hashlib
import html
from html.parser import HTMLParser
import importlib.util
import json
import pathlib
import re
import shutil
from urllib.parse import unquote, urlsplit

HERE = pathlib.Path(__file__).resolve().parent
MODULE = HERE.parent.parent
spec = importlib.util.spec_from_file_location('extract_archive', HERE / 'extract-archive.py')
extract = importlib.util.module_from_spec(spec)
spec.loader.exec_module(extract)
spec = importlib.util.spec_from_file_location('legacy_navigation', HERE / 'navigation.py')
navigation = importlib.util.module_from_spec(spec)
spec.loader.exec_module(navigation)


class SafeHTML(HTMLParser):
    """Preserve layout markup, dropping executable markup from the archive."""
    def __init__(self, rewrite, aliases=()):
        super().__init__(convert_charrefs=False)
        self.output, self.skip, self.rewrite = [], [], rewrite
        self.aliases = aliases
    def handle_starttag(self, tag, attrs):
        if tag in {'script', 'object', 'embed', 'base', 'form'}:
            self.skip.append(tag)
            return
        if self.skip:
            return
        if tag == 'img':
            dimensions = dict(attrs)
            # The news feed includes invisible GlobeNewswire tracking pixels.
            if all(re.fullmatch(r'(?:[01](?:\.\d+)?|2)(?:px)?', dimensions.get(key) or '') for key in ('width', 'height')):
                return
        clean = []
        attributes = dict(attrs)
        destination = attributes.get('data-mdb-url') or attributes.get('href') or ''
        same_tab = tag == 'a' and navigation.internal_page(self.rewrite(destination), 'download' in attributes)
        for key, value in attrs:
            if key.startswith('on') or key in {'srcdoc', 'formaction'}:
                continue
            if key == 'target' and same_tab:
                continue
            if value is not None:
                value = self.rewrite(value)
                if key in {'href', 'data-mdb-url'}:
                    value = navigation.canonical_url(value, self.aliases)
                if key in {'href', 'src', 'action', 'poster', 'data-mdb-url', 'embd-data-url'} and re.match(r'\s*(javascript|data|vbscript):', value, re.I):
                    continue
                if key == 'style' and re.search(r'expression\s*\(|javascript\s*:', value, re.I):
                    continue
                clean.append(f'{key}="{html.escape(value, quote=True)}"')
            else:
                clean.append(key)
        if tag == 'iframe':
            attrs_dict = dict(attrs)
            src = self.rewrite(attrs_dict.get('src') or '')
            host = urlsplit(src).hostname
            if host not in {'www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com', 'www.google.com', 'maps.google.com'}:
                self.skip.append(tag)
                return
            clean.append('loading="lazy"')
        self.output.append('<' + tag + (' ' + ' '.join(clean) if clean else '') + '>')
    def handle_endtag(self, tag):
        if self.skip:
            if tag == self.skip[-1]:
                self.skip.pop()
            return
        self.output.append('</' + tag + '>')
    def handle_data(self, data):
        if not self.skip:
            self.output.append(data)
    def handle_entityref(self, name):
        if not self.skip:
            self.output.append('&' + name + ';')
    def handle_charref(self, name):
        if not self.skip:
            self.output.append('&#' + name + ';')


def sha256_file(path):
    digest = hashlib.sha256()
    with path.open('rb') as source:
        for block in iter(lambda: source.read(1024 * 1024), b''):
            digest.update(block)
    return digest.hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('archive', type=pathlib.Path)
    parser.add_argument('--web-root', type=pathlib.Path, default=MODULE.parents[2])
    args = parser.parse_args()
    raw = extract.load_public(args.archive / 'wp-content/mysql.sql')
    posts = {p['id']: p for p in raw['posts'] if not (p['type'] == 'post' and p['slug'] == 'qwe')}
    for post in posts.values():
        post['title'] = re.sub(r'<[^>]+>', '', html.unescape(post['title'].replace('<sup>TM</sup>', '™')))
    media, missing = {}, set()
    recovered_file = MODULE / 'data/legacy-recovered-media.json'
    recovered = json.loads(recovered_file.read_text()).get('records', []) if recovered_file.exists() else []
    recovered = {unquote(urlsplit(row['source_url']).path.split('/wp-content/uploads/', 1)[1]): row for row in recovered if row.get('status') == 'recovered'}
    recovered_used = set()
    uploads = args.archive / 'wp-content/uploads'
    dest = args.web_root / 'sites/default/files/legacy'
    def media_path(path):
        path = unquote(path).replace('\\', '/')
        if '..' in pathlib.PurePosixPath(path).parts or path.startswith('/'):
            raise ValueError('Unsafe media path')
        source = uploads / path
        if not source.is_file():
            evidence = recovered.get(path)
            target = dest / path
            if evidence and target.is_file() and target.stat().st_size == evidence['bytes']:
                if path not in recovered_used and sha256_file(target) != evidence['sha256']:
                    raise ValueError('Recovered media hash mismatch: ' + path)
                recovered_used.add(path)
                media[path] = target.stat().st_size
                return '/sites/default/files/legacy/' + path.replace(' ', '%20')
            missing.add(path)
            return 'https://www.dyne-tx.com/wp-content/uploads/' + path
        if path not in media:
            target = dest / path
            target.parent.mkdir(parents=True, exist_ok=True)
            if not target.exists() or target.stat().st_size != source.stat().st_size:
                shutil.copy2(source, target)
            media[path] = source.stat().st_size
        return '/sites/default/files/legacy/' + path.replace(' ', '%20')
    def rewrite(value):
        value = navigation.VERIFIED_REDIRECTS.get(value, value)
        value = navigation.canonical_embedded_urls(value)
        value = value.replace('/wp-content/themes/dynetxdev2020/', '/themes/custom/dynetx/')
        # URLs occur both in HTML attributes and inline CSS declarations.
        value = re.sub(r'/wp-content/uploads/([^\"\'<>\)\?#]+)', lambda m: media_path(m[1]), value)
        return value
    def clean(value):
        parser = SafeHTML(rewrite, known_aliases)
        parser.feed(value or '')
        return ''.join(parser.output).strip()
    def attachment(ref):
        row = raw['attachments'].get(int(ref)) if str(ref or '').isdigit() else None
        return media_path(row['_wp_attached_file']) if row and row.get('_wp_attached_file') else ''
    def alias(p):
        prefix = {'jobs': '/jobs/', 'perspectives': '/perspectives/', 'team': '/team/', 'news': '/news/'}.get(p['type'], '/')
        parent = posts.get(p['parent']) if p['type'] == 'page' else None
        return (alias(parent) + '/' if parent else prefix) + p['slug']
    known_aliases = {alias(p).rstrip('/') or '/' for p in posts.values() if p['type'] in {'page', 'perspectives', 'jobs', 'team', 'news', 'post'}}
    def rich(body):
        return {'type': 'dyne_rich_text', 'fields': {'field_style': 'legacy-markup', 'field_body_text': body}}
    def seo_title(post):
        tokens = {'sitename': raw['public_site'].get('blogname', ''), 'sitedesc': raw['public_site'].get('blogdescription', ''), 'title': post['title'], 'sep': '-', 'page': '', 'pagenumber': ''}
        value = post['meta'].get('_yoast_wpseo_title', '')
        return re.sub(r'%%([a-z_]+)%%', lambda match: tokens.get(match[1], match[0]), value).strip()
    metadata, records, skipped = {}, [], []
    for p in posts.values():
        m = p['meta']
        if p['type'] in {'page', 'perspectives', 'jobs', 'team', 'news', 'post'}:
            metadata[alias(p)] = {'source_id': p['id'], 'title': p['title'], 'description': m.get('_yoast_wpseo_metadesc', ''), 'seo_title': seo_title(p), 'classes': m.get('additional_body_class', '').split(), 'hero': {'desktop': attachment(m.get('_thumbnail_id')), 'mobile': attachment(m.get('page_secondary-image_thumbnail_id')), 'overlay': attachment(m.get('page_hero-overlay-image_thumbnail_id')), 'line1': clean(m.get('first_line', '')), 'line2': clean(m.get('second_line', '')), 'third_level': m.get('hero_third_level') == '1', 'classes': 'perspectives' if p['type'] == 'perspectives' or p['slug'] == 'perspectives' else ''}}
    for route, meta in metadata.items():
        if route.startswith('/jobs/'):
            meta['hero'] = dict(metadata['/careers-culture']['hero'])
    team_groups = collections.defaultdict(list)
    for p in sorted(posts.values(), key=lambda p: (p['order'], p['id'])):
        if p['type'] == 'team':
            for term in p.get('terms', []):
                team_groups[term['slug']].append({'name': p['title'], 'role': clean(p['meta'].get('_position', '')), 'photo': attachment(p['meta'].get('_thumbnail_id')), 'bio': clean(p['content']), 'source_id': p['id']})
    privacy = ''
    for i, p in enumerate(sorted((p for p in posts.values() if p['type'] == 'wb_privacy_policy'), key=lambda p: (p['order'], p['id']))):
        date = p['meta'].get('_wb_last_updated') or p['modified'][:10]
        heading = 'h1' if i == 0 else 'h2'
        privacy_id = 'dyne-consumer-health' if 'consumer health' in p['title'].lower() else 'dyne-privacy-statement'
        privacy += f'<section id="{privacy_id}" class="container-fluid section page-intro"><div class="container"><{heading} class="h2 h2-new-sm h2-new-thin">{html.escape(p["title"])}</{heading}><p><i>Last Updated: {html.escape(date)}</i></p>{clean(p["content"])}</div></section>'
    perspectives = sorted((p for p in posts.values() if p['type'] == 'perspectives'), key=lambda p: int(p['meta'].get('perspectives_date') or 0), reverse=True)
    perspective_cards = '<section class="container-fluid section perspective-articles"><div class="container"><div class="row">'
    for p in perspectives:
        url = p['meta'].get('perspectives_external_link') or alias(p)
        if str(url) == '1': url = p['meta'].get('perspectives_url') or alias(p)
        photo = attachment(p['meta'].get('_thumbnail_id') or p['meta'].get('image'))
        perspective_cards += f'<article class="col-12 col-md-6 card"><img src="{html.escape(photo, quote=True)}" alt="" class="card-img-top" loading="lazy"><div class="card-body"><h2 class="h3">{html.escape(p["title"])}</h2><p>{html.escape(p["meta"].get("author", ""))}</p><p>{clean(p["excerpt"])}</p><a class="btn btn-primary" href="{html.escape(rewrite(url), quote=True)}">Read more</a></div></article>'
    perspective_cards += '</div></div></section>'
    quotes = [p for p in posts.values() if p['type'] == 'wb_quotes']
    quote_slides = ''.join(f'<div class="carousel-item {"active" if i == 0 else ""}"><blockquote>{clean(p["content"])}</blockquote><p>{html.escape(p["title"])}</p></div>' for i,p in enumerate(quotes))
    indicators = ''.join(f'<li data-bs-target="#empl-quotes" data-bs-slide-to="{i}" class="{"active" if i == 0 else ""}" aria-label="Quote {i+1}"></li>' for i in range(len(quotes)))
    news = sorted((p for p in posts.values() if p['type'] == 'news'), key=lambda p: int(p['meta'].get('news_date') or 0), reverse=True)
    news_items = [{'date': datetime.datetime.fromtimestamp(int(p['meta'].get('news_date') or 0), datetime.timezone.utc).strftime('%B %d, %Y'), 'title': p['title'], 'url': p['meta'].get('news_url') or alias(p)} for p in news[:2]]
    for p in posts.values():
        if p['type'] not in {'page', 'perspectives', 'jobs', 'team', 'news', 'post'}:
            continue
        m = p['meta']
        record = {'source_id': p['id'], 'source_type': p['type'], 'title': p['title'], 'alias': alias(p), 'modified': p['modified']}
        body = clean(p['content'])
        if p['type'] == 'page':
            if p['slug'] == 'home':
                body = (MODULE / 'data/legacy-home.html').read_text()
                body = body.split('<div class="container-fluid section section-news', 1)[0].rstrip() + '\n<!--DYNE_NEWS_COMPONENT-->'
            if p['slug'] == 'privacy-policy': body = privacy
            if p['slug'] == 'cookie-declaration':
                body = '<section class="container-fluid section page-intro"><div class="container"><h1>Cookie declaration</h1><div data-dyne-cookie-declaration></div><p><a href="/privacy-policy">Read our privacy policy</a></p></div></section>'
            if m.get('content_mask_enable') == '1' and m.get('content_mask_url'):
                video = rewrite(m['content_mask_url'])
                body = f'<section class="container-fluid section page-intro"><div class="container"><h1>{html.escape(p["title"])}</h1><video controls playsinline preload="metadata" class="w-100"><source src="{html.escape(video, quote=True)}" type="video/mp4"></video></div></section>'
                metadata[alias(p)]['hero']['third_level'] = True
            if not body.strip():
                skipped.append({'source_id': p['id'], 'alias': alias(p), 'reason': 'Published legacy page has no content or media destination.'})
                continue
            if p['slug'] == 'about-us':
                body = body.replace('href="mailto:partnerships@dyne-tx.com">patientadvocacy@dyne-tx.com', 'href="mailto:patientadvocacy@dyne-tx.com">patientadvocacy@dyne-tx.com')
            if p['slug'] == 'careers-culture':
                body = body.replace('<div id="dynamic"', '<span id="HfD" class="anchor" aria-hidden="true"></span><div id="dynamic"', 1)
            if p['slug'] == 'pipeline':
                body = body.replace('<div class="container-fluid section pipeline-chart-26', '<span id="programs" class="anchor" aria-hidden="true"></span><div class="container-fluid section pipeline-chart-26', 1)
            body = re.sub(r'\[modification_date\]', p['modified'][:10], body)
            body = re.sub(r'\[ot_webform\s*\]', '<div data-dyne-onetrust-form></div>', body)
            body = re.sub(r'\[custom-twitter-feeds[^]]*\]', '<p><a href="https://twitter.com/dyne_tx" target="_blank" rel="noopener noreferrer">Follow Dyne Therapeutics on X</a></p>', body)
            body = body.replace('[perspectives]', perspective_cards).replace('[wb_quote_list]', quote_slides).replace('[wb_quote_indicators]', indicators)
            pieces = re.split(r'(\[workbox-leadership-team\]|\[workbox-board-of-directors\]|\[workbox-founders\]|\[workbox_jobs\]|<!--DYNE_NEWS_COMPONENT-->)', body)
            components = [{'type': 'dyne_hero', 'fields': {'field_title_line1': clean(m.get('first_line', '')), 'field_title_line2': clean(m.get('second_line', ''))}}]
            for piece in pieces:
                if piece == '[workbox_jobs]':
                    components.append({'type': 'dyne_jobs_list', 'fields': {'field_heading': 'Open positions', 'field_anchor': 'positions-list', 'field_dynamic_jobs': True}})
                elif piece == '<!--DYNE_NEWS_COMPONENT-->':
                    components.append({'type': 'dyne_news_list', 'fields': {'field_heading': 'Latest news', 'field_items_json': news_items}})
                elif piece.startswith('[workbox-'):
                    key, heading = {'[workbox-leadership-team]': ('leadership-main', ''), '[workbox-board-of-directors]': ('board-of-directors', 'Board of directors'), '[workbox-founders]': ('scientific-advisory-board', 'Scientific advisory board')}[piece]
                    members = team_groups[key] + (team_groups['leadership'] if key == 'leadership-main' else [])
                    components.append({'type': 'dyne_team_grid', 'fields': {'field_heading': heading, 'field_items_json': members}})
                elif piece.strip(): components.append(rich(piece))
            record.update(bundle='dyne_page', components=components)
        elif p['type'] == 'jobs':
            department = m.get('_department', '')
            record.update(bundle='job', body=body, fields={'field_department_text': department, 'field_department_group': re.sub(r'^\s*\d+[A-Z]*\s*-\s*', '', department).strip(), 'field_location_text': m.get('_location', ''), 'field_apply_url': m.get('_apply_url', ''), 'field_open_position_weight': p['order']})
        elif p['type'] == 'team':
            record.update(bundle='leader', body=body, photo=attachment(m.get('_thumbnail_id')), role=clean(m.get('_position', '')), groups=[t['slug'] for t in p.get('terms', [])])
        elif p['type'] == 'perspectives':
            url = m.get('perspectives_external_link', '')
            if not body:
                body = f'<section class="container-fluid section perspective-article"><div class="container"><h1>{html.escape(p["title"])}</h1><p>{html.escape(m.get("author", ""))}</p><p>{clean(p["excerpt"])}</p><a class="btn btn-primary" href="{html.escape(rewrite(url), quote=True)}">Read the perspective</a></div></section>'
            record.update(bundle='perspective', body=body, photo=attachment(m.get('_thumbnail_id') or m.get('image')), link=rewrite(url))
        else:
            source_date = datetime.datetime.fromtimestamp(int(m['news_date']), datetime.timezone.utc).strftime('%B %d, %Y') if m.get('news_date') else datetime.datetime.strptime(p['date'], '%Y-%m-%d %H:%M:%S').strftime('%B %d, %Y')
            record.update(bundle='news', body=body, link=m.get('news_url', ''), fields={'field_date_text': source_date})
        records.append(record)
    jobs = sorted((r for r in records if r['bundle'] == 'job'), key=lambda r:(r['fields']['field_department_text'].lower(), r['title'].lower()))
    for i, record in enumerate(jobs):
        record['fields']['field_previous_url'] = '/careers-culture#positions'
        record['fields']['field_next_url'] = jobs[(i+1) % len(jobs)]['alias']
    menus=[]
    for p in sorted(posts.values(), key=lambda p:p['order']):
        if p['type'] != 'nav_menu_item' or not any(t['slug']=='primary-navigation' for t in p.get('terms',[])):
            continue
        m=p['meta'];target=posts.get(int(m.get('_menu_item_object_id') or 0))
        url=alias(target) if m.get('_menu_item_type')=='post_type' and target else m.get('_menu_item_url','')
        if url:
            menus.append({'source_id':p['id'],'parent':int(m.get('_menu_item_menu_item_parent') or 0),'title':p['title'].strip() or (target['title'] if target else ''),'url':rewrite(url),'weight':p['order'],'target':m.get('_menu_item_target','')})
    fixture={'source':'Supplied WordPress archive, public records only','source_sha256':hashlib.sha256((args.archive/'wp-content/mysql.sql').read_bytes()).hexdigest(),'records':records,'menus':menus,'news_items':news_items,'skipped':skipped,'assets':{'copied':len(media),'bytes':sum(media.values()),'missing':sorted(missing),'recovered':sorted(recovered_used)}}
    dispositions = {
        'page': '52 imported as dyne_page; two empty source shells /fshd-day and /our-focus recorded as skipped.',
        'post': '16 original press releases imported as news at original root aliases; test post ID 1576, title/slug qwe, excluded.',
        'news': '183 press-release records imported as news; latest two supply the native homepage news Paragraph.',
        'jobs': '49 imported as job nodes with full descriptions, department, location and Greenhouse URLs.',
        'team': '31 imported as leader nodes and referenced by the three native About team-grid Paragraphs; source has 15 portraits.',
        'perspectives': '13 imported as perspective nodes and included in the perspectives index; external URLs resolved from perspectives_external_link.',
        'wb_privacy_policy': 'Both blocks expanded into the Privacy Policy dyne_page, preserving source dates and consumer-health anchor.',
        'wb_quotes': 'All four expanded into the original Careers carousel; original hidden section state retained.',
        'nav_menu_item': '35 primary-navigation links reconciled into main; seven separate mobile-navigation links are unused by source header, which renders top_menu for both desktop and mobile.',
        'wb_news_xml2': '100 unrelated Jazz Pharmaceuticals feed records from a legacy plugin excluded; active Dyne news uses the separate news post type.',
        'comp_announcements': 'Only record ID 1317 is a test: title/slug test, body test testrjrjrktyktyk; not exposed by current source pages.',
        'press-releases': 'Only ID 1227 slug test is duplicate Susanna High release; canonical source news record 1431 is imported.',
        'wpa-stats': '136 WP Accessibility diagnostic results, not publications or website content; plugin wp-accessibility-stats.php confirms their purpose.',
        'iwp_log': '325 InfiniteWP operational logs excluded.',
        'acf-field': '140 WordPress field schema definitions replaced by existing Drupal fields; public stored values used where applicable.',
        'acf-field-group': 'Six WordPress field groups; page/hero metadata transferred to the Drupal metadata manifest.',
        '_pods_pod': 'WordPress Pods schema configuration, not public page content.',
        '_pods_field': 'WordPress Pods field configuration, not public page content.',
        'gravityview': 'Upload Form View administrative plugin view, not used by current public page markup.',
        'documentation': 'Empty Add new center admin documentation record excluded.',
        'shortcodesultimate': 'Three demo shortcode definitions; none referenced by imported public content.',
        'gc_templates': 'Employee profile example mapping template; no current public content references it.',
        'seedprod': 'Coming-soon plugin record with empty body; live theme does not use it.',
        'popupbuilder': 'Homepage Video placeholder, literal body Content goes here; actual hero/video behavior ported in Drupal theme.',
        'redirect_rule': 'Stale /world-fshd-day-2022/ -> /?show_video redirect targets placeholder popup; source page media destination retained as a video page instead.',
        'qr': 'Homepage QR generator block; no public content/body beyond generator shortcode.',
        'wp_global_styles': 'Default empty theme JSON object with version/isGlobalStylesUserThemeJSON only; no style rules to port.',
        'custom_css': 'Empty custom CSS record; full source theme CSS is ported by dynetx theme.',
    }
    inventory = {'source_sha256': fixture['source_sha256'], 'post_types': [{'type': kind, 'published_count': count, 'disposition': dispositions.get(kind, 'REVIEW REQUIRED')} for kind, count in sorted(raw['inventory'].items())], 'redirect_tables': [{'source': '/news/', 'target': '/media/', 'status': 301, 'disposition': 'Legacy redirect points to absent /media page; not imported as a broken Drupal redirect.'}], 'excluded_data': ['WordPress users and password hashes', 'plugin options and credentials', 'private/draft/revision content', 'logs and submitted form data'], 'limitations': fixture['skipped'] + [{'missing_asset': asset} for asset in fixture['assets']['missing']]}
    if any(item['disposition'] == 'REVIEW REQUIRED' for item in inventory['post_types']):
        raise ValueError('An unclassified source post type requires review before export.')
    (MODULE/'data/legacy-source-inventory.json').write_text(json.dumps(inventory,ensure_ascii=False,indent=2)+'\n')
    (MODULE/'data/legacy-content.json').write_text(json.dumps(fixture,ensure_ascii=False,indent=2)+'\n')
    (MODULE/'data/legacy-page-metadata.json').write_text(json.dumps(metadata,ensure_ascii=False,indent=2)+'\n')
    (MODULE/'data/legacy-asset-manifest.json').write_text(json.dumps(media,ensure_ascii=False,indent=2)+'\n')
    print(json.dumps({'records':dict(collections.Counter(r['bundle'] for r in records)),'menus':len(menus),'skipped':skipped,'assets':fixture['assets']},indent=2))

if __name__=='__main__': main()
