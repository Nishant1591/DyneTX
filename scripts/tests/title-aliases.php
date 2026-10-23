<?php

/**
 * @file
 * Read-only verification: ddev drush php:script scripts/tests/title-aliases.php
 *
 * Uses Pathauto's explicit 'return' operation and unsaved entity clones. Never
 * submits a form or saves entities, aliases, redirects, configuration or state.
 * Writes only output/aliases/verification.json and its alias comparison report.
 * Set DYNE_ALIAS_VERIFY_SETUP=1 inside DDEV for the one-time enrollment audit;
 * normal runs accept later manual opt-outs and treat old baselines as advisory.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\pathauto\PathautoState;
use Drupal\pathauto\PathautoWidget;

$directory = dirname(DRUPAL_ROOT) . '/output/aliases';
$setup = getenv('DYNE_ALIAS_VERIFY_SETUP') === '1';
$report = ['passed' => FALSE, 'setup_mode' => $setup, 'checks' => 0, 'failures' => [], 'patterns' => [], 'widgets' => [], 'hook_cases' => [], 'repaired_aliases' => [], 'entity_saves' => 0, 'alias_saves' => 0, 'config_saves' => 0, 'state_writes' => 0];
$assert = static function (bool $condition, string $message) use (&$report): void {
  $report['checks']++;
  if (!$condition) $report['failures'][] = $message;
};
$hash = static fn(array $rows): string => hash('sha256', serialize($rows));
$database = \Drupal::database();
$snapshot = static function () use ($database, $hash): array {
  $tables = ['path_alias' => 'id', 'redirect' => 'rid', 'node' => 'nid', 'node_field_data' => 'nid', 'node_revision' => 'vid'];
  $result = [];
  foreach ($tables as $table => $key) {
    $rows = $database->select($table, 't')->fields('t')->orderBy($key)->execute()->fetchAll(\PDO::FETCH_ASSOC);
    // Translation rows need deterministic ordering within their entity ID.
    if ($table === 'node_field_data') usort($rows, static fn(array $a, array $b): int => [$a['nid'], $a['langcode']] <=> [$b['nid'], $b['langcode']]);
    $result[$table] = ['count' => count($rows), 'sha256' => $hash($rows)];
  }
  $states = $database->select('key_value', 'k')->fields('k', ['name', 'value'])->condition('collection', 'pathauto_state.node')->orderBy('name')->execute()->fetchAll(\PDO::FETCH_ASSOC);
  $result['pathauto_states'] = ['count' => count($states), 'sha256' => $hash($states)];
  $config = $database->select('config', 'c')->fields('c')->orderBy('collection')->orderBy('name')->execute()->fetchAll(\PDO::FETCH_ASSOC);
  $result['configuration'] = ['count' => count($config), 'sha256' => $hash($config)];
  return $result;
};
$before = $snapshot();
$switched = FALSE;
$comparison = ['baseline' => $directory . '/before.json', 'enforced' => $setup, 'available' => FALSE, 'passed' => NULL, 'repaired_aliases' => [], 'changed_nodes' => [], 'added_nodes' => [], 'missing_nodes' => []];

try {
  foreach (['pathauto', 'redirect'] as $module) $assert(\Drupal::moduleHandler()->moduleExists($module), $module . ' must be enabled.');
  if ($report['failures']) throw new RuntimeException('Required alias modules are unavailable.');
  $generator = \Drupal::service('pathauto.generator');
  $generator->resetCaches();
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $nodes = $storage->loadMultiple();
  $alias_manager = \Drupal::service('path_alias.manager');
  $states = \Drupal::keyValue('pathauto_state.node')->getAll();
  $patterns = [
    'dyne_page' => '/[node:title]',
    'job' => '/jobs/[node:title]',
    'leader' => '/team/[node:title]',
    'news' => '/news/[node:title]',
    'perspective' => '/perspectives/[node:title]',
    'pipeline_program' => '/pipeline/[node:title]',
    'privacy_policy' => '/policies/[node:title]',
    'quote' => '/quotes/[node:title]',
  ];
  $current = [];
  $groups = [];
  foreach ($nodes as $node) {
    $id = $node->id(); $bundle = $node->bundle();
    $current[$id] = ['bundle' => $bundle, 'title' => $node->label(), 'alias' => $alias_manager->getAliasByPath('/node/' . $id, $node->language()->getId())];
    if (!isset($patterns[$bundle])) continue;
    $groups[$bundle] = ($groups[$bundle] ?? 0) + 1;
    $stored = $states[$id] ?? NULL;
    $assert(in_array($stored, [PathautoState::SKIP, PathautoState::CREATE, '0', '1'], TRUE), 'Current node ' . $id . ' must have a valid stored automatic/manual state.');
    $assert((int) $node->get('path')->pathauto === (int) $stored, 'Current node ' . $id . ': computed policy must match its stored automatic/manual choice.');
    if ($setup) $assert((int) $stored === PathautoState::CREATE, 'Current node ' . $id . ' must have the one-time automatic state enabled.');
    $assert($generator->getPatternByEntity($node)?->id() === 'dyne_' . $bundle, 'Current node ' . $id . ' must select its bundle pattern.');
  }
  $report['current_nodes'] = count($current);
  $report['bundle_counts'] = $groups;

  foreach ($patterns as $bundle => $expected) {
    $pattern = PathautoPattern::load('dyne_' . $bundle);
    $assert($pattern !== NULL && $pattern->status(), $bundle . ': active named pattern is required.');
    if (!$pattern) continue;
    $assert($pattern->getPattern() === $expected, $bundle . ': pattern must match the intended namespace.');
    $prototype = NULL;
    foreach ($nodes as $node) if ($node->bundle() === $bundle) { $prototype = $node; break; }
    // A bundle with no current records still exercises the real token mapper
    // and URL generator through an unsaved prototype carrying an unused ID.
    $prototype ??= $storage->create(['type' => $bundle, 'nid' => 990000000 + count($report['patterns']), 'title' => 'Unsaved alias prototype', 'langcode' => 'en']);
    $clone = clone $prototype;
    $clone->setOriginal(clone $prototype);
    $clone->setTitle('Alias Café Déjà Vu — QA & Research / 2026!');
    $selected = $generator->getPatternByEntity($clone);
    $assert($selected?->id() === 'dyne_' . $bundle, $bundle . ': actual entity must select the intended pattern.');
    $candidate = $generator->createEntityAlias($clone, 'return');
    $prefix = str_replace('[node:title]', '', $expected);
    $assert(is_string($candidate) && str_starts_with($candidate, $prefix), $bundle . ': candidate must stay in its namespace.');
    $assert(is_string($candidate) && preg_match('~^/[a-z0-9/-]+$~D', $candidate) === 1, $bundle . ': Unicode/punctuation must be cleaned into a safe lowercase alias.');
    $assert(is_string($candidate) && str_contains($candidate, 'cafe-deja-vu') && str_contains($candidate, 'qa-research'), $bundle . ': candidate must preserve transliterated title words.');
    $assert($clone->label() === 'Alias Café Déjà Vu — QA & Research / 2026!' && $prototype->label() !== $clone->label(), $bundle . ': candidate generation must only change the clone.');
    $report['patterns'][$bundle] = ['id' => $pattern->id(), 'pattern' => $pattern->getPattern(), 'candidate' => $candidate, 'prototype_is_new' => $prototype->isNew()];
  }

  // Exercise core collision handling against an existing alias, never create a
  // competing record just for a test.
  $assert(isset($nodes[3], $nodes[4]), 'Existing home/about records are required for collision verification.');
  if (isset($nodes[3], $nodes[4])) {
    $owner = clone $nodes[4];
    $occupied = $generator->createEntityAlias($owner, 'return');
    $assert($occupied === $current[4]['alias'], 'Collision owner title must resolve to its existing alias.');
    $competitor = clone $nodes[3];
    $competitor->setOriginal(clone $nodes[3])->setTitle($owner->label());
    $unique = $generator->createEntityAlias($competitor, 'return');
    $assert(is_string($unique) && $unique !== $occupied && preg_match('~^' . preg_quote((string) $occupied, '~') . '-[0-9]+$~D', $unique) === 1, 'Core must suffix an occupied title alias without overwriting its owner.');
    $report['collision'] = ['occupied' => $occupied, 'candidate' => $unique, 'owner_node' => 4, 'clone_node' => 3];
  }

  require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/dyne_migrate.module';
  $assert(function_exists('dyne_migrate_pathauto_alias_alter'), 'Unchanged-title preservation hook must exist.');
  foreach ([29, 334] as $id) {
    $node = $nodes[$id] ?? NULL;
    $assert($node !== NULL, 'Node ' . $id . ' must be present for hook/widget verification.');
    if (!$node) continue;
    if (function_exists('dyne_migrate_pathauto_alias_alter')) {
      foreach (['same_title', 'changed_title', 'manual_reenabled', 'insert'] as $case) {
        $clone = clone $node;
        $clone->setOriginal(clone $node);
        // Test the auto-managed branch independently of this record's current
        // policy; a future editor may intentionally have opted out.
        $clone->getOriginal()->get('path')->pathauto = PathautoState::CREATE;
        $clone->get('path')->pathauto = PathautoState::CREATE;
        if ($case === 'changed_title') $clone->setTitle($node->label() . ' Alias verification');
        if ($case === 'manual_reenabled') {
          $clone->getOriginal()->get('path')->pathauto = PathautoState::SKIP;
          $clone->get('path')->pathauto = PathautoState::CREATE;
        }
        $candidate = '/verification-proposed-alias-' . $id;
        $context = ['module' => 'node', 'op' => $case === 'insert' ? 'insert' : 'update', 'source' => '/node/' . $id, 'bundle' => $node->bundle(), 'data' => ['node' => $clone], 'language' => $node->language()->getId()];
        dyne_migrate_pathauto_alias_alter($candidate, $context);
        $expected = $case === 'same_title' ? $current[$id]['alias'] : '/verification-proposed-alias-' . $id;
        $assert($candidate === $expected, 'Node ' . $id . ': hook must handle ' . $case . ' without changing unrelated aliases.');
        $dispatched = '/verification-proposed-alias-' . $id;
        \Drupal::moduleHandler()->alter('pathauto_alias', $dispatched, $context);
        $assert($dispatched === $expected, 'Node ' . $id . ': Drupal must dispatch the alias hook for ' . $case . '.');
        $report['hook_cases'][$id][$case] = $candidate;
      }
    }
    $manual = clone $node;
    $manual->setOriginal(clone $node)->setTitle($node->label() . ' Manual alias retained');
    $manual->get('path')->pathauto = PathautoState::SKIP;
    $assert($generator->updateEntityAlias($manual, 'return') === NULL, 'Node ' . $id . ': future manual opt-out must skip core generation.');
  }

  $pathauto = \Drupal::config('pathauto.settings');
  $redirect = \Drupal::config('redirect.settings');
  $assert((int) $pathauto->get('update_action') === PathautoGeneratorInterface::UPDATE_ACTION_DELETE, 'Pathauto must update the existing alias entity so Redirect can preserve the old URL.');
  $assert($redirect->get('auto_redirect') === TRUE && (int) $redirect->get('default_status_code') === 301, 'Old aliases must receive automatic permanent redirects.');
  $assert($redirect->get('passthrough_querystring') === TRUE, 'Alias redirects must preserve query strings.');
  $assert($redirect->get('route_normalizer_enabled') === TRUE, 'Canonical route normalization must remain enabled.');
  $report['front'] = \Drupal::config('system.site')->get('page.front');
  $assert($report['front'] === '/node/3', 'Frontpage must retain the stable internal home route.');

  $administrator = \Drupal::entityTypeManager()->getStorage('user')->load(1);
  $assert($administrator !== NULL, 'Existing administrator is required for isolated widget access checks.');
  if ($administrator) {
    \Drupal::service('account_switcher')->switchTo($administrator);
    $switched = TRUE;
    foreach ([29, 334] as $id) {
      if (!isset($nodes[$id])) continue;
      $clone = clone $nodes[$id];
      $display = EntityFormDisplay::load('node.' . $clone->bundle() . '.default');
      $widget = $display?->getRenderer('path');
      $assert($widget instanceof PathautoWidget, 'Node ' . $id . ': configured path widget must use Pathauto.');
      if (!$widget) continue;
      $state = new FormState();
      $shell = ['#parents' => []];
      $built = $widget->form($clone->get('path'), $shell, $state);
      $element = $built['widget'][0] ?? [];
      $checkbox = $element['pathauto'] ?? [];
      $assert(($checkbox['#type'] ?? '') === 'checkbox', 'Node ' . $id . ': automatic alias checkbox must be visible.');
      $assert((string) ($checkbox['#title'] ?? '') === 'Generate automatic URL alias', 'Node ' . $id . ': checkbox must use the core title.');
      $assert((int) ($checkbox['#default_value'] ?? -1) === (int) ($states[$id] ?? -2), 'Node ' . $id . ': automatic alias checkbox must reflect its saved automatic/manual policy.');
      $report['widgets'][$id] = ['class' => get_class($widget), 'checkbox_type' => $checkbox['#type'] ?? NULL, 'title' => (string) ($checkbox['#title'] ?? ''), 'checked' => (int) ($checkbox['#default_value'] ?? -1) === PathautoState::CREATE, 'alias' => $element['alias']['#default_value'] ?? NULL];
    }
  }

  if (is_file($comparison['baseline'])) {
    $comparison['available'] = TRUE;
    $baseline = json_decode(file_get_contents($comparison['baseline']), TRUE, 512, JSON_THROW_ON_ERROR);
    // The user-authorized repair fixes only Job 334's already-changed title.
    // It is not a blanket allowance for aliases to drift during setup.
    $repair_path = $directory . '/current-job-repair.json';
    $repair = is_file($repair_path) ? json_decode(file_get_contents($repair_path), TRUE, 512, JSON_THROW_ON_ERROR) : NULL;
    foreach ($baseline['nodes'] ?? [] as $id => $original) {
      if (!isset($current[$id])) { $comparison['missing_nodes'][] = $id; continue; }
      foreach (['bundle', 'title', 'alias'] as $key) {
        if (($original[$key] ?? NULL) === $current[$id][$key]) continue;
        $accepted_repair = FALSE;
        if ($key === 'alias' && (int) $id === 334 && (int) ($repair['node_id'] ?? 0) === 334 && isset($nodes[$id])) {
          $expected = $generator->createEntityAlias(clone $nodes[$id], 'return');
          $redirect = \Drupal::service('redirect.repository')->findMatchingRedirect(ltrim((string) ($repair['before'] ?? ''), '/'), [], $nodes[$id]->language()->getId());
          $accepted_repair = ($original['bundle'] ?? NULL) === 'job'
            && $current[$id]['bundle'] === 'job'
            && ($original['title'] ?? NULL) === $current[$id]['title']
            && ($repair['title'] ?? NULL) === $current[$id]['title']
            && ($repair['before'] ?? NULL) === $original['alias']
            && ($repair['after'] ?? NULL) === $current[$id]['alias']
            && is_string($expected) && $repair['after'] === $expected
            && (int) ($repair['old_url_status'] ?? 0) === 301
            && ($repair['node_revision_unchanged'] ?? FALSE) === TRUE
            && $redirect && (int) $redirect->getStatusCode() === 301
            && $redirect->getRedirectUrl()->getInternalPath() === 'node/334';
          if ($accepted_repair) {
            $comparison['repaired_aliases'][$id] = ['title' => $current[$id]['title'], 'before' => $original['alias'], 'after' => $current[$id]['alias'], 'old_url_status' => 301, 'evidence' => $repair_path];
          }
        }
        if (!$accepted_repair) $comparison['changed_nodes'][$id][$key] = ['before' => $original[$key] ?? NULL, 'after' => $current[$id][$key]];
      }
    }
    $comparison['added_nodes'] = array_values(array_diff(array_keys($current), array_keys($baseline['nodes'] ?? [])));
    $comparison['front_before'] = $baseline['front'] ?? NULL;
    $comparison['front_after'] = $report['front'];
    $comparison['passed'] = $comparison['changed_nodes'] === [] && $comparison['added_nodes'] === [] && $comparison['missing_nodes'] === [] && $comparison['front_before'] === $comparison['front_after'];
    $report['repaired_aliases'] = $comparison['repaired_aliases'];
    if ($setup) $assert($comparison['passed'], 'Enabling automatic aliases must preserve all existing node titles, records, frontpage and aliases except the exact documented Job 334 repair.');
  }
  else {
    if ($setup) $assert(FALSE, 'Setup verification requires pre-update output/aliases/before.json to prove existing aliases were preserved.');
  }
}
catch (Throwable $error) {
  $report['failures'][] = get_class($error) . ': ' . $error->getMessage();
}
finally {
  if ($switched) \Drupal::service('account_switcher')->switchBack();
  $after = $snapshot();
  $report['read_only_before'] = $before;
  $report['read_only_after'] = $after;
  $assert($before === $after, 'Verification must leave aliases, redirects, nodes/revisions, Pathauto states and configuration unchanged.');
}

$report['passed'] = $report['failures'] === [];
if (!is_dir($directory)) mkdir($directory, 0775, TRUE);
file_put_contents($directory . '/alias-comparison.json', json_encode($comparison, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
$report['alias_comparison'] = $directory . '/alias-comparison.json';
file_put_contents($directory . '/verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
print json_encode(['passed' => $report['passed'], 'setup_mode' => $setup, 'checks' => $report['checks'], 'patterns' => count($report['patterns']), 'current_nodes' => $report['current_nodes'] ?? 0, 'widgets' => count($report['widgets']), 'repaired_aliases' => $report['repaired_aliases'], 'failures' => $report['failures'], 'report' => $directory . '/verification.json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if (!$report['passed']) throw new RuntimeException('Read-only alias verification failed; inspect output/aliases/verification.json.');
