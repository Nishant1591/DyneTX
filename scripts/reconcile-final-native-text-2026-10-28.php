<?php

/**
 * @file
 * Apply only the exact active native values reviewed against the public audit.
 * Read-only by default; set DYNE_FINAL_NATIVE_TEXT_APPLY=1 after a DB backup.
 */

$fixture = json_decode(file_get_contents(DRUPAL_ROOT . '/modules/custom/dyne_migrate/data/live-final-native-text-2026-10-28.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$apply = getenv('DYNE_FINAL_NATIVE_TEXT_APPLY') === '1';
$report = ['apply' => $apply, 'passed' => FALSE, 'planned' => [], 'changes' => [], 'already_complete' => [], 'conflicts' => [], 'baselines_reset' => 0];
$identity = static fn($entity): string => $entity->getEntityTypeId() . ':' . $entity->id();
$references = static function ($entity): array {
  $names = [];
  foreach ($entity->getFieldDefinitions() as $name => $definition) {
    if ($definition->getType() === 'entity_reference_revisions' && $definition->getSetting('target_type') === 'paragraph') $names[] = $name;
  }
  return $names;
};
$collect = function ($entity, array &$entities, int $depth = 0) use (&$collect, $references, $identity): void {
  if ($depth > 32) throw new \RuntimeException('Unexpected native section nesting.');
  $key = $identity($entity);
  if (isset($entities[$key])) return;
  $entities[$key] = $entity;
  foreach ($references($entity) as $name) foreach ($entity->get($name)->referencedEntities() as $child) $collect($child, $entities, $depth + 1);
};
$roots = []; $patches = [];
foreach ($fixture['records'] as $spec) {
  $node = \Drupal\node\Entity\Node::load($spec['node_id']);
  if (!$node || $node->bundle() !== $spec['bundle'] || (isset($spec['title']) && $node->label() !== $spec['title'])
    || (isset($spec['alias']) && \Drupal::service('path_alias.manager')->getPathByAlias($spec['alias']) !== '/node/' . $spec['node_id'])
    || (isset($spec['source_id']) && (!$node->hasField('field_wp_source_id') || (int) $node->get('field_wp_source_id')->value !== $spec['source_id']))) {
    $report['conflicts'][] = ['node' => $spec['node_id'], 'reason' => 'Reviewed identity, source ID or alias changed.'];
    continue;
  }
  $entities = []; $collect($node, $entities); $roots[$node->id()] = $node;
  foreach ($spec['patches'] as $patch) {
    $key = $patch['entity_type'] . ':' . $patch['id'];
    $entity = $entities[$key] ?? NULL;
    if (!$entity || $entity->bundle() !== $patch['bundle'] || !$entity->hasField($patch['field'])) {
      $report['conflicts'][] = ['entity' => $key, 'field' => $patch['field'], 'reason' => 'Reviewed editable field is not in the active section tree.'];
      continue;
    }
    $values = $entity->get($patch['field'])->getValue(); $desired = $values;
    if (($patch['kind'] ?? '') === 'values') {
      if ($values === $patch['after_values']) { $report['already_complete'][] = ['entity' => $key, 'field' => $patch['field']]; continue; }
      if ($values !== $patch['before_values']) { $report['conflicts'][] = ['entity' => $key, 'field' => $patch['field'], 'reason' => 'Native presentation was edited after review.']; continue; }
      $desired = $patch['after_values'];
      $plan = ['before' => $values, 'after' => $desired];
    }
    else {
      if (count($values) !== 1 || ($values[0]['format'] ?? NULL) !== $patch['format']) {
        $report['conflicts'][] = ['entity' => $key, 'field' => $patch['field'], 'reason' => 'Native text format/cardinality changed.']; continue;
      }
      $value = (string) $values[0]['value']; $hash = hash('sha256', $value);
      if ($hash === $patch['after_sha256']) { $report['already_complete'][] = ['entity' => $key, 'field' => $patch['field']]; continue; }
      if ($hash !== $patch['before_sha256']) { $report['conflicts'][] = ['entity' => $key, 'field' => $patch['field'], 'reason' => 'Native text was edited after review.']; continue; }
      foreach ($patch['replacements'] as $replacement) {
        if (substr_count($value, $replacement['from']) !== $replacement['occurrences']) throw new \RuntimeException('Reviewed replacement count changed for ' . $key);
        $value = str_replace($replacement['from'], $replacement['to'], $value);
      }
      if (hash('sha256', $value) !== $patch['after_sha256']) throw new \RuntimeException('Reviewed output hash changed for ' . $key);
      $desired[0]['value'] = $value;
      $plan = ['before_sha256' => $patch['before_sha256'], 'after_sha256' => $patch['after_sha256'], 'replacements' => $patch['replacements']];
    }
    $patches[$key][$patch['field']] = $desired;
    $report['planned'][] = ['entity' => $key, 'field' => $patch['field'], 'source' => $spec['source_url']] + $plan;
  }
}
if ($apply && !$report['conflicts'] && $patches) {
  $transaction = \Drupal::database()->startTransaction();
  try {
    $saved = [];
    $revise = function ($entity, int $depth = 0) use (&$revise, $references, $identity, $patches, &$saved, &$report): bool {
      if ($depth > 32) throw new \RuntimeException('Unexpected native section nesting during revision.');
      $key = $identity($entity);
      if (isset($saved[$key])) return $saved[$key];
      $changed = FALSE;
      foreach ($references($entity) as $name) foreach ($entity->get($name) as $item) {
        if ($item->entity && $revise($item->entity, $depth + 1)) { $item->target_revision_id = $item->entity->getRevisionId(); $changed = TRUE; }
      }
      foreach ($patches[$key] ?? [] as $name => $values) { $entity->set($name, $values); $changed = TRUE; }
      if (!$changed) return $saved[$key] = FALSE;
      $before = $entity->getRevisionId(); $entity->setNewRevision(TRUE);
      if (method_exists($entity, 'setRevisionLogMessage')) $entity->setRevisionLogMessage('Match exact reviewed public audit text and presentation; preserve old revisions and unrelated native fields.');
      $entity->save();
      $report['changes'][] = ['entity' => $key, 'old_revision' => (int) $before, 'new_revision' => (int) $entity->getRevisionId(), 'fields' => array_keys($patches[$key] ?? [])];
      return $saved[$key] = TRUE;
    };
    foreach ($roots as $node) $revise($node);
    unset($transaction);
  }
  catch (\Throwable $error) { $transaction->rollBack(); throw $error; }
}
$report['passed'] = !$report['conflicts'];
$directory = dirname(DRUPAL_ROOT) . '/output/live-local-audit-2026-10-28';
if (!is_dir($directory)) mkdir($directory, 0775, TRUE);
$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
file_put_contents($directory . '/final-native-text-' . ($apply ? 'applied' : 'dry-run') . '.json', $json);
print $json;
if (!$report['passed']) throw new \RuntimeException('Native audit reconciliation has conflicts; no changes applied.');
