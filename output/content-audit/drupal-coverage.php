<?php
// Read-only current entity/fixture audit. No entity or database writes.
$root = DRUPAL_ROOT . '/..';
$fixture = json_decode(file_get_contents(DRUPAL_ROOT . '/modules/custom/dyne_migrate/data/legacy-content.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$ledger = \Drupal::state()->get('dyne_migrate.legacy_import', []);
$manager = \Drupal::entityTypeManager();
$nodes = $manager->getStorage('node')->loadMultiple();
$paragraphs = $manager->getStorage('paragraph')->loadMultiple();
$node_counts = $paragraph_counts = [];
$aliases_by_path = [];
foreach ($manager->getStorage('path_alias')->loadMultiple() as $alias) $aliases_by_path[$alias->getPath()][] = $alias->getAlias();
foreach ($nodes as $n) {$key = $n->bundle() . ':' . ($n->isPublished() ? 'published' : 'unpublished'); $node_counts[$key] = ($node_counts[$key] ?? 0) + 1;}
foreach ($paragraphs as $p) $paragraph_counts[$p->bundle()] = ($paragraph_counts[$p->bundle()] ?? 0) + 1;
$records = [];
$covered = [];
$normalize = static function ($value) {return is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string) $value;};
$compare = static function ($entity, $name, $expected, &$issues, $label) use ($normalize) {
  if (!$entity->hasField($name)) {$issues[] = ['field'=>$label, 'issue'=>'field_missing']; return;}
  $actual = $entity->get($name)->value;
  if (in_array($name, ['field_items_json','field_rows_json','field_sections_json'], TRUE) && is_array($expected)) {
    $actual = json_decode((string) $actual, TRUE);
  }
  if ($normalize($actual) !== $normalize($expected)) $issues[] = ['field'=>$label, 'issue'=>'value_mismatch', 'expected_chars'=>strlen($normalize($expected)), 'actual_chars'=>strlen($normalize($actual)), 'expected_sha256'=>hash('sha256',$normalize($expected)), 'actual_sha256'=>hash('sha256',$normalize($actual))];
};
foreach ($fixture['records'] as $record) {
  $key = $record['source_type'] . ':' . $record['source_id'];
  $entry = $ledger[$key] ?? NULL;
  $node = $entry ? ($nodes[$entry['nid']] ?? NULL) : NULL;
  $issues = [];
  if (!$entry) $issues[] = ['issue'=>'missing_ledger'];
  if (!$node) $issues[] = ['issue'=>'missing_node'];
  $current_components = [];
  if ($node) {
    $covered[$node->id()] = TRUE;
    if ($node->bundle() !== $record['bundle']) $issues[] = ['issue'=>'bundle_mismatch'];
    if (!$node->isPublished()) $issues[] = ['issue'=>'unpublished'];
    if (!in_array($record['alias'], $aliases_by_path['/node/'.$node->id()] ?? [], TRUE)) $issues[] = ['issue'=>'alias_missing'];
    $compare($node, 'field_legacy_title', html_entity_decode($record['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), $issues, 'field_legacy_title');
    if (isset($record['body'])) {
      $compare($node, 'field_body_text', $record['body'], $issues, 'field_body_text');
      if ($record['bundle'] === 'leader') $compare($node, 'field_bio', $record['body'], $issues, 'field_bio');
    }
    foreach ($record['fields'] ?? [] as $name => $value) $compare($node, $name, $value, $issues, $name);
    if (isset($record['components'])) {
      $current_components = $node->get('field_components')->referencedEntities();
      if (count($record['components']) !== count($current_components)) $issues[] = ['issue'=>'component_count_mismatch','expected'=>count($record['components']),'actual'=>count($current_components)];
      foreach ($record['components'] as $index => $component) {
        $paragraph = $current_components[$index] ?? NULL;
        if (!$paragraph) {$issues[]=['issue'=>'component_missing','index'=>$index];continue;}
        if ($paragraph->bundle() !== $component['type']) $issues[]=['issue'=>'component_type_mismatch','index'=>$index];
        foreach ($component['fields'] as $name=>$value) $compare($paragraph,$name,$value,$issues,"components.$index.$name");
      }
    }
    if (!empty($record['role'])) $compare($node, 'field_role', strip_tags($record['role']), $issues, 'field_role');
    if (array_key_exists('link', $record)) {
      $expected = $record['link'] ? (str_starts_with($record['link'],'/') ? 'internal:'.$record['link'] : $record['link']) : NULL;
      if (($node->get('field_link')->uri ?: NULL) !== $expected) $issues[]=['issue'=>'link_mismatch','expected'=>$expected,'actual'=>$node->get('field_link')->uri];
    }
    if (!empty($record['photo'])) {
      $file=$node->get('field_image')->entity;
      $expected='public://'.rawurldecode(substr($record['photo'],strlen('/sites/default/files/')));
      if (!$file || $file->getFileUri() !== $expected) $issues[]=['issue'=>'image_mismatch'];
    }
  }
  $hash = hash('sha256', json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
  if ($entry && $entry['hash'] !== $hash) $issues[] = ['issue'=>'ledger_hash_mismatch'];
  $records[] = ['source_key'=>$key,'source_id'=>$record['source_id'],'source_type'=>$record['source_type'],'title'=>$record['title'],'alias'=>$record['alias'],'bundle'=>$record['bundle'],'nid'=>$node?->id(),'published'=>$node?->isPublished(),'components'=>array_values(array_map(fn($p)=>['id'=>$p->id(),'type'=>$p->bundle()], $current_components)),'issues'=>$issues];
}
$extra=[];
foreach ($nodes as $node) if (!isset($covered[$node->id()])) $extra[]=['nid'=>$node->id(),'bundle'=>$node->bundle(),'title'=>$node->label(),'published'=>$node->isPublished(),'aliases'=>$aliases_by_path['/node/'.$node->id()]??[]];
$menus=[];
foreach ($manager->getStorage('menu_link_content')->loadMultiple() as $m) $menus[]=['id'=>$m->id(),'menu'=>$m->getMenuName(),'title'=>$m->label(),'enabled'=>$m->isEnabled(),'parent'=>$m->getParentId(),'uri'=>$m->get('link')->uri];
$config=\Drupal::config('views.view.content')->getRawData();
$result=['audit'=>'Read-only Drupal current-entity versus public migration fixture','generated_at'=>date(DATE_ATOM),'node_total'=>count($nodes),'node_counts'=>$node_counts,'paragraph_total'=>count($paragraphs),'paragraph_counts'=>$paragraph_counts,'fixture_count'=>count($fixture['records']),'ledger_count'=>count($ledger),'matched_nodes'=>count($covered),'records_with_issues'=>count(array_filter($records,fn($r)=>$r['issues'])),'records'=>$records,'extra_nodes'=>$extra,'menus'=>$menus,'admin_content_view'=>$config];
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
