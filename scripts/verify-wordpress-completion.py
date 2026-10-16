#!/usr/bin/env python3
"""Verify additive content preservation, visibility and every managed attachment."""
import concurrent.futures
import json
from pathlib import Path
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
AUDIT = ROOT / 'output/content-audit'
BASE = 'http://dyne-tx-master.ddev.site'


def read(path):
    return json.loads(path.read_text())


def php_json(value):
    """PHP's associative JSON decode represents an empty object as an array."""
    if isinstance(value, dict):
        return {key: php_json(item) for key, item in value.items()} if value else []
    if isinstance(value, list):
        return [php_json(item) for item in value]
    return value


def main():
    before = read(AUDIT / 'drupal-preservation-before.json')
    after = read(AUDIT / 'drupal-preservation-after.json')
    completion = read(AUDIT / 'content-completion.json')
    source = read(ROOT / 'content-archive/wordpress-records.json')
    supplement = read(ROOT / 'content-archive/wordpress-supplement.json')
    report = {'failures': [], 'http_checks': [], 'source_records': len(source['records']), 'original_entities_unchanged': True}
    for group in ['nodes', 'paragraphs', 'files', 'menus', 'aliases']:
        for ident, old in before[group].items():
            new = after[group].get(ident, {})
            changed = [field for field, value in old.items() if new.get(field) != value]
            if changed:
                report['failures'].append({'entity': group, 'id': ident, 'changed_fields': changed})
                report['original_entities_unchanged'] = False
    if before['legacy_ledger'] != after['legacy_ledger']:
        report['failures'].append('Original migration ledger changed')
    source_ids = [p['id'] for p in source['records']]
    assert len(source_ids) == len(set(source_ids)) == source['total_records']
    report['new_status_counts'] = {'published': 0, 'unpublished': 0}
    tests = [('/', {200}), ('/about-us', {200}), ('/content-archive/wordpress-records.json', {403, 404})]
    for item in supplement['records']:
        mapping = completion['records'][str(item['source_id'])]
        node = after['nodes'][str(mapping['nid'])]
        status = bool(int(node['status'][0]['value']))
        report['new_status_counts']['published' if status else 'unpublished'] += 1
        if status != item['published']:
            report['failures'].append({'source_id': item['source_id'], 'problem': 'Publication status differs'})
        if int(node['field_wp_source_id'][0]['value']) != item['source_id']:
            report['failures'].append({'source_id': item['source_id'], 'problem': 'Source ID differs'})
        if php_json(json.loads(node['field_wp_source_data'][0]['value'])) != php_json(item['source_data']):
            report['failures'].append({'source_id': item['source_id'], 'problem': 'Archived source content differs'})
        if (node['field_body_text'][0]['value'] if node['field_body_text'] else '') != item['body']:
            report['failures'].append({'source_id': item['source_id'], 'problem': 'Imported body differs'})
        tests.append((mapping['alias'], {200} if status else {403, 404}))
    for item in completion['media'].values():
        tests.append((item['local_path'], {200}))

    def check(test):
        path, expected = test
        try:
            req = urllib.request.Request(BASE + path, method='HEAD')
            with urllib.request.urlopen(req, timeout=25) as response:
                status, url = response.status, response.url
        except urllib.error.HTTPError as error:
            status, url = error.code, error.url
        except Exception as error:
            return {'path': path, 'error': str(error), 'ok': False}
        return {'path': path, 'status': status, 'ok': status in expected and url.startswith(BASE + '/')}

    with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:
        report['http_checks'] = list(pool.map(check, tests))
    report['failures'].extend(item for item in report['http_checks'] if not item['ok'])
    report['source_missing_assets'] = completion['missing']
    report['available_content_verified'] = not report['failures']
    report['node_total'] = len(after['nodes'])
    report['managed_files'] = len(after['files'])
    (AUDIT / 'completion-verification.json').write_text(json.dumps(report, indent=2) + '\n')
    print(json.dumps({key: value for key, value in report.items() if key not in ['http_checks', 'source_missing_assets']}, indent=2))
    print('HTTP checks:', len(tests), 'Known missing source files:', len(completion['missing']))
    raise SystemExit(bool(report['failures']))


if __name__ == '__main__':
    main()
