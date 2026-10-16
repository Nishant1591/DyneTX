#!/usr/bin/env python3
"""Create an evidence-only URL ledger. Never infer redirects from title changes."""
import argparse,collections,importlib.util,json,pathlib,urllib.parse
ROOT=pathlib.Path(__file__).resolve().parents[1]
p=argparse.ArgumentParser();p.add_argument('archive',type=pathlib.Path);a=p.parse_args()
spec=importlib.util.spec_from_file_location('wp',ROOT/'web/modules/custom/dyne_migrate/scripts/legacy/extract-archive.py');wp=importlib.util.module_from_spec(spec);spec.loader.exec_module(wp)
source=json.loads((ROOT/'content-archive/wordpress-records.json').read_text())
public=json.loads((ROOT/'web/modules/custom/dyne_migrate/data/legacy-content.json').read_text())['records']
supplement=json.loads((ROOT/'content-archive/wordpress-supplement.json').read_text())['records']
records=public+supplement; byid={r['source_id']:r for r in records};aliases={r['alias'] for r in records}
ledger=[];candidates=[];excluded=[]
for r in records:
 ledger.append({'source_id':r['source_id'],'source_path':r['alias'],'destination':'/' if r['alias']=='/home' else r['alias'],'expected_status':301 if r['alias']=='/home' else (200 if r.get('published',True) else 403),'disposition':'Drupal canonical front page alias' if r['alias']=='/home' else ('unchanged alias; no redirect required' if r.get('published',True) else 'preserved unpublished; access intentionally restricted')})
 if r.get('published',True):
  target='/' if r['alias']=='/home' else r['alias']
  for param in ['p']+(['page_id'] if r['source_type']=='page' else []):
   candidates.append({'source':'','query':{param:str(r['source_id'])},'target':target,'source_id':r['source_id'],'evidence':'WordPress canonical numeric post URL','status':301})
for r in source['records']:
 old=r['meta'].get('_wp_old_slug')
 if not old or r['id'] not in byid:continue
 dest=byid[r['id']]
 if not dest.get('published',True):continue
 path=dest['alias'].rsplit('/',1)[0]+'/'+old
 if path in aliases:
  excluded.append({'source':path,'target':dest['alias'],'reason':'Historical slug is an active alias; preserve existing route'})
 else:candidates.append({'source':path.lstrip('/'),'query':{},'target':dest['alias'],'source_id':r['id'],'evidence':'WordPress _wp_old_slug metadata','status':301})
for r in wp.rows(a.archive/'wp-content/mysql.sql','wp_redirection_items'):
 item={'source':r[1],'target':r[12],'source_rule_id':r[0]}
 if r[9]!='enabled':excluded.append(item|{'reason':'Source WordPress redirect was disabled'})
 elif r[12].rstrip('/') not in aliases:excluded.append(item|{'reason':'No verified Drupal destination'})
 elif r[1].rstrip('/')==r[12].rstrip('/'):excluded.append(item|{'reason':'Self redirect'})
 else:candidates.append(item|{'source':r[1].strip('/'),'query':{},'status':int(r[11]),'evidence':'Enabled WordPress redirect rule'})
counts=collections.Counter((x['source'],tuple(sorted(x['query'].items()))) for x in candidates)
assert all(v==1 for v in counts.values()),'Conflicting redirect sources'
for r in candidates:ledger.append({'source_id':r.get('source_id'),'source_path':'/'+r['source'],'query':r['query'],'destination':r['target'],'expected_status':301,'disposition':r['evidence']})
media=json.loads((ROOT/'content-archive/wordpress-media.json').read_text())
assets=[]
for item in media['local_upload_inventory']:
 rel=item['relative_path']
 if not item['exists'] or rel.startswith('/') or '..' in rel.split('/'):continue
 source_path='/wp-content/uploads/'+urllib.parse.quote(rel,safe='/')
 target='/sites/default/files/legacy/'+urllib.parse.quote(rel,safe='/')
 assets.append({'source':source_path,'target':target,'relative_path':rel,'sha256':item['sha256']})
 ledger.append({'source_path':source_path,'destination':target,'expected_status':301,'disposition':'Verified WordPress upload moved to Drupal public files'})
for item in media['attachments']:
 rel=item.get('relative_path','')
 if rel and not (ROOT/'web/sites/default/files/legacy'/rel).is_file():
  ledger.append({'source_id':item['id'],'source_path':'/wp-content/uploads/'+urllib.parse.quote(rel,safe='/'),'destination':'','expected_status':404,'disposition':'Original attachment is missing from archive and original host; no false redirect'})
out={'asset_redirects':assets,'source_sha256':source['source_sha256'],'aliases':len(records),'redirects':candidates,'excluded':excluded,'url_ledger':ledger}
target=ROOT/'content-archive/wordpress-redirects.json';target.write_text(json.dumps(out,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'aliases':len(records),'redirects':len(candidates),'media_redirects':len(assets),'excluded':excluded},indent=2))
