import collections,hashlib,html,importlib.util,json,pathlib,re
ROOT=pathlib.Path('/Users/nishant.anand/Downloads/dyne-tx-master');SOURCE=pathlib.Path('/Users/nishant.anand/Downloads/site-archive-dynetx-live-1791770775-q7TsmjnzDlIdTIOsf5moGpH78az0yonIS07E/DyneTX');SQL=SOURCE/'wp-content/mysql.sql'
spec=importlib.util.spec_from_file_location('extract',ROOT/'web/modules/custom/dyne_migrate/scripts/legacy/extract-archive.py');m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)
fixture=json.loads((ROOT/'web/modules/custom/dyne_migrate/data/legacy-content.json').read_text()); imported={int(r['source_id']):r for r in fixture['records']};menumap={int(r['source_id']):r for r in fixture['menus']}
operational={'iwp_log','wpa-stats'}
configuration={'_pods_field','_pods_pod','acf-field','acf-field-group','custom_css','display_type','gal_display_source','lightbox_library','gravityview','gc_templates','seedprod','shortcodesultimate','wp_global_styles'}
editorial={'page','post','news','jobs','team','perspectives','wb_quotes','wb_privacy_policy','comp_announcements','press-releases','wb_news_xml2','documentation','popupbuilder','qr'}
allowed_meta={'_thumbnail_id','_wp_page_template','_position','_position_info','_department','_location','_apply_url','_job_id_new','_job_full_details','_orig_job_id','_wb_last_updated','_wb_last_updated_mode','_wb_modification_date','_wb_modification_date_mode','_yoast_wpseo_metadesc','_yoast_wpseo_title','_yoast_wpseo_opengraph-image','_yoast_wpseo_twitter-image','first_line','second_line','third_line','hero_third_level','additional_body_class','page_secondary-image_thumbnail_id','page_hero-overlay-image_thumbnail_id','content_mask_url','content_mask_method','content_mask_enable','author','image','external_url','unity_pub_source','unity_pub_external_url','unity_pub_date','_wp_attached_file','_wp_attachment_image_alt','_wp_attachment_metadata','_wp_attachment_backup_sizes','su_slide_link','_wp_old_slug','_wp_old_date','_yoast_wpseo_primary_category','source','post-link','url','label-text'}
posts={r[0]:r for r in m.rows(SQL,'wp_posts')};meta=collections.defaultdict(dict);dupes=collections.defaultdict(dict)
for row in m.rows(SQL,'wp_postmeta'):
    pid,key,val=row[1:];p=posts.get(pid)
    if not p or p[20] in operational|configuration:continue
    if key in allowed_meta or key.startswith(('perspectives_','news_','_menu_item_','flexible_content')):
        if key in meta[pid]:dupes[pid].setdefault(key,[meta[pid][key]]).append(val)
        meta[pid][key]=val
terms={r[0]:{'id':r[0],'name':r[1],'slug':r[2]} for r in m.rows(SQL,'wp_terms')};tax={r[0]:terms[r[1]]|{'taxonomy_id':r[0],'taxonomy':r[2],'description':r[3],'parent':r[4],'count':r[5]} for r in m.rows(SQL,'wp_term_taxonomy')};relationships=collections.defaultdict(list)
for r in m.rows(SQL,'wp_term_relationships'):
    if r[1] in tax:relationships[r[0]].append(tax[r[1]])
records=[]
for id,p in posts.items():
    kind=p[20];classification='unclassified'; disposition=''
    if id in imported: classification='migrated_public_content';disposition='Imported as '+imported[id]['bundle']+' at '+imported[id]['alias']
    elif kind=='nav_menu_item': classification='navigation';disposition='Active primary menu migrated' if id in menumap else 'Unused source mobile menu item; preserve separately'
    elif kind=='attachment':classification='media';disposition='Source attachment catalog; physical availability checked separately'
    elif kind=='revision':classification='revision_history';disposition='Historical content; preserve as history without publishing'
    elif kind in operational:classification='operational_log';disposition='Not website content; body and metadata deliberately excluded'
    elif kind in configuration:classification='plugin_configuration';disposition='WordPress plugin/theme definition, not Drupal page content; body and metadata deliberately excluded'
    elif kind in {'wb_privacy_policy','wb_quotes'}:classification='embedded_public_content';disposition='Expanded into existing Drupal privacy page or careers carousel'
    elif kind=='wb_news_xml2':classification='legacy_unrelated_feed';disposition='Legacy Jazz Pharmaceuticals feed; preserve unpublished, do not add to Dyne news'
    elif kind in editorial:
        classification='unpublished_editorial' if p[7]!='publish' else 'unmigrated_editorial'
        disposition='Preserve original visibility without exposing archived test/duplicate/placeholder content publicly'
    elif kind=='redirect_rule':classification='legacy_redirect';disposition='Archived WordPress redirect definition; review target before enabling'
    suppressed=kind in operational|configuration
    record={'id':id,'type':kind,'status':p[7],'password_protected':bool(p[10]),'title':('Operational entry #'+str(id)) if kind in operational else p[5],'slug':p[11] if kind not in operational else '', 'parent':p[17],'date':p[2],'modified':p[14],'order':p[19],'mime':p[21],'content':'' if suppressed else p[4],'excerpt':'' if suppressed else p[6],'content_suppressed':suppressed,'source_content_characters':len(p[4]),'meta':meta.get(id,{}),'terms':relationships[id],'classification':classification,'disposition':disposition,'drupal_alias':imported.get(id,{}).get('alias'),'drupal_bundle':imported.get(id,{}).get('bundle')}
    if id in dupes:record['duplicate_meta_values']=dupes[id]
    if kind=='attachment': record['asset_url']=p[18]
    records.append(record)
assert all(r['classification']!='unclassified' for r in records)
result={'source':str(SOURCE),'source_sha256':hashlib.sha256(SQL.read_bytes()).hexdigest(),'total_records':len(records),'counts':dict(collections.Counter(r['classification'] for r in records)),'privacy_notes':['No users, passwords, form submissions, operational log bodies, plugin options, or credentials exported.','Source private/draft/revision content is retained for local preservation; do not publish those records.'],'records':records,'taxonomies':list(tax.values())}
(ROOT/'output/content-audit/wordpress-records.json').write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n')
print(json.dumps({'total':len(records),'classifications':result['counts'],'size_bytes':(ROOT/'output/content-audit/wordpress-records.json').stat().st_size},indent=2))
