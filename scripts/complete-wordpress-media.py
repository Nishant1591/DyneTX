#!/usr/bin/env python3
"""Inventory WordPress attachments; copy safe uploads without changing existing files.

This script reads the SQL export as data, never executes it, and never changes
Drupal's database. Use --apply to copy files and --recover to attempt explicitly
recorded missing public originals. The JSON report remains outside the web root.
"""
import argparse
import collections
import datetime
import hashlib
import importlib.util
import json
import pathlib
import re
import shutil
import subprocess
import tempfile
import urllib.parse

PROJECT = pathlib.Path(__file__).resolve().parents[1]
MEDIA_EXTENSIONS = {'.jpg', '.jpeg', '.png', '.webp', '.gif', '.svg', '.pdf',
                    '.mp4', '.webm', '.mp3', '.wav', '.ogg', '.ico'}
EXCLUDED_DIRECTORIES = {
    'autoptimize': 'WordPress optimization cache and executable code',
    'sb-twitter-feed-images': 'Third-party social feed image cache',
}
ALLOWED_HOSTS = {'www.dyne-tx.com', 'dyne-tx.com',
                 'dynetxstaging.wpenginepowered.com', 'dynetxdev.wpengine.com'}


def digest(path):
    value = hashlib.sha256()
    with path.open('rb') as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b''):
            value.update(chunk)
    return value.hexdigest()


def file_info(path):
    if not path.is_file() or path.is_symlink():
        return {'exists': False, 'size': None, 'sha256': None}
    return {'exists': True, 'size': path.stat().st_size, 'sha256': digest(path)}


def safe_relative(value):
    value = str(value or '')
    path = pathlib.PurePosixPath(value)
    if not value or path.is_absolute() or '..' in path.parts or '\\' in value or '\0' in value:
        return None
    return path.as_posix()


def exclusion(path, relative):
    if path.is_symlink():
        return 'Symbolic link; never followed or published'
    for part in pathlib.PurePosixPath(relative).parts[:-1]:
        if part in EXCLUDED_DIRECTORIES:
            return EXCLUDED_DIRECTORIES[part]
    if path.suffix.lower() not in MEDIA_EXTENSIONS:
        return 'Not website media (code, configuration, logs, or other operational data)'
    return None


def signature_valid(path):
    """Reject error pages, code, and empty downloads posing as common media."""
    with path.open('rb') as stream:
        header = stream.read(1024)
    suffix = path.suffix.lower()
    if not header or b'<?php' in header.lower():
        return False
    if suffix == '.pdf':
        return header.startswith(b'%PDF-')
    if suffix in {'.jpg', '.jpeg'}:
        return header.startswith(b'\xff\xd8\xff')
    if suffix == '.png':
        return header.startswith(b'\x89PNG\r\n\x1a\n')
    if suffix == '.webp':
        return header[:4] == b'RIFF' and header[8:12] == b'WEBP'
    if suffix == '.gif':
        return header.startswith((b'GIF87a', b'GIF89a'))
    if suffix == '.mp4':
        return len(header) >= 12 and header[4:8] == b'ftyp'
    if suffix == '.svg':
        content = path.read_text(encoding='utf-8', errors='replace')
        return ('<svg' in content and not re.search(
            r'<(?:script|foreignObject)\b|\bon\w+\s*=|javascript:|<!ENTITY',
            content, re.IGNORECASE))
    return not header.lstrip().lower().startswith((b'<!doctype html', b'<html'))


def attachments(sql):
    loader = importlib.util.spec_from_file_location(
        'wordpress_sql_rows', PROJECT / 'web/modules/custom/dyne_migrate/scripts/legacy/extract-archive.py')
    module = importlib.util.module_from_spec(loader)
    loader.loader.exec_module(module)
    records = {}
    for row in module.rows(sql, 'wp_posts'):
        if row[20] == 'attachment':
            records[row[0]] = {
                'id': row[0], 'title': row[5], 'caption': row[6], 'description': row[4],
                'alt': '', 'mime': row[21], 'status_wordpress': row[7], 'parent_id': row[17],
                'created': row[2], 'modified': row[14], 'source_url': row[18], 'metadata': {},
            }
    for row in module.rows(sql, 'wp_postmeta'):
        if row[1] in records and row[2] in {
                '_wp_attached_file', '_wp_attachment_image_alt', '_wp_attachment_metadata'}:
            records[row[1]]['metadata'][row[2]] = row[3]
    for record in records.values():
        metadata = record.pop('metadata')
        record['alt'] = metadata.get('_wp_attachment_image_alt', '')
        record['relative_path'] = safe_relative(metadata.get('_wp_attached_file'))
        record['metadata_original'] = metadata.get('_wp_attachment_metadata', '')
        record['related_paths'] = []
        if record['relative_path']:
            parent = pathlib.PurePosixPath(record['relative_path']).parent
            for value in re.findall(r's:\d+:"(?:file|original_image)";s:\d+:"([^"]+)";',
                                    record['metadata_original']):
                candidate = safe_relative(value)
                if candidate:
                    # Main metadata file may contain the complete relative path.
                    related = candidate if '/' in candidate else (parent / candidate).as_posix()
                    if related != record['relative_path']:
                        record['related_paths'].append(related)
            record['related_paths'] = sorted(set(record['related_paths']))
    return sorted(records.values(), key=lambda record: record['id'])


def recover(record, target, remaining, attempts):
    """Fetch only a recorded uploads path, with a strict total transfer budget."""
    source = urllib.parse.urlsplit(record['source_url'])
    if source.hostname not in ALLOWED_HOSTS or not source.path.startswith('/wp-content/uploads/'):
        return 0
    # Domain replacement preserves the exact attachment path recorded by WordPress.
    candidates = list(dict.fromkeys([
        urllib.parse.urlunsplit(('https', source.netloc, source.path, '', '')),
        urllib.parse.urlunsplit(('https', 'www.dyne-tx.com', source.path, '', '')),
    ]))
    transferred = 0
    for url in candidates:
        encoded_url = urllib.parse.quote(url, safe=':/%')
        if remaining - transferred <= 0:
            break
        with tempfile.TemporaryDirectory(prefix='dyne-media-') as directory:
            download = pathlib.Path(directory) / target.name
            result = subprocess.run([
                'curl', '--silent', '--show-error', '--location', '--proto', '=https',
                '--proto-redir', '=https', '--max-redirs', '3', '--connect-timeout', '15',
                '--max-time', '120', '--max-filesize', str(remaining - transferred),
                '--output', str(download), '--write-out',
                '%{http_code}\n%{url_effective}\n%{content_type}\n%{size_download}', encoded_url,
            ], capture_output=True, text=True)
            lines = result.stdout.splitlines()
            size = download.stat().st_size if download.exists() else 0
            transferred += size
            attempt = {'attachment_id': record['id'], 'url': url,
                       'http_status': int(lines[0]) if lines and lines[0].isdigit() else None,
                       'effective_url': lines[1] if len(lines) > 1 else None,
                       'content_type': lines[2] if len(lines) > 2 else None,
                       'bytes': size, 'curl_exit': result.returncode,
                       'error': result.stderr.strip() or None, 'accepted': False}
            attempt['rejection_reason'] = (
                'Download failed' if result.returncode else
                'HTTP response was not 200' if attempt['http_status'] != 200 else
                'Redirect left the allowed original website hosts' if urllib.parse.urlsplit(attempt['effective_url']).hostname not in ALLOWED_HOSTS else
                'Response does not have the expected media signature' if not download.exists() or not signature_valid(download) else None)
            if (result.returncode == 0 and attempt['http_status'] == 200 and download.exists()
                    and urllib.parse.urlsplit(attempt['effective_url']).hostname in ALLOWED_HOSTS
                    and signature_valid(download)):
                target.parent.mkdir(parents=True, exist_ok=True)
                with target.open('xb') as stream, download.open('rb') as incoming:
                    shutil.copyfileobj(incoming, stream)
                attempt.update({'accepted': True, 'sha256': digest(target)})
            attempts.append(attempt)
            print(json.dumps({'recovery': record['id'], 'http_status': attempt['http_status'],
                              'bytes': size, 'accepted': attempt['accepted']}), flush=True)
            if attempt['accepted']:
                break
    return transferred


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('wp_content', type=pathlib.Path, help='Source wp-content directory containing mysql.sql and uploads')
    parser.add_argument('--destination', type=pathlib.Path, default=PROJECT / 'web/sites/default/files/legacy')
    parser.add_argument('--report', type=pathlib.Path, default=PROJECT / 'output/content-audit/wordpress-media.json')
    parser.add_argument('--archive-report', type=pathlib.Path, default=PROJECT / 'content-archive/wordpress-media.json')
    parser.add_argument('--known-media', type=pathlib.Path, default=PROJECT / 'web/modules/custom/dyne_migrate/data/legacy-recovered-media.json',
                        help='Previously recovered public-page media to verify against recorded size and SHA256')
    parser.add_argument('--apply', action='store_true', help='Copy only missing safe files; never overwrite')
    parser.add_argument('--recover', action='store_true', help='Recover missing originals from recorded public uploads URLs (requires --apply)')
    parser.add_argument('--download-limit-mb', type=int, default=300)
    args = parser.parse_args()
    if args.recover and not args.apply:
        parser.error('--recover requires --apply')
    uploads = (args.wp_content / 'uploads').resolve()
    target = args.destination.resolve()
    sql = args.wp_content / 'mysql.sql'
    records = attachments(sql)
    source_inventory, excluded, conflicts, attempts = [], [], [], []
    history, previous_attempts = [], []
    sql_hash = digest(sql)
    if args.report.exists():
        previous = json.loads(args.report.read_text(encoding='utf-8'))
        if previous.get('source_sql_sha256') == sql_hash and previous.get('local_uploads') == str(target):
            history = previous.get('history', [])
            previous_attempts = previous.get('recovery_attempts', [])
            if not history:
                history.append({'generated_at': previous['generated_at'], 'mode': previous['mode'], 'summary': previous['summary']})
    owners = collections.defaultdict(list)
    for record in records:
        for relative in [record['relative_path'], *record['related_paths']]:
            if relative:
                owners[relative].append(record['id'])
    copied = 0
    for path in sorted(uploads.rglob('*')):
        if not path.is_file() and not path.is_symlink():
            continue
        relative = path.relative_to(uploads).as_posix()
        reason = exclusion(path, relative)
        source_info = file_info(path)
        entry = {'relative_path': relative, 'source_path': str(path),
                 'attachment_ids': owners.get(relative, []), 'source': source_info}
        if not reason and not signature_valid(path):
            reason = 'Media signature invalid or active SVG content; not copied'
        if reason:
            entry.update({'status': 'excluded', 'reason': reason})
            excluded.append(entry)
        else:
            local = target / relative
            entry['local_path'] = str(local)
            if local.is_symlink():
                entry['status'] = 'conflict_symlink_preserved'
                conflicts.append(relative)
            elif local.exists():
                entry['status'] = 'present' if digest(local) == source_info['sha256'] else 'conflict_existing_preserved'
                if entry['status'].startswith('conflict'):
                    conflicts.append(relative)
            elif args.apply:
                local.parent.mkdir(parents=True, exist_ok=True)
                with local.open('xb') as outgoing, path.open('rb') as incoming:
                    shutil.copyfileobj(incoming, outgoing)
                entry['status'] = 'copied'
                copied += 1
            else:
                entry['status'] = 'missing_local'
            entry['local'] = file_info(local)
        source_inventory.append(entry)
    transferred = 0
    for record in records:
        relative = record['relative_path']
        record['source_path'] = str(uploads / relative) if relative else None
        record['local_path'] = str(target / relative) if relative else None
        record['local_uri'] = 'public://legacy/' + relative if relative else None
        record['local_url'] = '/sites/default/files/legacy/' + urllib.parse.quote(relative, safe='/') if relative else None
        if not relative:
            record.update({'status': 'missing_attached_file_metadata', 'source': {'exists': False}, 'local': {'exists': False}})
            continue
        source_info = file_info(uploads / relative)
        local_info = file_info(target / relative)
        if not source_info['exists'] and not local_info['exists'] and args.recover:
            transferred += recover(record, target / relative,
                                   args.download_limit_mb * 1024 * 1024 - transferred, attempts)
            local_info = file_info(target / relative)
        if local_info['exists'] and source_info['exists']:
            status = 'available' if local_info['sha256'] == source_info['sha256'] else 'conflict_existing_preserved'
        elif local_info['exists']:
            status = 'available_recovered' if any(a['accepted'] and a['attachment_id'] == record['id'] for a in [*previous_attempts, *attempts]) else 'available_existing_source_absent'
        else:
            status = 'missing_source_and_local' if not source_info['exists'] else 'missing_local'
        record.update({'status': status, 'source': source_info, 'local': local_info})
    local_inventory = []
    for path in sorted(target.rglob('*')):
        if path.is_file() and not path.is_symlink():
            relative = path.relative_to(target).as_posix()
            local_inventory.append({'relative_path': relative, 'local_path': str(path),
                                    'attachment_ids': owners.get(relative, []), **file_info(path)})
    original_paths = {r['relative_path'] for r in records if r['relative_path']}
    derivative_paths = {relative for r in records for relative in r['related_paths']} - original_paths
    safe_source_paths = {r['relative_path'] for r in source_inventory if r['status'] != 'excluded'}
    known_media = []
    local_by_path = {entry['local_path']: entry for entry in local_inventory}
    if args.known_media.exists():
        for record in json.loads(args.known_media.read_text(encoding='utf-8')).get('records', []):
            known_path = str((PROJECT / record['local_path']).resolve())
            actual = local_by_path.get(known_path)
            matches = bool(actual and actual['size'] == record['bytes'] and actual['sha256'] == record['sha256'])
            known_media.append({'source_url': record['source_url'], 'local_path': known_path,
                                'public_path': record['public_path'], 'aliases': record.get('aliases', []),
                                'expected_size': record['bytes'], 'expected_sha256': record['sha256'],
                                'local': actual or {'exists': False, 'size': None, 'sha256': None},
                                'status': 'verified' if matches else 'conflict_existing_preserved' if actual else 'missing_local'})
    summary = {
        'attachments': len(records), 'available_attachments': sum(r['local']['exists'] for r in records),
        'missing_source_attachment_ids': [r['id'] for r in records if not r['source']['exists']],
        'missing_local_attachment_ids': [r['id'] for r in records if not r['local']['exists']],
        'source_upload_files': len(source_inventory), 'safe_source_upload_files': len(source_inventory) - len(excluded),
        'safe_source_attachment_original_files': len(safe_source_paths & original_paths),
        'safe_source_attachment_derivative_files': len(safe_source_paths & derivative_paths),
        'safe_source_additional_upload_files': len(safe_source_paths - original_paths - derivative_paths),
        'excluded_upload_files': len(excluded), 'copied_this_run': copied,
        'conflicts': conflicts, 'local_upload_files': len(local_inventory),
        'local_upload_bytes': sum(i['size'] for i in local_inventory),
        'downloaded_bytes_this_run': transferred,
        'recovered_this_run': [a['attachment_id'] for a in attempts if a['accepted']],
        'previously_recovered_public_media_expected': len(known_media),
        'previously_recovered_public_media_verified': sum(r['status'] == 'verified' for r in known_media),
        'previously_recovered_public_media_problems': [r['public_path'] for r in known_media if r['status'] != 'verified'],
        'attachment_status_counts': dict(collections.Counter(r['status'] for r in records)),
    }
    generated = datetime.datetime.now(datetime.timezone.utc).isoformat()
    history.append({'generated_at': generated, 'mode': 'apply' if args.apply else 'audit', 'summary': summary})
    report = {'schema_version': 1, 'generated_at': generated,
              'source_sql': str(sql), 'source_sql_sha256': sql_hash, 'source_uploads': str(uploads),
              'local_uploads': str(target), 'mode': 'apply' if args.apply else 'audit',
              'policy': 'Read-only SQL parsing; safe media copied without overwrites; code, logs and plugin caches excluded; existing files preserved.',
              'summary': summary, 'attachments': records, 'source_upload_inventory': source_inventory,
              'local_upload_inventory': local_inventory, 'excluded_uploads': excluded,
              'recovery_attempts': [*previous_attempts, *attempts], 'history': history,
              'previously_recovered_public_media': known_media}
    content = json.dumps(report, ensure_ascii=False, indent=2) + '\n'
    for report_path in {args.report, args.archive_report}:
        report_path.parent.mkdir(parents=True, exist_ok=True)
        with tempfile.NamedTemporaryFile(mode='w', encoding='utf-8', dir=report_path.parent,
                                         prefix=report_path.name + '.', delete=False) as stream:
            stream.write(content)
            temporary = pathlib.Path(stream.name)
        temporary.replace(report_path)
    print(json.dumps(summary, indent=2), flush=True)


if __name__ == '__main__':
    main()
