<?php
/** Verify native fields and Media against the preserved archive. Read only. */
$root = dirname(DRUPAL_ROOT);
require_once DRUPAL_ROOT . '/themes/custom/dynetx/includes/native-metadata.inc';
$read = static fn(string $path): array => json_decode(file_get_contents($root . '/' . $path), TRUE, 512, JSON_THROW_ON_ERROR);
$ledger = \Drupal::state()->get('dyne_migrate.native_content', []);
$mapping = array_merge(\Drupal::state()->get('dyne_migrate.legacy_import', []), \Drupal::state()->get('dyne_migrate.content_completion', []));
$source = array_column($read('content-archive/wordpress-records.json')['records'], NULL, 'id');
$metadata = array_column($read('web/modules/custom/dyne_migrate/data/legacy-page-metadata.json'), NULL, 'source_id');
$report = ['checked_nodes' => 0, 'checked_metadata' => 0, 'checked_media' => 0, 'bodies_present' => [], 'failures' => [], 'missing_attachments' => []];
foreach ($mapping as $key => $entry) {
  $id = (int) substr($key, strrpos($key, ':') + 1);
  $node = \Drupal\node\Entity\Node::load($entry['nid']);
  $record = $source[$id];
  if ((int) $node->get('field_wp_source_id')->value !== $id) {
    $report['failures'][] = "$key source ID mismatch";
  }
  $report['checked_nodes']++;
  $bundle = $node->bundle();
  $report['bodies_present'][$bundle] = ($report['bodies_present'][$bundle] ?? 0) + (int) !$node->get('field_body_text')->isEmpty();
  if (isset($metadata[$id])) {
    $expected = $metadata[$id];
    $actual = _dynetx_entity_metadata($node);
    // Node label is intentionally authoritative; original metadata title is not.
    unset($expected['title'], $actual['title']);
    foreach ($node->get('field_components')->referencedEntities() as $component) {
      if ($component->bundle() === 'dyne_hero') {
        $expected['hero']['line1'] = $component->get('field_title_line1')->value ?? '';
        $expected['hero']['line2'] = $component->get('field_title_line2')->value ?? '';
        break;
      }
    }
    // Drupal URL generation percent-encodes @; compare equivalent asset paths.
    foreach (['desktop', 'mobile', 'overlay'] as $part) {
      $actual['hero'][$part] = rawurldecode($actual['hero'][$part]);
      $expected['hero'][$part] = rawurldecode($expected['hero'][$part]);
    }
    if ($actual != $expected) {
      $report['failures'][] = ['source_id' => $id, 'reason' => 'Presentation metadata mismatch', 'expected' => $expected, 'actual' => $actual];
    }
    $report['checked_metadata']++;
  }
  $required = match ($bundle) {
    'job' => ['field_department', 'field_location', 'field_greenhouse_id', 'field_application_link'],
    'leader' => array_merge(['field_leader_type', 'field_weight'], !empty($record['meta']['_position']) ? ['field_role'] : [], !empty($record['meta']['_thumbnail_id']) ? ['field_photo'] : []),
    'news' => ['field_news_date', 'field_news_type'],
    'perspective' => ['field_story_date', 'field_story_image'], default => [],
  };
  foreach ($required as $name) {
    if ($node->get($name)->isEmpty()) {
      $report['failures'][] = "$key missing $name";
    }
  }
}
foreach ($read('content-archive/wordpress-media.json')['attachments'] as $attachment) {
  $id = $attachment['id'];
  if (!isset($ledger['media'][$id])) {
    $report['missing_attachments'][] = $id;
    continue;
  }
  $media = \Drupal\media\Entity\Media::load($ledger['media'][$id]);
  $source_field = $media->getSource()->getConfiguration()['source_field'];
  $file = $media->get($source_field)->entity;
  if (!$file || !is_file($file->getFileUri()) || $file->getFileUri() !== 'public://legacy/' . $attachment['relative_path']) {
    $report['failures'][] = "Attachment $id file mismatch";
  }
  if ($media->bundle() === 'image' && (string) $media->get($source_field)->alt !== (string) $attachment['alt']) {
    $report['failures'][] = "Attachment $id alt mismatch";
  }
  if (!\Drupal::service('file.usage')->listUsage($file)) {
    $report['failures'][] = "Attachment $id missing file usage";
  }
  $report['checked_media']++;
}
$report['expected_missing_attachments'] = [1850, 1851, 1855, 1858, 1859, 1860, 1864, 1865, 1866, 1868, 1869, 1870];
if ($report['missing_attachments'] !== $report['expected_missing_attachments']) {
  $report['failures'][] = 'Unexpected missing attachment set';
}
$report['media_total'] = (int) \Drupal::entityQuery('media')->accessCheck(FALSE)->count()->execute();
$report['passed'] = !$report['failures'];
file_put_contents($root . '/output/document-6-review/native-content-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if (!$report['passed']) {
  throw new RuntimeException('Native content verification failed; inspect the report.');
}
