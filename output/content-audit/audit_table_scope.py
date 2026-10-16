import collections,importlib.util,json,pathlib,re
ROOT=pathlib.Path('/Users/nishant.anand/Downloads/dyne-tx-master');SRC=pathlib.Path('/Users/nishant.anand/Downloads/site-archive-dynetx-live-1791770775-q7TsmjnzDlIdTIOsf5moGpH78az0yonIS07E/DyneTX');SQL=SRC/'wp-content/mysql.sql'
spec=importlib.util.spec_from_file_location('extract',ROOT/'web/modules/custom/dyne_migrate/scripts/legacy/extract-archive.py');x=importlib.util.module_from_spec(spec);spec.loader.exec_module(x)
sql=SQL.read_text();schemas={m[1]:re.findall(r'^\s*`([^`]+)`',m[2],re.M) for m in re.finditer(r'CREATE TABLE `([^`]+)` \((.*?)\) ENGINE=',sql,re.S)}
counts=collections.Counter()
for line in sql.splitlines():
 m=re.match(r'INSERT INTO `([^`]+)` VALUES ',line)
 if not m:continue
 quoted=False;escape=False;depth=0
 for c in line[m.end():]:
  if escape:escape=False;continue
  if c=='\\' and quoted:escape=True;continue
  if c=="'":quoted=not quoted;continue
  if not quoted:
   if c=='(':
    if depth==0:counts[m[1]]+=1
    depth+=1
   elif c==')':depth-=1
selected=['wp_comments','wp_commentmeta','wp_links','wp_ctf_feed_caches','wp_ctf_feed_locator','wp_ctf_feeds','wp_ctf_feeds_posts','wp_ctf_posts','wp_gf_form','wp_gf_form_meta','wp_gf_form_revisions','wp_rg_form','wp_rg_form_meta','wp_sos_dqc_qrcodes']+[t for t in schemas if t.startswith('wp_wpgmza')]
comment_counts=collections.Counter()
for r in x.rows(SQL,'wp_comments'):comment_counts[(str(r[10]),str(r[12]))]+=1
option_names=[];widget_names=[]
for r in x.rows(SQL,'wp_options'):
 if r[1].startswith(('options_','widget_','theme_mods_')):
  option_names.append(r[1])
  if r[1].startswith('widget_'):widget_names.append(r[1])
content=json.loads((ROOT/'output/content-audit/wordpress-records.json').read_text())['records']
references=collections.defaultdict(list)
patterns={'comments':r'comments_template|wp_list_comments|comment_form','widgets':r'dynamic_sidebar|the_widget|wp_get_sidebars_widgets','maps':r'wpgmza|wpgmaps|\[wpgm','gallery':r'nextgen|nggallery|ngg_images|\[gallery|\[ngg','gravity_forms':r'gravityform|\[gform','twitter_feed':r'custom-twitter-feeds|\[ctf','links_blogroll':r'wp_list_bookmarks|get_bookmarks','direct_database_access':r'\$wpdb','menu':r'get_nav_menu_locations|wp_get_nav_menu'}
for f in (SRC/'wp-content/themes/dynetxdev2020').rglob('*.php'):
 for i,line in enumerate(f.read_text(errors='replace').splitlines(),1):
  for group,patt in patterns.items():
   if re.search(patt,line,re.I):references[group].append({'source':str(f.relative_to(SRC)),'line':i})
for r in content:
 if r['classification'] not in {'migrated_public_content','embedded_public_content','unpublished_editorial','unmigrated_editorial'}:continue
 text=r['content']+'\n'+'\n'.join(str(v or '') for v in r['meta'].values())
 for group,patt in patterns.items():
  if re.search(patt,text,re.I):references[group].append({'post_id':r['id'],'title':r['title'],'status':r['status']})
report={'source_sha256':json.loads((ROOT/'output/content-audit/wordpress-records.json').read_text())['source_sha256'],'table_count':len(schemas),'tables':[{'name':t,'columns':cols,'row_count':counts[t]} for t,cols in schemas.items()],'potential_content_tables':[{'name':t,'row_count':counts[t]} for t in selected],'comments_by_status_type':[{'approved':status,'type':kind,'count':n} for (status,kind),n in comment_counts.items()],'public_content_references':dict(references),'content_option_names_only':option_names,'notes':['Only table schemas, row counts, source code references and option names are exported. No users, credentials, option values, form submissions, comments, logs or security records are exported.','No NextGEN/gallery tables exist in this SQL dump.','Theme menus reference WordPress posts/terms; source pages/posts and editorial custom fields were already inventoried.']}
(ROOT/'output/content-audit/source-table-scope.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({k:v for k,v in report.items() if k!='tables'},ensure_ascii=False,indent=2))
