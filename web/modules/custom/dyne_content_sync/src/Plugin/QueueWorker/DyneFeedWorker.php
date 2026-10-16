<?php

namespace Drupal\dyne_content_sync\Plugin\QueueWorker;

use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Processes one validated record; retries failed updates without deleting nodes.
 */
#[QueueWorker(id: 'dyne_content_sync', title: new TranslatableMarkup('Dyne careers and news synchronization'), cron: ['time' => 30])]
final class DyneFeedWorker extends QueueWorkerBase {

  public function processItem($data): void {
    if (!is_array($data)) {
      throw new \UnexpectedValueException('Malformed Dyne feed queue item.');
    }
    \Drupal::service('dyne_content_sync.synchronizer')->process($data);
  }

}
