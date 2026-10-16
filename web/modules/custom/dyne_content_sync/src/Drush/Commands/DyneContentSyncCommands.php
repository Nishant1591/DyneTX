<?php

namespace Drupal\dyne_content_sync\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Operator-controlled preview/queue entry point; never publishes on dry run.
 */
final class DyneContentSyncCommands extends DrushCommands {

  #[CLI\Command(name: 'dyne:sync')]
  #[CLI\Argument(name: 'provider', description: 'greenhouse or gcs')]
  #[CLI\Option(name: 'dry-run', description: 'Fetch and validate without queuing or changing content (default).')]
  #[CLI\Option(name: 'enqueue', description: 'Queue validated changes; requires this provider to be enabled.')]
  #[CLI\Usage(name: 'drush dyne:sync greenhouse --dry-run', description: 'Preview Greenhouse without writes.')]
  public function sync(string $provider, array $options = ['dry-run' => FALSE, 'enqueue' => FALSE]): void {
    $dry = !$options['enqueue'] || $options['dry-run'];
    $result = \Drupal::service('dyne_content_sync.synchronizer')->refresh($provider, $dry);
    $this->output()->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
  }

}
