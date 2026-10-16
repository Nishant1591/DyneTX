<?php
use Drupal\dyne_content_sync\FeedSynchronizer;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
$path = dirname(DRUPAL_ROOT) . '/output/document-6-review/greenhouse-live-response.json';
$body = file_get_contents($path);
$http = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200, [], $body)]))]);
$service = new FeedSynchronizer($http, \Drupal::entityTypeManager(), \Drupal::configFactory(), \Drupal::state(), \Drupal::lock(), \Drupal::service('queue'), \Drupal::logger('dyne_content_sync'));
$report = $service->refresh('greenhouse', TRUE) + ['source' => 'https://boards-api.greenhouse.io/v1/boards/dynetherapeutics/jobs?content=true', 'response_sha256' => hash('sha256', $body), 'transport' => 'Host curl with certificate verification; container transport blocked by missing trusted issuer CA', 'executed_at' => gmdate('c'), 'content_writes' => 0];
file_put_contents(dirname($path) . '/greenhouse-live-preview.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
print json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
