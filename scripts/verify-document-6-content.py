#!/usr/bin/env python3
"""Compare public rendered content with the snapshot taken before native upgrades."""
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from urllib.request import urlopen
from urllib.parse import unquote
import json,re,difflib
from lxml import html
ROOT=Path(__file__).resolve().parents[1]
FIXTURE=json.loads((ROOT/'web/modules/custom/dyne_migrate/data/legacy-content.json').read_text())
OUT=ROOT/'output/document-6-review'

def normalized(markup):
    document=html.fromstring(markup)
    main=document.xpath('//main')[0]
    for item in main.xpath('.//script|.//style'):item.drop_tree()
    text=re.sub(r'\s+',' ',' '.join(main.itertext())).strip()
    links=[unquote(a) for a in main.xpath('.//a/@href')]
    images=[unquote(a) for a in main.xpath('.//img/@src')]
    return text,links,images

def check(row):
    before=(OUT/'before'/f"{row['source_id']}.html").read_bytes()
    with urlopen('http://dyne-tx-master.ddev.site'+row['alias'],timeout=45) as response: after=response.read()
    if b'The website encountered an unexpected error' in after:raise ValueError(row['alias'])
    (OUT/'after').mkdir(exist_ok=True)
    (OUT/'after'/f"{row['source_id']}.html").write_bytes(after)
    a,b=normalized(before),normalized(after)
    result={'route':row['alias'],'source_id':row['source_id'],'text_equal':a[0]==b[0], 'links_equal':a[1]==b[1], 'images_equal':a[2]==b[2], 'before_images':len(a[2]),'after_images':len(b[2])}
    if not result['text_equal']:
        diff=list(difflib.unified_diff(a[0].split(),b[0].split(),n=3))
        result['text_diff']='\n'.join(diff)[:3000]
    if not result['links_equal']:result['removed_links']=sorted(set(a[1])-set(b[1]));result['added_links']=sorted(set(b[1])-set(a[1]))
    if not result['images_equal']:result['removed_images']=sorted(set(a[2])-set(b[2]));result['added_images']=sorted(set(b[2])-set(a[2]))
    return result
with ThreadPoolExecutor(max_workers=4) as executor: results=list(executor.map(check,FIXTURE['records']))
report={'routes':len(results),'changed':[r for r in results if not all(r[k] for k in ['text_equal','links_equal','images_equal'])], 'results':results}
(OUT/'rendered-content-comparison.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps({'routes':len(results),'changed':report['changed']},indent=2))
