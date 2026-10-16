<?php

namespace Drupal\dyne_migrate\Plugin\migrate\destination;

use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\path_alias\Entity\PathAlias;

/**
 * Adopts migrated nodes without overwriting editor changes; creates missing rows.
 *
 * Rollback only removes Migrate bookkeeping. Existing content, revisions and
 * aliases are deliberately preserved. A reviewed DB backup is the restore path.
 */
#[MigrateDestination('dyne_archive_node')]
final class DyneArchiveNode extends DestinationBase {

  protected $supportsRollback = TRUE;

  public function fields(): array {
    return ['nid' => 'Drupal node ID', 'record' => 'Complete archive record'];
  }

  public function getIds(): array {
    return ['nid' => ['type' => 'integer']];
  }

  public function import(Row $row, array $old_destination_id_values = []): array {
    $record = $row->getDestinationProperty('record');
    $key = $record['source_type'] . ':' . $record['source_id'];
    $state = \Drupal::state();
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $legacy = $state->get('dyne_migrate.legacy_import', []) + $state->get('dyne_migrate.content_completion', []);
    $ledger = $state->get('dyne_migrate.framework_import', []);
    $hash = hash('sha256', json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    if (isset($ledger[$key]) && $ledger[$key]['hash'] !== $hash) {
      throw new \RuntimeException("Changed archive $key needs reviewed reconciliation; current editorial content was preserved.");
    }
    $nid = $ledger[$key]['nid'] ?? $legacy[$key]['nid'] ?? $old_destination_id_values[0] ?? NULL;
    $node = $nid ? $storage->load($nid) : NULL;
    $aliases = \Drupal::entityTypeManager()->getStorage('path_alias');
    $existing_aliases = $aliases->loadByProperties(['alias' => $record['alias']]);
    foreach ($existing_aliases as $alias) {
      if (!preg_match('#^/node/(\d+)$#', $alias->getPath(), $match)) {
        throw new \RuntimeException('Non-node alias collision: ' . $record['alias']);
      }
      if ($nid && (int) $nid !== (int) $match[1]) {
        throw new \RuntimeException('Conflicting archive ID and alias: ' . $key);
      }
      $node ??= $storage->load($match[1]);
    }
    $this->rollbackAction = MigrateIdMapInterface::ROLLBACK_PRESERVE;
    if ($node && $node->hasField('field_wp_source_id') && !$node->get('field_wp_source_id')->isEmpty() && (int) $node->get('field_wp_source_id')->value !== (int) $record['source_id']) {
      throw new \RuntimeException('Source ID collision at archive alias: ' . $record['alias']);
    }
    if ($node && !isset($legacy[$key], $ledger[$key]) && !$nid && (!$node->hasField('field_wp_source_id') || $node->get('field_wp_source_id')->isEmpty())) {
      throw new \RuntimeException('Unverified existing node occupies archive alias: ' . $record['alias']);
    }
    if ($node && $node->bundle() !== $record['bundle']) {
      throw new \RuntimeException('Bundle collision: ' . $key);
    }
    if (!$node) {
      $transaction = \Drupal::database()->startTransaction();
      try {
        $node = Node::create(['type' => $record['bundle'], 'uid' => 1, 'status' => (int) ($record['published'] ?? TRUE)]);
        $title = html_entity_decode($record['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $node->setTitle(mb_strimwidth($title, 0, 255, '…'));
        $this->setField($node, 'field_legacy_title', $title);
        if ($node->hasField('field_wp_source_id')) {
          $this->setField($node, 'field_wp_source_id', $record['source_id']);
        }
        foreach ($record['fields'] ?? [] as $field => $value) {
          $this->setField($node, $field, $value);
        }
        if (isset($record['body'])) {
          $this->setField($node, 'field_body_text', $record['body']);
          if ($record['bundle'] === 'leader') {
            $this->setField($node, 'field_bio', $record['body']);
          }
        }
        if (isset($record['role'])) {
          $this->setField($node, 'field_role', strip_tags($record['role']));
        }
        if (isset($record['link']) && $record['link']) {
          $uri = str_starts_with($record['link'], '/') ? 'internal:' . $record['link'] : $record['link'];
          $this->setField($node, 'field_link', ['uri' => $uri, 'title' => 'Read more']);
        }
        if (!empty($record['photo'])) {
          $uri = 'public://' . rawurldecode(substr($record['photo'], strlen('/sites/default/files/')));
          $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
          if (!$files) {
            throw new \RuntimeException('Import managed media before archive nodes: ' . $uri);
          }
          $file = reset($files);
          $this->setField($node, 'field_image', ['target_id' => $file->id(), 'alt' => $title]);
        }
        if (isset($record['source_status'])) {
          foreach (['field_wp_source_id' => $record['source_id'], 'field_wp_source_type' => $record['source_type'], 'field_wp_source_status' => $record['source_status'], 'field_wp_source_data' => json_encode($record['source_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)] as $field => $value) {
            $this->setField($node, $field, $value);
          }
        }
        $refs = [];
        foreach ($record['components'] ?? [] as $component) {
          $paragraph = Paragraph::create(['type' => $component['type']]);
          // Archived components must pass through the native conversion once.
          if ($paragraph->hasField('field_native_components')) {
            $paragraph->set('field_native_components', FALSE);
          }
          foreach ($component['fields'] as $field => $value) {
            $this->setField($paragraph, $field, $value);
          }
          $paragraph->save();
          $refs[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
        }
        if (isset($record['components'])) {
          $node->set('field_components', $refs);
        }
        if (!empty($record['date']) && ($created = strtotime($record['date'] . ' UTC')) > 0) {
          $node->setCreatedTime($created);
        }
        $node->setRevisionLogMessage('Complete WordPress archive migration: ' . $key);
        $node->save();
        PathAlias::create(['path' => '/node/' . $node->id(), 'alias' => $record['alias'], 'langcode' => 'en'])->save();
        unset($transaction);
      }
      catch (\Throwable $error) {
        $transaction->rollBack();
        throw $error;
      }
    }
    $ledger[$key] = ['nid' => (int) $node->id(), 'hash' => $hash, 'preserved_revision' => (int) $node->getRevisionId()];
    $state->set('dyne_migrate.framework_import', $ledger);
    return [(int) $node->id()];
  }

  private function setField($entity, string $name, mixed $value): void {
    if (!$entity->hasField($name)) {
      throw new \RuntimeException('Missing archive field: ' . $entity->bundle() . '.' . $name);
    }
    $type = $entity->getFieldDefinition($name)->getType();
    if (str_ends_with($name, '_json') && is_array($value)) {
      $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
    if (is_string($value) && in_array($type, ['text', 'text_long', 'text_with_summary'], TRUE)) {
      $value = ['value' => $value, 'format' => 'dyne_legacy_html'];
    }
    $entity->set($name, $value);
  }

}
