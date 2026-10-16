<?php

namespace Drupal\dyne_migrate\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * Complete, sanitized archive source; private records never enter web/.
 */
#[MigrateSource('dyne_archive')]
final class DyneArchive extends SourcePluginBase {

  protected function initializeIterator(): \Iterator {
    $module = \Drupal::service('extension.list.module')->getPath('dyne_migrate');
    $public = json_decode(file_get_contents(DRUPAL_ROOT . '/' . $module . '/data/legacy-content.json'), TRUE, 512, JSON_THROW_ON_ERROR);
    $path = dirname(DRUPAL_ROOT) . '/content-archive/wordpress-supplement.json';
    if (!is_file($path)) {
      throw new \RuntimeException('The complete private archive is required: content-archive/wordpress-supplement.json');
    }
    $supplement = json_decode(file_get_contents($path), TRUE, 512, JSON_THROW_ON_ERROR);
    if ($public['source_sha256'] !== $supplement['source_sha256']) {
      throw new \RuntimeException('Archive sources come from different snapshots.');
    }
    $records = [];
    foreach (array_merge($public['records'], $supplement['records']) as $record) {
      if ($record['bundle'] !== $this->configuration['bundle']) {
        continue;
      }
      $key = $record['source_type'] . ':' . $record['source_id'];
      if (isset($records[$key])) {
        throw new \RuntimeException('Duplicate archive source ID: ' . $key);
      }
      $records[$key] = ['source_key' => $key, 'source_id' => $record['source_id'], 'bundle' => $record['bundle'], 'record' => $record];
    }
    return new \ArrayIterator(array_values($records));
  }

  public function fields(): array {
    return ['source_key' => 'WordPress type and ID', 'source_id' => 'WordPress ID', 'bundle' => 'Existing Drupal bundle', 'record' => 'Complete archive record'];
  }

  public function getIds(): array {
    return ['source_key' => ['type' => 'string']];
  }

  public function __toString(): string {
    return 'Complete WordPress archive: ' . $this->configuration['bundle'];
  }

}
