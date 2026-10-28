<?php

/**
 * Reconcile the reviewed public-source content captured on October 28, 2026.
 *
 * Default: read-only plan. Apply after a backup with
 * ddev exec env DYNE_LIVE_RECONCILE_APPLY=1 drush php:script scripts/reconcile-live-content-2026-10-28.php
 * Existing detail pages remain published; only current-vacancy membership is
 * reconciled. Existing copy is changed only by explicit source/baseline guards.
 */

use Drupal\node\Entity\Node;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\redirect\Entity\Redirect;
use Drupal\taxonomy\Entity\Term;

require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/title-aliases.inc';
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/structured-content.inc';
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/live-content-reconciliation.inc';

$project = dirname(DRUPAL_ROOT);
$fixture_path = $project . '/web/modules/custom/dyne_migrate/data/live-content-reconciliation-2026-10-28.json';
$fixture = json_decode(file_get_contents($fixture_path), TRUE, 512, JSON_THROW_ON_ERROR);
$apply = getenv('DYNE_LIVE_RECONCILE_APPLY') === '1';
$nodes = \Drupal::entityTypeManager()->getStorage('node');
$previous = \Drupal::state()->get('dyne_migrate.live_reconciliation_20261028', []);
$source_reconciled = ($previous['fixture_sha256'] ?? '') === hash_file('sha256', $fixture_path);
// The final native-text review corrects three public job-title en dashes.
// Keep the original source fixture and its reconciliation hash unchanged;
// only an exact reviewed before/after value pair can extend its title guard.
$reviewed_titles = [];
$native_text = json_decode(file_get_contents(DRUPAL_ROOT . '/modules/custom/dyne_migrate/data/live-final-native-text-2026-10-28.json'), TRUE, 512, JSON_THROW_ON_ERROR);
foreach ($native_text['records'] as $spec) {
  if ($spec['bundle'] !== 'job' || empty($spec['alias'])) continue;
  foreach ($spec['patches'] as $patch) {
    if ($patch['entity_type'] === 'node' && (int) $patch['id'] === (int) $spec['node_id'] && $patch['bundle'] === 'job' && $patch['field'] === 'title' && ($patch['kind'] ?? '') === 'values' && count($patch['before_values']) === 1 && count($patch['after_values']) === 1) {
      $reviewed_titles[$spec['alias']] = ['node_id' => (int) $spec['node_id'], 'before' => $patch['before_values'], 'after' => $patch['after_values']];
    }
  }
}
$report = [
  'apply' => $apply, 'captured_on' => $fixture['captured_on'],
  'source_fixture_sha256' => hash_file('sha256', $fixture_path),
  'planned' => [], 'changes' => [], 'preserved' => [], 'conflicts' => [],
  'reference_changes' => $fixture['reference_changes'],
  'content_deletions' => 0, 'unpublished_nodes' => 0,
];

/** Resolve a public alias without assuming IDs from another environment. */
function dyne_audit_20261028_node(string $alias): ?\Drupal\node\NodeInterface {
  $path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
  return preg_match('@^/node/(\d+)$@D', $path, $match)
    ? \Drupal::entityTypeManager()->getStorage('node')->load($match[1]) : NULL;
}

/** Create a source-owned alias without changing the editor's Pathauto policy. */
function dyne_audit_20261028_save_new($node, string $alias): void {
  $state = dyne_migrate_preserve_import_alias($node);
  $node->save();
  PathAlias::create(['path' => '/node/' . $node->id(), 'alias' => $alias, 'langcode' => 'en'])->save();
  dyne_migrate_restore_import_alias($node, $state);
}

/** Reuse a native taxonomy term or create the source's exact department label. */
function dyne_audit_20261028_term(string $vocabulary, string $label): int {
  $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $matches = $storage->loadByProperties(['vid' => $vocabulary, 'name' => $label]);
  if ($matches) return (int) reset($matches)->id();
  $term = Term::create(['vid' => $vocabulary, 'name' => $label]);
  $term->save();
  return (int) $term->id();
}

/** Save a new revision, recording the exact old and new value in the report. */
function dyne_audit_20261028_set($node, string $field, $value, string $reason, array &$report): void {
  $before = $node->get($field)->getValue();
  $node->set($field, $value);
  $node->setNewRevision(TRUE);
  $node->setRevisionLogMessage($reason);
  $node->save();
  $report['changes'][] = ['entity' => 'node', 'id' => (int) $node->id(), 'revision' => (int) $node->getRevisionId(), 'field' => $field, 'before' => $before, 'after' => $node->get($field)->getValue()];
}

/** Correct only the reviewed typographic caption in active native sections. */
function dyne_audit_20261028_caption($entity, array $spec, bool $apply, array &$report, int $depth = 0): bool {
  if ($depth > 24) throw new RuntimeException('Unexpected resource section nesting.');
  $changed = FALSE;
  $fields = [];
  foreach (['field_components', 'field_article_sections'] as $name) {
    if (!$entity->hasField($name)) continue;
    foreach ($entity->get($name) as $item) {
      if ($item->entity && dyne_audit_20261028_caption($item->entity, $spec, $apply, $report, $depth + 1)) {
        if ($apply) $item->target_revision_id = $item->entity->getRevisionId();
        $changed = TRUE;
      }
    }
  }
  foreach ($spec['allowed_fields'] as $name) {
    if (!$entity->hasField($name)) continue;
    $values = $entity->get($name)->getValue();
    $replacement = $values;
    foreach ($replacement as &$item) {
      foreach (['value', 'title'] as $key) {
        if (isset($item[$key]) && str_contains((string) $item[$key], $spec['from'])) $item[$key] = str_replace($spec['from'], $spec['to'], $item[$key]);
      }
    }
    unset($item);
    if ($replacement !== $values) {
      $fields[$name] = ['before_sha256' => hash('sha256', json_encode($values)), 'after_sha256' => hash('sha256', json_encode($replacement))];
      if ($apply) $entity->set($name, $replacement);
      $changed = TRUE;
    }
  }
  if ($changed) {
    if ($apply) {
      $entity->setNewRevision(TRUE);
      if (method_exists($entity, 'setRevisionLogMessage')) $entity->setRevisionLogMessage('Match the verified public resource-link typography; preserve all other native content.');
      $entity->save();
    }
    $report[$apply ? 'changes' : 'planned'][] = ['entity' => $entity->getEntityTypeId(), 'id' => (int) $entity->id(), 'revision' => $apply ? (int) $entity->getRevisionId() : NULL, 'operation' => 'resource_typography', 'fields' => $fields];
  }
  return $changed;
}

// Resolve and validate all record identities before applying any schema/data.
$existing_jobs = [];
foreach ($fixture['vacancy_census'] as $record) {
  $node = dyne_audit_20261028_node($record['alias']);
  $reviewed = $reviewed_titles[$record['alias']] ?? NULL;
  $reviewed_title = $source_reconciled && $node && $reviewed && (int) $node->id() === $reviewed['node_id']
    && $reviewed['before'] === [['value' => $record['title']]] && $node->get('title')->getValue() === $reviewed['after'];
  if (!$node || $node->bundle() !== 'job' || (!$reviewed_title && $node->label() !== $record['title']) || !$node->isPublished()
    || !$node->hasField('field_wp_source_id') || (int) $node->get('field_wp_source_id')->value !== (int) $record['source_id']) {
    $report['conflicts'][] = ['operation' => 'vacancy_membership', 'alias' => $record['alias'], 'reason' => 'The reviewed job identity/publication changed; no update was applied.'];
    continue;
  }
  if ($reviewed_title) $report['preserved'][] = ['operation' => 'reviewed_title_typography', 'id' => (int) $node->id(), 'alias' => $record['alias'], 'current_title' => $node->label()];
  $existing_jobs[$record['alias']] = $node;
  $actual = $node->hasField('field_current_vacancy') && !$node->get('field_current_vacancy')->isEmpty() ? (bool) $node->get('field_current_vacancy')->value : NULL;
  if ($actual !== $record['current_vacancy']) {
    if ($source_reconciled) $report['preserved'][] = ['operation' => 'editorial_vacancy_override', 'id' => (int) $node->id(), 'alias' => $record['alias'], 'current_value' => $actual];
    else $report['planned'][] = ['operation' => 'vacancy_membership', 'id' => (int) $node->id(), 'alias' => $record['alias'], 'before' => $actual, 'after' => $record['current_vacancy']];
  }
}
foreach ($fixture['new_jobs'] as $record) {
  if (hash('sha256', $record['body_html']) !== $record['body_sha256'] || $record['text_word_count'] < 500) throw new RuntimeException('The verified complete job body is missing or changed.');
  $parsed = dyne_job_parse($record['body_html']);
  if (dyne_job_signature(dyne_job_render_parts($parsed)) !== dyne_job_signature($record['body_html'])) throw new RuntimeException('Job-field extraction does not round-trip the complete source.');
  $node = dyne_audit_20261028_node($record['alias']);
  if ($node && ($node->bundle() !== 'job' || $node->label() !== $record['title'] || (int) $node->get('field_wp_source_id')->value !== $record['source_id'])) {
    $report['conflicts'][] = ['operation' => 'new_job', 'alias' => $record['alias'], 'reason' => 'The source URL is occupied by a different record.'];
  }
  elseif (!$node && $source_reconciled) {
    $report['conflicts'][] = ['operation' => 'new_job', 'alias' => $record['alias'], 'reason' => 'A previously reconciled record/URL was removed or changed; preserve the editorial decision pending review.'];
  }
  elseif (!$node) {
    $matches = $nodes->getQuery()->accessCheck(FALSE)->condition('type', 'job')->condition('field_greenhouse_id', $record['greenhouse_id'])->execute();
    if ($matches) $report['conflicts'][] = ['operation' => 'new_job', 'alias' => $record['alias'], 'reason' => 'A record already owns this Greenhouse ID under another URL.'];
    else $report['planned'][] = ['operation' => 'create_job', 'alias' => $record['alias'], 'source_id' => $record['source_id'], 'word_count' => $record['text_word_count']];
  }
  else $report['preserved'][] = ['operation' => 'existing_reconciled_job', 'id' => (int) $node->id(), 'alias' => $record['alias']];
}
$leaders = [];
foreach ($fixture['leadership_changes'] as $record) {
  $matches = $nodes->loadByProperties(['type' => 'leader', 'field_wp_source_id' => $record['source_id']]);
  $node = $matches ? reset($matches) : NULL;
  if (!$node || $node->label() !== $record['title'] || (!$source_reconciled && !in_array((string) $node->get('field_role')->value, [$record['expected_role'], $record['role']], TRUE))) {
    $report['conflicts'][] = ['operation' => 'leader_role', 'source_id' => $record['source_id'], 'reason' => 'The reviewed identity/role baseline changed.'];
    continue;
  }
  $leaders[$record['source_id']] = $node;
  if ((string) $node->get('field_role')->value !== $record['role']) {
    if ($source_reconciled) $report['preserved'][] = ['operation' => 'editorial_role_override', 'id' => (int) $node->id()];
    else $report['planned'][] = ['operation' => 'leader_role', 'id' => (int) $node->id(), 'before' => $record['expected_role'], 'after' => $record['role']];
  }
}
$news_nodes = [];
foreach ($fixture['homepage_news'] as $record) {
  $matches = $nodes->getQuery()->accessCheck(FALSE)->condition('type', 'news')->condition('field_external_url.uri', $record['url'])->execute();
  if (!$matches && $source_reconciled) $matches = $nodes->getQuery()->accessCheck(FALSE)->condition('type', 'news')->condition('field_external_id', 'live-audit:' . substr(hash('sha256', $record['url']), 0, 24))->execute();
  if ($matches) $news_nodes[$record['url']] = $nodes->load(reset($matches));
  elseif ($source_reconciled) $report['conflicts'][] = ['operation' => 'external_news', 'url' => $record['url'], 'reason' => 'A previously reconciled headline was removed or changed; preserve the editorial decision pending review.'];
  else $report['planned'][] = ['operation' => 'create_external_news', 'title' => $record['title'], 'date' => $record['date'], 'url' => $record['url']];
}
$redirect_storage = \Drupal::entityTypeManager()->getStorage('redirect');
foreach ($fixture['redirects'] as $record) {
  $hash = Redirect::generateHash($record['source'], [], 'und');
  $matches = $redirect_storage->loadByProperties(['hash' => $hash]);
  $redirect = $matches ? reset($matches) : NULL;
  if ($redirect && ($redirect->getRedirect()['uri'] !== $record['target'] || (int) $redirect->getStatusCode() !== $record['status'])) $report['conflicts'][] = ['operation' => 'redirect', 'source' => $record['source'], 'reason' => 'An existing redirect has another target/status.'];
  elseif (!$redirect) $report['planned'][] = ['operation' => 'redirect', 'source' => $record['source'], 'target' => $record['target']];
  if (!dyne_audit_20261028_node(substr($record['target'], strlen('internal:')))) $report['conflicts'][] = ['operation' => 'redirect', 'source' => $record['source'], 'reason' => 'Canonical target is not an existing native page.'];
}

if (!$report['conflicts'] && $apply) {
  dyne_live_content_vacancy_schema();
  $nodes->resetCache();
  $transaction = \Drupal::database()->startTransaction();
  try {
    $careers = dyne_audit_20261028_node('/careers-culture');
    $numeric = \Drupal::state()->get('dyne_migrate.legacy_numeric_urls', []);
    foreach ($fixture['new_jobs'] as $record) {
      $node = dyne_audit_20261028_node($record['alias']);
      if ($node) continue;
      $node = Node::create(['type' => 'job', 'uid' => 1, 'status' => 1, 'title' => $record['title']]);
      $node->set('field_body_text', ['value' => $record['body_html'], 'format' => 'dyne_legacy_html']);
      $node->set('field_legacy_title', $record['title']);
      $node->set('field_wp_source_id', $record['source_id']);
      $node->set('field_department', ['target_id' => dyne_audit_20261028_term('department', $record['department'])]);
      $node->set('field_location', ['target_id' => dyne_audit_20261028_term('job_location', $record['location'])]);
      $node->set('field_application_link', ['uri' => $record['application_url']]);
      $node->set('field_greenhouse_id', $record['greenhouse_id']);
      $node->set('field_current_vacancy', TRUE);
      foreach (['field_previous_url' => $record['previous_url'], 'field_next_url' => $record['next_url'], 'field_department_text' => $record['department'], 'field_location_text' => $record['location']] as $field => $value) if ($node->hasField($field)) $node->set($field, $value);
      foreach (['field_native_presentation', 'field_hero_first_line', 'field_hero_second_line', 'field_hero_third_level', 'field_hero_classes', 'field_body_class', 'field_hero_image', 'field_hero_mobile_image', 'field_hero_overlay_image'] as $field) if ($careers?->hasField($field) && $node->hasField($field)) $node->set($field, $careers->get($field)->getValue());
      dyne_structured_job_backfill($node);
      $node->setRevisionLogMessage('Restore the complete publicly verified job from the October 28, 2026 source capture.');
      dyne_audit_20261028_save_new($node, $record['alias']);
      $numeric['p:' . $record['source_id']] = ['nid' => (int) $node->id(), 'target' => $record['alias']];
      $report['changes'][] = ['operation' => 'create_job', 'id' => (int) $node->id(), 'revision' => (int) $node->getRevisionId(), 'alias' => $record['alias'], 'source_id' => $record['source_id'], 'body_sha256' => $record['body_sha256'], 'source_words' => $record['text_word_count']];
    }
    \Drupal::state()->set('dyne_migrate.legacy_numeric_urls', $numeric);
    foreach ($source_reconciled ? [] : $fixture['vacancy_census'] as $record) {
      $node = $nodes->load($existing_jobs[$record['alias']]->id());
      if ($node->get('field_current_vacancy')->isEmpty() || (bool) $node->get('field_current_vacancy')->value !== $record['current_vacancy']) dyne_audit_20261028_set($node, 'field_current_vacancy', $record['current_vacancy'], 'Reconcile only current-vacancy membership against the verified public listing; preserve the published job detail.', $report);
    }
    foreach ($source_reconciled ? [] : $fixture['leadership_changes'] as $record) {
      $node = $nodes->load($leaders[$record['source_id']]->id());
      if ((string) $node->get('field_role')->value !== $record['role']) dyne_audit_20261028_set($node, 'field_role', $record['role'], 'Match the verified public leadership role captured October 28, 2026.', $report);
    }
    foreach ($fixture['homepage_news'] as $record) {
      if (isset($news_nodes[$record['url']])) continue;
      $node = Node::create(['type' => 'news', 'uid' => 1, 'status' => 1, 'title' => $record['title']]);
      $node->set('field_legacy_title', $record['title']);
      $node->set('field_news_date', ['value' => $record['date']]);
      $node->set('field_news_type', 'url');
      $node->set('field_external_url', ['uri' => $record['url']]);
      $node->set('field_external_id', 'live-audit:' . substr(hash('sha256', $record['url']), 0, 24));
      $node->setCreatedTime(strtotime($record['date'] . 'Z'));
      $node->setRevisionLogMessage('Add the public headline/date/link shown in the live homepage news panel; external release body remains at its source.');
      $node->save();
      $report['changes'][] = ['operation' => 'create_external_news', 'id' => (int) $node->id(), 'revision' => (int) $node->getRevisionId(), 'title' => $node->label(), 'date' => $record['date'], 'url' => $record['url']];
    }
    foreach ($fixture['redirects'] as $record) {
      $hash = Redirect::generateHash($record['source'], [], 'und');
      if ($redirect_storage->loadByProperties(['hash' => $hash])) continue;
      $redirect = Redirect::create(['redirect_source' => ['path' => $record['source'], 'query' => []], 'redirect_redirect' => ['uri' => $record['target']], 'language' => 'und', 'status_code' => $record['status']]);
      $violations = $redirect->validate();
      if ($violations->count()) throw new RuntimeException((string) $violations);
      $redirect->save();
      $report['changes'][] = ['operation' => 'redirect', 'id' => (int) $redirect->id(), 'source' => $record['source'], 'target' => $record['target'], 'status' => $record['status']];
    }
    if (!$source_reconciled && ($resource = dyne_audit_20261028_node($fixture['resources_typography']['node_alias']))) dyne_audit_20261028_caption($resource, $fixture['resources_typography'], TRUE, $report);
    \Drupal::state()->set('dyne_migrate.live_reconciliation_20261028', ['fixture_sha256' => $report['source_fixture_sha256'], 'applied_at' => gmdate(DATE_ATOM), 'changes' => $report['changes']]);
    unset($transaction);
  }
  catch (Throwable $error) {
    $transaction->rollBack();
    throw $error;
  }
}
elseif (!$report['conflicts'] && !$source_reconciled && ($resource = dyne_audit_20261028_node($fixture['resources_typography']['node_alias']))) {
  dyne_audit_20261028_caption($resource, $fixture['resources_typography'], FALSE, $report);
}

$report['current_vacancies_expected'] = count($fixture['current_vacancies']);
$report['passed'] = !$report['conflicts'];
$directory = $project . '/output/live-local-audit-2026-10-28';
if (!is_dir($directory)) mkdir($directory, 0775, TRUE);
$filename = $apply ? 'content-applied.json' : 'content-dry-run.json';
file_put_contents($directory . '/' . $filename, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);
print json_encode(['apply' => $apply, 'passed' => $report['passed'], 'planned' => count($report['planned']), 'changes' => count($report['changes']), 'preserved' => count($report['preserved']), 'conflicts' => $report['conflicts'], 'current_vacancies_expected' => $report['current_vacancies_expected'], 'report' => 'output/live-local-audit-2026-10-28/' . $filename], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if (!$report['passed']) throw new RuntimeException('Live reconciliation stopped on a reviewed-baseline conflict; inspect the report.');
