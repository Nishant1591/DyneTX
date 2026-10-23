<?php
/** Read-only entity/config snapshot for structured content migration verification. */
$phase = getenv('DYNE_VERIFY_PHASE') ?: 'before';
if (!preg_match('/^[a-z0-9_-]+$/', $phase)) {
  throw new InvalidArgumentException('Invalid snapshot phase.');
}
$root = dirname(DRUPAL_ROOT);
$directory = $root . '/output/structured-model';
if (!is_dir($directory)) {
  mkdir($directory, 0775, TRUE);
}
$manager = \Drupal::entityTypeManager();
$snapshot = ['phase' => $phase, 'node_counts' => [], 'published_counts' => [], 'bundles' => [], 'nodes' => [], 'paragraphs' => []];
foreach (['node', 'node_revision', 'paragraphs_item', 'paragraphs_item_revision'] as $table) {
  $snapshot['entity_and_revision_counts'][$table] = (int) \Drupal::database()->select($table)->countQuery()->execute()->fetchField();
}
foreach (\Drupal\node\Entity\NodeType::loadMultiple() as $type) {
  $bundle = $type->id();
  $snapshot['node_counts'][$bundle] = 0;
  $snapshot['published_counts'][$bundle] = 0;
  $fields = [];
  foreach (\Drupal::service('entity_field.manager')->getFieldDefinitions('node', $bundle) as $name => $definition) {
    if (str_starts_with($name, 'field_') || $name === 'body') {
      $fields[$name] = ['label' => $definition->getLabel(), 'type' => $definition->getType(), 'required' => $definition->isRequired(), 'cardinality' => $definition->getFieldStorageDefinition()->getCardinality()];
    }
  }
  $display = \Drupal\Core\Entity\Entity\EntityFormDisplay::load('node.' . $bundle . '.default');
  $snapshot['bundles'][$bundle] = ['label' => $type->label(), 'fields' => $fields, 'form_components' => $display?->getComponents(), 'form_hidden' => $display?->get('hidden')];
}
$ids = $manager->getStorage('node')->getQuery()->accessCheck(FALSE)->sort('nid')->execute();
foreach (array_chunk($ids, 50) as $chunk) {
  foreach ($manager->getStorage('node')->loadMultiple($chunk) as $node) {
    $values = $node->toArray();
    $fields = array_filter($values, static fn($name) => str_starts_with($name, 'field_') || $name === 'body', ARRAY_FILTER_USE_KEY);
    $snapshot['nodes'][$node->id()] = ['nid' => (int) $node->id(), 'vid' => (int) $node->getRevisionId(), 'uuid' => $node->uuid(), 'bundle' => $node->bundle(), 'title' => $node->label(), 'status' => $node->isPublished(), 'created' => $node->getCreatedTime(), 'changed' => $node->getChangedTime(), 'alias' => \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id()), 'fields' => $fields, 'fields_sha256' => hash('sha256', json_encode($fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))];
    $snapshot['node_counts'][$node->bundle()]++;
    $snapshot['published_counts'][$node->bundle()] += (int) $node->isPublished();
  }
  $manager->getStorage('node')->resetCache($chunk);
}
$ids = $manager->getStorage('paragraph')->getQuery()->accessCheck(FALSE)->sort('id')->execute();
foreach (array_chunk($ids, 50) as $chunk) {
  foreach ($manager->getStorage('paragraph')->loadMultiple($chunk) as $paragraph) {
    $values = $paragraph->toArray();
    $fields = array_filter($values, static fn($name) => str_starts_with($name, 'field_'), ARRAY_FILTER_USE_KEY);
    $snapshot['paragraphs'][$paragraph->id()] = ['id' => (int) $paragraph->id(), 'revision_id' => (int) $paragraph->getRevisionId(), 'bundle' => $paragraph->bundle(), 'parent_type' => $paragraph->get('parent_type')->value, 'parent_id' => $paragraph->get('parent_id')->value, 'parent_field_name' => $paragraph->get('parent_field_name')->value, 'fields' => $fields, 'fields_sha256' => hash('sha256', json_encode($fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))];
  }
  $manager->getStorage('paragraph')->resetCache($chunk);
}
file_put_contents($directory . '/' . $phase . '-entities.json', json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
echo json_encode(['phase' => $phase, 'nodes' => count($snapshot['nodes']), 'paragraphs' => count($snapshot['paragraphs']), 'node_counts' => $snapshot['node_counts'], 'published_counts' => $snapshot['published_counts']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
