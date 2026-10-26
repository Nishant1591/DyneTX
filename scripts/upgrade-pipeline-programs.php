<?php

/**
 * @file
 * Preview with Drush php:script; explicitly set DYNE_PIPELINE_APPLY=1 to apply.
 *
 * Existing sites normally apply update 8114 through Drush updatedb instead.
 */

require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/pipeline-programs.inc';
$plan = dyne_pipeline_migration_plan();
$report = $plan;
if (getenv('DYNE_PIPELINE_APPLY') === '1') $report = dyne_pipeline_program_upgrade();
if (isset($report['source']['html'])) unset($report['source']['html']);
$directory = dirname(DRUPAL_ROOT) . '/output/pipeline-programs';
if (!is_dir($directory)) mkdir($directory, 0775, TRUE);
$file = getenv('DYNE_PIPELINE_APPLY') === '1' ? 'migration.json' : 'dry-run.json';
file_put_contents($directory . '/' . $file, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
print json_encode(['apply' => getenv('DYNE_PIPELINE_APPLY') === '1', 'available' => $plan['available'], 'already_complete' => $plan['complete'] ?? FALSE, 'program_count' => count($report['programs'] ?? $plan['parsed']['programs'] ?? []), 'report' => 'output/pipeline-programs/' . $file], JSON_PRETTY_PRINT) . "\n";
