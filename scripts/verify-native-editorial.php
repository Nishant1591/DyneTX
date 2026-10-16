<?php
/** Read-only comparison of native perspective cards with the pre-change page. */
$root = dirname(DRUPAL_ROOT);
require_once DRUPAL_ROOT . '/themes/custom/dynetx/includes/native-metadata.inc';
require_once DRUPAL_ROOT . '/themes/custom/dynetx/includes/native-components.inc';
$expected = json_decode(file_get_contents($root . '/output/document-6-review/perspective-cards-before.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$storage = \Drupal::entityTypeManager()->getStorage('node');
$ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'perspective')->condition('status', 1)->sort('field_story_date', 'DESC')->sort('nid', 'ASC')->execute();
$actual = [];
foreach ($storage->loadMultiple($ids) as $node) {
  $external = $node->get('field_external_link')->first();
  $actual[] = [
    'title' => _dynetx_native_title($node), 'author' => $node->get('field_author')->value ?? '',
    'summary' => $node->get('field_story_excerpt')->value ?? '',
    'photo' => rawurldecode(_dynetx_native_media_url($node, 'field_story_image')),
    'url' => $external && $external->uri ? $external->getUrl()->toString() : $node->toUrl()->toString(),
  ];
}
$failures = [];
foreach ($expected as $index => $card) {
  if ($card != ($actual[$index] ?? NULL)) {
    $failures[] = ['index' => $index, 'expected' => $card, 'actual' => $actual[$index] ?? NULL];
  }
}
if (count($expected) !== count($actual)) $failures[] = 'Card count differs';
$report = ['expected_count' => count($expected), 'actual_count' => count($actual), 'failures' => $failures, 'passed' => !$failures];
file_put_contents($root . '/output/document-6-review/perspective-card-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if ($failures) throw new RuntimeException('Perspective cards differ from the preserved page.');
