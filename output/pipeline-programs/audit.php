<?php
$storage = \Drupal::entityTypeManager()->getStorage('node');
$aliases = \Drupal::service('path_alias.manager');
$report = ['bundles' => [], 'program_records' => [], 'pages' => [], 'pipeline_paragraphs' => []];
foreach ($storage->loadMultiple() as $node) {
  $report['bundles'][$node->bundle()] = ($report['bundles'][$node->bundle()] ?? 0) + 1;
  $path = $aliases->getAliasByPath('/node/' . $node->id());
  if ($node->bundle() === 'pipeline_program' || preg_match('~^/(?:pipeline|dyne-[0-9]+|dmd|dm1|fshd|pompe)(?:/|-|$)~', $path)) {
    $record = ['id' => $node->id(), 'bundle' => $node->bundle(), 'title' => $node->label(), 'alias' => $path, 'status' => $node->isPublished(), 'fields' => []];
    foreach ($node->getFieldDefinitions() as $name => $definition) {
      if (str_starts_with($name, 'field_') && !$node->get($name)->isEmpty()) $record['fields'][$name] = $node->get($name)->getValue();
    }
    $report[$node->bundle() === 'pipeline_program' ? 'program_records' : 'pages'][] = $record;
  }
}
$paragraphs = \Drupal::entityTypeManager()->getStorage('paragraph');
foreach ($paragraphs->loadMultiple($paragraphs->getQuery()->accessCheck(FALSE)->condition('type', ['dyne_pipeline_table', 'dyne_pipeline_row', 'dyne_drug_detail', 'dyne_acf_program_focus'], 'IN')->execute()) as $paragraph) {
  $record = ['id' => $paragraph->id(), 'revision' => $paragraph->getRevisionId(), 'type' => $paragraph->bundle(), 'fields' => []];
  foreach ($paragraph->getFieldDefinitions() as $name => $definition) if (str_starts_with($name, 'field_') && !$paragraph->get($name)->isEmpty()) $record['fields'][$name] = $paragraph->get($name)->getValue();
  $report['pipeline_paragraphs'][] = $record;
}
file_put_contents(dirname(DRUPAL_ROOT) . '/output/pipeline-programs/before.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
print json_encode(['bundles' => $report['bundles'], 'program_records' => count($report['program_records']), 'pages' => array_map(static fn($x) => array_intersect_key($x, array_flip(['id', 'bundle', 'title', 'alias', 'status'])), $report['pages']), 'pipeline_paragraphs' => count($report['pipeline_paragraphs'])], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
