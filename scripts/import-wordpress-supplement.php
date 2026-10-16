<?php

/**
 * Add missing editorial records and register the complete attachment library.
 * Run with ddev drush php:script scripts/import-wordpress-supplement.php.
 * DYNE_COMPLETION_DRY_RUN=1 previews without schema or database writes.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\system\Entity\Menu;
use Drupal\menu_link_content\Entity\MenuLinkContent;

$root = dirname(DRUPAL_ROOT);
$read = static fn(string $path): array => json_decode(file_get_contents($root . '/' . $path), TRUE, 512, JSON_THROW_ON_ERROR);
$fixture = $read('content-archive/wordpress-supplement.json');
$source = $read('content-archive/wordpress-records.json');
$media = $read('content-archive/wordpress-media.json');
$dry = getenv('DYNE_COMPLETION_DRY_RUN') === '1';
$state = \Drupal::state();
$ledger = $state->get('dyne_migrate.content_completion', []);
$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
$report = ['dry_run' => $dry, 'source_sha256' => $fixture['source_sha256'], 'created' => 0,
  'unchanged' => 0, 'files_created' => 0, 'files_existing' => 0, 'menus_created' => 0,
  'records' => [], 'media' => [], 'missing' => [], 'archive_path' => 'content-archive/wordpress-records.json'];

if (!$dry) {
  foreach (['dyne_page', 'news'] as $bundle) {
    foreach (['field_wp_source_id' => ['integer', 'WordPress record ID'],
      'field_wp_source_type' => ['string', 'WordPress record type'],
      'field_wp_source_status' => ['string', 'Original WordPress status'],
      'field_wp_source_data' => ['string_long', 'Original WordPress content and metadata (archive)']] as $name => [$type, $label]) {
      if (!FieldStorageConfig::loadByName('node', $name)) {
        FieldStorageConfig::create(['field_name' => $name, 'entity_type' => 'node', 'type' => $type, 'cardinality' => 1])->save();
      }
      if (!FieldConfig::loadByName('node', $bundle, $name)) {
        FieldConfig::create(['field_name' => $name, 'entity_type' => 'node', 'bundle' => $bundle, 'label' => $label])->save();
      }
    }
    // Archived data is text, not rendered markup, and is hidden on public views.
    $form = EntityFormDisplay::load("node.$bundle.default");
    if ($form) {
      foreach (['field_wp_source_id', 'field_wp_source_type', 'field_wp_source_status', 'field_wp_source_data'] as $weight => $name) {
        $form->setComponent($name, ['type' => $name === 'field_wp_source_id' ? 'number' : ($name === 'field_wp_source_data' ? 'string_textarea' : 'string_textfield'), 'weight' => 40 + $weight]);
      }
      $form->save();
    }
  }
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}

foreach ($fixture['records'] as $record) {
  $key = $record['source_type'] . ':' . $record['source_id'];
  $hash = hash('sha256', json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
  $node = isset($ledger[$key]) ? $node_storage->load($ledger[$key]['nid']) : NULL;
  if ($node) {
    if ($ledger[$key]['hash'] !== $hash) {
      throw new RuntimeException("Changed source $key requires review; existing editorial content was not overwritten.");
    }
    $report['unchanged']++;
  }
  else {
    if ($alias_storage->loadByProperties(['alias' => $record['alias']])) {
      throw new RuntimeException('Alias already occupied: ' . $record['alias']);
    }
    $report['created']++;
    if (!$dry) {
      $transaction = \Drupal::database()->startTransaction();
      try {
        $node = Node::create(['type' => $record['bundle'], 'uid' => 1, 'status' => (int) $record['published']]);
        $node->setTitle(mb_strimwidth($record['title'], 0, 255, '…'));
        $node->set('field_legacy_title', $record['title']);
        $node->set('field_body_text', ['value' => $record['body'], 'format' => 'dyne_legacy_html']);
        $node->set('field_wp_source_id', $record['source_id']);
        $node->set('field_wp_source_type', $record['source_type']);
        $node->set('field_wp_source_status', $record['source_status']);
        $node->set('field_wp_source_data', json_encode($record['source_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $node->setRevisionLogMessage($record['reason']);
        $created = strtotime($record['date'] . ' UTC');
        if ($created > 0) {
          $node->setCreatedTime($created);
        }
        if ($record['bundle'] === 'news') {
          $node->set('field_date_text', $created > 0 ? gmdate('F j, Y', $created) : '');
        }
        if (isset($record['components'])) {
          $refs = [];
          foreach ($record['components'] as $component) {
            $paragraph = Paragraph::create(['type' => $component['type']]);
            foreach ($component['fields'] as $name => $value) {
              $type = $paragraph->getFieldDefinition($name)->getType();
              $paragraph->set($name, in_array($type, ['text', 'text_long', 'text_with_summary'], TRUE) ? ['value' => $value, 'format' => 'dyne_legacy_html'] : $value);
            }
            $paragraph->save();
            $refs[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
          }
          $node->set('field_components', $refs);
        }
        $node->save();
        PathAlias::create(['path' => '/node/' . $node->id(), 'alias' => $record['alias'], 'langcode' => 'en'])->save();
        $ledger[$key] = ['nid' => $node->id(), 'hash' => $hash];
        $state->set('dyne_migrate.content_completion', $ledger);
        unset($transaction);
      }
      catch (Throwable $error) {
        $transaction->rollBack();
        throw $error;
      }
    }
  }
  $report['records'][(string) $record['source_id']] = ['source_id' => $record['source_id'], 'nid' => $node?->id(), 'bundle' => $record['bundle'], 'published' => $node ? $node->isPublished() : $record['published'], 'alias' => $record['alias'], 'disposition' => $record['reason']];
}

// Source attachment metadata stays in the private portable catalog. Drupal's
// existing File module manages originals; thumbnail derivatives remain files.
foreach ($media['attachments'] as $attachment) {
  $id = (string) $attachment['id'];
  $path = $attachment['relative_path'] ?? '';
  if (!$path || str_starts_with($path, '/') || in_array('..', explode('/', $path), TRUE)) {
    $report['missing'][] = ['source_id' => $id, 'reason' => 'No safe attachment path'];
    continue;
  }
  $uri = 'public://legacy/' . $path;
  if (!is_file($uri)) {
    $report['missing'][] = ['source_id' => $id, 'path' => $path, 'reason' => 'Original file unavailable'];
    continue;
  }
  $matches = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
  $file = $matches ? reset($matches) : NULL;
  if ($file) {
    $report['files_existing']++;
  }
  else {
    $report['files_created']++;
    if (!$dry) {
      $file = File::create(['uri' => $uri, 'filename' => basename($path), 'status' => 1, 'uid' => 1]);
      $file->save();
    }
  }
  $report['media'][$id] = ['source_id' => (int) $id, 'fid' => $file?->id(), 'uri' => $uri, 'local_path' => '/sites/default/files/legacy/' . str_replace('%2F', '/', rawurlencode($path)), 'disposition' => 'Managed Drupal file; title, alt text, caption and source association retained in the attachment catalog.'];
}

// Preserve the unused seven-link source menu in a separate, unplaced menu.
// Keeping links disabled prevents accidentally adding archived navigation.
$menu_id = 'legacy-mobile';
$source_nodes = [];
foreach ($read('web/modules/custom/dyne_migrate/data/legacy-content.json')['records'] as $item) {
  $source_nodes[$item['source_id']] = $item['alias'];
}
foreach ($fixture['records'] as $item) {
  $source_nodes[$item['source_id']] = $item['alias'];
}
$menu_ledger = $state->get('dyne_migrate.archived_menu', []);
$primary_ids = array_column($read('web/modules/custom/dyne_migrate/data/legacy-content.json')['menus'], 'source_id');
$items = array_filter($source['records'], static fn(array $p): bool => $p['type'] === 'nav_menu_item' && !in_array($p['id'], $primary_ids, TRUE));
uasort($items, static fn(array $a, array $b): int => $a['order'] <=> $b['order']);
if ($items && !$dry && !Menu::load($menu_id)) {
  Menu::create(['id' => $menu_id, 'label' => 'WordPress mobile navigation (archive)', 'description' => 'Unused source menu retained for completeness; not placed on the website.'])->save();
}
foreach ($items as $item) {
  $meta = $item['meta'];
  $id = (string) $item['id'];
  $url = $source_nodes[(int) ($meta['_menu_item_object_id'] ?? 0)] ?? ($meta['_menu_item_url'] ?? '');
  if (!$url) {
    $report['records'][$id] = ['source_id' => $item['id'], 'disposition' => 'Unresolved original menu destination; preserved in private source catalog.'];
    continue;
  }
  if (str_starts_with($url, '#')) {
    $url = '/' . $url;
  }
  $link = isset($menu_ledger[$id]) ? MenuLinkContent::load($menu_ledger[$id]) : NULL;
  if (!$link) {
    $report['menus_created']++;
    if (!$dry) {
      $parent_id = $menu_ledger[$meta['_menu_item_menu_item_parent'] ?? 0] ?? NULL;
      $parent = $parent_id ? MenuLinkContent::load($parent_id) : NULL;
      $label = strip_tags(html_entity_decode($item['title'], ENT_QUOTES | ENT_HTML5));
      if (!$label) {
        foreach ($source['records'] as $target) {
          if ($target['id'] === (int) ($meta['_menu_item_object_id'] ?? 0)) {
            $label = strip_tags($target['title']);
            break;
          }
        }
      }
      $link = MenuLinkContent::create(['menu_name' => $menu_id, 'title' => $label ?: 'Archived navigation link', 'link' => ['uri' => str_starts_with($url, '/') ? 'internal:' . $url : $url], 'enabled' => FALSE, 'weight' => $item['order'], 'parent' => $parent?->getPluginId() ?? '']);
      $link->save();
      $menu_ledger[$id] = $link->id();
      $state->set('dyne_migrate.archived_menu', $menu_ledger);
    }
  }
  $report['records'][$id] = ['source_id' => $item['id'], 'menu_link_id' => $link?->id(), 'disposition' => 'Preserved disabled in WordPress mobile navigation (archive); current primary navigation unchanged.'];
}
$report['node_total'] = (int) $node_storage->getQuery()->accessCheck(FALSE)->count()->execute();
$report['complete'] = !$dry && !$report['missing'];
$report_path = $root . '/output/content-audit/content-completion' . ($dry ? '-dry-run' : '') . '.json';
file_put_contents($report_path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
echo json_encode(array_diff_key($report, array_flip(['records', 'media'])), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
