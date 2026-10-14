#!/usr/bin/env python3
"""Validate dyne_migrate CSVs + YAML + theme scaffold (no PHP needed)."""
import csv, sys, pathlib
root = pathlib.Path('/home/ubuntu/projects/dyne-tx/web/modules/custom/dyne_migrate')
errors = []
for name, key in [('data/pages.csv','Slug'),('data/jobs-sample.csv','Slug'),('data/perspectives.csv','Slug'),('data/leaders.csv','Name')]:
    p = root / name
    rows = list(csv.DictReader(p.open()))
    print(f'{name}: {len(rows)} rows, cols={rows[0].keys() if rows else []}')
    if not rows: errors.append(f'{name} empty')
    for i,r in enumerate(rows,2):
        if not r.get(key): errors.append(f'{name} line {i} missing {key}')
maps = list(csv.DictReader((root/'data/url-map.csv').open()))
print(f'url-map.csv: {len(maps)} rules')
for m in maps:
    assert m['wp_url'].startswith('https://www.dyne-tx.com'), m
# check YAML exist
for y in ['migrations/dyne_pages.yml','migrations/dyne_jobs.yml','migrations/dyne_perspectives.yml','migrations/dyne_leaders.yml']:
    assert (root/y).exists(), y
    print(f'OK {y}')
# check theme
for t in ['../../../themes/custom/dynetx/dynetx.info.yml','../../../themes/custom/dynetx/templates/page.html.twig','../../../themes/custom/dynetx/css/style.css']:
    assert (root/t).exists(), t
if errors:
    print('FAIL', errors); sys.exit(1)
print('ALL VALIDATION PASSED')
