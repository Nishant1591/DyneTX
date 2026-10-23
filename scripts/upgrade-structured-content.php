<?php

/** Run with ddev drush php:script scripts/upgrade-structured-content.php. */
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/structured-content.inc';
dyne_structured_schema();
$transaction = \Drupal::database()->startTransaction();
try {
  $report = dyne_structured_backfill();
  $report['workplaces'] = dyne_structured_complete_workplaces();
  $report['images'] = dyne_structured_complete_images();
  unset($transaction);
  $directory = dirname(DRUPAL_ROOT) . '/output/structured-model';
  if (!is_dir($directory)) {
    mkdir($directory, 0775, TRUE);
  }
  file_put_contents($directory . '/migration.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
  print json_encode($report, JSON_PRETTY_PRINT) . "\n";
}
catch (\Throwable $error) {
  $transaction->rollBack();
  throw $error;
}
