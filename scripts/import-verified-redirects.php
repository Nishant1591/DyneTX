<?php

/**
 * @file
 * Import reviewed historical paths and numeric WordPress URLs, idempotently.
 * Preview: DYNE_REDIRECT_DRY_RUN=1 ddev drush php:script scripts/import-verified-redirects.php
 */
use Drupal\redirect\Entity\Redirect;

$plan = json_decode(file_get_contents(dirname(DRUPAL_ROOT) . '/content-archive/wordpress-redirects.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$dry = getenv('DYNE_REDIRECT_DRY_RUN') === '1';
$storage = \Drupal::entityTypeManager()->getStorage('redirect');
$alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
$repository = \Drupal::service('redirect.repository');
$numeric = [];
$assets = [];
$report = ['numeric_mapped' => 0, 'dry_run' => $dry, 'created' => 0, 'unchanged' => 0, 'excluded' => $plan['excluded'], 'records' => []];
$transaction = !$dry ? \Drupal::database()->startTransaction() : NULL;
try {
foreach ($plan['redirects'] as $item) {
  $source = trim($item['source'], '/');
  $target = rtrim($item['target'], '/') ?: '/';
  if ((!$item['query'] && '/' . $source === $target) || !str_starts_with($target, '/') || str_starts_with($target, '//')) {
    throw new RuntimeException('Unsafe or self redirect in reviewed plan.');
  }
  // An active destination alias is evidence; do not redirect real source routes.
  if (!$item['query'] && $alias_storage->loadByProperties(['alias' => '/' . $source])) {
    throw new RuntimeException('Redirect source is an active content alias: ' . $source);
  }
  $path = $target === '/' ? \Drupal::config('system.site')->get('page.front') : \Drupal::service('path_alias.manager')->getPathByAlias($target);
  if (!preg_match('#^/node/(\d+)$#', $path, $match) || !($node = \Drupal::entityTypeManager()->getStorage('node')->load($match[1])) || !$node->isPublished()) {
    throw new RuntimeException('Missing or unpublished redirect destination: ' . $target);
  }
  if ($repository->findMatchingRedirect(ltrim($target, '/'), [], 'en')) {
    throw new RuntimeException('Destination itself redirects; avoid chains and loops: ' . $target);
  }
  if ($item['query']) {
    $param = array_key_first($item['query']);
    if ($source !== '' || !in_array($param, ['p', 'page_id'], TRUE) || count($item['query']) !== 1) {
      throw new RuntimeException('Unexpected numeric redirect mapping.');
    }
    $numeric[$param . ':' . $item['query'][$param]] = ['nid' => (int) $node->id(), 'target' => $target];
    $report['numeric_mapped']++;
    $report['records'][] = ['source' => '/', 'query' => $item['query'], 'target' => $target, 'expected_status' => 301];
    continue;
  }
  $hash = Redirect::generateHash($source, $item['query'], 'und');
  $existing = $storage->loadByProperties(['hash' => $hash]);
  if ($existing) {
    $redirect = reset($existing);
    if ($redirect->getRedirect()['uri'] !== 'internal:' . $target || (int) $redirect->getStatusCode() !== 301) {
      throw new RuntimeException('Existing redirect conflicts with reviewed plan: ' . $source);
    }
    $report['unchanged']++;
  }
  else {
    $report['created']++;
    if (!$dry) {
      $redirect = Redirect::create(['redirect_source' => ['path' => $source, 'query' => $item['query']], 'redirect_redirect' => ['uri' => 'internal:' . $target], 'language' => 'und', 'status_code' => 301]);
      $violations = $redirect->validate();
      if ($violations->count()) {
        throw new RuntimeException((string) $violations);
      }
      $redirect->save();
    }
  }
  $report['records'][] = ['source' => '/' . $source, 'query' => $item['query'], 'target' => $target, 'expected_status' => 301];
}
foreach ($plan['asset_redirects'] ?? [] as $item) {
  $uri = 'public://legacy/' . $item['relative_path'];
  if (!str_starts_with($item['source'], '/wp-content/uploads/') || !str_starts_with($item['target'], '/sites/default/files/legacy/') || str_starts_with($item['relative_path'], '/') || in_array('..', explode('/', $item['relative_path']), TRUE) || !is_file($uri)) {
    throw new RuntimeException('Unverified asset redirect in URL plan.');
  }
  $assets[rawurldecode($item['source'])] = ['target' => $item['target'], 'uri' => $uri];
}
$report['media_mapped'] = count($assets);
if (!$dry) {
  \Drupal::state()->set('dyne_migrate.legacy_numeric_urls', $numeric);
  \Drupal::state()->set('dyne_migrate.legacy_upload_urls', $assets);
}
if ($transaction) { unset($transaction); }
}
catch (Throwable $error) {
  if (isset($transaction)) { $transaction->rollBack(); }
  throw $error;
}
$out = dirname(DRUPAL_ROOT) . '/output/document-6-review/redirect-import' . ($dry ? '-dry-run' : '') . '.json';
file_put_contents($out, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
print json_encode(array_diff_key($report, ['records' => 1]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
