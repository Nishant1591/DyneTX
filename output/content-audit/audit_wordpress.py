import collections, hashlib, html, importlib.util, json, pathlib, re
ROOT=pathlib.Path('/Users/nishant.anand/Downloads/dyne-tx-master')
SOURCE=pathlib.Path('/Users/nishant.anand/Downloads/site-archive-dynetx-live-1791770775-q7TsmjnzDlIdTIOsf5moGpH78az0yonIS07E/DyneTX')
spec=importlib.util.spec_from_file_location('extract',ROOT/'web/modules/custom/dyne_migrate/scripts/legacy/extract-archive.py'); extract=importlib.util.module_from_spec(spec);spec.loader.exec_module(extract)
sql=SOURCE/'wp-content/mysql.sql'; posts={r[0]:r for r in extract.rows(sql,'wp_posts')}
fixture=json.loads((ROOT/'web/modules/custom/dyne_migrate/data/legacy-content.json').read_text()); current={int(r['source_id']):r for r in fixture['records']}
counts=collections.Counter((r[20],r[7]) for r in posts.values()); metas=collections.defaultdict(dict)
for r in extract.rows(sql,'wp_postmeta'):
    if r[2] in {'_wp_attached_file','_wp_attachment_image_alt','_wp_page_template','_menu_item_type','_menu_item_url','_menu_item_object_id','_thumbnail_id','_position','_position_info','_department','_location','content_mask_url','content_mask_enable'}:
        metas[r[1]][r[2]]=r[3]
terms={r[0]:{'id':r[0],'name':r[1],'slug':r[2]} for r in extract.rows(sql,'wp_terms')}
taxonomies={r[0]:{'id':r[0],'term':terms.get(r[1]),'taxonomy':r[2],'description':r[3],'parent':r[4],'count':r[5]} for r in extract.rows(sql,'wp_term_taxonomy')}
relationships=collections.defaultdict(list)
for r in extract.rows(sql,'wp_term_relationships'): relationships[r[0]].append(r[1])
contenttypes={'page','post','news','jobs','team','perspectives','wb_quotes','wb_privacy_policy','comp_announcements','press-releases','wb_news_xml2','documentation','gc_templates','popupbuilder','shortcodesultimate','qr','seedprod'}
records=[]
for r in posts.values():
    if r[20] not in contenttypes|{'attachment','nav_menu_item','revision','custom_css','wp_global_styles'}: continue
    body=re.sub(r'\s+',' ',html.unescape(re.sub('<[^>]+>',' ',r[4]))).strip()
    record={'id':r[0],'type':r[20],'status':r[7],'password_protected':bool(r[10]),'title':html.unescape(r[5]),'slug':r[11],'date':r[2],'modified':r[14],'parent':r[17],'mime':r[21],'body_characters':len(r[4]),'text_characters':len(body),'excerpt_characters':len(r[6]),'in_current_fixture':r[0] in current,'alias':current.get(r[0],{}).get('alias'),'meta':metas.get(r[0],{}),'terms':[taxonomies[x] for x in relationships[r[0]] if x in taxonomies]}
    if r[20] not in {'revision','attachment','nav_menu_item'}: record['text_preview']=body[:400]
    records.append(record)
attachments=[]
for r in records:
    if r['type']!='attachment':continue
    path=r['meta'].get('_wp_attached_file','')
    local=SOURCE/'wp-content/uploads'/path
    dest=ROOT/'web/sites/default/files/legacy'/path
    attachments.append({'id':r['id'],'title':r['title'],'mime':r['mime'],'path':path,'source_exists':local.is_file(),'drupal_file_exists':dest.is_file(),'bytes':local.stat().st_size if local.is_file() else 0})
files=[]
for p in (SOURCE/'wp-content/uploads').rglob('*'):
    if p.is_file():
        rel=str(p.relative_to(SOURCE/'wp-content/uploads')); dest=ROOT/'web/sites/default/files/legacy'/rel
        files.append({'path':rel,'bytes':p.stat().st_size,'extension':p.suffix.lower(),'drupal_file_exists':dest.is_file(),'exact_size':dest.is_file() and p.stat().st_size==dest.stat().st_size})
report={'source_sha256':hashlib.sha256(sql.read_bytes()).hexdigest(),'total_wp_posts':len(posts),'counts':[{'type':t,'status':s,'count':c} for (t,s),c in sorted(counts.items())],'records':records,'taxonomies':list(taxonomies.values()),'attachments':attachments,'upload_files':files,'fixture_records':len(current),'fixture_menus':len(fixture['menus'])}
(ROOT/'output/content-audit/wordpress-audit.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({k:v for k,v in report.items() if k not in {'records','taxonomies','attachments','upload_files'}},indent=2))
print('ATTACHMENTS',len(attachments),'source missing',sum(not x['source_exists'] for x in attachments),'Drupal missing',sum(not x['drupal_file_exists'] for x in attachments))
print('UPLOAD FILES',len(files),'bytes',sum(x['bytes'] for x in files),'Drupal missing',sum(not x['drupal_file_exists'] for x in files))
print('TAXONOMIES',dict(collections.Counter(t['taxonomy'] for t in taxonomies.values())))
print('NONPUBLISHED CONTENT',json.dumps([r for r in records if r['type'] in contenttypes and (r['status']!='publish' or r['password_protected'])],ensure_ascii=False,indent=2))
