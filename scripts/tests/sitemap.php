<?php

/**
 * @file
 * Read-only sitemap regression checks; run with drush php:script.
 *
 * DYNE_SITEMAP_FINGERPRINT_ONLY=1 prints only the content fingerprint, allowing
 * the caller to capture output/sitemap/content-before.json before installation.
 * DYNE_SITEMAP_BASELINE_FILE selects an explicitly named independent baseline;
 * relative paths resolve from the project root. The original default is kept.
 */

/** Hash existing content rows without including configuration or cache tables. */
function dyne_sitemap_content_fingerprint(): array {
  $connection = \Drupal::database();
  $tables = array_values(array_filter($connection->schema()->findTables('%'), static fn(string $table): bool => (bool) preg_match('/^(?:node(?:_|$)|paragraphs_item(?:_|$)|paragraph(?:_|$)|path_alias(?:_|$))/', $table)));
  sort($tables, SORT_STRING);
  $result = [];
  foreach ($tables as $table) {
    $rows = [];
    foreach ($connection->select($table, 'content')->fields('content')->execute()->fetchAll(\PDO::FETCH_ASSOC) as $row) {
      ksort($row);
      $rows[] = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
    sort($rows, SORT_STRING);
    $result[$table] = ['rows' => count($rows), 'sha256' => hash('sha256', implode("\n", $rows))];
  }
  return $result;
}

if (getenv('DYNE_SITEMAP_FINGERPRINT_ONLY') === '1') {
  print json_encode(dyne_sitemap_content_fingerprint(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
  return;
}

$directory = dirname(DRUPAL_ROOT) . '/output/sitemap';
$report = ['passed' => FALSE, 'checks' => 0, 'failures' => [], 'modules' => [], 'counts' => [], 'policy_probes' => [], 'entity_saves' => 0, 'config_saves' => 0, 'state_writes' => 0, 'network_requests' => 0, 'cron_calls' => 0];
$baseline_override = trim((string) getenv('DYNE_SITEMAP_BASELINE_FILE'));
$baseline_file = $baseline_override !== '' ? $baseline_override : $directory . '/content-before.json';
if (!str_starts_with($baseline_file, '/')) $baseline_file = dirname(DRUPAL_ROOT) . '/' . $baseline_file;
$report['content_baseline_file'] = $baseline_file;
$report['content_baseline_explicit'] = $baseline_override !== '';
$assert = static function (bool $condition, string $message) use (&$report): void {
  $report['checks']++;
  if (!$condition) $report['failures'][] = $message;
};
$before = dyne_sitemap_content_fingerprint();
$index_before = NULL;
$table_fingerprint = static function (string $table): string {
  $rows = [];
  foreach (\Drupal::database()->select($table, 'item')->fields('item')->execute()->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    ksort($row);
    $rows[] = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
  }
  sort($rows, SORT_STRING);
  return hash('sha256', implode("\n", $rows));
};

try {
  foreach (['xmlsitemap', 'robotstxt'] as $module) {
    $enabled = \Drupal::moduleHandler()->moduleExists($module);
    $report['modules'][$module] = $enabled;
    $assert($enabled, $module . ' must be enabled.');
  }
  // Scheduler installation is deliberately outside sitemap scope. Ordinary
  // site cron can refresh XML Sitemap without enabling request-triggered cron.
  $report['modules']['automated_cron'] = \Drupal::moduleHandler()->moduleExists('automated_cron');
  if (!$report['modules']['xmlsitemap']) throw new RuntimeException('XML Sitemap APIs are unavailable until the module is enabled.');
  $assert(function_exists('dyne_migrate_xmlsitemap_link_alter'), 'The sitemap content policy hook is loaded.');
  $assert(\Drupal::moduleHandler()->hasImplementations('xmlsitemap_link_alter', ['dyne_migrate']), 'The active module handler discovers the content policy hook.');
  $assert(\Drupal::config('xmlsitemap.settings')->get('disable_cron_regeneration') === FALSE, 'Generated files must be eligible for normal cron refresh.');
  // Drush deliberately sets a runtime interval=0 override during bootstrap so
  // CLI commands do not launch web-request cron. Verify deployment storage,
  // rather than mistaking that documented CLI suppression for disabled cron.
  $cron_configuration = \Drupal::service('config.storage')->read('automated_cron.settings') ?: [];
  $interval = (int) ($cron_configuration['interval'] ?? 0);
  $runtime_interval = (int) \Drupal::config('automated_cron.settings')->get('interval');
  $report['automated_cron_interval_seconds'] = $interval;
  $report['automated_cron_runtime_interval_seconds'] = $runtime_interval;
  $report['automated_cron_cli_suppression'] = $runtime_interval === 0 && $interval > 0;
  $index_before = $table_fingerprint('xmlsitemap');

  $included = ['dyne_page', 'job', 'leader', 'perspective', 'pipeline_program', 'news'];
  foreach (xmlsitemap_get_link_info() as $entity_type => $info) {
    foreach ($info['bundles'] ?? [] as $bundle => $bundle_info) {
      $settings = xmlsitemap_link_bundle_load($entity_type, $bundle, FALSE);
      $expected_status = $entity_type === 'node' && in_array($bundle, $included, TRUE);
      $assert((bool) $settings['status'] === $expected_status, $entity_type . '.' . $bundle . ' default inclusion matches the public-page scope.');
    }
  }
  foreach ($included as $bundle) $assert((bool) xmlsitemap_link_bundle_load('node', $bundle, FALSE)['status'], $bundle . ' is configured for indexing.');

  $sitemap = \Drupal\xmlsitemap\Entity\XmlSitemap::loadByContext();
  $assert($sitemap !== NULL, 'A default-context sitemap exists.');
  if (!$sitemap) throw new RuntimeException('No default-context sitemap is available.');
  $assert(trim((string) $sitemap->label()) !== '' && !filter_var($sitemap->label(), FILTER_VALIDATE_URL), 'The default sitemap has a generic human label, not an environment URL.');
  $base = rtrim((string) \Drupal\Core\Site\Settings::get('xmlsitemap_base_url', \Drupal::state()->get('xmlsitemap_base_url')), '/');
  $environment_base = rtrim((string) (\Drupal\Core\Site\Settings::get('xmlsitemap_base_url') ?: \Drupal\Core\Url::fromUri('base:', ['absolute' => TRUE])->toString()), '/');
  $parts = parse_url($base);
  $valid_base = $parts && in_array($parts['scheme'] ?? '', ['http', 'https'], TRUE) && !empty($parts['host']) && !isset($parts['query']) && !isset($parts['fragment']) && !isset($parts['user']) && !isset($parts['pass']);
  $assert((bool) $valid_base, 'The effective sitemap base is a valid absolute HTTP(S) deployment URL.');
  $assert($base === $environment_base, 'The sitemap base matches this environment or its explicit settings override; run Drush with the correct --uri.');
  $origin = static function (string $url): string {
    $value = parse_url($url);
    if (!$value || empty($value['scheme']) || empty($value['host'])) return '';
    $scheme = strtolower($value['scheme']); $host = strtolower($value['host']);
    $port = $value['port'] ?? ($scheme === 'https' ? 443 : 80);
    return $scheme . '://' . $host . ':' . $port;
  };
  $expected_origin = $origin($base);
  $report['base_url'] = $base;
  $report['sitemap_label'] = $sitemap->label();

  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
  $anonymous = new \Drupal\Core\Session\AnonymousUserSession();
  $aliases = \Drupal::service('path_alias.manager');
  $eligible = [];
  $all_node_links = [];
  $representatives = [];
  $news_representative = NULL;
  $public_node = NULL;
  $frontpage_node = NULL;
  $archived = NULL;
  $expected_urls = [];
  $absolute = ['absolute' => TRUE, 'base_url' => $base];
  $home_url = \Drupal\Core\Url::fromRoute('<front>', [], $absolute)->toString();
  $front_path = '/' . ltrim((string) \Drupal::config('system.site')->get('page.front'), '/');
  $front_internal = $aliases->getPathByAlias($front_path);
  $expected_urls[$home_url] = TRUE;
  foreach ($nodes as $node) {
    $representatives[$node->bundle()] ??= $node;
    foreach ($node->getTranslationLanguages() as $language) {
      $translation = $node->getTranslation($language->getId());
      $path = '/' . ltrim($translation->toUrl()->getInternalPath(), '/');
      $is_frontpage_node = $path === $front_internal;
      if ($is_frontpage_node) $frontpage_node ??= $translation;
      $alias = $aliases->getAliasByPath($path, $language->getId());
      $is_archive = $alias === '/legacy-archive' || str_starts_with($alias, '/legacy-archive/');
      if ($is_archive) $archived ??= $translation;
      if ($translation->bundle() === 'news' && !$is_archive) $news_representative ??= $translation;
      $news_type = $translation->bundle() === 'news' && $translation->hasField('field_news_type') ? strtolower((string) $translation->get('field_news_type')->value) : '';
      $allowed = $translation->isPublished() && in_array($translation->bundle(), $included, TRUE) && !in_array($news_type, ['url', 'file'], TRUE) && !$is_archive && !$is_frontpage_node && $translation->access('view', $anonymous);
      $key = 'node:' . $node->id() . ':' . $language->getId();
      $all_node_links[$key] = ['eligible' => (bool) $allowed, 'loc' => $path, 'bundle' => $node->bundle()];
      if (!$allowed) continue;
      if ($translation->bundle() !== 'news') $public_node ??= $translation;
      $eligible[$key] = $all_node_links[$key];
      $expected_urls[$translation->toUrl('canonical', $absolute + ['language' => $language])->toString()] = TRUE;
    }
  }
  $stored = \Drupal::database()->select('xmlsitemap', 'link')->fields('link')->execute()->fetchAll(\PDO::FETCH_ASSOC);
  $visible_nodes = [];
  $frontpages = 0;
  foreach ($stored as $link) {
    $visible = (bool) $link['status'] && (bool) $link['access'];
    if ($link['type'] === 'node') {
      $key = 'node:' . $link['id'] . ':' . $link['language'];
      $expected = $all_node_links[$key] ?? NULL;
      $assert(!$visible || ($expected && $expected['eligible']), 'Visible table row ' . $key . ' refers to an eligible anonymous public page.');
      if ($expected) $assert($link['loc'] === $expected['loc'], $key . ' stores the current canonical internal path.');
      if ($visible) $visible_nodes[$key] = TRUE;
    }
    elseif ($visible && $link['type'] === 'frontpage') {
      $frontpages++;
      $assert($link['loc'] === '/', 'The dedicated frontpage row stores the site root.');
    }
    else $assert(!$visible, 'Other entity type ' . $link['type'] . ' remains outside the sitemap.');
  }
  $assert($frontpages === 1, 'Exactly one dedicated frontpage table row is visible.');
  $missing = array_values(array_diff(array_keys($eligible), array_keys($visible_nodes)));
  $extra = array_values(array_diff(array_keys($visible_nodes), array_keys($eligible)));
  $assert($missing === [] && $extra === [], 'Visible sitemap rows reconcile with every eligible anonymous-access node; missing=' . count($missing) . ', unexpected=' . count($extra) . '.');
  $report['counts'] = ['nodes' => count($nodes), 'eligible_node_translations' => count($eligible), 'visible_node_rows' => count($visible_nodes), 'frontpage_rows' => $frontpages, 'stored_rows' => count($stored)];
  if ($missing) $report['missing_node_keys'] = $missing;
  if ($extra) $report['unexpected_node_keys'] = $extra;

  // Probe the real core creation and normal module-alter chain on clones only.
  // Force inclusion before the alter so exclusion tests cannot pass merely
  // because their bundle default was already off. Never call link_storage.save.
  $link_storage = \Drupal::service('xmlsitemap.link_storage');
  $probe = static function ($node, string $name, bool $expected_visible, ?string $loc = NULL) use ($link_storage, $assert, &$report): void {
    $node = clone $node;
    $node->xmlsitemap = [];
    \Drupal::entityTypeManager()->getAccessControlHandler('node')->resetCache();
    $link = $link_storage->create($node);
    $link['status'] = 1;
    if ($loc !== NULL) $link['loc'] = $loc;
    $context = ['node' => $node, 'entity' => $node];
    \Drupal::moduleHandler()->alter('xmlsitemap_link', $link, $context);
    $visible = (bool) $link['status'] && (bool) $link['access'];
    $assert($visible === $expected_visible, $name . ': real core creation plus registered policy alter yields expected visibility.');
    $report['policy_probes'][$name] = ['visible' => $visible, 'expected' => $expected_visible];
  };
  $assert($public_node !== NULL, 'A published public node is available for in-memory probes.');
  if ($public_node) {
    $probe($public_node, 'published_public_node', TRUE);
    $unpublished = clone $public_node; $unpublished->setUnpublished();
    $probe($unpublished, 'unpublished_node', FALSE);
    $probe($public_node, 'archive_path_policy', FALSE, '/legacy-archive/read-only-verification');
  }
  $assert($frontpage_node !== NULL, 'The configured frontpage node is available for the duplicate-homepage exclusion probe.');
  if ($frontpage_node) { $clone = clone $frontpage_node; $clone->setPublished(); $probe($clone, 'frontpage_node_even_manually_included', FALSE); }
  if ($archived) { $archive_clone = clone $archived; $archive_clone->setPublished(); $probe($archive_clone, 'existing_archive_alias', FALSE); }
  foreach (['quote', 'privacy_policy'] as $bundle) {
    $assert(isset($representatives[$bundle]), $bundle . ' has a representative for the exclusion probe.');
    if (isset($representatives[$bundle])) { $clone = clone $representatives[$bundle]; $clone->setPublished(); $probe($clone, $bundle . '_even_when_manually_included', FALSE); }
  }
  $news = $news_representative;
  $assert($news && $news->hasField('field_news_type'), 'News delivery types are available for policy probes.');
  if ($news) foreach (['html' => TRUE, '' => TRUE, 'url' => FALSE, 'file' => FALSE] as $type => $visible) {
    $clone = clone $news; $clone->setPublished(); $clone->set('field_news_type', $type);
    $probe($clone, 'news_' . ($type === '' ? 'empty_type_is_html' : $type), $visible);
  }

  // Read the already-generated local XML. An index's chunk URLs are mapped to
  // trusted local chunk filenames; no HTTP requests or regeneration occurs.
  $read_xml = static function (string $file) use ($assert): ?\DOMDocument {
    $assert(is_file($file) && is_readable($file), 'Generated sitemap file is readable: ' . $file);
    if (!is_file($file) || !is_readable($file)) return NULL;
    $document = new \DOMDocument();
    $previous = libxml_use_internal_errors(TRUE);
    try { $valid = $document->loadXML(file_get_contents($file), LIBXML_NONET); }
    finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    $assert($valid && $document->documentElement?->namespaceURI === 'http://www.sitemaps.org/schemas/sitemap/0.9', 'Generated sitemap XML has a valid standard namespace: ' . $file);
    return $valid ? $document : NULL;
  };
  $chunks = (int) $sitemap->getChunks();
  $assert($chunks >= 1, 'The sitemap has already generated at least one chunk.');
  $entry_file = xmlsitemap_sitemap_get_file($sitemap, $chunks > 1 ? 'index' : 1);
  $entry = $read_xml($entry_file);
  $files = [];
  if ($entry && $entry->documentElement->localName === 'sitemapindex') {
    foreach ($entry->getElementsByTagNameNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'loc') as $loc) {
      $chunk_url = trim($loc->textContent);
      $assert($origin($chunk_url) === $expected_origin, 'Every generated index URL uses this deployment origin.');
      parse_str((string) parse_url($chunk_url, PHP_URL_QUERY), $query);
      $page = $query['page'] ?? NULL;
      $assert(is_scalar($page) && ctype_digit((string) $page) && (int) $page >= 1 && (int) $page <= $chunks, 'Index references an expected numeric local chunk.');
      if (is_scalar($page) && ctype_digit((string) $page) && (int) $page >= 1 && (int) $page <= $chunks) $files[(int) $page] = xmlsitemap_sitemap_get_file($sitemap, (int) $page);
    }
    $assert(count($files) === $chunks, 'Generated index references every chunk exactly once.');
  }
  elseif ($entry) {
    $assert($entry->documentElement->localName === 'urlset' && $chunks === 1, 'Single-file sitemap serves its urlset directly.');
    $files[1] = $entry_file;
  }
  $actual_urls = [];
  foreach ($files as $page => $file) {
    $document = $file === $entry_file ? $entry : $read_xml($file);
    if (!$document) continue;
    $assert($document->documentElement->localName === 'urlset', 'Each generated chunk is a URL set.');
    foreach ($document->getElementsByTagNameNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'loc') as $loc) {
      $value = trim($loc->textContent);
      $assert($origin($value) === $expected_origin, 'Generated page URL uses the environment origin: ' . $value);
      $assert(!isset($actual_urls[$value]), 'Generated page URL appears only once: ' . $value);
      $actual_urls[$value] = ($actual_urls[$value] ?? 0) + 1;
    }
  }
  $missing_urls = array_values(array_diff(array_keys($expected_urls), array_keys($actual_urls)));
  $extra_urls = array_values(array_diff(array_keys($actual_urls), array_keys($expected_urls)));
  $assert($missing_urls === [] && $extra_urls === [], 'Generated XML URLs reconcile with eligible canonical public URLs plus homepage; missing=' . count($missing_urls) . ', unexpected=' . count($extra_urls) . '.');
  $assert(($actual_urls[$home_url] ?? 0) === 1, 'Homepage appears exactly once in generated XML.');
  $report['counts'] += ['generated_chunks' => count($files), 'generated_urls' => count($actual_urls), 'expected_unique_urls' => count($expected_urls), 'homepage_occurrences' => $actual_urls[$home_url] ?? 0];
  if ($missing_urls) $report['missing_urls'] = $missing_urls;
  if ($extra_urls) $report['unexpected_urls'] = $extra_urls;
  $robots = \Drupal\robotstxt\Controller\RobotsTxtController::create(\Drupal::getContainer())->content()->getContent();
  $assert((bool) preg_match('/^Sitemap:\s*' . preg_quote($base, '/') . '\/sitemap\.xml(?:\s|$)/mi', $robots), 'Robots output advertises this deployment sitemap through the real module controller.');
}
catch (\Throwable $error) {
  $report['failures'][] = get_class($error) . ': ' . $error->getMessage();
  $report['failure_location'] = basename($error->getFile()) . ':' . $error->getLine();
}
finally {
  $after = dyne_sitemap_content_fingerprint();
  $assert($before === $after, 'Read-only verification leaves all node/revision/paragraph/alias rows unchanged.');
  $report['content_fingerprints'] = ['before' => hash('sha256', json_encode($before, JSON_THROW_ON_ERROR)), 'after' => hash('sha256', json_encode($after, JSON_THROW_ON_ERROR)), 'tables' => count($before)];
  if ($baseline_override !== '' || is_file($baseline_file)) {
    try {
      if (!is_file($baseline_file) || !is_readable($baseline_file)) throw new RuntimeException('Selected content baseline is missing or unreadable: ' . $baseline_file);
      $baseline = json_decode(file_get_contents($baseline_file), TRUE, 512, JSON_THROW_ON_ERROR);
      if (!is_array($baseline) || !$baseline) throw new RuntimeException('Selected content baseline must be a nonempty JSON table-fingerprint object.');
      foreach ($baseline as $table => $fingerprint) {
        if (!is_string($table) || !is_array($fingerprint) || !isset($fingerprint['rows'], $fingerprint['sha256']) || !is_int($fingerprint['rows']) || $fingerprint['rows'] < 0 || !is_string($fingerprint['sha256']) || !preg_match('/^[a-f0-9]{64}$/D', $fingerprint['sha256'])) throw new RuntimeException('Selected content baseline contains an invalid table fingerprint.');
      }
      $report['content_baseline_sha256'] = hash('sha256', json_encode($baseline, JSON_THROW_ON_ERROR));
      $assert($baseline === $after, $baseline_override !== '' ? 'Current content matches the selected independently captured content baseline.' : 'Sitemap installation and indexing preserve the independently captured pre-upgrade content fingerprint.');
      $report['baseline_content_unchanged'] = $baseline === $after;
      if ($baseline_override === '') $report['pre_upgrade_content_unchanged'] = $baseline === $after;
      if ($baseline !== $after) {
        $difference_key = $baseline_override !== '' ? 'baseline_content_differences' : 'pre_upgrade_content_differences';
        foreach (array_unique(array_merge(array_keys($baseline), array_keys($after))) as $table) {
          if (($baseline[$table] ?? NULL) !== ($after[$table] ?? NULL)) $report[$difference_key][$table] = ['before' => $baseline[$table] ?? NULL, 'after' => $after[$table] ?? NULL];
        }
      }
    }
    catch (\Throwable $error) {
      $report['failures'][] = get_class($error) . ': ' . $error->getMessage();
    }
  }
  if ($index_before !== NULL) $assert($index_before === $table_fingerprint('xmlsitemap'), 'Read-only probes leave the sitemap link index unchanged.');
}
$report['passed'] = $report['failures'] === [];
if (!is_dir($directory)) mkdir($directory, 0775, TRUE);
file_put_contents($directory . '/verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) . PHP_EOL);
print json_encode(['passed' => $report['passed'], 'checks' => $report['checks'], 'counts' => $report['counts'], 'content_baseline_file' => $baseline_file, 'content_baseline_explicit' => $baseline_override !== '', 'failures' => $report['failures']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if (!$report['passed']) throw new RuntimeException('Sitemap verification failed; see output/sitemap/verification.json.');
