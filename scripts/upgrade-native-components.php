<?php

/**
 * @file
 * Upgrade active structured components without rewriting historical revisions.
 *
 * Run after scripts/upgrade-native-content.php. A database backup is required.
 * Existing native records are never overwritten on repeat runs.
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

$project = dirname(DRUPAL_ROOT);
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/native-components.inc';
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/native-content.inc';
dyne_native_components_schema();
\Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
$storage = \Drupal::entityTypeManager()->getStorage('node');
$state = \Drupal::state();
$ledger = $state->get('dyne_migrate.native_components', []);
$legacy = array_merge($state->get('dyne_migrate.legacy_import', []), $state->get('dyne_migrate.framework_import', []));
$archive = json_decode(file_get_contents($project . '/content-archive/wordpress-records.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$records = array_column($archive['records'], NULL, 'id');
$report = ['created_quotes' => 0, 'converted_components' => [], 'updated_nodes' => [], 'careers_split' => FALSE, 'unchanged_components' => 0];

$quote = static function (string $key, string $name, string $text, string $photo = '', int $source_id = 0, int $width = 7) use (&$ledger, &$report, $storage): Node {
  if (isset($ledger['quotes'][$key]) && $existing = $storage->load($ledger['quotes'][$key])) {
    return $existing;
  }
  if ($source_id) {
    $found = $storage->loadByProperties(['type' => 'quote', 'field_wp_source_id' => $source_id]);
    if ($found) {
      $existing = reset($found);
      $ledger['quotes'][$key] = (int) $existing->id();
      return $existing;
    }
  }
  $node = Node::create(['type' => 'quote', 'title' => $name, 'status' => 1, 'uid' => 1, 'field_quote_text' => ['value' => $text, 'format' => 'dyne_legacy_html'], 'field_quote_width' => $width]);
  if ($source_id) {
    $node->set('field_wp_source_id', $source_id);
  }
  if ($photo && $media = dyne_native_media_for_url($photo)) {
    $node->set('field_speaker_image', $media->id());
  }
  $node->setRevisionLogMessage('Preserved source quotation as reusable native content.');
  $node->save();
  $ledger['quotes'][$key] = (int) $node->id();
  $report['created_quotes']++;
  return $node;
};
$archived_quote_refs = [];
foreach ($records as $record) {
  if ($record['type'] !== 'wb_quotes' || $record['status'] !== 'publish' || $record['password_protected']) {
    continue;
  }
  $attachment = $records[(int) ($record['meta']['_thumbnail_id'] ?? 0)] ?? [];
  $relative = $attachment['meta']['_wp_attached_file'] ?? '';
  $photo = $relative ? '/sites/default/files/legacy/' . $relative : '';
  $entity = $quote('wb_quotes:' . $record['id'], $record['title'], $record['content'], $photo, (int) $record['id']);
  $archived_quote_refs[] = ['target_id' => $entity->id()];
}
$state->set('dyne_migrate.native_components', $ledger);

$ref = static fn(Paragraph $paragraph): array => ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
$rich_text = static function (string $markup) use ($ref): array {
  $paragraph = Paragraph::create(['type' => 'dyne_rich_text', 'field_style' => ['value' => 'legacy-markup', 'format' => 'dyne_legacy_html'], 'field_body_text' => ['value' => $markup, 'format' => 'dyne_legacy_html']]);
  $paragraph->save();
  return $ref($paragraph);
};

foreach ($storage->loadMultiple() as $node) {
  if (!$node->hasField('field_components')) {
    continue;
  }
  $refs = [];
  $changed = FALSE;
  foreach ($node->get('field_components')->referencedEntities() as $paragraph) {
    if ($paragraph->hasField('field_native_components') && $paragraph->get('field_native_components')->value) {
      $refs[] = $ref($paragraph);
      $report['unchanged_components']++;
      continue;
    }
    $bundle = $paragraph->bundle();
    if ($bundle === 'dyne_rich_text' && str_contains((string) $paragraph->get('field_body_text')->value, 'id="quotesCarousel"')) {
      $body = (string) $paragraph->get('field_body_text')->value;
      $start_marker = '<div class="container-fluid section dynamic-dynamos">';
      $end_marker = '<div class="container-fluid section service-day bg-cyan-blue">';
      $start = strpos($body, $start_marker);
      $end = strpos($body, $end_marker);
      $quote_start = strpos($body, '<div class="row quotes-carousel">');
      if ($start === FALSE || $end === FALSE || $quote_start === FALSE || $end <= $quote_start) {
        throw new RuntimeException('Careers section markup changed; preserve current content and review the split.');
      }
      $intro_start = strpos($body, '<div class="container">', $start) + strlen('<div class="container">');
      $document = new DOMDocument();
      $previous = libxml_use_internal_errors(TRUE);
      $document->loadHTML('<?xml encoding="utf-8" ?>' . $body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
      libxml_clear_errors();
      libxml_use_internal_errors($previous);
      $xpath = new DOMXPath($document);
      $featured = [];
      foreach ($xpath->query('//*[@id="quotesCarousel"]//*[contains(concat(" ",normalize-space(@class)," ")," multi-carousel-item ")]') as $card) {
        $name = trim($xpath->evaluate('string(.//p[@class="title"])', $card));
        $text = trim($xpath->evaluate('string(.//p[@class="text"])', $card));
        $photo = $xpath->evaluate('string(.//img/@src)', $card);
        $classes = $xpath->evaluate('string(.//*[contains(@class,"quote-container")]/@class)', $card);
        preg_match('/\bcol-(\d+)\b/', $classes, $width);
        if (!$name || !$text) {
          throw new RuntimeException('Could not extract a complete featured employee quote.');
        }
        $entity = $quote('careers:' . hash('sha256', $name), $name, $text, $photo, 0, (int) ($width[1] ?? 7));
        $featured[] = ['target_id' => $entity->id()];
      }
      if (count($featured) !== 5 || count($archived_quote_refs) !== 4) {
        throw new RuntimeException('Careers quote count differs from the verified source (five featured, four archived).');
      }
      $refs[] = $rich_text(substr($body, 0, $start));
      $component = Paragraph::create(['type' => 'dyne_careers_quotes', 'field_native_components' => 1, 'field_body_text' => ['value' => substr($body, $intro_start, $quote_start - $intro_start), 'format' => 'dyne_legacy_html'], 'field_quotes' => $featured, 'field_archived_quotes' => $archived_quote_refs]);
      $component->save();
      $refs[] = $ref($component);
      $refs[] = $rich_text(substr($body, $end));
      $report['careers_split'] = ['previous_paragraph' => $paragraph->id(), 'paragraph' => $component->id(), 'featured_quotes' => count($featured), 'archived_quotes' => count($archived_quote_refs)];
      $changed = TRUE;
      continue;
    }
    if (!$paragraph->hasField('field_native_components')) {
      $refs[] = $ref($paragraph);
      continue;
    }
    $items = $paragraph->hasField('field_items_json') ? json_decode($paragraph->get('field_items_json')->value ?: '[]', TRUE, 512, JSON_THROW_ON_ERROR) : [];
    if ($bundle === 'dyne_team_grid') {
      $members = [];
      foreach ($items as $item) {
        $source_id = (int) ($item['source_id'] ?? 0);
        $nid = $legacy['team:' . $source_id]['nid'] ?? NULL;
        $member = $nid ? $storage->load($nid) : NULL;
        if (!$member || $member->bundle() !== 'leader') {
          throw new RuntimeException('Cannot resolve the team profile ' . ($item['name'] ?? $source_id));
        }
        $members[] = ['target_id' => $member->id()];
      }
      $paragraph->set('field_team_members', $members);
    }
    elseif ($bundle === 'dyne_news_list') {
      $paragraph->set('field_news_limit', count($items));
    }
    elseif ($bundle === 'dyne_jobs_list') {
      $paragraph->set('field_dynamic_jobs', TRUE);
    }
    else {
      throw new RuntimeException('Unexpected active legacy collection ' . $bundle . '; do not silently flatten its content.');
    }
    $paragraph->set('field_native_components', 1);
    $paragraph->setNewRevision(TRUE);
    $paragraph->save();
    $refs[] = $ref($paragraph);
    $report['converted_components'][] = ['id' => $paragraph->id(), 'bundle' => $bundle, 'revision' => $paragraph->getRevisionId(), 'items' => count($items)];
    $changed = TRUE;
  }
  if ($changed) {
    $node->set('field_components', $refs);
    $node->setNewRevision(TRUE);
    $node->setRevisionLogMessage('Use native team/news/quote components; previous component revisions retained.');
    $node->save();
    $report['updated_nodes'][] = $node->id();
  }
}
$photo_backfill = [];
// Early runs before local theme Media support may have left these five image
// references empty. Complete that bounded repair once, retaining later edits.
if (empty($ledger['featured_quote_media_completed'])) {
  $fixture = json_decode(file_get_contents(DRUPAL_ROOT . '/modules/custom/dyne_migrate/data/legacy-content.json'), TRUE, 512, JSON_THROW_ON_ERROR);
  foreach ($fixture['records'] as $record) {
    if (($record['alias'] ?? '') !== '/careers-culture') {
      continue;
    }
    foreach ($record['components'] ?? [] as $source_component) {
      $markup = $source_component['fields']['field_body_text'] ?? '';
      if (!str_contains($markup, 'id="quotesCarousel"')) {
        continue;
      }
      $document = new DOMDocument();
      $previous = libxml_use_internal_errors(TRUE);
      $document->loadHTML('<?xml encoding="utf-8" ?>' . $markup, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
      libxml_clear_errors();
      libxml_use_internal_errors($previous);
      $xpath = new DOMXPath($document);
      foreach ($xpath->query('//*[@id="quotesCarousel"]//*[contains(concat(" ",normalize-space(@class)," ")," multi-carousel-item ")]') as $card) {
        $name = trim($xpath->evaluate('string(.//p[@class="title"])', $card));
        $key = 'careers:' . hash('sha256', $name);
        $node = isset($ledger['quotes'][$key]) ? $storage->load($ledger['quotes'][$key]) : NULL;
        if (!$node) {
          throw new RuntimeException('Featured quote not found during Media completion: ' . $name);
        }
        if ($node->get('field_speaker_image')->isEmpty()) {
          $media = dyne_native_media_for_url($xpath->evaluate('string(.//img/@src)', $card));
          if (!$media) {
            throw new RuntimeException('Missing featured quote portrait: ' . $name);
          }
          $node->set('field_speaker_image', $media->id());
          $node->setNewRevision(TRUE);
          $node->setRevisionLogMessage('Complete native Media reference for existing employee portrait.');
          $node->save();
          $photo_backfill[] = $node->id();
        }
      }
    }
  }
  $ledger['featured_quote_media_completed'] = TRUE;
}
$report['quote_portraits_completed'] = $photo_backfill;
$state->set('dyne_migrate.native_components', $ledger);
file_put_contents($project . '/output/document-6-review/native-components-applied.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
print json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
