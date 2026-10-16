<?php

namespace Drupal\dyne_content_sync;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Fetches validated complete batches; queues repeatable, revisioned node updates.
 */
final class FeedSynchronizer {

  public function __construct(
    private ClientInterface $http,
    private EntityTypeManagerInterface $entities,
    private ConfigFactoryInterface $configs,
    private StateInterface $state,
    private LockBackendInterface $lock,
    private QueueFactory $queues,
    private LoggerInterface $logger,
  ) {}

  public function refresh(string $provider, bool $dry_run = TRUE): array {
    if (!in_array($provider, ['greenhouse', 'gcs'], TRUE)) {
      throw new \InvalidArgumentException('Use greenhouse or gcs.');
    }
    $config = $this->configs->get('dyne_content_sync.settings');
    if (!$dry_run && !$config->get($provider . '_enabled')) {
      throw new \UnexpectedValueException('Provider is disabled. Enable it after reviewing the dry run.');
    }
    $lock = 'dyne_content_sync.fetch.' . $provider;
    if (!$this->lock->acquire($lock, 180)) {
      throw new \UnexpectedValueException('A feed refresh is already running.');
    }
    try {
      if ($provider === 'greenhouse') {
        // This integration is specific to Dyne; never silently switch employers.
        if ($config->get('greenhouse_board') !== 'dynetherapeutics') {
          throw new \UnexpectedValueException('Only the verified dynetherapeutics board is supported.');
        }
        $payload = $this->request('https://boards-api.greenhouse.io/v1/boards/dynetherapeutics/jobs?content=true');
      }
      else {
        $client = $config->get('gcs_client_id');
        if (!$client || !preg_match('/^[A-Za-z0-9_-]+$/D', $client)) {
          throw new \UnexpectedValueException('Set the approved GCS client identifier using a settings.php override.');
        }
        $payload = $this->request('https://clientapi.gcs-web.com/data/' . $client . '/News');
        if (!isset($payload['data']) || !is_array($payload['data']) || !$payload['data']) {
          throw new \UnexpectedValueException('GCS returned no releases; existing content was preserved.');
        }
        foreach ($payload['data'] as &$item) {
          $url = $item['body'][0]['link']['url'] ?? '';
          if (!FeedPayload::allowedUrl($url, ['clientapi.gcs-web.com'])) {
            throw new \UnexpectedValueException('GCS body endpoint is outside the verified provider host.');
          }
          $detail = $this->request($url);
          $item['_resolved_body'] = $detail['data'] ?? NULL;
        }
        unset($item);
      }
      $rows = FeedPayload::normalize($provider, $payload);
      $summary = ['provider' => $provider, 'dry_run' => $dry_run, 'received' => count($rows), 'create' => 0, 'update' => 0, 'unchanged' => 0, 'protected' => 0, 'queued' => 0, 'unpublished_or_deleted' => 0];
      $planned = [];
      // Validate all mappings before enqueueing any writes.
      foreach ($rows as $row) {
        $node = $this->findNode($row);
        $action = !$node ? 'create' : (!$node->isPublished() ? 'protected' : ($this->matches($node, $row) ? 'unchanged' : 'update'));
        $summary[$action]++;
        if (in_array($action, ['create', 'update'], TRUE)) {
          $planned[] = $row;
        }
      }
      if (!$dry_run) {
        foreach ($planned as $row) {
          $hash = hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
          $queued = (int) $this->state->get('dyne_content_sync.queued.' . $hash, 0);
          if ($queued && $queued > time() - 7200) {
            continue;
          }
          $this->queues->get('dyne_content_sync')->createItem($row);
          $this->state->set('dyne_content_sync.queued.' . $hash, time());
          $summary['queued']++;
        }
        $this->state->set('dyne_content_sync.last_queued.' . $provider, time());
      }
      return $summary;
    }
    finally {
      $this->lock->release($lock);
    }
  }

  public function process(array $row): string {
    $provider = $row['provider'] ?? '';
    if (!in_array($provider, ['greenhouse', 'gcs'], TRUE) || !$this->configs->get('dyne_content_sync.settings')->get($provider . '_enabled')) {
      throw new \UnexpectedValueException('Queued provider is disabled or invalid.');
    }
    $key = $provider . ':' . $row['id'];
    if (!$this->lock->acquire('dyne_content_sync.row.' . $key, 120)) {
      throw new \Drupal\Core\Queue\RequeueException('Record is being synchronized.');
    }
    try {
      $node = $this->findNode($row);
      if ($node && !$node->isPublished()) {
        return 'protected';
      }
      if ($node && $this->matches($node, $row)) {
        return 'unchanged';
      }
      $baseline = $this->state->get('dyne_content_sync.node_hash.' . $key);
      if ($node && $baseline && $baseline !== $this->nodeHash($node, $provider)) {
        throw new \UnexpectedValueException('Editorial changes conflict with feed update; node preserved: ' . $node->id());
      }
      $transaction = \Drupal::database()->startTransaction();
      try {
        $created = !$node;
        $node ??= $this->entities->getStorage('node')->create(['type' => $provider === 'greenhouse' ? 'job' : 'news', 'uid' => 1, 'status' => 1]);
        if ($created && $node->hasField('field_native_presentation')) {
          $node->set('field_native_presentation', TRUE);
          $templates = $this->entities->getStorage('node')->getQuery()->accessCheck(FALSE)->condition('type', $node->bundle())->condition('status', 1)->range(0, 1)->execute();
          $template = $templates ? $this->entities->getStorage('node')->load(reset($templates)) : NULL;
          foreach ($node->getFieldDefinitions() as $name => $definition) {
            if ($template && (str_starts_with($name, 'field_hero_') || $name === 'field_body_class')) {
              $node->set($name, $template->get($name)->getValue());
            }
          }
        }
        $node->setTitle(mb_strimwidth($row['title'], 0, 255, '…'));
        $this->set($node, 'field_legacy_title', $row['title']);
        $this->set($node, 'field_body_text', ['value' => $row['body'], 'format' => 'dyne_legacy_html']);
        if ($node->hasField('body')) {
          $this->set($node, 'body', ['value' => $row['body'], 'format' => 'dyne_legacy_html']);
        }
        if ($provider === 'greenhouse') {
          $this->set($node, 'field_greenhouse_id', $row['id']);
          $this->set($node, 'field_apply_url', $row['url']);
          $this->set($node, 'field_application_link', ['uri' => $row['url']]);
          $this->set($node, 'field_department_text', $row['department']);
          $this->set($node, 'field_department_group', preg_replace('/^\S+\s*-\s*/', '', $row['department']));
          $this->set($node, 'field_location_text', $row['location']);
          $this->setTerm($node, 'field_department', 'department', $row['department']);
          $this->setTerm($node, 'field_location', 'job_location', $row['location']);
        }
        else {
          $this->set($node, 'field_external_id', $row['id']);
          $this->set($node, 'field_news_date', $row['date']);
          $this->set($node, 'field_date_text', gmdate('F j, Y', strtotime($row['date'] . 'Z')));
          $this->set($node, 'field_link', ['uri' => $row['url'], 'title' => 'Read more']);
          if ($node->hasField('field_external_url')) {
            $this->set($node, 'field_external_url', ['uri' => $row['url']]);
          }
          if ($node->hasField('field_news_type')) {
            $this->set($node, 'field_news_type', 'url');
          }
        }
        $node->setNewRevision(TRUE);
        $node->setRevisionLogMessage('Validated ' . $provider . ' feed update: ' . $row['id']);
        $node->save();
        if ($created) {
          $slug = trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $row['title'])), '-');
          $alias = '/' . ($provider === 'greenhouse' ? 'jobs/' : 'news/') . substr($slug, 0, 160) . '-' . $row['id'];
          if ($this->entities->getStorage('path_alias')->loadByProperties(['alias' => $alias])) {
            throw new \UnexpectedValueException('Feed alias collision; transaction rolled back.');
          }
          $this->entities->getStorage('path_alias')->create(['path' => '/node/' . $node->id(), 'alias' => $alias, 'langcode' => 'en'])->save();
        }
        $this->entities->getStorage('node')->resetCache([$node->id()]);
        $persisted = $this->entities->getStorage('node')->load($node->id());
        $this->state->set('dyne_content_sync.node_hash.' . $key, $this->nodeHash($persisted, $provider));
        $this->state->delete('dyne_content_sync.queued.' . hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)));
        unset($transaction);
        return $created ? 'created' : 'updated';
      }
      catch (\Throwable $error) {
        $transaction->rollBack();
        throw $error;
      }
    }
    finally {
      $this->lock->release('dyne_content_sync.row.' . $key);
    }
  }

  private function findNode(array $row): ?NodeInterface {
    $field = $row['provider'] === 'greenhouse' ? 'field_greenhouse_id' : 'field_external_id';
    $bundle = $row['provider'] === 'greenhouse' ? 'job' : 'news';
    $storage = $this->entities->getStorage('node');
    $ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', $bundle)->condition($field, $row['id'])->execute();
    if (count($ids) > 1) {
      throw new \UnexpectedValueException('Duplicate remote IDs in Drupal; review before synchronization.');
    }
    return $ids ? $storage->load(reset($ids)) : NULL;
  }

  private function matches(NodeInterface $node, array $row): bool {
    $fields = $this->values($row);
    foreach ($fields as $name => $value) {
      if (!$node->hasField($name) || $node->get($name)->value !== $value) {
        return FALSE;
      }
    }
    if ($row['provider'] === 'gcs' && (!$node->hasField('field_external_url') || $node->get('field_external_url')->uri !== $row['url'])) {
      return FALSE;
    }
    return $node->label() === mb_strimwidth($row['title'], 0, 255, '…');
  }

  private function values(array $row): array {
    return ['field_body_text' => $row['body']] + ($row['provider'] === 'greenhouse' ? ['field_apply_url' => $row['url'], 'field_department_text' => $row['department'], 'field_location_text' => $row['location']] : ['field_news_date' => $row['date']]);
  }

  private function nodeHash(NodeInterface $node, string $provider): string {
    $fields = ['title', 'field_body_text', 'body', 'field_legacy_title', 'field_apply_url', 'field_application_link', 'field_department_text', 'field_department_group', 'field_location_text', 'field_department', 'field_location', 'field_news_date', 'field_date_text', 'field_link', 'field_external_url', 'field_news_type'];
    $values = [];
    foreach ($fields as $name) {
      if ($node->hasField($name)) {
        $values[$name] = $node->get($name)->getValue();
      }
    }
    return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
  }

  private function set(NodeInterface $node, string $name, mixed $value): void {
    if (!$node->hasField($name)) {
      throw new \UnexpectedValueException('Missing feed field: ' . $name . '; run the native-field upgrade first.');
    }
    $node->set($name, $value);
  }

  private function setTerm(NodeInterface $node, string $field, string $vocabulary, string $name): void {
    if (!$node->hasField($field) || !$name) {
      return;
    }
    $terms = $this->entities->getStorage('taxonomy_term')->loadByProperties(['vid' => $vocabulary, 'name' => $name]);
    $term = $terms ? reset($terms) : $this->entities->getStorage('taxonomy_term')->create(['vid' => $vocabulary, 'name' => $name]);
    if ($term->isNew()) {
      $term->save();
    }
    $node->set($field, $term->id());
  }

  private function request(string $url): array {
    try {
      $response = $this->http->request('GET', $url, ['timeout' => 30, 'connect_timeout' => 10, 'allow_redirects' => FALSE, 'headers' => ['Accept' => 'application/json']]);
      if ($response->getStatusCode() !== 200) {
        throw new \UnexpectedValueException('Provider did not return HTTP 200.');
      }
      $body = (string) $response->getBody();
      if (strlen($body) > 20 * 1024 * 1024) {
        throw new \UnexpectedValueException('Provider response exceeds the safe import size.');
      }
      $payload = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
      if (!is_array($payload)) {
        throw new \UnexpectedValueException('Provider did not return a JSON object.');
      }
      return $payload;
    }
    catch (\Throwable $error) {
      // The exception must not disclose a client key from a URL.
      throw new \UnexpectedValueException('Provider request or JSON validation failed; content was preserved.');
    }
  }

}
