#!/usr/bin/env python3
"""Capture every PDF-ledger URL without submitting forms or parsing MP4 data.

Run on the host for live URLs, or inside DDEV with its local CA for local HTTPS.
Browser interaction/visual checks are recorded separately from these HTTP facts.
"""

import argparse
import concurrent.futures
from html.parser import HTMLParser
import json
from pathlib import Path
import re
import subprocess
from urllib.parse import quote


class HtmlFacts(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.hidden = 0
        self.text = []
        self.title = []
        self.in_title = False
        self.scripts = []

    def handle_starttag(self, tag, pairs):
        attrs = dict(pairs)
        if tag in ('script', 'style', 'head'):
            self.hidden += 1
        if tag == 'title':
            self.in_title = True
        if tag == 'script' and attrs.get('src'):
            self.scripts.append(attrs['src'])

    def handle_endtag(self, tag):
        if tag in ('script', 'style', 'head'):
            self.hidden = max(0, self.hidden - 1)
        if tag == 'title':
            self.in_title = False

    def handle_data(self, data):
        if self.in_title:
            self.title.append(data)
        if not self.hidden and data.strip():
            self.text.append(data)


def header_blocks(text):
    result = []
    for block in re.split(r'\r?\n\r?\n', text.strip()):
        lines = block.splitlines()
        if not lines or not lines[0].startswith('HTTP/'):
            continue
        fields = {}
        for line in lines[1:]:
            if ':' in line:
                key, value = line.split(':', 1)
                fields[key.lower()] = value.strip()
        result.append({'status': int(lines[0].split()[1]), 'headers': fields})
    return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--ledger', required=True, type=Path)
    parser.add_argument('--base', required=True)
    parser.add_argument('--output', required=True, type=Path)
    parser.add_argument('--ca-file', type=Path)
    parser.add_argument('--workers', type=int, default=5)
    args = parser.parse_args()
    if not args.base.startswith(('https://', 'http://')):
        parser.error('--base must be an HTTP(S) origin')
    records = json.loads(args.ledger.read_text())['records']
    args.output.mkdir(parents=True, exist_ok=True)

    def capture(record):
        path = record['path']
        if not path.startswith('/') or path.startswith('//'):
            raise ValueError('Only ledger-relative paths are allowed')
        slug = record['id'].lower()
        media = 'video_unavailable' in record['finding_flags']
        body = args.output / (slug + ('.headers-only' if media else '.html'))
        headers = args.output / (slug + '.headers')
        command = [
            'curl', '--noproxy', '*', '--location', '--silent', '--show-error',
            '--max-time', '40', '--retry', '2', '--retry-delay', '1',
            '--dump-header', str(headers),
            '--output', str(body), '--write-out', '%{http_code}\t%{url_effective}\t%{content_type}',
        ]
        if args.ca_file:
            command += ['--cacert', str(args.ca_file)]
        if media:
            command.append('--head')
        else:
            command += ['--max-filesize', '4000000']
        command.append(args.base.rstrip('/') + quote(path, safe='/%?=&+#'))
        completed = subprocess.run(command, text=True, capture_output=True)
        meta = completed.stdout.split('\t', 2)
        status = int(meta[0]) if meta and meta[0].isdigit() else 0
        result = {
            'id': record['id'], 'path': path, 'status': status,
            'final_url': meta[1] if len(meta) > 1 else '',
            'content_type': meta[2] if len(meta) > 2 else '',
            'kind': 'media_destination' if media else 'html_reference',
            'curl_exit': completed.returncode,
            'error': completed.stderr.strip(),
            'responses': header_blocks(headers.read_text(errors='replace')) if headers.exists() else [],
        }
        if not media and body.exists() and 'html' in result['content_type']:
            facts = HtmlFacts()
            facts.feed(body.read_text(errors='replace'))
            visible = ' '.join(facts.text)
            result.update({
                'title': ''.join(facts.title).strip(),
                'word_count': len(re.findall(r'\w+', visible)),
                'literal_tags': re.findall(r'<\/?(?:sup|sub|br)\b[^>]*>', visible, flags=re.I),
                'drupal_error_page': 'The website encountered an unexpected error' in visible,
                'userway_loaders': sum(src == 'https://cdn.userway.org/widget.js' for src in facts.scripts),
            })
        return result

    with concurrent.futures.ThreadPoolExecutor(max_workers=args.workers) as pool:
        results = list(pool.map(capture, records))
    summary = {
        'base': args.base, 'count': len(results),
        'media_destinations': sum(r['kind'] == 'media_destination' for r in results),
        'status_counts': {str(code): sum(r['status'] == code for r in results) for code in sorted({r['status'] for r in results})},
        'transport_errors': [r for r in results if r['curl_exit']],
        'drupal_errors': [r['path'] for r in results if r.get('drupal_error_page')],
        'literal_tag_paths': [r['path'] for r in results if r.get('literal_tags')],
        'records': results,
    }
    (args.output / 'http-verification.json').write_text(json.dumps(summary, indent=2) + '\n')
    print(json.dumps({k: v for k, v in summary.items() if k != 'records'}, indent=2))
    return bool(summary['transport_errors'] or summary['drupal_errors'])


if __name__ == '__main__':
    raise SystemExit(main())
