<?php

/**
 * @file
 * Reconcile the sanitized public WordPress archive into existing Drupal types.
 *
 * Run: drush php:script web/modules/custom/dyne_migrate/scripts/import-legacy.php
 * Preview: DYNE_IMPORT_DRY_RUN=1 drush php:script .../import-legacy.php
 *
 * Existing node IDs/aliases are retained. Each changed node gets a revision;
 * old Paragraphs are never deleted, so previous revisions remain restorable.
 * A source hash ledger makes repeat runs no-ops and preserves later editing.
 */

use Drupal\file\Entity\File;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\path_alias\Entity\PathAlias;

$module = dirname(__DIR__);
require_once $module . '/includes/title-aliases.inc';
$fixture = json_decode(file_get_contents($module . '/data/legacy-content.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$navigation_plan = json_decode(file_get_contents($module . '/data/legacy-navigation-update.json'), TRUE, 512, JSON_THROW_ON_ERROR)['records'];
require_once __DIR__ . '/navigation-normalizer.inc';
$navigation_aliases = array_fill_keys(array_map(static fn(array $record): string => rtrim($record['alias'], '/') ?: '/', $fixture['records']), TRUE);
$navigation_report = ['records' => [], 'fields' => [], 'entities' => [], 'links' => ['urls' => 0, 'targets' => 0, 'media' => 0]];
$dry_run = getenv('DYNE_IMPORT_DRY_RUN') === '1';
$state = \Drupal::state();
$ledger = $state->get('dyne_migrate.legacy_import', []);
$storage = \Drupal::entityTypeManager()->getStorage('node');
$aliases = \Drupal::entityTypeManager()->getStorage('path_alias');
$summary = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'paragraphs_created' => 0, 'menus_changed' => 0, 'metadata_updated' => 0];

if (!$dry_run) {
  require_once $module . '/dyne_migrate.install';
  _dyne_migrate_legacy_schema();
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}

$find_node = static function (string $alias) use ($aliases, $storage): ?Node {
  $matches = $aliases->loadByProperties(['alias' => $alias]);
  foreach ($matches as $match) {
    if (preg_match('#^/node/(\d+)$#', $match->getPath(), $id)) {
      return $storage->load($id[1]);
    }
  }
  return NULL;
};
$set_value = static function ($entity, string $name, $value): void {
  if (!$entity->hasField($name)) {
    throw new RuntimeException("Missing {$entity->getEntityTypeId()}.{$entity->bundle()}.$name");
  }
  $type = $entity->getFieldDefinition($name)->getType();
  if (in_array($name, ['field_items_json', 'field_rows_json', 'field_sections_json'], TRUE) && is_array($value)) {
    $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
  }
  if (in_array($type, ['text', 'text_long', 'text_with_summary'], TRUE) && is_string($value)) {
    $value = ['value' => $value, 'format' => 'dyne_legacy_html'];
  }
  $entity->set($name, $value);
};
$get_file = static function (string $url): ?File {
  $prefix = '/sites/default/files/';
  if (!str_starts_with($url, $prefix)) {
    return NULL;
  }
  $uri = 'public://' . rawurldecode(substr($url, strlen($prefix)));
  if (!is_file($uri)) {
    throw new RuntimeException('Missing imported asset: ' . $uri);
  }
  $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
  if ($files) {
    return reset($files);
  }
  $file = File::create(['uri' => $uri, 'status' => 1, 'uid' => 1]);
  $file->save();
  return $file;
};

$fill_metadata = static function (Node $node, array $record): bool {
  $changed = FALSE;
  if ($node->hasField('field_legacy_title') && $node->get('field_legacy_title')->isEmpty()) {
    $node->set('field_legacy_title', html_entity_decode($record['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $changed = TRUE;
  }
  if ($record['bundle'] === 'leader' && $node->hasField('field_leader_type') && $node->get('field_leader_type')->isEmpty()) {
    $name = in_array('board-of-directors', $record['groups'] ?? [], TRUE) ? 'Board' : (in_array('scientific-advisory-board', $record['groups'] ?? [], TRUE) ? 'SAB' : 'Leadership');
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'leader_type', 'name' => $name]);
    if ($terms) {
      $term = reset($terms);
      $node->set('field_leader_type', $term->id());
      $changed = TRUE;
    }
  }
  if (array_key_exists('link', $record) && !$record['link'] && $node->hasField('field_link') && !$node->get('field_link')->isEmpty()) {
    $uri = (string) $node->get('field_link')->uri;
    if (!preg_match('/^(?:https?:|internal:|entity:|mailto:)/', $uri)) {
      $node->set('field_link', []);
      $changed = TRUE;
    }
  }
  return $changed;
};

foreach ($fixture['records'] as $record) {
  $key = $record['source_type'] . ':' . $record['source_id'];
  $hash = hash('sha256', json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
  $node = isset($ledger[$key]['nid']) ? $storage->load($ledger[$key]['nid']) : $find_node($record['alias']);
  if ($node && ($ledger[$key]['hash'] ?? '') === $hash) {
    if (!$dry_run && $fill_metadata($node, $record)) {
      $node->setNewRevision(TRUE);
      $node->setRevisionLogMessage('Fill missing legacy title and source leader category.');
      $node->save();
      $summary['metadata_updated']++;
    }
    $summary['unchanged']++;
    continue;
  }
  if ($node && $node->bundle() !== $record['bundle']) {
    throw new RuntimeException('Bundle conflict at ' . $record['alias'] . '; nothing was deleted.');
  }
  // This known fixture change only repairs navigation. Apply the same token
  // transforms to current content instead of replacing editorial fields or
  // creating new Paragraph IDs. A hash guard prevents an unrelated source
  // version from accidentally taking this targeted migration path.
  if ($node && isset($navigation_plan[$key], $ledger[$key]) && $navigation_plan[$key]['normalized_hash'] === $hash) {
    if (!in_array($ledger[$key]['hash'], $navigation_plan[$key]['previous_hashes'] ?? [$navigation_plan[$key]['previous_hash']], TRUE)) {
      throw new RuntimeException('Unexpected navigation source version at ' . $record['alias'] . '; existing editorial content was not overwritten.');
    }
    if ($dry_run) {
      $summary['updated']++;
      continue;
    }
    $transaction = \Drupal::database()->startTransaction();
    try {
      $old_revision = $node->getRevisionId();
      if (_dyne_navigation_entity($node, $navigation_aliases, $navigation_report)) {
        $summary['updated']++;
      }
      else {
        $summary['unchanged']++;
      }
      $ledger[$key] = ['nid' => $node->id(), 'hash' => $hash, 'previous_revision' => $old_revision, 'imported_revision' => $node->getRevisionId()];
      $state->set('dyne_migrate.legacy_import', $ledger);
      $navigation_report['records'][] = $record['alias'];
      unset($transaction);
    }
    catch (Throwable $exception) {
      $transaction->rollBack();
      throw $exception;
    }
    continue;
  }
  $action = $node ? 'updated' : 'created';
  if ($dry_run) {
    $summary[$action]++;
    continue;
  }
  $transaction = \Drupal::database()->startTransaction();
  try {
    $old_revision = $node ? $node->getRevisionId() : NULL;
    $node = $node ?: Node::create(['type' => $record['bundle'], 'uid' => 1, 'status' => 1]);
    $node->setNewRevision(TRUE);
    $node->setRevisionLogMessage('Reconciled public legacy archive content (source ' . $key . '). Previous components retained in prior revision.');
    $node->setRevisionCreationTime(time());
    $full_title = html_entity_decode($record['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $node->setTitle(mb_strlen($full_title) > 255 ? mb_substr($full_title, 0, 254) . '…' : $full_title);
    $node->set('field_legacy_title', $full_title);
    if (isset($record['components'])) {
      $refs = [];
      foreach ($record['components'] as $component) {
        $paragraph = Paragraph::create(['type' => $component['type']]);
        foreach ($component['fields'] as $name => $value) {
          $set_value($paragraph, $name, $value);
        }
        $paragraph->save();
        $refs[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
        $summary['paragraphs_created']++;
      }
      $node->set('field_components', $refs);
    }
    if (isset($record['body'])) {
      $set_value($node, 'field_body_text', $record['body']);
      if ($node->hasField('field_bio') && $record['bundle'] === 'leader') {
        $set_value($node, 'field_bio', $record['body']);
      }
    }
    foreach ($record['fields'] ?? [] as $name => $value) {
      $set_value($node, $name, $value);
    }
    if (!empty($record['role'])) {
      $set_value($node, 'field_role', strip_tags($record['role']));
    }
    if (!empty($record['photo']) && ($file = $get_file($record['photo']))) {
      $node->set('field_image', ['target_id' => $file->id(), 'alt' => $record['title']]);
    }
    if (array_key_exists('link', $record) && !$record['link']) {
      $node->set('field_link', []);
    }
    if (!empty($record['link'])) {
      $url = $record['link'];
      $node->set('field_link', ['uri' => str_starts_with($url, '/') ? 'internal:' . $url : $url, 'title' => 'Read more']);
    }
    $fill_metadata($node, $record);
    // Reconciliation retains the source-owned alias. Preserve an existing
    // editor's opt-out, then restore that policy without another node save.
    $import_alias_state = dyne_migrate_preserve_import_alias($node);
    $node->save();
    $matches = $aliases->loadByProperties(['alias' => $record['alias']]);
    if (!$matches) {
      PathAlias::create(['path' => '/node/' . $node->id(), 'alias' => $record['alias'], 'langcode' => 'en'])->save();
    }
    dyne_migrate_restore_import_alias($node, $import_alias_state);
    $ledger[$key] = ['nid' => $node->id(), 'hash' => $hash, 'previous_revision' => $old_revision, 'imported_revision' => $node->getRevisionId()];
    $state->set('dyne_migrate.legacy_import', $ledger);
    $summary[$action]++;
    unset($transaction);
  }
  catch (Throwable $exception) {
    $transaction->rollBack();
    throw $exception;
  }
}

// Reconcile the source primary menu using actual menu-link UUID parent plugins.
// Keep all previous links restorable; disable superseded links, never delete.
$menu_hash = hash('sha256', json_encode($fixture['menus'], JSON_THROW_ON_ERROR));
if (!$dry_run && $state->get('dyne_migrate.legacy_menu_hash') !== $menu_hash) {
  $menu_storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
  $existing = $menu_storage->loadByProperties(['menu_name' => 'main']);
  $old_links = [];
  foreach ($existing as $link) {
    $old_links[$link->id()] = $link->toArray();
  }
  if (!$state->get('dyne_migrate.pre_legacy_menu')) {
    $state->set('dyne_migrate.pre_legacy_menu', $old_links);
  }
  $source_links = [];
  $used = [];
  $normal_title = static fn(string $value): string => mb_strtolower(trim(preg_replace('/\s+/u', ' ', str_replace('\xC2\xA0', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5))))));
  foreach ($fixture['menus'] as $item) {
    $parent = $item['parent'] ? ($source_links[$item['parent']]->getPluginId() ?? '') : '';
    $title = trim(strip_tags(str_replace(['<sup>TM</sup>', '<sup>™</sup>'], '™', $item['title'])));
    $uri = str_starts_with($item['url'], '/') ? 'internal:' . $item['url'] : $item['url'];
    $link = NULL;
    foreach ($existing as $candidate) {
      if (!isset($used[$candidate->id()]) && $normal_title($candidate->label()) === $normal_title($title) && $candidate->getParentId() === $parent) {
        $link = $candidate;
        break;
      }
    }
    $link = $link ?: MenuLinkContent::create(['menu_name' => 'main']);
    $link->set('title', $title)->set('link', ['uri' => $uri, 'options' => $item['target'] ? ['attributes' => ['target' => $item['target'], 'rel' => 'noopener noreferrer']] : []]);
    $link->set('parent', $parent)->set('weight', $item['weight'])->set('enabled', TRUE)->set('expanded', TRUE)->save();
    $used[$link->id()] = TRUE;
    $source_links[$item['source_id']] = $link;
    $summary['menus_changed']++;
  }
  foreach ($existing as $link) {
    if (!isset($used[$link->id()]) && $link->isEnabled()) {
      $link->set('enabled', FALSE)->save();
    }
  }
  $state->set('dyne_migrate.legacy_menu_hash', $menu_hash);
}
if (!$dry_run) {
  $home = $find_node('/home');
  if ($home && !$state->get('dyne_migrate.legacy_front_installed')) {
    \Drupal::configFactory()->getEditable('system.site')->set('page.front', '/node/' . $home->id())->save();
    $state->set('dyne_migrate.legacy_front_installed', TRUE);
  }
  \Drupal::service('cache_tags.invalidator')->invalidateTags(['node_list', 'paragraph_list', 'config:system.menu.main']);
}
print json_encode(['dry_run' => $dry_run, 'summary' => $summary, 'navigation_update' => $navigation_report, 'source_skipped' => $fixture['skipped'], 'source_assets' => $fixture['assets']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
