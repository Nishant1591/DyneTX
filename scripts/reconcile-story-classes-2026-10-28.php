<?php

/**
 * @file
 * Restore six verified source story selectors in native presentation fields.
 * Preview by default; DYNE_STORY_CLASSES_APPLY=1 creates guarded revisions.
 */

$fixture = json_decode(file_get_contents(DRUPAL_ROOT . '/modules/custom/dyne_migrate/data/live-story-classes-2026-10-28.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$apply = getenv('DYNE_STORY_CLASSES_APPLY') === '1';
$report = ['apply' => $apply, 'passed' => FALSE, 'planned' => [], 'changes' => [], 'already_complete' => [], 'conflicts' => []];
$pending = [];
foreach ($fixture['records'] as $spec) {
  $node = \Drupal\node\Entity\Node::load($spec['id']);
  $path = \Drupal::service('path_alias.manager')->getPathByAlias($spec['alias']);
  if (!$node || $path !== '/node/' . $spec['id'] || $node->bundle() !== 'dyne_page'
      || (int) $node->get('field_wp_source_id')->value !== $spec['source_id'] || !$node->hasField('field_body_class')) {
    $report['conflicts'][] = ['node' => $spec['id'], 'reason' => 'Reviewed story identity changed.']; continue;
  }
  $current = $node->get('field_body_class')->getValue();
  if ($current === $spec['after']) { $report['already_complete'][] = $spec['id']; continue; }
  if ($current !== $spec['before'] || (int) $node->getRevisionId() !== $spec['revision']) {
    $report['conflicts'][] = ['node' => $spec['id'], 'reason' => 'Story presentation was edited after review.']; continue;
  }
  $pending[] = [$node, $spec];
  $report['planned'][] = ['node' => $spec['id'], 'alias' => $spec['alias'], 'before' => $current, 'after' => $spec['after'], 'source' => $spec['source_url']];
}
if ($apply && !$report['conflicts']) {
  $transaction = \Drupal::database()->startTransaction();
  try {
    foreach ($pending as [$node, $spec]) {
      $revision = $node->getRevisionId();
      $node->set('field_body_class', $spec['after']);
      $node->setNewRevision(TRUE);
      $node->setRevisionLogMessage('Restore verified source page_stories selector; preserve all story text and previous revisions.');
      $node->save();
      $report['changes'][] = ['node' => (int) $node->id(), 'old_revision' => (int) $revision, 'new_revision' => (int) $node->getRevisionId(), 'field' => 'field_body_class'];
    }
    unset($transaction);
  }
  catch (\Throwable $error) { $transaction->rollBack(); throw $error; }
}
$report['passed'] = !$report['conflicts'];
$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
file_put_contents(dirname(DRUPAL_ROOT) . '/output/live-local-audit-2026-10-28/story-classes-' . ($apply ? 'applied' : 'dry-run') . '.json', $json);
print $json;
if (!$report['passed']) throw new \RuntimeException('Story presentation repair has conflicts; no changes applied.');
