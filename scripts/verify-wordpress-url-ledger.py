#!/usr/bin/env python3
"""HEAD every unchanged/private alias and verified legacy redirect."""
import concurrent.futures,json,pathlib,urllib.request,urllib.error,urllib.parse
ROOT=pathlib.Path(__file__).resolve().parents[1]
BASE='http://dyne-tx-master.ddev.site'
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,req,fp,code,msg,headers,newurl):return None
ledger=json.loads((ROOT/'content-archive/wordpress-redirects.json').read_text())['url_ledger']
# Explicit privacy and tracking cases also cover subscriber behavior.
ledger += [{'source_path':'/','query':{'p':'2826'},'expected_status':404,'destination':'','disposition':'Private WordPress ID remains inaccessible'}, {'source_path':'/','query':{'p':'999999999999'},'expected_status':404,'destination':'','disposition':'Unknown WordPress ID is not the homepage'}, {'source_path':'/','query':{'p':'6','utm_source':'audit'},'expected_status':301,'destination':'/?utm_source=audit','disposition':'Tracking query retained without legacy numeric ID'}, {'source_path':'/','query':{},'expected_status':200,'destination':'/','disposition':'Homepage unchanged'}]
def check(row):
 url=BASE+row['source_path']+('?' + urllib.parse.urlencode(row['query']) if row.get('query') else '')
 try:
  r=urllib.request.build_opener(NoRedirect()).open(urllib.request.Request(url,method='HEAD'),timeout=40);status=r.status;location=r.headers.get('Location','')
 except urllib.error.HTTPError as e:status=e.code;location=e.headers.get('Location','')
 except Exception as e:return row|{'url':url,'error':str(e),'passed':False}
 location=location.removeprefix(BASE)
 ok=status==row['expected_status'] and (status!=301 or location==row['destination'])
 return row|{'url':url,'status':status,'location':location,'passed':ok}
with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:results=list(pool.map(check,ledger))
report={'checked':len(results),'passed':sum(x['passed'] for x in results),'failures':[x for x in results if not x['passed']],'records':results}
(ROOT/'output/document-6-review/url-ledger-verification.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps({k:v for k,v in report.items() if k!='records'},indent=2))
raise SystemExit(bool(report['failures']))
