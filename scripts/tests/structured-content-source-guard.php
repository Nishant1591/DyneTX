<?php
/** Pure in-memory Job import checks: no entity, state or revision saves. */
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/structured-content.inc';
$storage = \Drupal::entityTypeManager()->getStorage('node');
$original = $storage->load(28);
$baseline = \Drupal::state()->get('dyne_migrate.structured_baseline.28');
if ($baseline !== dyne_structured_editorial_hash($original)) throw new RuntimeException('Refresh initial normalized source baselines before this read-only guard check.');
$source = (string) $original->get('field_body_text')->value;
$marker = 'DYNE_IN_MEMORY_SOURCE_CHECK';
$incoming = clone $original;
$incoming->setOriginal(clone $original);
$incoming->set('field_body_text', ['value' => $source . '<p>' . $marker . '</p>', 'format' => $original->get('field_body_text')->format]);
dyne_migrate_node_presave($incoming);
if (!str_contains((string) $incoming->get('field_content_template')->value . json_encode($incoming->get('field_additional_job_content')->getValue()) . json_encode($incoming->get('field_equal_opportunity')->getValue()), $marker)) throw new RuntimeException('Accepted source copy was not retained by native fields/template.');
$edited = clone $original;
$edited->set('field_role_summary', ['value' => '<p>Native editorial change retained in memory.</p>', 'format' => 'dyne_job_html']);
$conflicting = clone $edited;
$conflicting->setOriginal(clone $edited);
$conflicting->set('field_body_text', ['value' => $source . '<p>Conflicting source revision.</p>', 'format' => $original->get('field_body_text')->format]);
$rejected = FALSE;
try { dyne_migrate_node_presave($conflicting); }
catch (\RuntimeException $error) {
  if (!str_contains($error->getMessage(), 'edited structured content')) throw $error;
  $rejected = TRUE;
}
if (!$rejected) throw new RuntimeException('Source import overwrote an in-memory native editorial change.');
$storage->resetCache([28]);
if ($baseline !== dyne_structured_editorial_hash($storage->load(28))) throw new RuntimeException('Read-only guard check changed persistent content.');
$report = ['passed' => TRUE, 'unchanged_source_baseline_accepts_incoming_copy_in_memory' => TRUE, 'native_editorial_change_blocks_conflicting_source_in_memory' => TRUE, 'persistent_content_unchanged' => TRUE, 'entity_saves' => 0, 'state_writes' => 0];
file_put_contents(dirname(DRUPAL_ROOT) . '/output/structured-model/source-guard-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
