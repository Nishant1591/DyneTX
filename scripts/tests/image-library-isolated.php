<?php

/**
 * @file
 * Real Media Library and image-placement checks on an explicit disposable clone.
 *
 * Standalone PHP only. The caller creates the clone; this script never creates,
 * restores or drops a database and never boots the shared default database.
 * DYNE_IMAGE_TEST_DATABASE=dyne_image_verify_20261026 php scripts/tests/image-library-isolated.php
 */

$test_database = getenv('DYNE_IMAGE_TEST_DATABASE');
if (!is_string($test_database) || !preg_match('/^dyne_image_verify_[A-Za-z0-9_]{1,40}$/D', $test_database) || $test_database === 'db') {
  fwrite(STDERR, "Refusing to bootstrap: DYNE_IMAGE_TEST_DATABASE must name an explicit disposable dyne_image_verify_* database.\n");
  exit(64);
}
if (defined('DRUPAL_ROOT') || class_exists('Drupal', FALSE)) {
  fwrite(STDERR, "Refusing an already bootstrapped runtime. Use standalone PHP, not Drush.\n");
  exit(64);
}
$app_root = dirname(__DIR__, 2) . '/web';
$report_directory = dirname($app_root) . '/output/image-library';
$report = ['passed' => FALSE, 'database' => $test_database, 'checks' => 0, 'guards' => 0, 'failures' => [], 'default_database_booted' => FALSE, 'schema_content_unchanged' => FALSE, 'widgets' => [], 'placements' => [], 'temporary_files_removed' => 0];
$switched = FALSE;
$temporary_uris = [];
$assert = static function (bool $condition, string $message) use (&$report): void {
  $report['checks']++;
  if (!$condition) throw new RuntimeException($message);
};
$guard = static function () use ($test_database, &$report): void {
  $info = \Drupal\Core\Database\Database::getConnectionInfo('default');
  if (($info['default']['database'] ?? NULL) !== $test_database) throw new RuntimeException('Isolation guard: configured connection changed.');
  $actual = \Drupal\Core\Database\Database::getConnection('default', 'default')->query('SELECT DATABASE()')->fetchField();
  if ($actual !== $test_database || $actual === 'db' || !str_starts_with($actual, 'dyne_image_verify_')) throw new RuntimeException('Isolation guard: active connection is not the explicitly authorized clone.');
  $report['guards']++;
};

try {
  $loader = require $app_root . '/autoload.php';
  chdir($app_root);
  $request = \Symfony\Component\HttpFoundation\Request::create('http://dyne-tx-workspace.ddev.site/');
  $kernel = \Drupal\Core\DrupalKernel::createFromRequest($request, $loader, 'prod', FALSE, $app_root);
  $connections = \Drupal\Core\Database\Database::getConnectionInfo('default');
  if (!isset($connections['default']) || ($connections['default']['driver'] ?? '') !== 'mysql') throw new RuntimeException('A MariaDB/MySQL clone connection is required.');
  $clone = $connections['default'];
  $clone['database'] = $test_database;
  foreach (array_keys(\Drupal\Core\Database\Database::getAllConnectionInfo()) as $key) \Drupal\Core\Database\Database::removeConnection($key);
  \Drupal\Core\Database\Database::addConnectionInfo('default', 'default', $clone, $loader, $app_root);
  \Drupal\Core\Database\Database::setActiveConnection('default');
  $guard();
  $kernel->boot();
  $guard();
  \Drupal\Component\FileCache\FileCacheFactory::setConfiguration([\Drupal\Component\FileCache\FileCacheFactory::DISABLE_CACHE => TRUE]);
  $kernel->rebuildContainer();
  $guard();
  $kernel->preHandle($request);
  $guard();
  $administrator = \Drupal::entityTypeManager()->getStorage('user')->load(1);
  $assert($administrator !== NULL, 'Existing administrator is available in the clone.');
  \Drupal::service('account_switcher')->switchTo($administrator);
  $switched = TRUE;
  require_once $app_root . '/modules/custom/dyne_migrate/includes/image-library.inc';
  require_once $app_root . '/themes/custom/dynetx/dynetx.theme';
  $assert(\Drupal::moduleHandler()->moduleExists('media_library'), 'Core Media Library is enabled.');
  $assert(function_exists('_dynetx_library_images_preprocess'), 'Native image preprocess helper is loaded.');
  $entities = \Drupal::entityTypeManager();
  $paragraphs = $entities->getStorage('paragraph');
  $media_storage = $entities->getStorage('media');
  $file_storage = $entities->getStorage('file');
  $save = static function ($entity) use ($guard): void { $guard(); $entity->save(); $guard(); };

  // Configuration installation must not create revisions, register images, or
  // alter existing content rows. Hash only pre-existing content tables; newly
  // added empty field tables are configuration's expected storage footprint.
  $fingerprint = static function (?array $table_names = NULL): array {
    $connection = \Drupal::database();
    if ($table_names === NULL) $table_names = array_values(array_filter($connection->schema()->findTables('%'), static fn(string $table): bool => (bool) preg_match('/^(?:node(?:_|$)|paragraphs_item(?:_|$)|paragraph(?:_|$)|media(?:_|$)|file_managed$)/D', $table)));
    sort($table_names);
    $result = [];
    foreach ($table_names as $table) {
      $rows = [];
      foreach ($connection->select($table, 'content')->fields('content')->execute()->fetchAll(\PDO::FETCH_ASSOC) as $row) {
        ksort($row);
        $rows[] = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
      }
      sort($rows, SORT_STRING);
      $result[$table] = ['rows' => count($rows), 'sha256' => hash('sha256', implode("\n", $rows))];
    }
    return $result;
  };
  $before = $fingerprint();
  $media_before = (int) $media_storage->getQuery()->accessCheck(FALSE)->count()->execute();
  $files_before = (int) $file_storage->getQuery()->accessCheck(FALSE)->count()->execute();
  $guard();
  dyne_image_library_schema();
  $guard();
  $after = $fingerprint(array_keys($before));
  $assert($before === $after, 'Schema upgrade preserves every existing node/paragraph revision, Media and File content row.');
  $assert((int) $media_storage->getQuery()->accessCheck(FALSE)->count()->execute() === $media_before, 'Schema does not register or duplicate any Media.');
  $assert((int) $file_storage->getQuery()->accessCheck(FALSE)->count()->execute() === $files_before, 'Schema does not register or duplicate any File.');
  $report['schema_content_unchanged'] = TRUE;
  $report['schema_content_tables'] = count($before);
  $report['schema_content_digests'] = ['before' => hash('sha256', json_encode($before, JSON_THROW_ON_ERROR)), 'after' => hash('sha256', json_encode($after, JSON_THROW_ON_ERROR))];
  $report['initial_media_count'] = $media_before;
  $report['initial_file_count'] = $files_before;

  $type = \Drupal\media\Entity\MediaType::load('image');
  $assert($type !== NULL && $type->getSource()->getPluginId() === 'image', 'Image Media uses the core image source.');
  $source_field = $type->getSource()->getSourceFieldDefinition($type)->getName();
  $media_form = \Drupal\Core\Entity\Entity\EntityFormDisplay::load('media.image.default');
  $assert(($media_form->getComponent($source_field)['type'] ?? '') === 'image_image', 'The Media source itself retains the image upload widget.');
  foreach (['node.dyne_page.default' => 'field_image', 'paragraph.dyne_hero.default' => 'field_banner_logo'] as $id => $field) {
    $display = \Drupal\Core\Entity\Entity\EntityFormDisplay::load($id);
    $assert(!$display || !$display->getComponent($field), $id . ' hides its unused archival upload control.');
  }
  $find_state = static function (array $element) use (&$find_state) {
    if (isset($element['#media_library_state'])) return $element['#media_library_state'];
    foreach ($element as $key => $child) if (is_array($child) && !str_starts_with((string) $key, '#')) {
      if ($state = $find_state($child)) return $state;
    }
    return NULL;
  };
  $library_state = NULL;
  foreach (dyne_image_library_fields() as $bundle => $definition) {
    $display = \Drupal\Core\Entity\Entity\EntityFormDisplay::load('paragraph.' . $bundle . '.default');
    $assert(($display->getComponent($definition['media'])['type'] ?? '') === 'media_library_widget', $bundle . ' uses the real core Media Library widget.');
    $assert(!$display->getComponent($definition['legacy']) && !$display->getComponent('field_media_images_ready'), $bundle . ' hides archival File and readiness controls.');
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('paragraph', $bundle);
    $translatable = $field_definitions[$definition['legacy']]->isTranslatable();
    foreach (array_filter([$definition['media'], $definition['alt'] ?? NULL, 'field_media_images_ready']) as $native_name) $assert($field_definitions[$native_name]->isTranslatable() === $translatable, $bundle . ' native field translation policy follows its original image placement: ' . $native_name);
    $new = $paragraphs->create(['type' => $bundle]);
    $guard();
    $form = \Drupal::service('entity.form_builder')->getForm($new);
    $guard();
    $assert(isset($form[$definition['media']]['widget']), $bundle . ' new form exposes its native image widget.');
    $state = $find_state($form[$definition['media']]);
    $assert($state instanceof \Drupal\media_library\MediaLibraryState, $bundle . ' actual widget supplies a signed modal opener state.');
    $assert($state->getAllowedTypeIds() === ['image'] && $state->getSelectedTypeId() === 'image', $bundle . ' modal is restricted to images.');
    $assert(\Drupal::service('media_library.opener.field_widget')->checkAccess($state, $administrator)->isAllowed(), $bundle . ' real field opener grants editor access.');
    $assert(\Drupal::service('media_library.ui_builder')->checkAccess($administrator, $state)->isAllowed(), $bundle . ' core library grid is accessible.');
    $signed = \Symfony\Component\HttpFoundation\Request::create('/media-library', 'GET', $state->all());
    $verified = \Drupal\media_library\MediaLibraryState::fromRequest($signed);
    $assert($verified->getHash() === $state->getHash(), $bundle . ' modal signed state verifies through the core request API.');
    $tampered = $state->all();
    $tampered['media_library_opener_parameters']['field_name'] = $definition['legacy'];
    try {
      \Drupal\media_library\MediaLibraryState::fromRequest(\Symfony\Component\HttpFoundation\Request::create('/media-library', 'GET', $tampered));
      $assert(FALSE, 'Tampered field state must be rejected.');
    }
    catch (\Symfony\Component\HttpKernel\Exception\BadRequestHttpException $error) { $assert(TRUE, $bundle . ' core rejects tampered opener parameters.'); }
    $widget = $form[$definition['media']];
    $widget_html = (string) \Drupal::service('renderer')->renderInIsolation($widget);
    $assert(str_contains($widget_html, 'js-media-library-open-button'), $bundle . ' actual widget renders Add media modal button.');
    $assert(!str_contains($widget_html, 'type="file"'), $bundle . ' placement widget presents a library chooser rather than a direct File upload.');
    $report['widgets'][$bundle] = ['image_only' => TRUE, 'signed_state_verified' => TRUE, 'tampered_state_rejected' => TRUE, 'opener_access' => TRUE];
    $library_state ??= $state;
  }
  $guard();
  $ui = \Drupal::service('media_library.ui_builder')->buildUi($library_state);
  $library_html = (string) \Drupal::service('renderer')->renderInIsolation($ui);
  $guard();
  $assert(str_contains($library_html, 'media-library-item') && str_contains($library_html, 'type="checkbox"'), 'The actual image library grid renders existing images with selection checkboxes.');
  $assert(str_contains($library_html, 'type="file"'), 'The same core library modal offers a new image upload.');
  $report['library_grid'] = ['existing_image_selection' => TRUE, 'new_upload_control' => TRUE];

  // Select two genuinely usable existing Image Media instead of guessing IDs.
  $samples = [];
  $ids = $media_storage->getQuery()->accessCheck(FALSE)->condition('bundle', 'image')->condition('status', 1)->sort('mid')->execute();
  foreach ($media_storage->loadMultiple($ids) as $media) {
    $file = $media->get($source_field)->entity;
    if (!$file || !str_starts_with($file->getFileUri(), 'public://') || !is_file($file->getFileUri()) || !\Drupal::service('image.factory')->get($file->getFileUri())->isValid()) continue;
    if (isset($samples[0]) && $samples[0]['file']->id() === $file->id()) continue;
    $samples[] = ['media' => $media, 'file' => $file];
    if (count($samples) === 2) break;
  }
  $assert(count($samples) === 2, 'Two existing reusable local Image Media are available.');
  [$first, $second] = $samples;
  $url = static fn($file): string => \Drupal::service('file_url_generator')->generateString($file->getFileUri());
  $reset = static function () use ($paragraphs, $media_storage, $file_storage, $entities): void {
    $paragraphs->resetCache(); $media_storage->resetCache(); $file_storage->resetCache();
    foreach (['paragraph', 'media', 'file'] as $entity_type) $entities->getAccessControlHandler($entity_type)->resetCache();
  };
  $render = static function ($paragraph, bool $uncached = FALSE) use ($guard): array {
    $guard();
    $build = \Drupal::entityTypeManager()->getViewBuilder('paragraph')->view($paragraph, 'default');
    if ($uncached) $build['#cache']['max-age'] = 0;
    $html = (string) \Drupal::service('renderer')->renderInIsolation($build);
    $guard();
    return ['html' => $html, 'tags' => $build['#cache']['tags'] ?? []];
  };
  $binding = static function ($paragraph): array { $variables = ['paragraph' => $paragraph]; _dynetx_library_images_preprocess($variables); return $variables['dyne_library_image']; };
  $guard();
  \Drupal::theme()->setActiveTheme(\Drupal::service('theme.initialization')->getActiveThemeByName('dynetx'));
  \Drupal::service('theme.registry')->reset();
  $guard();
  $placements = [];
  foreach (dyne_image_library_fields() as $bundle => $definition) {
    $values = ['type' => $bundle, $definition['legacy'] => ['target_id' => $first['file']->id(), 'alt' => 'Legacy placement alternative']];
    if ($bundle === 'dyne_video') $values['field_video_url'] = 'https://www.youtube.com/watch?v=abcdefghijk';
    if ($bundle === 'dyne_cta') $values['field_heading'] = 'Clone image verification';
    $paragraph = $paragraphs->create($values);
    $legacy = $binding($paragraph);
    $assert(!$legacy['ready'] && !$legacy['managed'] && $legacy['url'] === $url($first['file']), $bundle . ' retains its legacy source before conversion.');
    $count = (int) $media_storage->getQuery()->accessCheck(FALSE)->count()->execute();
    $save($paragraph);
    $assert((bool) $paragraph->get('field_media_images_ready')->value, $bundle . ' real presave marks the native placement ready.');
    $assert((int) $paragraph->get($definition['media'])->target_id === (int) $first['media']->id(), $bundle . ' first save reuses existing Media for the same File.');
    $assert((int) $media_storage->getQuery()->accessCheck(FALSE)->count()->execute() === $count, $bundle . ' legacy reuse creates no duplicate Media.');
    if (isset($definition['alt'])) $assert($paragraph->get($definition['alt'])->value === 'Legacy placement alternative', 'Banner first save copies the placement alternative without editing shared Media.');
    $managed = $binding($paragraph);
    $html = $render($paragraph);
    $assert($managed['ready'] && $managed['managed'] && str_contains($html['html'], htmlspecialchars($managed['url'], ENT_QUOTES, 'UTF-8')), $bundle . ' real Twig renders the new Media source.');
    if (isset($definition['alt'])) $assert(str_contains($html['html'], 'alt="Legacy placement alternative"'), 'Banner real Twig renders its placement alternative.');
    $paragraph->set($definition['media'], []); $paragraph->setNewRevision(TRUE); $save($paragraph); $reset();
    $paragraph = $paragraphs->load($paragraph->id());
    $cleared = $binding($paragraph); $cleared_html = $render($paragraph);
    $assert($cleared['ready'] && $cleared['managed'] && $cleared['url'] === '', $bundle . ' clearing a ready placement does not resurrect the old File.');
    $assert(!str_contains($cleared_html['html'], '<img'), $bundle . ' real Twig preserves the deliberately cleared image.');
    $paragraph->set($definition['media'], ['target_id' => $second['media']->id()]);
    if (isset($definition['alt'])) $paragraph->set($definition['alt'], 'Selected placement alternative');
    $paragraph->setNewRevision(TRUE); $save($paragraph); $reset();
    $paragraph = $paragraphs->load($paragraph->id());
    $swapped = $binding($paragraph); $swapped_html = $render($paragraph);
    $assert($swapped['url'] === $url($second['file']) && str_contains($swapped_html['html'], htmlspecialchars($swapped['url'], ENT_QUOTES, 'UTF-8')), $bundle . ' swapping the picker changes real Twig output.');
    $assert(in_array('media:' . $second['media']->id(), $swapped_html['tags'], TRUE) && in_array('file:' . $second['file']->id(), $swapped_html['tags'], TRUE), $bundle . ' rendered cache depends on selected Media and File.');
    $placements[$bundle] = $paragraph->id();
    $report['placements'][$bundle] = ['legacy_preserved_before_ready' => TRUE, 'existing_media_reused' => TRUE, 'clearing_preserved' => TRUE, 'swapping_rendered' => TRUE, 'media_file_cache_tags' => TRUE];
  }

  // An explicit new selection takes precedence even on the very first save.
  $chosen = $paragraphs->create(['type' => 'dyne_alert_banner', 'field_banner_logo' => ['target_id' => $first['file']->id(), 'alt' => 'Old source alt'], 'field_banner_logo_media' => ['target_id' => $second['media']->id()], 'field_banner_logo_alt' => 'Explicit new alternative']);
  $save($chosen);
  $assert((int) $chosen->get('field_banner_logo_media')->target_id === (int) $second['media']->id(), 'Presave never overwrites a newly selected Media with the old File.');
  $assert($chosen->get('field_banner_logo_alt')->value === 'Explicit new alternative', 'Presave never overwrites a new placement alternative.');
  $chosen->set('field_banner_logo_alt', ''); $chosen->setNewRevision(TRUE); $save($chosen);
  $assert($binding($chosen)['alt'] === '', 'An explicitly empty banner alternative stays decorative rather than inheriting shared Media text.');

  // CTA's old branded default remains before ownership changes, and is seeded
  // into Media on its first normal save so later clearing really means empty.
  $default_cta = $paragraphs->create(['type' => 'dyne_cta', 'field_heading' => 'Default image verification']);
  $default_before = $render($default_cta, TRUE);
  $assert(str_contains($default_before['html'], 'home-culture.webp'), 'Unconverted empty CTA preserves its existing branded fallback.');
  $save($default_cta);
  $assert((bool) $default_cta->get('field_media_images_ready')->value && !$default_cta->get('field_bg_media')->isEmpty(), 'First CTA save adopts its verified local default into Media.');
  $default_cta->set('field_bg_media', []); $default_cta->setNewRevision(TRUE); $save($default_cta);
  $assert(!str_contains($render($default_cta, TRUE)['html'], '<img'), 'Clearing the adopted CTA default does not resurrect the branded fallback.');

  // Stage a new upload in a unique temporary file, never a public source path.
  // Automatic conversion deliberately refuses unpublished/private staging
  // wrappers; the real core library modal above supplies the upload control.
  $png_source = $app_root . '/themes/custom/dynetx/images/rdd-bg-top.png';
  $assert(is_file($png_source), 'A local PNG fixture exists for the new-upload path.');
  $temporary_uri = 'temporary://dyne-image-verify-' . $test_database . '-' . bin2hex(random_bytes(5)) . '.png';
  $temporary_uris[] = $temporary_uri;
  $guard();
  $uploaded_file = \Drupal::service('file.repository')->writeData(file_get_contents($png_source), $temporary_uri, \Drupal\Core\File\FileExists::Error);
  $guard();
  $assert($uploaded_file->id() && $uploaded_file->getMimeType() === 'image/png', 'A new local upload becomes a managed PNG File only in the clone.');
  $count = (int) $media_storage->getQuery()->accessCheck(FALSE)->count()->execute();
  $guard(); $staged_media = dyne_image_library_media_for_file((int) $uploaded_file->id()); $guard();
  $assert($staged_media === NULL && (int) $media_storage->getQuery()->accessCheck(FALSE)->count()->execute() === $count, 'Automatic conversion refuses temporary upload staging without registering Media.');
  $guard(); $existing_again = dyne_image_library_media_for_file((int) $first['file']->id()); $guard();
  $assert($existing_again->id() === $first['media']->id(), 'Repeated existing public File selection reuses its existing Media.');
  // A fresh clone-only Media wrapper isolates the mutation/cache assertions
  // from all existing editorial Media. Both source Files remain unchanged.
  $uploaded_media = $media_storage->create(['bundle' => 'image', 'name' => 'Isolated image cache verification', 'uid' => 1, 'status' => 1, $source_field => ['target_id' => $first['file']->id(), 'alt' => 'Shared source alternative']]);
  $save($uploaded_media);
  $shared = $paragraphs->create(['type' => 'dyne_alert_banner', 'field_banner_logo_media' => ['target_id' => $uploaded_media->id()], 'field_banner_logo_alt' => 'Shared media placement alternative']);
  $save($shared);
  $first_shared = $render($shared);
  $assert(in_array('media:' . $uploaded_media->id(), $first_shared['tags'], TRUE) && in_array('file:' . $first['file']->id(), $first_shared['tags'], TRUE), 'New Media placement render carries both cache dependencies.');
  $uploaded_media->set($source_field, ['target_id' => $second['file']->id(), 'alt' => 'Shared replacement alternative']);
  $uploaded_media->setNewRevision(TRUE); $save($uploaded_media); $reset();
  $shared = $paragraphs->load($shared->id()); $changed_shared = $render($shared);
  $assert(str_contains($changed_shared['html'], htmlspecialchars($url($second['file']), ENT_QUOTES, 'UTF-8')) && !str_contains($changed_shared['html'], htmlspecialchars($url($first['file']), ENT_QUOTES, 'UTF-8')), 'Changing the shared Media source invalidates cached placement output without a Paragraph save.');
  $assert(str_contains($changed_shared['html'], 'Shared media placement alternative'), 'A shared Media source change preserves per-placement alternative text.');
  $uploaded_media = $media_storage->load($uploaded_media->id()); $uploaded_media->setUnpublished(); $uploaded_media->setNewRevision(TRUE); $save($uploaded_media); $reset();
  $hidden = $render($paragraphs->load($shared->id()));
  $assert(!str_contains($hidden['html'], '<img'), 'Unpublishing selected Media suppresses cached image output even for the administrator.');
  $assert(in_array('media:' . $uploaded_media->id(), $hidden['tags'], TRUE), 'Suppressed Media remains a cache dependency for republishing.');
  $report['upload_and_shared_media'] = ['temporary_upload_file_registered' => TRUE, 'temporary_conversion_rejected' => TRUE, 'existing_public_media_reused' => TRUE, 'source_change_invalidates_cache' => TRUE, 'placement_alt_preserved' => TRUE, 'unpublished_media_suppressed' => TRUE];
  $guard();
  $report['passed'] = TRUE;
}
catch (\Throwable $error) {
  $report['failures'][] = get_class($error) . ': ' . $error->getMessage();
  // Keep diagnostics useful without dumping entities, file contents, or HTML.
  $report['failure_location'] = basename($error->getFile()) . ':' . $error->getLine();
}
finally {
  foreach ($temporary_uris as $uri) {
    if (str_starts_with($uri, 'temporary://dyne-image-verify-' . $test_database . '-') && is_file($uri)) {
      try { \Drupal::service('file_system')->delete($uri); $report['temporary_files_removed']++; }
      catch (\Throwable $error) { $report['failures'][] = 'Temporary test file cleanup failed.'; $report['passed'] = FALSE; }
    }
  }
  if ($switched) \Drupal::service('account_switcher')->switchBack();
}
if (!is_dir($report_directory)) mkdir($report_directory, 0775, TRUE);
file_put_contents($report_directory . '/isolated-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n");
print json_encode(['passed' => $report['passed'], 'database' => $test_database, 'checks' => $report['checks'], 'guards' => $report['guards'], 'failures' => $report['failures']], JSON_PRETTY_PRINT) . "\n";
exit($report['passed'] ? 0 : 1);
