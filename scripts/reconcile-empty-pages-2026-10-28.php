<?php

/**
 * @file
 * Preserve recovered page sections while matching verified empty public pages.
 * Preview by default; set DYNE_EMPTY_PAGE_APPLY=1 to create two new revisions.
 */

$fixture = json_decode(file_get_contents(DRUPAL_ROOT . '/modules/custom/dyne_migrate/data/live-empty-pages-2026-10-28.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$apply = getenv('DYNE_EMPTY_PAGE_APPLY') === '1';
$report = ['apply' => $apply, 'passed' => FALSE, 'planned' => [], 'changes' => [], 'already_complete' => [], 'conflicts' => []];
$pending = [];
$snapshot = static function ($entity, int $depth = 0) use (&$snapshot): array {
  if ($depth > 32) throw new \RuntimeException('Unexpected preserved section nesting.');
  $values = $entity->toArray();
  foreach (['vid', 'revision_id', 'revision_timestamp', 'revision_created', 'revision_log', 'revision_default', 'revision_translation_affected', 'changed', 'field_display_page_content'] as $key) unset($values[$key]);
  foreach ($entity->getFieldDefinitions() as $name => $definition) {
    if ($definition->getType() !== 'entity_reference_revisions') continue;
    foreach ($values[$name] as &$item) unset($item['target_revision_id']);
    unset($item);
    $values['preserved_tree:' . $name] = [];
    foreach ($entity->get($name)->referencedEntities() as $child) $values['preserved_tree:' . $name][] = $snapshot($child, $depth + 1);
  }
  return $values;
};
foreach ($fixture['records'] as $spec) {
  $node = \Drupal\node\Entity\Node::load($spec['node_id']);
  $path = \Drupal::service('path_alias.manager')->getPathByAlias($spec['alias']);
  if (!$node || $path !== '/node/' . $spec['node_id'] || $node->bundle() !== 'dyne_page'
      || $node->label() !== $spec['title'] || (int) $node->get('field_wp_source_id')->value !== $spec['source_id']
      || !$node->hasField('field_display_page_content')) {
    $report['conflicts'][] = ['node' => $spec['node_id'], 'reason' => 'Reviewed identity or visibility schema changed.'];
    continue;
  }
  $components = $node->get('field_components')->getValue();
  foreach ($components as &$item) foreach (['target_id', 'target_revision_id'] as $key) $item[$key] = (string) $item[$key];
  unset($item);
  if (!$node->get('field_display_page_content')->isEmpty() && !(bool) $node->get('field_display_page_content')->value) {
    $original = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($spec['revision_id']);
    if (!$original || $snapshot($original) !== $snapshot($node)) {
      $report['conflicts'][] = ['node' => $spec['node_id'], 'reason' => 'Preserved recursive content changed after visibility repair.'];
    }
    else $report['already_complete'][] = $spec['node_id'];
    continue;
  }
  if ($components !== $spec['components']) {
    $report['conflicts'][] = ['node' => $spec['node_id'], 'reason' => 'Preserved section tree changed after review.'];
    continue;
  }
  if ((string) $node->getRevisionId() !== $spec['revision_id']) {
    $report['conflicts'][] = ['node' => $spec['node_id'], 'reason' => 'Page was revised after source comparison.'];
    continue;
  }
  $pending[] = [$node, $components];
  $report['planned'][] = ['node' => $spec['node_id'], 'alias' => $spec['alias'], 'operation' => 'Preserve sections; disable public section display.', 'source' => $spec['verified_live_url']];
}
if ($apply && !$report['conflicts']) {
  $transaction = \Drupal::database()->startTransaction();
  try {
    foreach ($pending as [$node, $components]) {
      $before = $snapshot($node);
      $old_revision = $node->getRevisionId();
      $node->set('field_display_page_content', 0);
      $node->setNewRevision(TRUE);
      $node->setRevisionLogMessage('Match verified empty public page; preserve recovered sections and prior revisions.');
      $node->save();
      // Paragraphs creates revision copies when the parent is revised. Compare
      // the full recursive content tree, allowing only those revision IDs.
      $after = $snapshot($node);
      if ($before !== $after) {
        $differences = [];
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
          if (($before[$key] ?? NULL) !== ($after[$key] ?? NULL)) $differences[$key] = ['before' => $before[$key] ?? NULL, 'after' => $after[$key] ?? NULL];
        }
        throw new \RuntimeException('An unrelated page field changed while saving visibility: ' . json_encode($differences));
      }
      $report['changes'][] = ['node' => (int) $node->id(), 'old_revision' => (int) $old_revision, 'new_revision' => (int) $node->getRevisionId(), 'previous_components' => $components, 'current_components' => $node->get('field_components')->getValue(), 'unrelated_fields_and_recursive_content_preserved' => TRUE];
    }
    unset($transaction);
  }
  catch (\Throwable $error) { $transaction->rollBack(); throw $error; }
}
$report['passed'] = !$report['conflicts'];
$directory = dirname(DRUPAL_ROOT) . '/output/live-local-audit-2026-10-28';
if (!is_dir($directory)) mkdir($directory, 0775, TRUE);
$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
file_put_contents($directory . '/empty-pages-' . ($apply ? 'applied' : 'dry-run') . '.json', $json);
print $json;
if (!$report['passed']) throw new \RuntimeException('Reviewed page visibility has conflicts; no changes applied.');
