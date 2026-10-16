<?php

/** Native Migrate safety and fresh-row branch checks; all fixtures roll back. */
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

$assertions = 0;
$check = static function (bool $pass, string $label) use (&$assertions): void { if (!$pass) { throw new RuntimeException($label); } $assertions++; };
$state = \Drupal::state();
$storage = \Drupal::entityTypeManager()->getStorage('node');
$before = (int) $storage->getQuery()->accessCheck(FALSE)->count()->execute();
$maps = [];
$manager = \Drupal::service('plugin.manager.migration');
foreach (['dyne_pages' => 59, 'dyne_jobs' => 49, 'dyne_leaders' => 31, 'dyne_perspectives' => 13, 'dyne_news' => 306] as $id => $expected) {
  $migration = $manager->createInstance($id);
  $check($migration->getSourcePlugin()->count() === $expected, $id . ' complete source');
  $check($migration->getIdMap()->importedCount() === $expected, $id . ' complete map');
  $maps[$id] = ['source' => $expected, 'imported' => $migration->getIdMap()->importedCount()];
}
$legacy = $state->get('dyne_migrate.legacy_import', []) + $state->get('dyne_migrate.content_completion', []);
$framework = $state->get('dyne_migrate.framework_import', []);
$check(count($framework) === 458, '458 mapped archive records');
foreach ($framework as $key => $entry) {
  $check(isset($legacy[$key]) && (int) $legacy[$key]['nid'] === $entry['nid'], 'Existing node ID preserved: ' . $key);
  $node = $storage->load($entry['nid']);
  $check($node !== NULL, 'Adopted node exists');
}
$transaction = \Drupal::database()->startTransaction();
try {
  $migration = $manager->createInstance('dyne_pages');
  $destination = $migration->getDestinationPlugin();
  $record = ['source_id' => 999999992, 'source_type' => 'page', 'title' => 'Transaction-only archive fixture', 'bundle' => 'dyne_page', 'alias' => '/legacy-archive/transaction-only-999999992', 'published' => FALSE, 'components' => [['type' => 'dyne_team_grid', 'fields' => ['field_items_json' => []]]]];
  $row = new Row(['source_key' => 'page:' . $record['source_id']], ['source_key' => ['type' => 'string']]);
  $row->setDestinationProperty('record', $record);
  $ids = $destination->import($row);
  $node = $storage->load($ids[0]);
  $check(!$node->isPublished(), 'Fresh archive draft stays unpublished');
  $check((int) $node->get('field_wp_source_id')->value === $record['source_id'], 'Fresh archive source ID retained');
  $paragraph = $node->get('field_components')->entity;
  $check(!(bool) $paragraph->get('field_native_components')->value, 'Archive JSON explicitly pending native conversion');
  $check($destination->import($row)[0] === $ids[0], 'Repeat import adopts same node');
  $check($destination->rollbackAction() === MigrateIdMapInterface::ROLLBACK_PRESERVE, 'Map protects adopted and created nodes on rollback');
  $destination->rollback($ids);
  $check($storage->load($ids[0]) !== NULL, 'Rollback preserves node');
  $changed = $record; $changed['title'] = 'Changed source'; $row->setDestinationProperty('record', $changed);
  $rejected = FALSE;
  try { $destination->import($row); } catch (RuntimeException $e) { $rejected = str_contains($e->getMessage(), 'needs reviewed reconciliation'); }
  $check($rejected, 'Changed archive blocked instead of overwriting editors');
  $collision = $record; $collision['source_id']++; $collision['alias'] = '/about-us'; $row->setDestinationProperty('record', $collision);
  $rejected = FALSE;
  try { $destination->import($row); } catch (RuntimeException $e) { $rejected = str_contains($e->getMessage(), 'collision'); }
  $check($rejected, 'Existing alias with different source ID cannot be adopted');
}
finally {
  $transaction->rollBack(); unset($transaction);
  $storage->resetCache();
  \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache();
  $state->resetCache();
}
$after = (int) $storage->getQuery()->accessCheck(FALSE)->count()->execute();
$check($before === $after, 'All temporary nodes rolled back');
$report = ['assertions' => $assertions, 'failures' => 0, 'maps' => $maps, 'node_count_before' => $before, 'node_count_after' => $after, 'writes_rolled_back' => TRUE];
file_put_contents(dirname(DRUPAL_ROOT) . '/output/document-6-review/migration-framework-tests.json', json_encode($report, JSON_PRETTY_PRINT));
print json_encode($report, JSON_PRETTY_PRINT) . "\n";
