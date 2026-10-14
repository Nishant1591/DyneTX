<?php

/**
 * Append a database-backed Open positions section to Careers + Culture.
 *
 * Run with:
 *   ddev drush php:script web/modules/custom/dyne_migrate/scripts/add-careers-open-positions.php
 */

include_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/dyne_migrate.install';
_dyne_migrate_job_detail_fields();
_dyne_migrate_dynamic_jobs_list_field();

$path_alias_manager = \Drupal::service('path_alias.manager');
$internal_path = $path_alias_manager->getPathByAlias('/careers-culture');
if (!preg_match('#^/node/(\d+)$#', $internal_path, $matches)) {
  throw new \RuntimeException('Could not resolve the Careers + Culture page alias.');
}

$careers = \Drupal\node\Entity\Node::load($matches[1]);
if (!$careers || $careers->bundle() !== 'dyne_page' || !$careers->hasField('field_components')) {
  throw new \RuntimeException('Expected a component-built Careers + Culture page.');
}

foreach ($careers->get('field_components')->referencedEntities() as $paragraph) {
  if ($paragraph->bundle() === 'dyne_jobs_list'
    && $paragraph->hasField('field_anchor')
    && $paragraph->get('field_anchor')->value === 'positions'
    && $paragraph->hasField('field_dynamic_jobs')
    && (bool) $paragraph->get('field_dynamic_jobs')->value) {
    print "Dynamic Open positions section already exists.\n";
    return;
  }
}

$jobs = \Drupal\paragraphs\Entity\Paragraph::create([
  'type' => 'dyne_jobs_list',
  'field_heading' => 'Open positions',
  'field_anchor' => 'positions',
  'field_dynamic_jobs' => 1,
  'field_body_text' => [
    'value' => 'Interested in joining the Dyne team? See our open positions below. For more information or to apply, contact <a href="mailto:people@dyne-tx.com">people@dyne-tx.com</a>.',
    'format' => 'dyne_job_html',
  ],
  'field_items_json' => '[]',
]);
$jobs->save();

$references = [];
foreach ($careers->get('field_components')->referencedEntities() as $paragraph) {
  $references[] = [
    'target_id' => $paragraph->id(),
    'target_revision_id' => $paragraph->getRevisionId(),
  ];
}
$references[] = [
  'target_id' => $jobs->id(),
  'target_revision_id' => $jobs->getRevisionId(),
];
$careers->set('field_components', $references);
$careers->save();

print "Added dynamic Open positions Paragraph {$jobs->id()} to Careers + Culture.\n";