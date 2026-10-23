<?php
$db = \Drupal::database();
$report = [];
foreach (['node_revision' => ['vid', 'nid'], 'paragraphs_item_revision' => ['revision_id', 'id']] as $table => [$revision, $id]) {
  $report[$table] = $db->select($table, 'r')->fields('r')->orderBy($revision, 'DESC')->range(0, 32)->execute()->fetchAllAssoc($revision);
}
$report['test_nodes'] = $db->select('node_field_data', 'n')->fields('n')->condition('title', 'DYNE_STRUCTURED_TEST_%', 'LIKE')->execute()->fetchAllAssoc('nid');
$report['test_fields'] = [];
foreach (['node__field_role_summary', 'node__field_article_body', 'paragraph__field_section_body'] as $table) {
  $column = substr($table, strpos($table, '__') + 2) . '_value';
  $report['test_fields'][$table] = $db->select($table, 'f')->fields('f')->condition($column, '%DYNE_STRUCTURED_TEST_%', 'LIKE')->execute()->fetchAllAssoc('entity_id');
}
file_put_contents(dirname(DRUPAL_ROOT) . '/output/structured-model/test-residue-inspection.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
