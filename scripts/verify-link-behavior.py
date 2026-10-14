#!/usr/bin/env python3
"""Audit all imported routes, redirect chains, link origins and new-tab targets.

No external destinations are followed. Optional --archive compares source link
hosts using only the allowlisted public WordPress export.
"""
import argparse,collections,concurrent.futures,importlib.util,json,re,sys
from pathlib import Path
from html.parser import HTMLParser
from urllib.parse import urlsplit,urljoin,unquote
from urllib.request import Request,build_opener,HTTPRedirectHandler
from urllib.error import HTTPError,URLError
ROOT=Path(__file__).resolve().parent.parent
args=argparse.ArgumentParser(description=__doc__)
args.add_argument('--base',default='http://dyne-tx-master.ddev.site')
args.add_argument('--output',default='output/route-link-classification.json')
args.add_argument('--archive',type=Path)
args=args.parse_args()
BASE=args.base.rstrip('/')
HOST=urlsplit(BASE).hostname
LEGACY={'www.dyne-tx.com','dyne-tx.com','dynetxdev.wpengine.com'}
VOID={'area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'}
verified_redirects=json.loads((ROOT/'web/modules/custom/dyne_migrate/data/legacy-verified-redirects.json').read_text())['urls']
fixture=json.loads((ROOT/'web/modules/custom/dyne_migrate/data/legacy-content.json').read_text())
routes=sorted({'/'}|{r['alias'] for r in fixture['records']})
class Page(HTMLParser):
 def __init__(self):super().__init__(convert_charrefs=True);self.links=[];self.stack=[];self.anchor=None;self.refresh=[];self.base=[]
 def handle_starttag(self,t,pairs):
  a=dict(pairs);zone=self.stack[-1][1] if self.stack else 'shell'
  if t in {'header','footer','main'}:zone=t
  if 'dyne-mobile-drawer' in a.get('class',''):zone='mobile_navigation'
  if t not in VOID:self.stack.append((t,zone))
  if t=='base':self.base.append(a)
  if t=='meta' and a.get('http-equiv','').lower()=='refresh':self.refresh.append(a.get('content',''))
  if (t=='a' and 'href' in a) or a.get('data-mdb-url'):
   self.anchor={'href':a.get('data-mdb-url') or a.get('href'),'id':a.get('id',''),'download':'download' in a,'target':a.get('target',''),'rel':a.get('rel',''),'zone':zone,'text':'','class':a.get('class',''),'source_attr':'data-mdb-url' if a.get('data-mdb-url') else 'href'};self.links.append(self.anchor)
 def handle_data(self,d):
  if self.anchor:self.anchor['text']+=d
 def handle_endtag(self,t):
  if t=='a':self.anchor=None
  for i in range(len(self.stack)-1,-1,-1):
   if self.stack[i][0]==t:self.stack=self.stack[:i];break
class Redirects(HTTPRedirectHandler):
 def __init__(self):self.chain=[]
 def redirect_request(self,req,fp,code,msg,headers,newurl):
  self.chain.append({'from':req.full_url,'to':newurl,'status':code})
  if urlsplit(newurl).hostname!=HOST:raise HTTPError(req.full_url,code,'External redirect blocked by read-only audit',headers,fp)
  return super().redirect_request(req,fp,code,msg,headers,newurl)
def fetch(route):
 rd=Redirects();out={'path':route};url=urljoin(BASE,route)
 try:
  with build_opener(rd).open(Request(url,headers={'User-Agent':'DyneRouteTargetAudit/1.0'}),timeout=35) as r:
   body=r.read().decode('utf-8','replace');p=Page();p.feed(body)
   out.update(status=r.status,final_url=r.url,links=p.links,meta_refresh=p.refresh,base_tags=p.base,error_page='The website encountered an unexpected error' in body)
 except Exception as e:out.update(status=getattr(e,'code',0),error=str(e),links=[])
 out['redirects']=rd.chain
 return out
def classify(link,route):
 value=link['href'];url=urlsplit(urljoin(BASE+route,value));host=(url.hostname or '').lower();path=unquote(url.path)
 if link.get('id')=='dyneExitContinue' and value=='#':kind='dialog_action'
 elif url.scheme not in {'http','https'}:kind=url.scheme or 'fragment'
 elif value in verified_redirects:kind='verified_internal_tracker'
 elif host in LEGACY:kind='legacy_internal_host'
 elif host!=HOST:kind='external'
 elif link.get('download') or re.search(r'\.(pdf|docx?|xlsx?|pptx?|zip|jpe?g|png|webp|svg|mp4|mp3)$',path,re.I):kind='local_document_or_media'
 else:kind='local_page_or_anchor'
 return dict(link,from_path=route,text=re.sub(r'\s+',' ',link['text']).strip()[:150],resolved=url.geturl(),host=host,path=path,kind=kind,new_context=link['target'].lower() not in {'','_self','_parent','_top'})
with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:pages=list(pool.map(fetch,routes))
links=[classify(l,p['path']) for p in pages for l in p['links']]
unique={}
for l in links:
 key=tuple(l[k] for k in ['href','target','zone','text']);row=unique.setdefault(key,dict(l,occurrences=0,from_paths=[]));row['occurrences']+=1
 if l['from_path'] not in row['from_paths']:row['from_paths'].append(l['from_path'])
rows=list(unique.values())
source_urls=collections.Counter()
source={'posts':[]}
if args.archive:
 spec=importlib.util.spec_from_file_location('public_archive',ROOT/'web/modules/custom/dyne_migrate/scripts/legacy/extract-archive.py')
 extractor=importlib.util.module_from_spec(spec);spec.loader.exec_module(extractor)
 source=extractor.load_public(args.archive/'wp-content/mysql.sql')
for p in source['posts']:
 page=Page();page.feed(p.get('content',''))
 for l in page.links:
  try:source_urls[urlsplit(l['href']).hostname or 'relative']+=1
  except ValueError:pass
out={'base':BASE,'route_count':len(pages),'link_occurrences':len(links),'unique_link_variants':len(rows),
 'classification_counts':dict(collections.Counter(l['kind'] for l in links)),
 'new_context_counts':dict(collections.Counter(l['kind'] for l in links if l['new_context'])),
 'legacy_internal_host_links':[l for l in rows if l['kind']=='legacy_internal_host'],
 'verified_internal_tracker_links':[l for l in rows if l['kind']=='verified_internal_tracker'],
 'local_new_context_links':[l for l in rows if l['new_context'] and l['kind'].startswith('local') and l.get('id')!='dyneExitContinue'],
 'external_host_counts':dict(collections.Counter(l['host'] for l in links if l['kind']=='external')),
 'source_content_host_counts':dict(source_urls),
 'unmapped_local_pages':[l for l in rows if l['kind']=='local_page_or_anchor' and (l['path'].rstrip('/') or '/') not in routes and not re.match(r'^/(node/\d+|user|sites/|themes/|modules/)',l['path'])],
 'route_failures':[dict(p,links=[]) for p in pages if p['status']!=200 or p.get('error_page')],
 'redirects':[dict(path=p['path'],chain=p['redirects']) for p in pages if p['redirects']],
 'meta_refresh':[dict(path=p['path'],values=p['meta_refresh']) for p in pages if p.get('meta_refresh')],
 'base_tags':[dict(path=p['path'],values=p['base_tags']) for p in pages if p.get('base_tags')],
 'links':rows}
evidence_path=ROOT/'output/tracking-link-resolution.json'
if evidence_path.exists():
 evidence=json.loads(evidence_path.read_text())
 out['external_tracking_audit']={'unique_candidates':evidence.get('unique_candidates'),'status_counts':evidence.get('status_counts'),'note':'External provider destinations are retained. Opaque attachment responses were not interpreted as first-party routes.'}
(ROOT/args.output).write_text(json.dumps(out,indent=2)+'\n')
issues={
 'route_failures':len(out['route_failures']),
 'legacy_host_page_links':len(out['legacy_internal_host_links']),
 'verified_internal_tracker_links':len(out['verified_internal_tracker_links']),
 'internal_page_new_tab_links':sum(l['kind']=='local_page_or_anchor' for l in out['local_new_context_links']),
 'unmapped_local_pages':len(out['unmapped_local_pages']),
 'meta_refresh':len(out['meta_refresh']),
 'base_tags':len(out['base_tags']),
}
print(json.dumps({k:out[k] for k in ['route_count','link_occurrences','unique_link_variants','classification_counts','new_context_counts']}|{'issues':issues,'report':args.output},indent=2))
raise SystemExit(any(issues.values()))
