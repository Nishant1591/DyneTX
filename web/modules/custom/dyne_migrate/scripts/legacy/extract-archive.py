#!/usr/bin/env python3
"""Read public content from a trusted local WordPress SQL export, never execute SQL."""
import argparse
import collections
import json
import pathlib
import re


def rows(sql_path, table):
    prefix = f'INSERT INTO `{table}` VALUES '
    escapes = {'0': '\0', 'n': '\n', 'r': '\r', 't': '\t', 'Z': '\x1a'}
    with open(sql_path, encoding='utf-8') as sql:
        for line in sql:
            if not line.startswith(prefix):
                continue
            data, i, size = line[len(prefix):], 0, len(line) - len(prefix)
            while i < size:
                if data[i] != '(':
                    i += 1
                    continue
                row = []
                i += 1
                while i < size:
                    if data[i] == "'":
                        i += 1
                        chars = []
                        while i < size:
                            char = data[i]
                            i += 1
                            if char == '\\':
                                char = data[i]
                                i += 1
                                chars.append(escapes.get(char, char))
                            elif char == "'":
                                if i < size and data[i] == "'":
                                    chars.append("'")
                                    i += 1
                                else:
                                    break
                            else:
                                chars.append(char)
                        row.append(''.join(chars))
                    else:
                        start = i
                        while i < size and data[i] not in ',)':
                            i += 1
                        token = data[start:i]
                        row.append(None if token == 'NULL' else int(token) if re.fullmatch(r'-?\d+', token) else token)
                    if data[i] == ')':
                        i += 1
                        yield row
                        break
                    if data[i] != ',':
                        raise ValueError(f'Unexpected SQL delimiter for {table}')
                    i += 1


def load_public(sql_path):
    allowed = {'page', 'jobs', 'perspectives', 'leadership', 'board', 'team', 'scientific', 'wb_quotes', 'wb_privacy_policy', 'news', 'post', 'nav_menu_item'}
    posts = {}
    attachments = {}
    inventory = collections.Counter()
    for row in rows(sql_path, 'wp_posts'):
        if row[7] == 'publish' and not row[10]:
            inventory[row[20]] += 1
            if row[20] in allowed:
                posts[row[0]] = {'id': row[0], 'type': row[20], 'title': row[5], 'slug': row[11], 'content': row[4], 'excerpt': row[6], 'date': row[2], 'modified': row[14], 'parent': row[17], 'order': row[19], 'meta': {}}
        elif row[20] == 'attachment':
            attachments[row[0]] = {'id': row[0], 'title': row[5], 'url': row[18]}
    meta_keys = collections.Counter()
    allowed_meta = {'_thumbnail_id', '_wp_page_template', '_position', '_position_info', '_department', '_location', '_apply_url', '_job_id_new', '_wb_last_updated', '_wb_last_updated_mode', '_wb_modification_date', '_wb_modification_date_mode', '_yoast_wpseo_metadesc', '_yoast_wpseo_title', 'first_line', 'second_line', 'third_line', 'hero_third_level', 'additional_body_class', 'page_secondary-image_thumbnail_id', 'page_hero-overlay-image_thumbnail_id', 'content_mask_url', 'content_mask_method', 'content_mask_enable', 'author', 'image'}
    for row in rows(sql_path, 'wp_postmeta'):
        if row[1] in posts:
            meta_keys[row[2]] += 1
            if row[2] in allowed_meta or row[2].startswith(('perspectives_', 'news_', '_menu_item_')):
                posts[row[1]]['meta'][row[2]] = row[3]
        elif row[1] in attachments and row[2] in {'_wp_attached_file', '_wp_attachment_image_alt'}:
            attachments[row[1]][row[2]] = row[3]
    terms = {r[0]: {'name': r[1], 'slug': r[2]} for r in rows(sql_path, 'wp_terms')}
    taxonomy = {r[0]: terms[r[1]] | {'taxonomy': r[2]} for r in rows(sql_path, 'wp_term_taxonomy') if r[2] in {'team_category', 'nav_menu'}}
    for row in rows(sql_path, 'wp_term_relationships'):
        if row[0] in posts and row[1] in taxonomy:
            posts[row[0]].setdefault('terms', []).append(taxonomy[row[1]])
    public_site = {r[1]: r[2] for r in rows(sql_path, 'wp_options') if r[1] in {'blogname', 'blogdescription'}}
    return {'public_site': public_site, 'posts': list(posts.values()), 'attachments': attachments, 'inventory': dict(inventory), 'meta_keys': dict(meta_keys)}


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('archive', type=pathlib.Path)
    parser.add_argument('--output', type=pathlib.Path, required=True)
    args = parser.parse_args()
    result = load_public(args.archive / 'wp-content/mysql.sql')
    args.output.write_text(json.dumps(result, ensure_ascii=False, indent=2))
    print(json.dumps({'published_types': result['inventory'], 'extracted_records': len(result['posts']), 'content_fields': result['meta_keys']}, indent=2))
