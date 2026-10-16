<?php

/**
 * @file
 * Transactional integration checks; no fixture nodes survive this test.
 * Run: ddev drush php:script web/modules/custom/dyne_content_sync/tests/test-feed-integration.php
 */
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\dyne_content_sync\FeedPayload;
use Drupal\dyne_content_sync\FeedSynchronizer;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

$assertions = 0;
$check = static function (bool $pass, string $label) use (&$assertions): void {
  if (!$pass) { throw new RuntimeException($label); }
  $assertions++;
};
$config = \Drupal::configFactory();
$config->addOverride(new class implements ConfigFactoryOverrideInterface {
  public function loadOverrides($names) { return ['dyne_content_sync.settings' => ['greenhouse_enabled' => TRUE, 'gcs_enabled' => TRUE, 'gcs_client_id' => 'fixture-not-a-credential']]; }
  public function getCacheSuffix() { return 'dyne-feed-integration-test'; }
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) { return NULL; }
  public function getCacheableMetadata($name) { return new CacheableMetadata(); }
});
$config->reset('dyne_content_sync.settings');
$storage = \Drupal::entityTypeManager()->getStorage('node');
$before = (int) $storage->getQuery()->accessCheck(FALSE)->count()->execute();
$transaction = \Drupal::database()->startTransaction();
$job = ['id' => '99999999999991', 'title' => 'Dyne transaction-only fixture', 'absolute_url' => 'https://job-boards.greenhouse.io/dynetherapeutics/jobs/99999999999991', 'content' => '<p>Validated fixture description</p>', 'departments' => [['name' => 'Clinical']], 'location' => ['name' => 'Waltham, MA']];
$service = static function (array $responses) use ($config): FeedSynchronizer {
  $http = new Client(['handler' => HandlerStack::create(new MockHandler($responses))]);
  return new FeedSynchronizer($http, \Drupal::entityTypeManager(), $config, \Drupal::state(), \Drupal::lock(), \Drupal::service('queue'), \Drupal::logger('dyne_content_sync'));
};
try {
  $sync = $service([new Response(200, ['Content-Type' => 'application/json'], json_encode(['jobs' => [$job]]))]);
  $preview = $sync->refresh('greenhouse', TRUE);
  $check($preview['create'] === 1 && $preview['queued'] === 0, 'Dry run predicts creation without queue writes');
  $check((int) $storage->getQuery()->accessCheck(FALSE)->count()->execute() === $before, 'Dry run leaves nodes unchanged');
  $row = FeedPayload::normalize('greenhouse', ['jobs' => [$job]])[0];
  $check($sync->process($row) === 'created', 'Validated feed row creates node');
  $ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'job')->condition('field_greenhouse_id', $row['id'])->execute();
  $check(count($ids) === 1, 'One stable remote ID creates one node');
  $node = $storage->load(reset($ids));
  $nid = $node->id();
  $vid = $node->getRevisionId();
  $check($node->get('field_application_link')->uri === $row['url'], 'Native application link populated');
  $check((bool) $node->get('field_native_presentation')->value, 'New feed nodes use native presentation');
  $check($sync->process($row) === 'unchanged', 'Repeat processing is idempotent');
  $check($storage->load($nid)->getRevisionId() === $vid, 'Idempotent replay creates no revision');
  $row['body'] = '<p>Changed provider description</p>';
  $check($sync->process($row) === 'updated', 'Changed provider content updates existing node');
  $check($storage->load($nid)->getRevisionId() !== $vid, 'Updates create a revision');
  $node = $storage->load($nid);
  $node->set('field_body_text', ['value' => '<p>Editor change</p>', 'format' => 'dyne_legacy_html']);
  $node->save();
  $conflict = FALSE;
  try { $row['body'] = '<p>New remote version</p>'; $sync->process($row); }
  catch (UnexpectedValueException $e) { $conflict = str_contains($e->getMessage(), 'Editorial changes'); }
  $check($conflict, 'Editorial change blocks overwrite');
  $node->setUnpublished(); $node->save();
  $check($sync->process($row) === 'protected' && !$storage->load($nid)->isPublished(), 'Unpublished node remains private');
  $news = ['id' => 'fixture-release-999991', 'title' => 'Dyne Therapeutics Fixture Release', 'releaseDate' => ['date' => '2026-10-16T10:30:00Z'], 'link' => ['hostedUrl' => 'https://investors.dyne-tx.com/news-releases/fixture'], 'body' => [['link' => ['url' => 'https://clientapi.gcs-web.com/data/fixture/body']]]];
  $gcs = $service([new Response(200, [], json_encode(['data' => [$news]])), new Response(200, [], json_encode(['data' => '<p>Full release body</p>']))]);
  $result = $gcs->refresh('gcs', TRUE);
  $check($result['create'] === 1 && $result['queued'] === 0, 'GCS detail body request validated in dry run');
  $failure = FALSE;
  try { $service([new Response(200, [], '{malformed')])->refresh('greenhouse', FALSE); }
  catch (UnexpectedValueException) { $failure = TRUE; }
  $check($failure, 'Malformed provider response cannot queue writes');
}
finally {
  $transaction->rollBack();
  unset($transaction);
  $storage->resetCache();
  \Drupal::state()->resetCache();
}
$check((int) $storage->getQuery()->accessCheck(FALSE)->count()->execute() === $before, 'All temporary writes rolled back');
$report = ['assertions' => $assertions, 'failures' => 0, 'node_count_before' => $before, 'node_count_after' => (int) $storage->getQuery()->accessCheck(FALSE)->count()->execute(), 'writes_rolled_back' => TRUE];
file_put_contents(dirname(DRUPAL_ROOT) . '/output/document-6-review/feed-integration-tests.json', json_encode($report, JSON_PRETTY_PRINT));
print json_encode($report, JSON_PRETTY_PRINT) . "\n";
