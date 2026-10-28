<?php

/**
 * @file
 * Guarded live Perspectives metadata, punctuation and cached public post repair.
 * Dry run by default; set DYNE_PERSPECTIVE_APPLY=1 inside DDEV to apply.
 */

$root = dirname(DRUPAL_ROOT);
$fixture = json_decode(file_get_contents(DRUPAL_ROOT . '/modules/custom/dyne_migrate/data/live-perspective-reconciliation-2026-10-28.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$apply = getenv('DYNE_PERSPECTIVE_APPLY') === '1';
$report = ['passed' => FALSE, 'apply' => $apply, 'planned' => [], 'changes' => [], 'conflicts' => [], 'already_complete' => [], 'baselines_reset' => 0];
$nodes = \Drupal::entityTypeManager()->getStorage('node');
$load_node = static function (string $alias, int $id) use ($nodes) {
  $path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
  if ($path !== '/node/' . $id || !($node = $nodes->load($id))) throw new \RuntimeException('Reviewed identity changed at ' . $alias);
  return $node;
};
$field_values = static function ($entity, string $name): array {
  $values = $entity->get($name)->getValue();
  foreach ($values as &$item) foreach (['target_id', 'target_revision_id'] as $key) if (isset($item[$key])) $item[$key] = (string) $item[$key];
  return $values;
};
$identity = static function ($entity): string { return $entity->getEntityTypeId() . ':' . $entity->id(); };
$collect = static function ($entity, array &$entities, int $depth = 0) use (&$collect, $identity): void {
  if ($depth > 32) throw new \RuntimeException('Unexpected article nesting.');
  $key = $identity($entity);
  if (isset($entities[$key])) return;
  $entities[$key] = $entity;
  foreach (['field_components', 'field_article_sections'] as $name) if ($entity->hasField($name)) foreach ($entity->get($name)->referencedEntities() as $child) $collect($child, $entities, $depth + 1);
};
$metadata = [];
foreach ($fixture['perspectives'] as $spec) {
  $node = $load_node($spec['alias'], $spec['id']);
  if ($node->bundle() !== 'perspective' || (int) $node->get('field_wp_source_id')->value !== $spec['source_id']) throw new \RuntimeException('Perspective source identity changed: ' . $spec['alias']);
  $changes = [];
  if ($spec['category'] !== '') {
    $current = $field_values($node, 'field_story_category'); $desired = [['value' => $spec['category']]];
    if ($current === $desired) $report['already_complete'][] = ['node' => $spec['id'], 'field' => 'field_story_category'];
    elseif ($current === $spec['category_before']) $changes['field_story_category'] = $desired;
    else $report['conflicts'][] = ['node' => $spec['id'], 'field' => 'field_story_category', 'reason' => 'Current category differs from both reviewed values.'];
  }
  if ($spec['external_action']) {
    $current = $field_values($node, 'field_external_link');
    $desired = $spec['external_action']['before']; $desired[0]['title'] = $spec['external_action']['after_title'];
    if ($current === $desired) $report['already_complete'][] = ['node' => $spec['id'], 'field' => 'field_external_link'];
    elseif ($current === $spec['external_action']['before']) $changes['field_external_link'] = $desired;
    else $report['conflicts'][] = ['node' => $spec['id'], 'field' => 'field_external_link', 'reason' => 'External destination/caption changed after review.'];
  }
  $metadata[$spec['id']] = ['node' => $node, 'changes' => $changes];
}
$image_spec = $fixture['featured_image']; $image_node = $metadata[$image_spec['node']]['node'];
$current_media = $image_node->get('field_story_image')->entity;
$current_image_uri = '';
if ($current_media) {
  $source_field = $current_media->getSource()->getConfiguration()['source_field'];
  $current_image_uri = $current_media->get($source_field)->entity?->getFileUri() ?? '';
}
$image_needed = $current_image_uri !== $image_spec['after_uri'];
if ($image_needed && $current_image_uri !== $image_spec['before_uri']) $report['conflicts'][] = ['node' => $image_spec['node'], 'field' => 'field_story_image', 'reason' => 'Featured image changed after review.'];
if (!is_file($image_spec['after_uri']) || hash_file('sha256', $image_spec['after_uri']) !== $image_spec['sha256']) $report['conflicts'][] = ['field' => 'field_story_image', 'reason' => 'The local source image is missing or differs from the verified live file.'];

$story_nodes = []; $patches = [];
foreach ($fixture['stories'] as $spec) {
  $node = $load_node($spec['alias'], $spec['id']); $entities = []; $collect($node, $entities); $story_nodes[] = $node;
  foreach ($spec['patches'] as $patch) {
    $key = $patch['entity_type'] . ':' . $patch['id'];
    $entity = $entities[$key] ?? NULL;
    if (!$entity || !$entity->hasField($patch['field'])) { $report['conflicts'][] = ['entity' => $key, 'field' => $patch['field'], 'reason' => 'Reviewed field is no longer in the active section tree.']; continue; }
    $values = $entity->get($patch['field'])->getValue();
    if (count($values) !== 1 || ($values[0]['format'] ?? NULL) !== $patch['format']) { $report['conflicts'][] = ['entity' => $key, 'field' => $patch['field'], 'reason' => 'Field format/cardinality changed.']; continue; }
    $value = (string) $values[0]['value']; $hash = hash('sha256', $value);
    if ($hash === $patch['after_sha256']) { $report['already_complete'][] = ['entity' => $key, 'field' => $patch['field']]; continue; }
    if ($hash !== $patch['before_sha256']) { $report['conflicts'][] = ['entity' => $key, 'field' => $patch['field'], 'reason' => 'Native text was edited after the source comparison.']; continue; }
    if (($patch['operation'] ?? '') === 'cached_public_social_post') $value = $patch['reviewed_after_value'];
    else foreach ($patch['replacements'] as $replacement) {
      if (substr_count($value, $replacement['from']) !== $replacement['occurrences']) throw new \RuntimeException('Reviewed replacement count changed: ' . $key);
      $value = str_replace($replacement['from'], $replacement['to'], $value);
    }
    if (hash('sha256', $value) !== $patch['after_sha256']) throw new \RuntimeException('Punctuation output differs from reviewed hash: ' . $key);
    $values[0]['value'] = $value; $patches[$key][$patch['field']] = ['values' => $values, 'spec' => $patch];
    $report['planned'][] = ['entity' => $key, 'field' => $patch['field'], 'operation' => $patch['operation'] ?? 'punctuation_only', 'before_sha256' => $patch['before_sha256'], 'after_sha256' => $patch['after_sha256'], 'replacements' => $patch['replacements']];
  }
}
foreach ($metadata as $id => $entry) foreach ($entry['changes'] as $field => $value) $report['planned'][] = ['entity' => 'node:' . $id, 'field' => $field, 'before' => $field_values($entry['node'], $field), 'after' => $value];
if ($image_needed) $report['planned'][] = ['entity' => 'node:' . $image_spec['node'], 'field' => 'field_story_image', 'before_uri' => $current_image_uri, 'after_uri' => $image_spec['after_uri'], 'source_sha256' => $image_spec['sha256']];

if ($apply && !$report['conflicts']) {
  $transaction = \Drupal::database()->startTransaction();
  try {
    if ($image_needed) {
      $file_storage = \Drupal::entityTypeManager()->getStorage('file');
      $files = $file_storage->loadByProperties(['uri' => $image_spec['after_uri']]);
      $file = $files ? reset($files) : \Drupal\file\Entity\File::create(['uri' => $image_spec['after_uri'], 'filename' => basename($image_spec['after_uri']), 'status' => 1, 'uid' => 1]);
      if ($file->isNew()) $file->save();
      $media_storage = \Drupal::entityTypeManager()->getStorage('media');
      $media_type = \Drupal\media\Entity\MediaType::load('image');
      $source_field = $media_type->getSource()->getConfiguration()['source_field'];
      $media_ids = $media_storage->getQuery()->accessCheck(FALSE)->condition('bundle', 'image')->condition($source_field . '.target_id', $file->id())->execute();
      $media = $media_ids ? $media_storage->load(reset($media_ids)) : $media_storage->create(['bundle' => 'image', 'name' => 'Kate Mitchell - featured perspective', 'status' => 1, 'uid' => 1, $source_field => ['target_id' => $file->id(), 'alt' => $image_spec['alt']]]);
      if ($media->isNew()) $media->save();
      $metadata[$image_spec['node']]['changes']['field_story_image'] = [['target_id' => $media->id()]];
    }
    foreach ($metadata as $id => $entry) {
      if (!$entry['changes']) continue;
      $node = $entry['node']; $old_revision = $node->getRevisionId();
      foreach ($entry['changes'] as $field => $value) {
        $report['changes'][] = ['entity' => 'node:' . $id, 'field' => $field, 'before' => $field_values($node, $field), 'after' => $value];
        $node->set($field, $value);
      }
      $node->setNewRevision(TRUE); $node->setRevisionLogMessage('Match verified public Perspectives listing metadata and featured Media; preserve article content.'); $node->save();
      $report['changes'][] = ['entity' => 'node:' . $id, 'old_revision' => (int) $old_revision, 'new_revision' => (int) $node->getRevisionId(), 'operation' => 'listing_metadata_revision'];
    }
    $revise = static function ($entity, int $depth = 0) use (&$revise, $patches, $identity, &$report): bool {
      if ($depth > 32) throw new \RuntimeException('Unexpected article nesting while revising.');
      $changed = FALSE;
      foreach (['field_components', 'field_article_sections'] as $name) {
        if (!$entity->hasField($name)) continue;
        foreach ($entity->get($name) as $item) {
          if ($item->entity && $revise($item->entity, $depth + 1)) { $item->target_revision_id = $item->entity->getRevisionId(); $changed = TRUE; }
        }
      }
      foreach ($patches[$identity($entity)] ?? [] as $name => $patch) { $entity->set($name, $patch['values']); $changed = TRUE; }
      if (!$changed) return FALSE;
      $old_revision = $entity->getRevisionId(); $entity->setNewRevision(TRUE);
      if (method_exists($entity, 'setRevisionLogMessage')) $entity->setRevisionLogMessage('Match reviewed public story punctuation or cached social post; preserve all previous revisions.');
      $entity->save();
      $report['changes'][] = ['entity' => $identity($entity), 'old_revision' => (int) $old_revision, 'new_revision' => (int) $entity->getRevisionId(), 'operation' => 'reviewed_content_revision', 'fields' => array_keys($patches[$identity($entity)] ?? [])];
      return TRUE;
    };
    foreach ($story_nodes as $node) $revise($node);
    unset($transaction);
  }
  catch (\Throwable $error) { $transaction->rollBack(); throw $error; }
}
$report['passed'] = !$report['conflicts'];
$directory = $root . '/output/live-local-audit-2026-10-28';
if (!is_dir($directory)) mkdir($directory, 0775, TRUE);
$name = $apply ? 'perspectives-applied.json' : 'perspectives-dry-run.json';
file_put_contents($directory . '/' . $name, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);
print json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
if (!$report['passed']) throw new \RuntimeException('Perspective reconciliation has conflicts; no changes applied.');
