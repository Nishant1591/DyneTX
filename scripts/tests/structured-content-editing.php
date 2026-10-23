<?php
/**
 * Transactional integration checks for structured editorial fields and caching.
 * Run only after the structured-content backfill. All test entities, edits and
 * revisions are rolled back; render/entity caches are cleared in finally.
 */
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/structured-content.inc';
require_once DRUPAL_ROOT . '/themes/custom/dynetx/dynetx.theme';

$manager = \Drupal::entityTypeManager();
$database = \Drupal::database();
$storage = $manager->getStorage('node');
$paragraph_storage = $manager->getStorage('paragraph');
$renderer = \Drupal::service('renderer');
$checks = [];
$assert = static function (bool $condition, string $message) use (&$checks): void {
  if (!$condition) throw new RuntimeException($message);
  $checks[] = $message;
  echo '[check] ' . $message . "\n";
};
$counts = static function () use ($database): array {
  $result = [];
  foreach (['node', 'node_revision', 'paragraphs_item', 'paragraphs_item_revision'] as $table) {
    $result[$table] = (int) $database->select($table)->countQuery()->execute()->fetchField();
  }
  return $result;
};
$before_counts = $counts();
if (getenv('DYNE_VERIFY_CLEANUP_ONLY')) {
  foreach (['cache.render', 'cache.page', 'cache.dynamic_page_cache', 'cache.entity'] as $service) if (\Drupal::hasService($service)) \Drupal::service($service)->deleteAll();
  echo json_encode(['content_and_revision_counts' => $before_counts, 'test_caches_cleared' => TRUE]) . "\n";
  return;
}
if (getenv('DYNE_VERIFY_ALLOW_TRANSACTIONAL_SAVES') !== '1') {
  throw new RuntimeException('Save tests are disabled by default. Use read-only structured-content-forms.php and structured-content-source-guard.php.');
}
$saved_originals = [];
$created_entities = [];
$remember = static function ($entity) use (&$saved_originals, &$created_entities): void {
  $key = $entity->getEntityTypeId() . ':' . $entity->id();
  if (isset($created_entities[$key])) return;
  $saved_originals[$key] ??= $entity->toArray();
};
$save = static function ($entity) use ($remember, &$created_entities): void {
  $new = $entity->isNew();
  if (!$entity->isNew()) $remember($entity);
  // A normal editor save is a new request. Reset request-local checksum
  // coalescing so repeated saves in this CLI process invalidate render tags.
  \Drupal::service('cache_tags.invalidator.checksum')->reset();
  $entity->setNewRevision(TRUE);
  $entity->save();
  if ($new) $created_entities[$entity->getEntityTypeId() . ':' . $entity->id()] = TRUE;
};
$render = static function ($entity, string $mode = 'full') use ($manager, $renderer): string {
  $build = $manager->getViewBuilder($entity->getEntityTypeId())->view($entity, $mode);
  return (string) $renderer->renderInIsolation($build);
};
$body = static function ($entity) use ($renderer): string {
  $build = $entity->bundle() === 'job' ? _dynetx_structured_job_body($entity) : _dynetx_structured_article_body($entity);
  return (string) $renderer->renderInIsolation($build);
};
$first = static function (string $bundle, ?string $field = NULL) use ($storage) {
  $query = $storage->getQuery()->accessCheck(FALSE)->condition('type', $bundle)->condition('status', 1)->sort('nid')->range(0, 1);
  if ($field) $query->exists($field);
  $ids = $query->execute();
  if (!$ids) throw new RuntimeException('No test fixture for ' . $bundle . ':' . $field);
  return $storage->load(reset($ids));
};
$find_section = static function ($entity) use (&$find_section): ?array {
  if ($entity->bundle() === 'dyne_article_section' && !$entity->get('field_section_body')->isEmpty()) return ['entity' => $entity, 'path' => []];
  foreach (['field_article_sections', 'field_components'] as $field) {
    if (!$entity->hasField($field)) continue;
    foreach ($entity->get($field) as $delta => $item) {
      if ($item->entity && ($found = $find_section($item->entity))) {
        array_unshift($found['path'], ['entity' => $entity, 'field' => $field, 'delta' => $delta]);
        return $found;
      }
    }
  }
  return NULL;
};
$edit_child = static function ($root, string $marker) use ($find_section, $save, $remember): void {
  $found = $find_section($root);
  if (!$found) throw new RuntimeException('No editable nested section in ' . $root->bundle() . ':' . $root->id());
  $child = $found['entity'];
  $remember($child);
  $child->set('field_section_body', ['value' => '<p>' . $marker . '</p>', 'format' => 'dyne_legacy_html']);
  $save($child);
  foreach (array_reverse($found['path']) as $step) {
    $parent = $step['entity'];
    $remember($parent);
    $refs = $parent->get($step['field'])->getValue();
    $refs[$step['delta']] = ['target_id' => $child->id(), 'target_revision_id' => $child->getRevisionId()];
    $parent->set($step['field'], $refs);
    $save($parent);
    $child = $parent;
  }
};
$marker = 'DYNE_STRUCTURED_TEST_' . bin2hex(random_bytes(5));
$theme_manager = \Drupal::service('theme.manager');
$old_theme = $theme_manager->getActiveTheme();
$theme_manager->setActiveTheme(\Drupal::service('theme.initialization')->getActiveThemeByName('dynetx'));
$account_switcher = \Drupal::service('account_switcher');
$transaction = $database->startTransaction();
$rolled_back = FALSE;
$rollback_reserve = str_repeat('x', 65536);
register_shutdown_function(static function () use (&$transaction, &$rolled_back, &$rollback_reserve): void {
  $rollback_reserve = NULL;
  if (!$rolled_back) {
    try { $transaction->rollBack(); $rolled_back = TRUE; }
    catch (\Throwable $error) { error_log('Verification rollback failure: ' . $error->getMessage()); }
  }
});
$failure = NULL;
try {
  // Warm full entity render cache before saving actual editorial values.
  $job = $first('job', 'field_pay_range_min');
  $remember($job);
  $original_body = [(string) $job->get('field_body_text')->value, (string) $job->get('field_body_text')->format];
  $render($job); $render($job);
  $job->set('field_role_summary', ['value' => '<p>' . $marker . '_ROLE</p>', 'format' => 'dyne_job_html']);
  $job->set('field_company_website', ['uri' => 'https://example.com/' . strtolower($marker)]);
  $job->set('field_pay_range_min', '123456.00');
  $job->set('field_pay_range_max', '234567.00');
  $save($job);
  $html = $render($job);
  if (!str_contains($html, $marker . '_ROLE')) {
    file_put_contents(dirname(DRUPAL_ROOT) . '/output/structured-model/editing-job-debug.html', $html);
    file_put_contents(dirname(DRUPAL_ROOT) . '/output/structured-model/editing-job-body-debug.html', $body($job));
  }
  $assert(str_contains($html, $marker . '_ROLE'), 'Saved Job role summary appears after warming full entity cache.');
  $assert(str_contains($html, 'https://example.com/' . strtolower($marker)), 'Saved typed company URL appears in the Job link.');
  $assert(str_contains($html, '123,456') && str_contains($html, '234,567'), 'Saved decimal salary endpoints appear in the Job body.');
  $assert([(string) $job->get('field_body_text')->value, (string) $job->get('field_body_text')->format] === $original_body, 'Editing structured Job fields preserves the archived merged body bytes and text format.');

  $news = $first('news', 'field_article_body');
  $remember($news); $render($news); $render($news);
  $news->set('field_article_body', ['value' => '<p>' . $marker . '_NEWS</p>', 'format' => 'dyne_legacy_html']);
  $save($news);
  $assert(str_contains($render($news), $marker . '_NEWS'), 'Saved News article appears after warming full entity cache.');

  foreach (['perspective', 'dyne_page'] as $bundle) {
    $fixture = NULL;
    foreach ($storage->loadMultiple($storage->getQuery()->accessCheck(FALSE)->condition('type', $bundle)->condition('status', 1)->sort('nid')->execute()) as $candidate) {
      if ($find_section($candidate)) { $fixture = $candidate; break; }
    }
    if (!$fixture) throw new RuntimeException('No nested section fixture for ' . $bundle);
    $render($fixture); $render($fixture);
    $edit_child($fixture, $marker . '_' . strtoupper($bundle));
    $assert(str_contains($render($fixture), $marker . '_' . strtoupper($bundle)), 'Saved nested ' . $bundle . ' section propagates through full entity cache.');
  }

  $policy = $first('privacy_policy');
  $policy_page = NULL;
  foreach ($storage->loadByProperties(['type' => 'dyne_page', 'status' => 1]) as $candidate) {
    foreach ($candidate->get('field_components')->referencedEntities() as $component) {
      if ($component->bundle() === 'dyne_policy_list' && in_array((string) $policy->id(), array_map('strval', array_column($component->get('field_policies')->getValue(), 'target_id')), TRUE)) $policy_page = $candidate;
    }
  }
  if (!$policy_page) throw new RuntimeException('No privacy policy listing fixture.');
  $render($policy_page); $render($policy_page);
  $edit_child($policy, $marker . '_POLICY');
  $assert(str_contains($render($policy_page), $marker . '_POLICY'), 'Saved policy section propagates through the cached public privacy listing.');

  // New content must work using editor controls, with no hidden layout values.
  $new_job = \Drupal\node\Entity\Node::create(['type' => 'job', 'title' => $marker . '_NEW_JOB', 'uid' => 1, 'status' => 1, 'field_role_summary' => ['value' => '<p>' . $marker . '_NEW_ROLE</p>', 'format' => 'dyne_job_html'], 'field_company_website' => ['uri' => 'https://example.com/new-company'], 'field_pay_range_min' => '100000', 'field_pay_range_max' => '150000']);
  $save($new_job);
  $assert($new_job->get('field_content_template')->isEmpty() && str_contains($render($new_job), $marker . '_NEW_ROLE'), 'New Job renders native fields with an empty preserved template.');
  $new_news = \Drupal\node\Entity\Node::create(['type' => 'news', 'title' => $marker . '_NEW_NEWS', 'uid' => 1, 'status' => 1, 'field_article_body' => ['value' => '<p>' . $marker . '_NEW_ARTICLE</p>', 'format' => 'dyne_legacy_html']]);
  $save($new_news);
  $assert($new_news->get('field_content_template')->isEmpty() && str_contains($render($new_news), $marker . '_NEW_ARTICLE'), 'New News renders native article text with an empty preserved template.');
  $sections = [];
  foreach (['A', 'B', 'C'] as $name) {
    $section = \Drupal\paragraphs\Entity\Paragraph::create(['type' => 'dyne_article_section', 'field_section_heading' => ['value' => $marker . '_HEADING_' . $name, 'format' => 'dyne_legacy_html'], 'field_section_body' => ['value' => '<p>' . $marker . '_SECTION_' . $name . '</p>', 'format' => 'dyne_legacy_html']]);
    $save($section); $sections[$name] = $section;
  }
  $refs = static fn(array $order): array => array_map(static fn($name) => ['target_id' => $sections[$name]->id(), 'target_revision_id' => $sections[$name]->getRevisionId()], $order);
  $new_story = \Drupal\node\Entity\Node::create(['type' => 'perspective', 'title' => $marker . '_NEW_STORY', 'uid' => 1, 'status' => 1, 'field_article_sections' => $refs(['A', 'B'])]);
  $save($new_story);
  $html = $render($new_story);
  $assert(str_contains($html, $marker . '_HEADING_A') && strpos($html, $marker . '_SECTION_A') < strpos($html, $marker . '_SECTION_B'), 'New empty-template sections render headings and text in field order.');
  $new_story->set('field_article_sections', $refs(['B', 'C', 'A'])); $save($new_story); $html = $render($new_story);
  $assert(strpos($html, $marker . '_SECTION_B') < strpos($html, $marker . '_SECTION_C') && strpos($html, $marker . '_SECTION_C') < strpos($html, $marker . '_SECTION_A'), 'Adding and reordering native sections updates the public article.');
  $new_story->set('field_article_sections', $refs(['C'])); $save($new_story); $html = $render($new_story);
  $assert(str_contains($html, $marker . '_SECTION_C') && !str_contains($html, $marker . '_SECTION_A') && !str_contains($html, $marker . '_SECTION_B'), 'Deleting section references removes their public content.');
  $new_rich = \Drupal\paragraphs\Entity\Paragraph::create(['type' => 'dyne_rich_text', 'field_article_sections' => $refs(['A'])]);
  $save($new_rich);
  $new_page = \Drupal\node\Entity\Node::create(['type' => 'dyne_page', 'title' => $marker . '_NEW_PAGE', 'uid' => 1, 'status' => 1, 'field_components' => [['target_id' => $new_rich->id(), 'target_revision_id' => $new_rich->getRevisionId()]]]);
  $save($new_page);
  $assert(str_contains($render($new_page), $marker . '_SECTION_A'), 'Editor-created marketing rich-text sections render without hidden template or flag input.');

  // FormBuilder runs widget processing and hook_form_alter as the real admin.
  $account_switcher->switchTo(\Drupal\user\Entity\User::load(1));
  try {
    $forms = [];
    $expected = [
      'job' => array_merge(array_keys(dyne_structured_job_fields()), ['field_department', 'field_location', 'field_application_link']),
      'news' => array_keys(dyne_structured_article_fields()['news']),
      'perspective' => array_keys(dyne_structured_article_fields()['perspective']),
      'privacy_policy' => ['field_article_intro', 'field_article_sections', 'field_policy_updated'],
      'leader' => ['field_role', 'field_body_text', 'field_photo'],
      'dyne_page' => ['field_components'],
      'pipeline_program' => ['field_disease_text', 'field_prevalence', 'field_target', 'field_phase', 'field_status'],
      'quote' => ['field_quote_text', 'field_speaker_image', 'field_quote_width'],
    ];
    foreach ($expected as $bundle => $fields) {
      $node = \Drupal\node\Entity\Node::create(['type' => $bundle, 'title' => $marker . '_FORM']);
      $form = \Drupal::service('entity.form_builder')->getForm($node);
      foreach ($fields as $field) $assert(isset($form[$field]['widget']) && ($form[$field]['#access'] ?? TRUE) !== FALSE, $bundle . ' admin form exposes the ' . $field . ' widget.');
      if (in_array($bundle, ['job', 'news', 'perspective', 'privacy_policy'], TRUE)) $assert(!isset($form['field_body_text']) || ($form['field_body_text']['#access'] ?? TRUE) === FALSE, $bundle . ' admin form hides the archived merged body.');
      $forms[$bundle] = $form;
    }
    foreach (['field_company_overview' => 'dyne_company', 'field_role_summary' => 'dyne_role', 'field_pay_range_min' => 'dyne_pay'] as $field => $group) $assert(($forms['job'][$field]['#group'] ?? '') === $group && isset($forms['job'][$group]), 'Job widget ' . $field . ' belongs to its actual editorial form group.');
    foreach ([['field_company_overview', 'field_company_website', 'field_company_twitter', 'field_company_linkedin', 'field_company_facebook'], ['field_role_summary', 'field_responsibilities', 'field_education_skills', 'field_additional_job_content']] as $ordered) {
      $weights = array_map(static fn($name) => $forms['job'][$name]['#weight'], $ordered);
      $sorted = $weights; sort($sorted);
      $assert($weights === $sorted && count(array_unique($weights)) === count($weights), 'Actual processed Job form preserves semantic order for ' . implode(', ', $ordered) . '.');
    }
    $assert($forms['job']['field_department']['#weight'] < $forms['job']['dyne_company']['#weight'] && $forms['job']['field_application_link']['#weight'] < $forms['job']['dyne_company']['#weight'], 'Actual Job form places identifying metadata and application URL before Company information.');
  }
  finally { $account_switcher->switchBack(); }
}
catch (\Throwable $error) { $failure = $error; }
finally {
  $transaction->rollBack();
  $rolled_back = TRUE;
  $theme_manager->setActiveTheme($old_theme);
  foreach (['node', 'paragraph'] as $type) $manager->getStorage($type)->resetCache();
  \Drupal::service('cache_tags.invalidator.checksum')->reset();
  foreach (['cache.render', 'cache.page', 'cache.dynamic_page_cache', 'cache.entity'] as $service) if (\Drupal::hasService($service)) \Drupal::service($service)->deleteAll();
}
$after_counts = $counts();
$assert($before_counts === $after_counts, 'Rollback restores exact node, paragraph and revision counts.');
foreach ($saved_originals as $key => $values) {
  [$type, $id] = explode(':', $key);
  $entity = $manager->getStorage($type)->load($id);
  $assert($entity && $entity->toArray() === $values, 'Rollback restores unchanged entity ' . $key . '.');
}
$report = ['passed' => !$failure, 'checks' => $checks, 'counts_before' => $before_counts, 'counts_after' => $after_counts, 'error' => $failure?->getMessage()];
file_put_contents(dirname(DRUPAL_ROOT) . '/output/structured-model/editing-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if ($failure) throw $failure;
