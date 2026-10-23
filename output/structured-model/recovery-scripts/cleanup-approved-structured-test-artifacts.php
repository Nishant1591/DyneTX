<?php
/** User-approved cleanup of verification artifacts; never rewrites an original entity. */
$root = dirname(DRUPAL_ROOT);
$manager = \Drupal::entityTypeManager();
$db = \Drupal::database();
$marker = 'DYNE_STRUCTURED_TEST_0c0e0dd306_';
$test_ids = ['node' => [480, 481, 482, 483], 'paragraph' => [2038, 2039, 2040, 2041]];
$test_revisions = ['node' => range(2099, 2109), 'paragraph' => range(2601, 2618)];
$contains_marker = static function ($entity, array $seen = []) use (&$contains_marker, $marker): bool {
  $key = $entity->getEntityTypeId() . ':' . $entity->getRevisionId();
  if (isset($seen[$key])) return FALSE;
  $seen[$key] = TRUE;
  foreach ($entity->getFieldDefinitions() as $name => $definition) {
    $field = $entity->get($name);
    foreach ($field->getValue() as $item) foreach ($item as $value) if (is_string($value) && str_contains($value, $marker)) return TRUE;
    if ($definition->getType() === 'entity_reference_revisions') foreach ($field as $item) if ($item->entity && $contains_marker($item->entity, $seen)) return TRUE;
  }
  return FALSE;
};
$proof = ['authorization' => 'User explicitly approved test-only cleanup.', 'rewrites_original_defaults' => FALSE, 'active_original_references_to_test_revisions' => [], 'test_entities' => [], 'test_revisions' => []];
$originals = [];
foreach (['node', 'paragraph'] as $type) {
  $storage = $manager->getStorage($type);
  foreach ($test_ids[$type] as $id) {
    $entity = $storage->load($id);
    if (!$entity || !$contains_marker($entity)) throw new RuntimeException('Invalid temporary entity provenance: ' . $type . ':' . $id);
    if ($type === 'node' && ($entity->isPublished() || !str_starts_with($entity->label(), $marker))) throw new RuntimeException('Temporary node is not safely unpublished.');
    $proof['test_entities'][] = ['type' => $type, 'id' => $id, 'exact_marker_verified' => TRUE];
  }
  foreach ($test_revisions[$type] as $revision) {
    $entity = $storage->loadRevision($revision);
    if (!$entity) continue;
    if (!$contains_marker($entity)) throw new RuntimeException('Invalid temporary revision provenance: ' . $type . ':' . $revision);
    if (!in_array((int) $entity->id(), $test_ids[$type], TRUE) && $storage->load($entity->id())->getRevisionId() == $revision) throw new RuntimeException('An original current revision cannot be deleted.');
    $proof['test_revisions'][] = ['type' => $type, 'id' => (int) $entity->id(), 'revision' => $revision, 'exact_marker_or_child_verified' => TRUE];
  }
  foreach (\Drupal::service('entity_field.manager')->getFieldStorageDefinitions($type) as $name => $definition) {
    if ($definition->getType() !== 'entity_reference_revisions' || ($definition->getSetting('target_type') !== 'paragraph')) continue;
    $table = $storage->getTableMapping()->getDedicatedDataTableName($definition);
    if (!$db->schema()->tableExists($table)) continue;
    $rows = $db->select($table, 'f')->fields('f')->condition($name . '_target_revision_id', $test_revisions['paragraph'], 'IN')->condition('entity_id', $test_ids[$type], 'NOT IN')->execute()->fetchAll();
    foreach ($rows as $row) $proof['active_original_references_to_test_revisions'][] = ['table' => $table, 'row' => $row];
  }
  foreach ($storage->loadMultiple($storage->getQuery()->accessCheck(FALSE)->execute()) as $id => $entity) if (!in_array((int) $id, $test_ids[$type], TRUE)) $originals[$type][$id] = clone $entity;
}
if ($proof['active_original_references_to_test_revisions']) throw new RuntimeException('Current originals still reference temporary revisions.');
file_put_contents($root . '/output/structured-model/approved-test-cleanup-dry-run.json', json_encode($proof, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
if (getenv('DYNE_APPROVED_TEST_CLEANUP_APPLY') !== '1') {
  echo json_encode(['dry_run' => TRUE, 'test_entities' => $proof['test_entities'], 'test_revision_count' => count($proof['test_revisions']), 'current_original_references_to_test_revisions' => 0, 'original_entities_protected' => array_map('count', $originals)]) . "\n";
  return;
}
$backup = $root . '/output/structured-model/before-approved-test-cleanup.sql.gz';
if (!is_file($backup) || filesize($backup) < 10000) throw new RuntimeException('Current full database backup is required.');
$report = ['authorization' => $proof['authorization'], 'backup' => $backup, 'rewrites_original_defaults' => FALSE, 'deleted_entities' => [], 'deleted_revisions' => []];
foreach (['node', 'paragraph'] as $type) {
  $storage = $manager->getStorage($type);
  foreach ($test_ids[$type] as $id) if ($entity = $storage->load($id)) {
    $storage->delete([$entity]);
    $report['deleted_entities'][] = ['type' => $type, 'id' => $id];
  }
}
foreach (['node', 'paragraph'] as $type) {
  $storage = $manager->getStorage($type);
  foreach ($test_revisions[$type] as $revision) if ($storage->loadRevision($revision)) {
    $storage->deleteRevision($revision);
    $report['deleted_revisions'][] = ['type' => $type, 'revision' => $revision];
  }
}
foreach ($originals as $type => $entities) {
  $storage = $manager->getStorage($type); $storage->resetCache();
  foreach ($entities as $id => $original) {
    $current = $storage->load($id);
    if (!$current || $current->getRevisionId() != $original->getRevisionId()) throw new RuntimeException('An original default was changed by cleanup.');
    foreach ($original->getFieldDefinitions() as $name => $definition) if (!$definition->isComputed() && !$current->get($name)->equals($original->get($name))) throw new RuntimeException('An original field changed during cleanup: ' . $type . ':' . $id . ':' . $name);
  }
}
$report['counts'] = [];
foreach (['node', 'node_revision', 'paragraphs_item', 'paragraphs_item_revision'] as $table) $report['counts'][$table] = (int) $db->select($table)->countQuery()->execute()->fetchField();
$report['all_original_values_and_defaults_preserved'] = TRUE;
$report['expected_original_entities_before_cleanup'] = array_map('count', $originals);
$report['passed'] = $report['counts']['node'] === count($originals['node']) && $report['counts']['paragraphs_item'] === count($originals['paragraph']);
foreach (['cache.render', 'cache.page', 'cache.dynamic_page_cache', 'cache.entity'] as $service) \Drupal::service($service)->deleteAll();
file_put_contents($root . '/output/structured-model/approved-test-cleanup.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if (!$report['passed']) throw new RuntimeException('Final entity counts differ from the original model.');
