<?php

/**
 * Upgrade existing archive records to native Drupal fields and core Media.
 * Usage: ddev drush php:script scripts/upgrade-native-content.php
 * DYNE_NATIVE_PHASE=schema|backfill|all (default all).
 * Run only after taking a database backup. Idempotent; reruns preserve edits.
 */
$root = dirname(DRUPAL_ROOT);
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/native-content.inc';
$phase = getenv('DYNE_NATIVE_PHASE') ?: 'all';
if (!in_array($phase, ['all', 'schema', 'backfill'], TRUE)) {
  throw new InvalidArgumentException('Expected DYNE_NATIVE_PHASE schema, backfill, or all.');
}
if (in_array($phase, ['all', 'schema'], TRUE)) {
  dyne_native_schema();
}
if (in_array($phase, ['all', 'backfill'], TRUE)) {
  $read = static fn(string $path): array => json_decode(file_get_contents($root . '/' . $path), TRUE, 512, JSON_THROW_ON_ERROR);
  $report = dyne_native_backfill($read('content-archive/wordpress-records.json'), $read('content-archive/wordpress-media.json'), $read('web/modules/custom/dyne_migrate/data/legacy-page-metadata.json'));
  $report['generated_at'] = gmdate(DATE_ATOM);
  $directory = $root . '/output/document-6-review';
  if (!is_dir($directory)) {
    mkdir($directory, 0775, TRUE);
  }
  file_put_contents($directory . '/native-content-upgrade.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
  unset($report['nodes'], $report['attachments']);
  echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}
