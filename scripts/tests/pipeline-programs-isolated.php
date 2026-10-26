<?php

/**
 * @file
 * Real-save pipeline migration and editing checks, exclusively against a disposable database clone.
 *
 * Run as standalone PHP, never Drush:
 * DYNE_PIPELINE_TEST_DATABASE=dyne_pipeline_verify_20261023 php scripts/tests/pipeline-programs-isolated.php
 *
 * The caller creates the clone beforehand. No database is created, restored,
 * dropped, or selected implicitly by this script. Test content stays in that
 * clone for inspection; the shared site's default database is never booted.
 */

$test_database = getenv('DYNE_PIPELINE_TEST_DATABASE');
if (!is_string($test_database) || !preg_match('/^dyne_pipeline_verify_[A-Za-z0-9_]{1,40}$/D', $test_database) || $test_database === 'db') {
  fwrite(STDERR, "Refusing to bootstrap: DYNE_PIPELINE_TEST_DATABASE must name a disposable dyne_pipeline_verify_* database.\n");
  exit(64);
}
if (defined('DRUPAL_ROOT') || class_exists('Drupal', FALSE)) {
  fwrite(STDERR, "Refusing an already bootstrapped runtime. Invoke this script with standalone PHP, not Drush.\n");
  exit(64);
}

$app_root = dirname(__DIR__, 2) . '/web';
$directory = dirname($app_root) . '/output/pipeline-programs';
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
  if ($actual !== $test_database || $actual === 'db' || !str_starts_with($actual, 'dyne_pipeline_verify_')) {
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
  \Drupal::service('account_switcher')->switchTo($administrator);
  $switched = TRUE;
  require_once $app_root . '/modules/custom/dyne_migrate/includes/pipeline-programs.inc';
  require_once $app_root . '/themes/custom/dynetx/dynetx.theme';
  $nodes = \Drupal::entityTypeManager()->getStorage('node');
  $paragraphs = \Drupal::entityTypeManager()->getStorage('paragraph');
  $plan = dyne_pipeline_migration_plan();
  $assert($plan['available'] && !$plan['complete'], 'Clone contains an unmigrated current pipeline.');
  $assert(count($plan['parsed']['programs']) === 8, 'Current source names exactly eight programs.');
  $source_page = $nodes->load($plan['page_id']);
  $original_fields = $source_page->toArray();
  $old_component = $paragraphs->loadRevision($plan['source']['revision']);
  $old_component_fields = $old_component->toArray();
  $other_nodes = [];
  foreach ($nodes->loadMultiple() as $node) if ((int) $node->id() !== $plan['page_id']) $other_nodes[$node->id()] = $node->toArray();
  $original_count = (int) $nodes->getQuery()->accessCheck(FALSE)->count()->execute();
  $guard();
  $migration = dyne_pipeline_program_upgrade();
  $guard();
  $assert(count($migration['programs']) === 8, 'Migration records eight program identities.');
  $assert((int) $nodes->getQuery()->accessCheck(FALSE)->count()->execute() === $original_count + 8, 'Only eight native nodes were added.');
  $nodes->resetCache();
  $paragraphs->resetCache();
  foreach ($other_nodes as $id => $before) $assert($nodes->load($id)->toArray() === $before, 'Unrelated node ' . $id . ' is byte-for-byte unchanged.');
  $page = $nodes->load($plan['page_id']);
  $assert((int) $page->getRevisionId() !== $plan['page_revision'], 'Pipeline page has a new revision.');
  $assert($nodes->loadRevision($plan['page_revision'])->toArray() === $original_fields, 'The previous pipeline page revision is unchanged.');
  $assert($paragraphs->loadRevision($plan['source']['revision'])->toArray() === $old_component_fields, 'The previous HTML component revision is unchanged.');
  $assert($page->label() === $source_page->label(), 'Pipeline page title is preserved.');
  $assert(\Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $page->id()) === '/pipeline', 'Pipeline page URL remains /pipeline.');
  $component = $paragraphs->load($migration['component_id']);
  $assert($component->bundle() === 'dyne_program_pipeline', 'The page has an entity-backed program pipeline component.');
  $assert(count($component->get('field_pipeline_programs')) === 8, 'All eight program references are populated.');
  $render = static function ($component): array {
    $variables = ['paragraph' => $component];
    _dynetx_pipeline_programs_preprocess($variables);
    return $variables;
  };
  // Exercise the real Paragraph/node Twig suggestions, not only preprocess
  // arrays. Registry/cache writes below still target the guarded clone.
  $guard();
  \Drupal::theme()->setActiveTheme(\Drupal::service('theme.initialization')->getActiveThemeByName('dynetx'));
  \Drupal::service('theme.registry')->reset();
  $guard();
  $render_html = static function ($entity, string $mode = 'default', bool $uncached = FALSE): array {
    $build = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, $mode);
    if ($uncached) $build['#cache']['max-age'] = 0;
    $html = (string) \Drupal::service('renderer')->renderInIsolation($build);
    return ['html' => $html, 'cache_tags' => $build['#cache']['tags'] ?? []];
  };
  $fresh_component = static function (int $id) use ($nodes, $paragraphs) {
    // Simulate a new request's entity/access memory without clearing render
    // caches: stale rendered output would expose a missing cache dependency.
    $nodes->resetCache(); $paragraphs->resetCache();
    \Drupal::entityTypeManager()->getAccessControlHandler('node')->resetCache();
    return $paragraphs->load($id);
  };
  $html_xpath = static function (string $html): \DOMXPath {
    $document = new \DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(TRUE);
    try { $document->loadHTML('<?xml encoding="UTF-8"><div id="pipeline-test-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); }
    finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    return new \DOMXPath($document);
  };
  $class_xpath = static fn(string $class): string => 'contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")';
  $normal_text = static fn(string $html): string => trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
  $detail_text = static function (\DOMXPath $xpath, string $anchor, bool $mobile) use ($normal_text): string {
    $element = $xpath->query('//*[@id="' . ($mobile ? 'collapse' : '') . $anchor . '"]')->item(0);
    if (!$element) return '';
    return $normal_text($mobile ? $element->textContent : $element->parentNode->textContent);
  };
  foreach ($migration['programs'] as $code => $record) {
    $node = $nodes->load($record['id']);
    $assert($node->get('field_program_code')->value === $code, $code . ' has its stable code.');
    $assert($node->isPublished() === $page->isPublished(), $code . ' follows the source publication state.');
    $assert(!$node->get('field_prevalence_us')->isEmpty() && !$node->get('field_prevalence_eu')->isEmpty(), $code . ' has separate U.S. and European prevalence.');
    $assert($node->get('field_target')->value === $plan['parsed']['programs'][$code]['target'], $code . ' preserves its target.');
    $assert($node->get('field_phase')->value === $plan['parsed']['programs'][$code]['phase_label'], $code . ' preserves the literal source chart label.');
    $assert($node->get('field_body_text')->isEmpty() === empty($plan['parsed']['programs'][$code]['html']['introduction']), $code . ' has only supported narrative copy.');
    $violations = $node->validate();
    $assert(count($violations) === 0, $code . ' passes Drupal entity validation: ' . (string) $violations);
  }
  $dmd = $nodes->load($migration['programs']['DYNE-251']['id']);
  $dm1 = $nodes->load($migration['programs']['DYNE-101']['id']);
  $fshd = $nodes->load($migration['programs']['DYNE-302']['id']);
  $assert(str_contains($fshd->get('field_body_text')->value, 'DYNE-302 is'), 'FSHD uses the complete desktop narrative instead of the truncated mobile duplicate.');
  $assert($dmd->get('field_phase')->value === 'Commercial' && str_contains($dmd->get('field_body_text')->value, 'investigational'), 'Source chart labels and narrative are preserved without inferring approval.');
  $assert(!$dm1->get('field_program_video')->isEmpty() && !$dm1->get('field_program_video_cover')->isEmpty(), 'DM1 has a managed video cover and editable video URL.');
  $form = \Drupal::service('entity.form_builder')->getForm($dmd);
  foreach (array_keys(dyne_pipeline_program_model_fields()) as $name) $assert(isset($form[$name]['widget']) && ($form[$name]['#access'] ?? TRUE) !== FALSE, 'Program edit form exposes ' . $name . '.');
  foreach (['field_prevalence', 'field_components', 'field_legacy_title'] as $name) $assert(!isset($form[$name]) || ($form[$name]['#access'] ?? TRUE) === FALSE, 'Program edit form hides archived ' . $name . '.');
  foreach (['field_body_text', 'field_disease_onset', 'field_disease_symptoms'] as $name) $assert(($form[$name]['widget'][0]['#allowed_formats'] ?? []) === ['dyne_legacy_html'], $name . ' uses the generic Full HTML format with CKEditor.');
  $assert(\Drupal\filter\Entity\FilterFormat::load('dyne_legacy_html')->label() === 'Full HTML', 'Text format display name stays generic.');
  $assert(\Drupal\editor\Entity\Editor::load('dyne_legacy_html')->getEditor() === 'ckeditor5', 'The program rich-text format uses CKEditor 5.');
  $first = $render($component);
  $report['render_variable_keys'] = array_keys($first);
  $first_html = $render_html($fresh_component((int) $component->id()));
  $first_xpath = $html_xpath($first_html['html']);
  $assert($first_xpath->query('//div[' . $class_xpath('chart-body') . ']')->length === 5, 'Real Twig renders five disease chart rows.');
  $assert($first_xpath->query('//div[' . $class_xpath('chart') . ' and ' . $class_xpath('short') . ']')->length === 8, 'Real Twig renders eight native program bars.');
  foreach (['DMD' => 'DYNE-251', 'DM1' => 'DYNE-101', 'FSHD' => 'DYNE-302'] as $anchor => $code) {
    $assert($first_xpath->query('//*[@id="' . $anchor . '"]')->length === 1 && $first_xpath->query('//*[@id="collapse' . $anchor . '"]')->length === 1, $code . ' has unique desktop and mobile detail anchors.');
    foreach (['introduction', 'disease_overview', 'onset', 'cause', 'symptoms', 'unmet_need'] as $key) {
      $expected = $normal_text($plan['parsed']['programs'][$code]['html'][$key]);
      if ($expected === '') continue;
      foreach ([TRUE, FALSE] as $mobile) $assert(str_contains($detail_text($first_xpath, $anchor, $mobile), $expected), $code . ' complete ' . $key . ' reaches ' . ($mobile ? 'mobile' : 'desktop') . ' Twig output.');
    }
  }
  $assert(in_array('node:' . $dmd->id(), $first_html['cache_tags'], TRUE), 'Paragraph render cache depends on its referenced DMD program.');
  $report['twig_rendering'] = ['initial_rows' => 5, 'initial_bars' => 8, 'initial_bytes' => strlen($first_html['html']), 'referenced_program_cache_tag' => 'node:' . $dmd->id()];
  $guard();
  $page_revision = $page->getRevisionId();
  $revisions = [];
  foreach ($migration['programs'] as $record) $revisions[$record['id']] = $nodes->load($record['id'])->getRevisionId();
  $again = dyne_pipeline_program_upgrade();
  $nodes->resetCache();
  $assert(!empty($again['complete']) && $nodes->load($page->id())->getRevisionId() === $page_revision, 'Rerunning the upgrade creates no extra page revisions.');
  foreach ($revisions as $id => $revision) $assert($nodes->load($id)->getRevisionId() === $revision, 'Rerun preserves program revision ' . $id . '.');
  // Real edits happen only in this disposable clone.
  $dmd = $nodes->load($dmd->id());
  $dmd->set('field_target', 'Exon 51 verification edit');
  $dmd->set('field_disease_onset', ['value' => '<p>Edited onset with <strong>emphasis</strong>.</p>', 'format' => 'dyne_legacy_html']);
  $dmd->setNewRevision(TRUE);
  $guard(); $dmd->save(); $guard();
  $updated_html = $render_html($fresh_component((int) $component->id()));
  $updated_xpath = $html_xpath($updated_html['html']);
  $assert(str_contains($updated_html['html'], 'Exon 51 verification edit') && !str_contains($first_html['html'], 'Exon 51 verification edit'), 'A saved target edit invalidates cached chart HTML.');
  foreach ([TRUE, FALSE] as $mobile) $assert(str_contains($detail_text($updated_xpath, 'DMD', $mobile), 'Edited onset with emphasis.'), 'A saved onset edit reaches fresh ' . ($mobile ? 'mobile' : 'desktop') . ' detail output without a page save.');
  $assert(str_contains($updated_html['html'], '<strong>emphasis</strong>'), 'Filtered rich emphasis reaches the real Twig HTML.');
  $nodes->resetCache();
  $assert($nodes->load($dmd->id())->get('field_target')->value === 'Exon 51 verification edit', 'A normal program save persists the separate target control.');
  $assert(str_contains($nodes->load($dmd->id())->get('field_disease_onset')->value, '<strong>emphasis</strong>'), 'CKEditor markup survives normal program saves.');
  $assert($nodes->load($page->id())->getRevisionId() === $page_revision, 'Program field edits do not require a pipeline page revision.');

  // Populate an anonymous render-cache variant before changing publication.
  $switcher = \Drupal::service('account_switcher');
  $switcher->switchTo(new \Drupal\Core\Session\AnonymousUserSession());
  try {
    $anonymous_before = $render_html($fresh_component((int) $component->id()));
    $assert(str_contains($anonymous_before['html'], 'Exon 51 verification edit'), 'Published program appears in anonymous real Twig listing.');
  }
  finally { $switcher->switchBack(); }
  $dmd = $nodes->load($dmd->id()); $dmd->setUnpublished(); $dmd->setNewRevision(TRUE);
  $guard(); $dmd->save(); $guard();
  $switcher->switchTo(new \Drupal\Core\Session\AnonymousUserSession());
  try {
    $anonymous_after = $render_html($fresh_component((int) $component->id()));
    $anonymous_xpath = $html_xpath($anonymous_after['html']);
    $assert(!str_contains($anonymous_after['html'], 'Exon 51 verification edit') && $anonymous_xpath->query('//*[@id="collapseDMD" or @id="DMD"]')->length === 0, 'Unpublishing removes chart facts and both detail panels from cached anonymous output.');
  }
  finally { $switcher->switchBack(); }
  $admin_unpublished = $render_html($fresh_component((int) $component->id()));
  $assert(!str_contains($admin_unpublished['html'], 'Exon 51 verification edit'), 'Privileged listing output also excludes unpublished programs.');
  $dmd = $nodes->load($dmd->id());
  $preview = $render_html($dmd, 'full');
  $assert(str_contains($preview['html'], 'Edited onset with') && str_contains($preview['html'], 'Exon 51 verification edit'), 'Authorized standalone full view can preview an unpublished native program.');
  $dmd->setPublished(); $dmd->setNewRevision(TRUE);
  $guard(); $dmd->save(); $guard();
  $republished = $render_html($fresh_component((int) $component->id()));
  $assert(str_contains($republished['html'], 'Exon 51 verification edit'), 'Republishing restores the native program without saving the page.');

  $dmd = $nodes->load($dmd->id()); $onset_values = $dmd->get('field_disease_onset')->getValue();
  $dmd->set('field_disease_onset', []); $dmd->setNewRevision(TRUE);
  $guard(); $dmd->save(); $guard();
  $cleared = $render_html($fresh_component((int) $component->id())); $cleared_xpath = $html_xpath($cleared['html']);
  foreach ([TRUE, FALSE] as $mobile) {
    $text = $detail_text($cleared_xpath, 'DMD', $mobile);
    $assert(!str_contains($text, 'Edited onset with') && !str_contains($text, $normal_text($plan['parsed']['programs']['DYNE-251']['html']['onset'])), 'Clearing onset stays empty in ' . ($mobile ? 'mobile' : 'desktop') . ' output without archived-copy fallback.');
  }
  $dmd = $nodes->load($dmd->id()); $dmd->set('field_disease_onset', $onset_values); $dmd->setNewRevision(TRUE);
  $guard(); $dmd->save(); $guard();

  // The component's ordered references alone drive chart order. Its current
  // revision can be changed in this clone without creating a page revision.
  $component = $fresh_component((int) $component->id());
  $original_references = $component->get('field_pipeline_programs')->getValue();
  $component->set('field_pipeline_programs', array_reverse($original_references)); $component->setNewRevision(FALSE);
  $guard(); $component->save(); $guard();
  $reordered = $render_html($fresh_component((int) $component->id())); $reordered_xpath = $html_xpath($reordered['html']);
  $first_bar = $reordered_xpath->query('//div[' . $class_xpath('chart') . ' and ' . $class_xpath('short') . ']')->item(0);
  $assert($first_bar && str_contains($first_bar->textContent, 'DYNE-401'), 'Reordered native references change the first rendered program.');
  $assert($nodes->load($page->id())->getRevisionId() === $page_revision, 'Chart reference reordering does not save the page.');
  $component = $paragraphs->load($component->id()); $component->set('field_pipeline_programs', $original_references); $component->setNewRevision(FALSE);
  $guard(); $component->save(); $guard();

  // A new program has no retained template or archived body to fall back to.
  $optional = \Drupal\node\Entity\Node::create([
    'type' => 'pipeline_program', 'title' => 'Native optional renderer verification', 'uid' => 1, 'status' => 1,
    'field_program_code' => 'DYNE-9999', 'field_disease_text' => 'Verification disease', 'field_disease_short' => 'VERIFY',
    'field_target' => 'Native optional target', 'field_phase' => 'Verification stage', 'field_program_progress' => 10,
    'field_body_text' => ['value' => '<p>New native optional description.</p>', 'format' => 'dyne_legacy_html'],
    'field_disease_onset' => ['value' => '<p>New native optional onset.</p>', 'format' => 'dyne_legacy_html'],
    'field_trial_links' => [['uri' => 'https://example.org/clone-trial', 'title' => 'Native trial link']],
    'field_program_video' => ['uri' => 'https://www.youtube.com/watch?v=abcdefghijk', 'title' => 'Native video title'],
    'field_program_video_cover' => $dm1->get('field_program_video_cover')->getValue(),
    'field_program_video_alt' => 'Native video placement alternative',
    'field_program_image' => $dm1->get('field_program_video_cover')->getValue(),
  ]);
  $guard(); $optional->save(); $guard();
  $optional_html = $render_html($optional, 'full');
  foreach (['New native optional description.', 'New native optional onset.', 'Native optional target', 'Native trial link', 'Native video title', 'Native video placement alternative'] as $text) $assert(str_contains($optional_html['html'], $text), 'Standalone full Twig displays new optional native value: ' . $text);
  $assert(str_contains($optional_html['html'], '/clone-trial') && str_contains($optional_html['html'], '<img'), 'Standalone new program renders its native link and managed media.');
  $native_component = clone $fresh_component((int) $component->id());
  $native_component->set('field_pipeline_programs', array_merge($original_references, [['target_id' => $optional->id()]]));
  $native_listing = $render_html($native_component, 'default', TRUE); $native_xpath = $html_xpath($native_listing['html']);
  $assert($native_xpath->query('//*[@id="DYNE-9999" or @id="collapseDYNE-9999"]')->length === 2, 'New native detail fields get reachable desktop and mobile anchors without a retained template.');

  $heading_only = clone $nodes->load($migration['programs']['DYNE-253']['id']);
  $heading_only->set('field_disease_heading', ['value' => '<p>Native heading only</p>', 'format' => 'dyne_legacy_html']);
  $heading_cache = \Drupal\Core\Cache\CacheableMetadata::createFromObject($heading_only);
  $heading_data = _dynetx_pipeline_program_build($heading_only, $heading_cache);
  $assert($heading_data['has_detail'] && $heading_data['anchor'] === 'DYNE-253', 'An optional disease heading alone makes native details reachable.');
  $optional->set('field_program_progress', []); $optional->setNewRevision(TRUE);
  $guard(); $optional->save(); $guard();
  $widthless_component = clone $fresh_component((int) $component->id());
  $widthless_component->set('field_pipeline_programs', array_merge($original_references, [['target_id' => $optional->id()]]));
  $widthless = $render_html($widthless_component, 'default', TRUE);
  $assert(str_contains($widthless['html'], 'Verification stage'), 'Clearing chart width does not hide the separate phase label.');

  $optional->set('field_trial_links', [['uri' => 'javascript:alert(1)', 'title' => 'Unsafe trial link sentinel']]);
  $optional->set('field_program_video', ['uri' => 'javascript:alert(2)', 'title' => 'Unsafe video link sentinel']);
  $optional->setNewRevision(TRUE); $guard(); $optional->save(); $guard();
  $nodes->resetCache([$optional->id()]);
  $unsafe = $render_html($nodes->load($optional->id()), 'full');
  $assert(!str_contains($unsafe['html'], 'javascript:') && !str_contains($unsafe['html'], 'Unsafe trial link sentinel') && !str_contains($unsafe['html'], 'Unsafe video link sentinel'), 'Active URL schemes are suppressed in trial and video output.');
  $deleted_id = $optional->id(); $guard(); $optional->delete(); $guard();
  $missing_component = clone $fresh_component((int) $component->id());
  $missing_component->set('field_pipeline_programs', array_merge($original_references, [['target_id' => $deleted_id], ['target_id' => 2147483647]]));
  $missing = $render_html($missing_component, 'default', TRUE); $missing_xpath = $html_xpath($missing['html']);
  $assert($missing_xpath->query('//div[' . $class_xpath('chart') . ' and ' . $class_xpath('short') . ']')->length === 8 && !str_contains($missing['html'], 'Native optional renderer verification'), 'Deleted and nonexistent references are skipped without breaking the remaining eight bars.');
  $assert(in_array('node:' . $deleted_id, $missing['cache_tags'], TRUE) && in_array('node:2147483647', $missing['cache_tags'], TRUE), 'Missing reference identities remain render-cache dependencies.');
  $report['twig_rendering'] += ['edited_cache_invalidated' => TRUE, 'anonymous_publication_cache_invalidated' => TRUE, 'unpublished_author_preview' => TRUE, 'clearing_has_no_legacy_fallback' => TRUE, 'reference_order_controls_chart' => TRUE, 'native_optional_program' => TRUE, 'unsafe_url_schemes_suppressed' => TRUE, 'deleted_and_missing_references_skipped' => TRUE];
  $guard();
  $report['migration'] = $migration;
  $report['passed'] = TRUE;
}
catch (\Throwable $error) {
  $report['failures'][] = $error->getMessage();
  $report['trace'] = $error->getTraceAsString();
}
finally {
  if ($switched) \Drupal::service('account_switcher')->switchBack();
}
if (!is_dir($directory)) mkdir($directory, 0775, TRUE);
file_put_contents($directory . '/isolated-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n");
print json_encode(['passed' => $report['passed'], 'database' => $test_database, 'checks' => $report['checks'], 'guards' => $report['guards'], 'failures' => $report['failures']], JSON_PRETTY_PRINT) . "\n";
exit($report['passed'] ? 0 : 1);

