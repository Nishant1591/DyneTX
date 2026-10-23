<?php
/** Recover only the explicitly identified, interrupted verification run. */
$manager = \Drupal::entityTypeManager();
$db = \Drupal::database();
$plans = [
  'paragraph' => ['table' => 'paragraphs_item_revision', 'id' => 'id', 'revision' => 'revision_id', 'first_test_revision' => 2601, 'existing' => [1221, 1223, 1224, 367, 381, 382, 383, 384, 193, 1978], 'new' => [2038, 2039, 2040, 2041]],
  'node' => ['table' => 'node_revision', 'id' => 'nid', 'revision' => 'vid', 'first_test_revision' => 2099, 'existing' => [28, 44, 74, 3, 474], 'new' => [480, 481, 482, 483]],
];
$report = ['restored' => [], 'deleted_test_nodes' => [], 'deleted_test_paragraphs' => [], 'deleted_test_revisions' => []];
$marker = 'DYNE_STRUCTURED_TEST_0c0e0dd306_';
$has_marker = static function ($entity, array $seen = []) use (&$has_marker, $marker): bool {
  $key = $entity->getEntityTypeId() . ':' . $entity->getRevisionId();
  if (isset($seen[$key])) return FALSE;
  $seen[$key] = TRUE;
  foreach ($entity->getFieldDefinitions() as $name => $definition) {
    $field = $entity->get($name);
    foreach ($field->getValue() as $item) foreach ($item as $value) if (is_string($value) && str_contains($value, $marker)) return TRUE;
    if ($definition->getType() === 'entity_reference_revisions') foreach ($field as $item) if ($item->entity && $has_marker($item->entity, $seen)) return TRUE;
  }
  return FALSE;
};
$proof = ['unique_marker' => $marker, 'originals' => [], 'test_revisions' => []];
foreach ($plans as $type => $plan) {
  $storage = $manager->getStorage($type);
  foreach ($plan['existing'] as $id) {
    $revision = (int) $db->select($plan['table'], 'r')->fields('r', [$plan['revision']])->condition($plan['id'], $id)->condition($plan['revision'], $plan['first_test_revision'], '<')->orderBy($plan['revision'], 'DESC')->range(0, 1)->execute()->fetchField();
    $prior = $storage->loadRevision($revision);
    $current = $storage->load($id);
    if (!$prior || $has_marker($prior)) throw new RuntimeException('Original contains test marker: ' . $type . ':' . $id);
    if (!$has_marker($current)) throw new RuntimeException('Current entity lacks verified test provenance: ' . $type . ':' . $id);
    $proof['originals'][] = ['type' => $type, 'id' => $id, 'prior_revision' => $revision, 'prior_fields_sha256' => hash('sha256', serialize($prior->toArray())), 'current_revision' => (int) $current->getRevisionId(), 'current_fields_sha256' => hash('sha256', serialize($current->toArray())), 'prior_contains_test_marker' => FALSE, 'current_contains_test_marker_or_verified_child' => TRUE, 'workplace_preserved' => $prior->hasField('field_workplace_arrangement') ? $prior->get('field_workplace_arrangement')->value : NULL];
  }
  $revisions = $db->select($plan['table'], 'r')->fields('r', [$plan['revision']])->condition($plan['revision'], $plan['first_test_revision'], '>=')->execute()->fetchCol();
  foreach ($revisions as $revision) {
    $entity = $storage->loadRevision($revision);
    if (!$has_marker($entity)) throw new RuntimeException('Deletion candidate lacks exact test provenance: ' . $type . ':' . $revision);
    $proof['test_revisions'][] = ['type' => $type, 'id' => (int) $entity->id(), 'revision' => (int) $revision, 'verified_exact_test_marker_or_child' => TRUE];
  }
}
file_put_contents(dirname(DRUPAL_ROOT) . '/output/structured-model/test-recovery-dry-run.json', json_encode($proof, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
if (getenv('DYNE_RECOVERY_APPLY') !== '1') {
  echo json_encode($proof, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
  return;
}
$backup = dirname(DRUPAL_ROOT) . '/output/structured-model/interrupted-test-recovery-backup.sql.gz';
if (!is_file($backup) || filesize($backup) < 10000) throw new RuntimeException('Full reversible recovery backup is required.');
$report['database_backup'] = $backup;
$report['database_backup_sha256'] = hash_file('sha256', $backup);
$expected = [];
foreach ($plans as $type => $plan) {
  $storage = $manager->getStorage($type);
  foreach ($plan['existing'] as $id) {
    $revision_id = (int) $db->select($plan['table'], 'r')->fields('r', [$plan['revision']])->condition($plan['id'], $id)->condition($plan['revision'], $plan['first_test_revision'], '<')->orderBy($plan['revision'], 'DESC')->range(0, 1)->execute()->fetchField();
    if (!$revision_id) throw new RuntimeException('Missing original revision for ' . $type . ':' . $id);
    $entity = $storage->loadRevision($revision_id);
    $expected[$type][$id] = clone $entity;
    $entity->setNewRevision(FALSE);
    $entity->isDefaultRevision(TRUE);
    $entity->setSyncing(TRUE);
    $entity->save();
    $report['restored'][] = ['type' => $type, 'id' => $id, 'revision' => $revision_id];
  }
}
foreach (['node', 'paragraph'] as $type) {
  $storage = $manager->getStorage($type);
  foreach ($plans[$type]['new'] as $id) if ($entity = $storage->load($id)) {
    if ($type === 'node' && !str_starts_with($entity->label(), 'DYNE_STRUCTURED_TEST_0c0e0dd306_')) throw new RuntimeException('Unexpected test entity title: ' . $id);
    $storage->delete([$entity]);
    $report[$type === 'node' ? 'deleted_test_nodes' : 'deleted_test_paragraphs'][] = $id;
  }
}
foreach (['node', 'paragraph'] as $type) {
  $plan = $plans[$type]; $storage = $manager->getStorage($type);
  $ids = $db->select($plan['table'], 'r')->fields('r', [$plan['revision']])->condition($plan['revision'], $plan['first_test_revision'], '>=')->execute()->fetchCol();
  foreach ($ids as $revision_id) {
    $storage->deleteRevision($revision_id);
    $report['deleted_test_revisions'][] = ['type' => $type, 'revision' => (int) $revision_id];
  }
}
foreach ($expected as $type => $entities) {
  $storage = $manager->getStorage($type); $storage->resetCache();
  foreach ($entities as $id => $original) {
    $current = $storage->load($id);
    if ($current->getRevisionId() != $original->getRevisionId()) throw new RuntimeException('Revision not restored: ' . $type . ':' . $id);
    foreach ($original->getFieldDefinitions() as $name => $definition) {
      if (!$definition->isComputed() && !$current->get($name)->equals($original->get($name))) throw new RuntimeException('Field not restored: ' . $type . ':' . $id . ':' . $name);
    }
  }
}
$report['counts'] = [];
foreach (['node', 'node_revision', 'paragraphs_item', 'paragraphs_item_revision'] as $table) $report['counts'][$table] = (int) $db->select($table)->countQuery()->execute()->fetchField();
if ($report['counts'] !== ['node' => 469, 'node_revision' => 2082, 'paragraphs_item' => 1924, 'paragraphs_item_revision' => 2487]) throw new RuntimeException('Recovery counts differ: ' . json_encode($report['counts']));
foreach (['cache.render', 'cache.page', 'cache.dynamic_page_cache', 'cache.entity'] as $service) \Drupal::service($service)->deleteAll();
$report['passed'] = TRUE;
file_put_contents(dirname(DRUPAL_ROOT) . '/output/structured-model/test-residue-recovery.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
