#!/usr/bin/env python3
"""Compare current HTTP and read-only browser evidence for every audited URL.

This is a structural comparison, not the original PDF's unavailable pixel-score
formula. Exit nonzero for missing evidence, transport/server errors on usable
references, visible literal tags, overflow or broken local images. Text/geometry
differences remain explicit review items rather than being silently accepted.
"""

import argparse
from collections import Counter, defaultdict
import hashlib
import html
import json
from pathlib import Path
import re


def normalized(value):
    return re.sub(r'\s+', ' ', html.unescape(value)).strip()


def collector_compatibility(reference, local, key, root, reconciliation):
    """Require equal versions or an explicit, hash-bound v3/v4 scope proof."""
    versions = [reference.get('collectorVersion'), local.get('collectorVersion')]
    if any(not isinstance(value, int) for value in versions):
        return {'passed': False, 'versions': versions, 'reason': 'Missing collector version metadata.'}
    if versions[0] == versions[1]:
        return {'passed': True, 'versions': versions, 'reason': 'Same collector version.'}
    if versions != [3, 4] or reconciliation.get('versions') != [3, 4]:
        return {'passed': False, 'versions': versions, 'reason': 'Collector versions differ without a supported scope reconciliation.'}
    proof = reconciliation.get('records', {}).get(key, {})
    if not proof.get('v3_v4_scope_compatible') or proof.get('potential_added_text'):
        return {'passed': False, 'versions': versions, 'reason': 'The v4 text scope adds source nodes; recapture the reference using v4.'}
    # Scope reconciliation is evidence, not permission to relabel old captures.
    # Both the reviewed collector and its persisted source DOM must still match.
    for file_key, hash_key, record in (
        ('collector_v4_file', 'collector_v4_sha256', reconciliation),
        ('reference_http_file', 'reference_http_sha256', proof),
    ):
        relative = record.get(file_key)
        if not relative:
            return {'passed': False, 'versions': versions, 'reason': 'Scope reconciliation is missing its evidence file.'}
        path = root / relative
        if not path.is_file() or hashlib.sha256(path.read_bytes()).hexdigest() != record.get(hash_key):
            return {'passed': False, 'versions': versions, 'reason': 'Scope reconciliation evidence changed; regenerate the proof.'}
    return {'passed': True, 'versions': versions, 'reason': 'Explicit v3/v4 reconciliation: the reviewed scope adds no source text nodes.', 'scope_reconciled': True}


def compare(live, local):
    reference = defaultdict(list)
    candidate = defaultdict(list)
    for item in live['text']:
        reference[normalized(item['text'])].append(item)
    for item in local['text']:
        candidate[normalized(item['text'])].append(item)
    missing, extra, matched = [], [], []
    for text in reference.keys() | candidate.keys():
        a, b = reference[text], candidate[text]
        for first, second in zip(a, b):
            matched.append({
                'text': text,
                'reference_tag': first['tag'], 'local_tag': second['tag'],
                'delta': {key: round(second[key] - first[key], 2) for key in ('x', 'y', 'w', 'h')},
                'style_differences': {key: [first.get(key), second.get(key)] for key in ('fontFamily', 'fontSize', 'lineHeight', 'color', 'letterSpacing') if first.get(key) != second.get(key)},
            })
        missing.extend(x['text'] for x in a[len(b):])
        extra.extend(x['text'] for x in b[len(a):])
    return {
        'reference_height': live['height'], 'local_height': local['height'],
        'height_delta': local['height'] - live['height'],
        'reference_text_boxes': len(live['text']), 'local_text_boxes': len(local['text']),
        'matched_text_boxes': len(matched), 'missing_text': missing, 'extra_text': extra,
        'reference_overflow': live['overflow'], 'local_overflow': local['overflow'],
        'local_literal_tags': local['literalTags'],
        'reference_broken_images': live['brokenImages'], 'local_broken_images': local['brokenImages'],
        'reference_heroes': live['hero'], 'local_heroes': local['hero'],
        'matched_boxes': matched,
        'max_box_dimension_delta': max((abs(x['delta'][k]) for x in matched for k in ('x', 'w', 'h')), default=0),
        'max_vertical_delta': max((abs(x['delta']['y']) for x in matched), default=0),
        'style_difference_count': sum(bool(x['style_differences']) for x in matched),
        'viewports': [live['viewport'], local['viewport']],
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--evidence', type=Path, default=Path('output/live-local-remediation'))
    parser.add_argument('--allow-incomplete', action='store_true', help='Inventory an in-progress browser pass without treating missing captures as final failures.')
    args = parser.parse_args()
    root = args.evidence
    ledger = json.loads((root / 'source-ledger.json').read_text())['records']
    live_http = {r['id']: r for r in json.loads((root / 'http-live/http-verification.json').read_text())['records']}
    local_http = {r['id']: r for r in json.loads((root / 'http-local/http-verification.json').read_text())['records']}
    scope_path = root / 'capture-scope-reconciliation.json'
    reconciliation = json.loads(scope_path.read_text()) if scope_path.exists() else {}
    result = {'method': 'Exact normalized visible text, CSS/font values, box geometry and HTTP/media evidence; not a raster similarity score.', 'records': [], 'failures': [], 'missing_captures': []}
    for record in ledger:
        key = record['id']
        first, second = live_http[key], local_http[key]
        item = {'id': key, 'path': record['path'], 'original_findings': record['finding_flags'], 'live_status': first['status'], 'local_status': second['status'], 'kind': first['kind'], 'comparisons': {}}
        if first['status'] != 200:
            item['disposition'] = 'Upstream reference unavailable; preserve historical content where available.'
        else:
            if second['status'] != 200 or second['curl_exit'] or second.get('drupal_error_page') or second.get('literal_tags'):
                result['failures'].append({'id': key, 'phase': 'http', 'status': second['status'], 'error': second.get('error'), 'literal_tags': second.get('literal_tags')})
            item['disposition'] = 'Media destination verified separately.' if first['kind'] == 'media_destination' else 'Current HTML reference.'
            if first['kind'] == 'html_reference':
                for width, height in ((1440, 1000), (390, 844)):
                    paths = [root / folder / f'{key}-{width}.json' for folder in ('reference', 'local')]
                    if not all(p.exists() for p in paths):
                        result['missing_captures'].append({'id': key, 'width': width})
                        continue
                    captures = [json.loads(p.read_text()) for p in paths]
                    version_check = collector_compatibility(*captures, key, root, reconciliation)
                    comparison = compare(*captures)
                    comparison['collector_compatibility'] = version_check
                    item['comparisons'][str(width)] = comparison
                    if not version_check['passed']:
                        result['failures'].append({'id': key, 'width': width, 'phase': 'collector_version', **version_check})
                    if any(v != [width, height] for v in comparison['viewports']):
                        result['failures'].append({'id': key, 'width': width, 'phase': 'viewport', 'actual': comparison['viewports']})
                    for name in ('local_overflow', 'local_literal_tags', 'local_broken_images'):
                        if comparison[name]:
                            result['failures'].append({'id': key, 'width': width, 'phase': name, 'value': comparison[name]})
        result['records'].append(item)
    comparisons = [c for r in result['records'] for c in r['comparisons'].values()]
    result['summary'] = {
        'ledger_urls': len(ledger), 'live_status_counts': dict(Counter(str(r['live_status']) for r in result['records'])),
        'local_status_counts': dict(Counter(str(r['local_status']) for r in result['records'])),
        'browser_pairs': len(comparisons), 'expected_browser_pairs': 192,
        'exact_visible_text_pairs': sum(not c['missing_text'] and not c['extra_text'] for c in comparisons),
        'identical_height_pairs': sum(c['height_delta'] == 0 for c in comparisons),
        'height_within_2px_pairs': sum(abs(c['height_delta']) <= 2 for c in comparisons),
        'max_box_dimensions_within_2px_pairs': sum(c['max_box_dimension_delta'] <= 2 for c in comparisons),
        'zero_style_difference_pairs': sum(c['style_difference_count'] == 0 for c in comparisons),
        'zero_local_overflow_pairs': sum(not c['local_overflow'] for c in comparisons),
        'collector_scope_reconciled_pairs': sum(bool(c['collector_compatibility'].get('scope_reconciled')) for c in comparisons),
        'incompatible_collector_pairs': sum(not c['collector_compatibility']['passed'] for c in comparisons),
        'failure_count': len(result['failures']), 'missing_capture_count': len(result['missing_captures']),
    }
    result['passed'] = not result['failures'] and (args.allow_incomplete or not result['missing_captures'])
    (root / 'comparison.json').write_text(json.dumps(result, indent=2, ensure_ascii=False) + '\n')
    print(json.dumps(result['summary'], indent=2))
    for r in result['records']:
        for width, c in r['comparisons'].items():
            if c['missing_text'] or c['extra_text'] or abs(c['height_delta']) > 2 or c['max_box_dimension_delta'] > 2 or c['style_difference_count']:
                print(f"REVIEW {r['id']} {width} height={c['height_delta']} boxes={c['max_box_dimension_delta']} styles={c['style_difference_count']} missing={len(c['missing_text'])} extra={len(c['extra_text'])} {r['path']}")
    return not result['passed']


if __name__ == '__main__':
    raise SystemExit(main())
