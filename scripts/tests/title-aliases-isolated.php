<?php

/**
 * @file
 * Real-save alias checks, exclusively against a disposable database clone.
 *
 * Run as standalone PHP, never Drush:
 * DYNE_ALIAS_TEST_DATABASE=dyne_alias_verify_20261023 php scripts/tests/title-aliases-isolated.php
 *
 * The caller creates the clone beforehand. No database is created, restored,
 * dropped, or selected implicitly by this script. Test content stays in that
 * clone for inspection; the shared site's default database is never booted.
 */

$test_database = getenv('DYNE_ALIAS_TEST_DATABASE');
if (!is_string($test_database) || !preg_match('/^dyne_alias_verify_[A-Za-z0-9_]{1,40}$/D', $test_database) || $test_database === 'db') {
  fwrite(STDERR, "Refusing to bootstrap: DYNE_ALIAS_TEST_DATABASE must name a disposable dyne_alias_verify_* database.\n");
  exit(64);
}
if (defined('DRUPAL_ROOT') || class_exists('Drupal', FALSE)) {
  fwrite(STDERR, "Refusing an already bootstrapped runtime. Invoke this script with standalone PHP, not Drush.\n");
  exit(64);
}

$app_root = dirname(__DIR__, 2) . '/web';
$directory = dirname($app_root) . '/output/aliases';
$report = ['passed' => FALSE, 'database' => $test_database, 'checks' => 0, 'guards' => 0, 'failures' => [], 'bundles' => [], 'created_node_ids' => [], 'default_database_booted' => FALSE];
$assert = static function (bool $condition, string $message) use (&$report): void {
  $report['checks']++;
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$guard = static function () use ($test_database, &$report): void {
  $info = \Drupal\Core\Database\Database::getConnectionInfo('default');
  if (($info['default']['database'] ?? NULL) !== $test_database) {
    throw new RuntimeException('Isolation guard: configured default connection changed.');
  }
  $actual = \Drupal\Core\Database\Database::getConnection('default', 'default')->query('SELECT DATABASE()')->fetchField();
  if ($actual !== $test_database || $actual === 'db' || !str_starts_with($actual, 'dyne_alias_verify_')) {
    throw new RuntimeException('Isolation guard: active database differs from the explicitly allowed clone.');
  }
  $report['guards']++;
};
$switched = FALSE;

try {
  $loader = require $app_root . '/autoload.php';
  chdir($app_root);
  $request = \Symfony\Component\HttpFoundation\Request::create('http://dyne-tx-workspace.ddev.site/');
  // createFromRequest initializes Settings; it does not boot the database-backed
  // container. Replace the connection immediately, before the first boot.
  $kernel = \Drupal\Core\DrupalKernel::createFromRequest($request, $loader, 'prod', FALSE, $app_root);
  $connections = \Drupal\Core\Database\Database::getConnectionInfo('default');
  if (!isset($connections['default']) || ($connections['default']['driver'] ?? '') !== 'mysql') {
    throw new RuntimeException('The clone verifier requires the existing MariaDB/MySQL connection settings.');
  }
  $clone_connection = $connections['default'];
  $clone_connection['database'] = $test_database;
  // Eliminate alternate connections/replicas, so a service cannot fall back to
  // a settings-defined connection pointing at the shared database.
  foreach (array_keys(\Drupal\Core\Database\Database::getAllConnectionInfo()) as $key) {
    \Drupal\Core\Database\Database::removeConnection($key);
  }
  \Drupal\Core\Database\Database::addConnectionInfo('default', 'default', $clone_connection, $loader, $app_root);
  \Drupal\Core\Database\Database::setActiveConnection('default');
  $guard();
  $kernel->boot();
  $guard();
  // A database clone may carry hook discovery from before the code change.
  // Recompile against the clone, without reading/writing the shared PHP/APCu
  // file-discovery cache or dumping a shared compiled container to disk.
  \Drupal\Component\FileCache\FileCacheFactory::setConfiguration([
    \Drupal\Component\FileCache\FileCacheFactory::DISABLE_CACHE => TRUE,
  ]);
  $kernel->rebuildContainer();
  $guard();
  $kernel->preHandle($request);
  $guard();
  require_once $app_root . '/modules/custom/dyne_migrate/includes/title-aliases.inc';
  $hook_map = \Drupal::keyValue('hook_data')->get('hook_list', []);
  $alias_hooks = $hook_map['pathauto_alias_alter'] ?? [];
  $assert(($alias_hooks['dyne_migrate_pathauto_alias_alter'] ?? NULL) === 'dyne_migrate', 'Rebuilt container hook map must register the imported-alias preservation hook.');
  $assert(\Drupal::moduleHandler()->hasImplementations('pathauto_alias_alter', ['dyne_migrate']), 'Active module handler must discover the imported-alias preservation hook.');
  $report['hook_discovery'] = ['container_rebuilt' => TRUE, 'shared_file_cache_disabled' => TRUE, 'pathauto_alias_alter' => $alias_hooks];

  $administrator = \Drupal::entityTypeManager()->getStorage('user')->load(1);
  $assert($administrator !== NULL, 'Clone must contain the existing administrator.');
  \Drupal::service('account_switcher')->switchTo($administrator);
  $switched = TRUE;

  $nodes = \Drupal::entityTypeManager()->getStorage('node');
  $aliases = \Drupal::entityTypeManager()->getStorage('path_alias');
  $redirects = \Drupal::entityTypeManager()->getStorage('redirect');
  $alias_snapshot = static function () use ($aliases): array {
    $rows = [];
    foreach ($aliases->loadMultiple() as $alias) {
      $rows[$alias->id()] = [$alias->getPath(), $alias->getAlias(), $alias->language()->getId()];
    }
    ksort($rows);
    return $rows;
  };
  $existing_aliases = $alias_snapshot();
  $guard();
  dyne_migrate_title_alias_schema();
  $guard();
  dyne_migrate_title_alias_enroll();
  $aliases->resetCache();
  $assert($existing_aliases === $alias_snapshot(), 'Installing patterns/enrolling policy must not rename existing aliases.');

  $save = static function ($entity) use ($guard): void {
    $guard();
    $entity->save();
  };
  $reload = static function ($node) use ($nodes) {
    $nodes->resetCache([$node->id()]);
    return $nodes->load($node->id());
  };
  $alias_of = static function ($node): string {
    $row = \Drupal::service('path_alias.repository')->lookupBySystemPath('/node/' . $node->id(), $node->language()->getId());
    return $row['alias'] ?? '/node/' . $node->id();
  };
  $one_alias = static function ($node) use ($aliases, $assert): void {
    $aliases->resetCache();
    $assert(count($aliases->loadByProperties(['path' => '/node/' . $node->id()])) === 1, 'Node ' . $node->id() . ' must have one canonical alias, with no competing source/title alias.');
  };
  $redirect_from = static function (string $old_alias, $node) use ($redirects, $aliases, $assert): void {
    $redirects->resetCache();
    $hash = \Drupal\redirect\Entity\Redirect::generateHash(ltrim($old_alias, '/'), [], $node->language()->getId());
    $matches = $redirects->loadByProperties(['hash' => $hash]);
    $assert(count($matches) === 1, 'Old alias must have exactly one redirect: ' . $old_alias);
    $redirect = reset($matches);
    $assert((int) $redirect->getStatusCode() === 301, 'Old alias must use HTTP 301: ' . $old_alias);
    $assert(($redirect->getRedirect()['uri'] ?? '') === 'internal:/node/' . $node->id(), 'Redirect must target the internal node, avoiding alias chains: ' . $old_alias);
    $aliases->resetCache();
    $assert(!$aliases->loadByProperties(['alias' => $old_alias]), 'Old alias must be replaced rather than remain a second canonical alias: ' . $old_alias);
  };
  $new_node = static function (string $bundle, string $title) use ($save, &$report) {
    $node = \Drupal\node\Entity\Node::create(['type' => $bundle, 'title' => $title, 'uid' => 1, 'status' => 1, 'langcode' => 'en']);
    $save($node);
    $report['created_node_ids'][] = (int) $node->id();
    return $node;
  };
  $run = 'Aliasverify ' . bin2hex(random_bytes(4));
  $prefixes = ['dyne_page' => '/', 'job' => '/jobs/', 'leader' => '/team/', 'news' => '/news/', 'perspective' => '/perspectives/', 'pipeline_program' => '/pipeline/', 'privacy_policy' => '/policies/', 'quote' => '/quotes/'];
  foreach ($prefixes as $bundle => $prefix) {
    $node = $new_node($bundle, $run . ' ' . $bundle . ' First');
    $node = $reload($node);
    $initial = $alias_of($node);
    $assert(str_starts_with($initial, $prefix) && $initial !== '/node/' . $node->id(), $bundle . ': new content must receive its matching bundle pattern.');
    $one_alias($node);
    $node->setTitle($run . ' ' . $bundle . ' Second');
    $save($node);
    $node = $reload($node);
    $second = $alias_of($node);
    $assert($second !== $initial && str_starts_with($second, $prefix), $bundle . ': title change must update canonical alias.');
    $one_alias($node);
    $redirect_from($initial, $node);
    $node->setTitle($run . ' ' . $bundle . ' Third');
    $save($node);
    $node = $reload($node);
    $third = $alias_of($node);
    $assert($third !== $second && str_starts_with($third, $prefix), $bundle . ': second title change must update canonical alias again.');
    $one_alias($node);
    $redirect_from($initial, $node);
    $redirect_from($second, $node);
    $report['bundles'][$bundle] = ['nid' => (int) $node->id(), 'initial' => $initial, 'renamed' => $second, 'renamed_again' => $third];
  }

  // A deliberate manual alias is preserved until automatic generation is
  // explicitly re-enabled, including when the title itself remains unchanged.
  $manual = $new_node('job', $run . ' Manual First');
  $manual = $reload($manual);
  $manual_alias = '/jobs/' . strtolower(str_replace(' ', '-', $run)) . '-custom';
  $manual->get('path')->first()->set('pathauto', \Drupal\pathauto\PathautoState::SKIP);
  $manual->get('path')->first()->set('alias', $manual_alias);
  $save($manual);
  $manual = $reload($manual);
  $assert($alias_of($manual) === $manual_alias, 'Manual alias must be saved.');
  $manual->setTitle($run . ' Manual Renamed');
  $save($manual);
  $manual = $reload($manual);
  $assert($alias_of($manual) === $manual_alias, 'Manual opt-out must survive a title change.');
  $assert((int) $manual->get('path')->pathauto === \Drupal\pathauto\PathautoState::SKIP, 'Manual opt-out must remain persisted.');
  $manual_state = dyne_migrate_preserve_import_alias($manual);
  $save($manual);
  $guard();
  dyne_migrate_restore_import_alias($manual, $manual_state);
  $manual = $reload($manual);
  $assert($manual_state === \Drupal\pathauto\PathautoState::SKIP && (int) $manual->get('path')->pathauto === \Drupal\pathauto\PathautoState::SKIP, 'Source helper must preserve an existing editorial opt-out.');
  $manual->get('path')->first()->set('pathauto', \Drupal\pathauto\PathautoState::CREATE);
  $save($manual);
  $manual = $reload($manual);
  $assert($alias_of($manual) !== $manual_alias, 'Re-enabling automatic generation must apply the pattern without requiring another title change.');
  $redirect_from($manual_alias, $manual);
  $one_alias($manual);
  $report['manual_override'] = ['nid' => (int) $manual->id(), 'custom' => $manual_alias, 'automatic' => $alias_of($manual)];

  $first = $new_node('job', $run . ' Collision');
  $second = $new_node('job', $run . ' Collision');
  $assert($alias_of($first) !== $alias_of($second), 'Two jobs with identical titles must receive distinct aliases.');
  $one_alias($first);
  $one_alias($second);
  $report['collision'] = [$alias_of($first), $alias_of($second)];

  // Only the clone's existing leader is saved. No recursive content fields or
  // Paragraphs forms are rebuilt during this regression test.
  $nodes->resetCache([29]);
  $leader = $nodes->load(29);
  $assert($leader && $leader->bundle() === 'leader', 'Clone must contain expected existing leader 29.');
  $assert($alias_of($leader) === '/team/joshua-brumm', 'Leader 29 must start with the imported historical alias.');
  $save($leader);
  $leader = $reload($leader);
  $assert($alias_of($leader) === '/team/joshua-brumm', 'An unchanged title must preserve an imported alias during an unrelated save.');
  $leader->setTitle($run . ' Imported Leader Renamed');
  $save($leader);
  $leader = $reload($leader);
  $assert($alias_of($leader) !== '/team/joshua-brumm', 'Changing an imported leader title must regenerate its alias.');
  $redirect_from('/team/joshua-brumm', $leader);
  $report['imported_leader'] = ['nid' => 29, 'renamed_alias' => $alias_of($leader)];

  $source = \Drupal\node\Entity\Node::create(['type' => 'job', 'title' => $run . ' Source First', 'uid' => 1, 'status' => 1, 'langcode' => 'en']);
  $state = dyne_migrate_preserve_import_alias($source);
  $assert($state === \Drupal\pathauto\PathautoState::CREATE, 'A new source node should restore automatic-generation policy.');
  $save($source);
  $report['created_node_ids'][] = (int) $source->id();
  $assert($alias_of($source) === '/node/' . $source->id(), 'Preserved source save must not create a competing automatic alias.');
  $source_alias = '/jobs/' . strtolower(str_replace(' ', '-', $run)) . '-source-owned';
  $path_alias = \Drupal\path_alias\Entity\PathAlias::create(['path' => '/node/' . $source->id(), 'alias' => $source_alias, 'langcode' => 'en']);
  $save($path_alias);
  $revision = $source->getRevisionId();
  $guard();
  dyne_migrate_restore_import_alias($source, $state);
  $source = $reload($source);
  $assert((int) $source->getRevisionId() === (int) $revision, 'Restoring source alias policy must not resave the node.');
  $assert($alias_of($source) === $source_alias && (int) $source->get('path')->pathauto === \Drupal\pathauto\PathautoState::CREATE, 'Source alias and intended automatic policy must coexist after import.');
  $one_alias($source);
  $source->setTitle($run . ' Source Renamed');
  $save($source);
  $source = $reload($source);
  $assert($alias_of($source) !== $source_alias, 'A subsequent source-node title edit must resume normal pattern generation.');
  $redirect_from($source_alias, $source);
  $one_alias($source);
  $report['source_alias'] = ['nid' => (int) $source->id(), 'imported' => $source_alias, 'renamed' => $alias_of($source)];
  $guard();
  $report['passed'] = TRUE;
}
catch (Throwable $error) {
  $report['failures'][] = get_class($error) . ': ' . $error->getMessage();
}
finally {
  if ($switched && \Drupal::hasContainer()) {
    \Drupal::service('account_switcher')->switchBack();
  }
}

if (!is_dir($directory)) {
  mkdir($directory, 0775, TRUE);
}
file_put_contents($directory . '/functional-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
print json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit($report['passed'] ? 0 : 1);
