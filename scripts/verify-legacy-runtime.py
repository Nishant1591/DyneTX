#!/usr/bin/env python3
"""Verify imported public routes, local images/scripts/styles and internal links.

Run against the isolated DDEV preview; never submits forms or follows external
links. Results are written as JSON to make incomplete source assets explicit.
"""
import argparse
import concurrent.futures
import json
import re
from html.parser import HTMLParser
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import quote, urljoin, urlsplit, unquote
from urllib.request import Request, HTTPRedirectHandler, build_opener

ROOT = Path(__file__).resolve().parent.parent
TEXT_CONTENT_TYPES = {'text/html', 'application/xhtml+xml', 'text/css'}


def read_text_response(response, parse):
    """Never read media bodies into the HTML/CSS parser after a redirect."""
    if not parse or response.headers.get_content_type() not in TEXT_CONTENT_TYPES:
        return ''
    return response.read().decode('utf-8', errors='replace')


class Page(HTMLParser):
    def __init__(self):
        super().__init__()
        self.assets, self.links, self.ids = set(), set(), set()
        self.h1 = 0

    def handle_starttag(self, tag, pairs):
        attrs = dict(pairs)
        if attrs.get('id'):
            self.ids.add(attrs['id'])
        if tag == 'h1':
            self.h1 += 1
        if tag in ('img', 'script', 'source', 'video') and attrs.get('src'):
            self.assets.add(attrs['src'])
        if tag in ('img', 'source') and attrs.get('srcset'):
            self.assets.update(candidate.strip().split()[0]
                               for candidate in attrs['srcset'].split(',') if candidate.strip())
        if tag == 'video' and attrs.get('poster'):
            self.assets.add(attrs['poster'])
        if tag == 'link' and (attrs.get('rel') == 'stylesheet' or attrs.get('as') == 'font'):
            self.assets.add(attrs.get('href', ''))
        if tag == 'a' and attrs.get('href'):
            self.links.add(attrs['href'])


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--base', default='http://dyne-tx-master.ddev.site')
    parser.add_argument('--output', default='output/legacy-runtime-verification.json')
    args = parser.parse_args()
    base = args.base.rstrip('/')
    host = urlsplit(base).netloc
    data = json.loads((ROOT / 'web/modules/custom/dyne_migrate/data/legacy-content.json').read_text())

    class LocalRedirects(HTTPRedirectHandler):
        def redirect_request(self, request, response, code, message, headers, new_url):
            if urlsplit(new_url).netloc != host:
                raise HTTPError(request.full_url, code, 'Unexpected external redirect: ' + new_url, headers, None)
            return super().redirect_request(request, response, code, message, headers, new_url)

    def fetch(path, parse=False):
        url = quote(urljoin(base + '/', path), safe=':/?=&%#@+')
        try:
            with build_opener(LocalRedirects()).open(Request(url, headers={'User-Agent': 'DyneLocalMigrationQA/1.0'}), timeout=30) as response:
                content_type = response.headers.get_content_type()
                body = read_text_response(response, parse)
                page = Page()
                if parse and content_type in TEXT_CONTENT_TYPES:
                    if content_type == 'text/css':
                        for groups in re.findall(r'''url\(\s*(?:"([^"]*)"|'([^']*)'|([^)]*))\s*\)''', body):
                            value = next((part for part in groups if part), '').strip()
                            if value and not value.startswith(('data:', '#')):
                                page.assets.add(urljoin(response.url, value))
                    else:
                        page.feed(body)
                return {'path': path, 'status': response.status, 'final_url': response.url, 'h1': page.h1,
                        'content_type': content_type,
                        'error_page': 'The website encountered an unexpected error' in body,
                        'assets': sorted(page.assets), 'links': sorted(page.links), 'ids': sorted(page.ids)}
        except (HTTPError, URLError, TimeoutError) as error:
            return {'path': path, 'status': getattr(error, 'code', 0), 'error': str(error)}

    routes = sorted(set(['/'] + [r['alias'] for r in data['records']]))
    with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:
        pages = list(pool.map(lambda path: fetch(path, True), routes))
    assets, links = set(), set()
    by_path = {p['path'].rstrip('/') or '/': p for p in pages}
    anchor_failures = set()
    for page in pages:
        for key, target in [('assets', assets), ('links', links)]:
            for value in page.get(key, []):
                parsed = urlsplit(urljoin(base + page['path'], value))
                if parsed.netloc == host and parsed.scheme in ('http', 'https'):
                    target.add(unquote(parsed.path))
                    fragment = unquote(parsed.fragment)
                    destination = by_path.get(unquote(parsed.path).rstrip('/') or '/')
                    if key == 'links' and fragment and fragment != '!' and destination and fragment not in destination.get('ids', []):
                        anchor_failures.add((page['path'], parsed.path + '#' + fragment))
    stylesheets = sorted(path for path in assets if path.endswith('.css'))
    with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:
        style_checks = list(pool.map(lambda path: fetch(path, True), stylesheets))
    for stylesheet in style_checks:
        for value in stylesheet.get('assets', []):
            parsed = urlsplit(value)
            if parsed.netloc == host and parsed.scheme in ('http', 'https'):
                assets.add(unquote(parsed.path))
    with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:
        checks = style_checks + list(pool.map(fetch, sorted((assets | links) - set(routes) - set(stylesheets))))
    failures = [p for p in pages + checks if not 200 <= p['status'] < 300 or p.get('error_page') or p.get('error')]
    result = {'base': base, 'imported_routes_checked': len(pages),
              'additional_local_assets_and_links_checked': len(checks),
              'failures': failures,
              'missing_internal_anchors': [{'from': source, 'target': target} for source, target in sorted(anchor_failures)],
              'headings': [{'path': p['path'], 'h1': p.get('h1', 0)} for p in pages],
              'routes': [{'path': p['path'], 'status': p['status'], 'final_url': p.get('final_url')} for p in pages],
              'source_missing_assets': data['assets']['missing'], 'source_skipped': data['skipped']}
    output = ROOT / args.output
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(json.dumps(result, indent=2) + '\n')
    print(json.dumps({k: v for k, v in result.items() if k not in ('headings', 'routes', 'source_missing_assets')}, indent=2))
    raise SystemExit(bool(failures or anchor_failures))


if __name__ == '__main__':
    main()
