<?php
/** Reversible recovery: retain every entity and revision; restore through new revisions. */
$root = dirname(DRUPAL_ROOT);
$proof = json_decode(file_get_contents($root . '/output/structured-model/test-recovery-dry-run.json'), TRUE, 512, JSON_THROW_ON_ERROR);
if ($proof['unique_marker'] !== 'DYNE_STRUCTURED_TEST_0c0e0dd306_') throw new RuntimeException('Wrong verification provenance.');
$manager = \Drupal::entityTypeManager();
$report = ['recovery_mode' => 'new_revision_restoration_and_unpublish_only', 'entities_deleted' => 0, 'revisions_deleted' => 0, 'restored' => [], 'unpublished_test_nodes' => []];
$expected = [];
foreach ($proof['originals'] as $record) {
  $storage = $manager->getStorage($record['type']);
  $original = $storage->loadRevision($record['prior_revision']);
  $current = $storage->load($record['id']);
  if (!$original || !$current) throw new RuntimeException('Missing reversible recovery entity.');
  $expected[$record['type']][$record['id']] = clone $original;
  foreach ($original->getFieldDefinitions() as $name => $definition) {
    if (str_starts_with($name, 'field_') || in_array($name, ['title', 'status', 'created', 'changed', 'uid', 'parent_id', 'parent_type', 'parent_field_name'], TRUE)) $current->set($name, $original->get($name)->getValue());
  }
  $current->setNewRevision(TRUE);
  $current->setSyncing(TRUE);
  if (method_exists($current, 'setRevisionLogMessage')) $current->setRevisionLogMessage('Restore unchanged source content after interrupted editorial verification; retain all history.');
  $current->save();
  $report['restored'][] = ['type' => $record['type'], 'id' => $record['id'], 'source_revision_preserved' => $record['prior_revision'], 'restoration_revision' => (int) $current->getRevisionId()];
}
$storage = $manager->getStorage('node');
foreach ([480, 481, 482, 483] as $id) {
  $node = $storage->load($id);
  if (!$node || !str_starts_with($node->label(), $proof['unique_marker'])) throw new RuntimeException('Unpublish candidate has no exact test provenance.');
  $node->setUnpublished();
  $node->setNewRevision(TRUE);
  $node->setRevisionLogMessage('Hide temporary verification content; retain entity and revision history.');
  $node->setSyncing(TRUE);
  $node->save();
  $report['unpublished_test_nodes'][] = $id;
}
foreach ($expected as $type => $entities) {
  $storage = $manager->getStorage($type); $storage->resetCache();
  foreach ($entities as $id => $original) {
    $current = $storage->load($id);
    foreach ($original->getFieldDefinitions() as $name => $definition) {
      if (str_starts_with($name, 'field_') && !$current->get($name)->equals($original->get($name))) throw new RuntimeException('Editorial field not restored: ' . $type . ':' . $id . ':' . $name);
    }
  }
}
foreach (['cache.render', 'cache.page', 'cache.dynamic_page_cache', 'cache.entity'] as $service) \Drupal::service($service)->deleteAll();
$report['published_nodes'] = (int) $manager->getStorage('node')->getQuery()->accessCheck(FALSE)->condition('status', 1)->count()->execute();
$report['passed'] = $report['published_nodes'] === 357;
file_put_contents($root . '/output/structured-model/reversible-test-recovery.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if (!$report['passed']) throw new RuntimeException('Published count differs after reversible recovery.');
