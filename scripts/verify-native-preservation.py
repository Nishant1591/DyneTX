#!/usr/bin/env python3
"""Reconcile intentional native-field changes against the pre-upgrade snapshot."""
from pathlib import Path
import collections,json
root=Path(__file__).resolve().parents[1];out=root/'output/document-6-review'
a=json.loads((out/'entities-before.json').read_text());b=json.loads((out/'entities-after.json').read_text())
revision={'vid','revision_timestamp','revision_uid','revision_log','changed','revision_translation_affected'}
# New source IDs/taxonomy references are independently source-verified by
# verify-native-content.php; old source text values and all bodies are retained.
expected=revision|{'field_components','field_department','field_location','field_leader_type','field_wp_source_id'}
fail=[]
for nid,node in a['nodes'].items():
 if nid not in b['nodes']:fail.append({'node':nid,'reason':'missing'});continue
 for field,value in node.items():
  if field not in expected and b['nodes'][nid].get(field)!=value:fail.append({'node':nid,'field':field})
for kind in ['menus','aliases']:
 for eid,entity in a[kind].items():
  if b[kind].get(eid)!=entity:fail.append({'entity_type':kind,'id':eid})
changed=[]
for nid,node in a['nodes'].items():
 if [i['target_id'] for i in node.get('field_components',[])] != [i['target_id'] for i in b['nodes'][nid].get('field_components',[])]:changed.append({'nid':nid,'title':node['title'][0]['value']})
report={'original_nodes_retained':len(a['nodes']),'current_nodes':len(b['nodes']),'media':len(b['media']),'files':len(b['files']),'menus_preserved':len(a['menus']),'aliases_preserved':len(a['aliases']),'component_ids_changed_on':changed,'unchanged_original_body_title_status_fields':not fail,'unexpected_changes':fail,'node_types':dict(collections.Counter(x['type'][0]['target_id'] for x in b['nodes'].values())),'status':dict(collections.Counter(str(x['status'][0]['value']) for x in b['nodes'].values()))}
(out/'final-preservation-verification.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps(report,indent=2))
if fail:raise SystemExit(1)
