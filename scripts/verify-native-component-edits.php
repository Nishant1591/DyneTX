<?php

/**
 * @file
 * Verify node edits invalidate anonymously cached component output.
 *
 * Every temporary field edit is restored in finally, using new revisions.
 * This does not rebuild caches or alter migration state.
 */

$storage = \Drupal::entityTypeManager()->getStorage('node');
$legacy = \Drupal::state()->get('dyne_migrate.legacy_import', []);
$native = \Drupal::state()->get('dyne_migrate.native_components', []);
$news_ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'news')->condition('status', 1)->sort('field_news_date', 'DESC')->range(0, 1)->execute();
$tests = [
  ['label' => 'leader biography', 'nid' => $legacy['team:1262']['nid'], 'field' => 'field_body_text', 'path' => '/about-us'],
  ['label' => 'latest news headline', 'nid' => reset($news_ids), 'field' => 'title', 'path' => '/'],
  ['label' => 'employee quotation', 'nid' => $native['quotes']['careers:' . hash('sha256', 'Oreoluwa Ade-army')], 'field' => 'field_quote_text', 'path' => '/careers-culture'],
];
$get = static function (string $path): string {
  $response = \Drupal::httpClient()->get('http://localhost' . $path, ['headers' => ['Host' => 'dyne-tx-master.ddev.site'], 'timeout' => 30, 'http_errors' => FALSE]);
  if ($response->getStatusCode() !== 200) {
    throw new RuntimeException('Preview returned HTTP ' . $response->getStatusCode() . ' at ' . $path);
  }
  return (string) $response->getBody();
};
$report = [];
foreach ($tests as $index => $test) {
  $node = $storage->load($test['nid']);
  $original = $node->get($test['field'])->getValue();
  $marker = 'DYNE_NATIVE_EDIT_VERIFICATION_' . $index . '_' . bin2hex(random_bytes(4));
  $get($test['path']);
  $get($test['path']);
  try {
    // Each editorial save normally runs in a separate HTTP request. Reset only
    // request-local checksum bookkeeping so this long-running Drush process
    // does not coalesce the edit and restoration invalidations into one.
    \Drupal::service('cache_tags.invalidator.checksum')->reset();
    $value = $original;
    $value[0]['value'] = $test['field'] === 'title' ? $marker : $value[0]['value'] . ' ' . $marker;
    $node->set($test['field'], $value);
    $node->setNewRevision(TRUE);
    $node->setRevisionLogMessage('Temporary native component edit/cache verification.');
    $node->save();
    $appeared = str_contains($get($test['path']), $marker);
    if (!$appeared) {
      throw new RuntimeException('Saved ' . $test['label'] . ' did not appear on the cached landing page.');
    }
  }
  finally {
    \Drupal::service('cache_tags.invalidator.checksum')->reset();
    $node->set($test['field'], $original);
    $node->setNewRevision(TRUE);
    $node->setRevisionLogMessage('Restore unchanged source content after native component verification.');
    $node->save();
  }
  $restored = !str_contains($get($test['path']), $marker);
  if (!$restored) {
    throw new RuntimeException('Original ' . $test['label'] . ' did not return after restoring the field.');
  }
  $report[] = $test + ['edit_visible_without_cache_rebuild' => $appeared, 'restored_without_cache_rebuild' => $restored];
}
$path = dirname(DRUPAL_ROOT) . '/output/document-6-review/native-component-edit-verification.json';
file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
print json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
