<?php

/**
 * @file
 * Standard Pathauto patterns and preservation of imported URL choices.
 */

use Drupal\node\NodeInterface;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\pathauto\PathautoState;

/** Title patterns shared by initial setup and later-created native bundles. */
function dyne_migrate_title_alias_patterns(): array {
  return [
    'dyne_page' => '/[node:title]',
    'job' => '/jobs/[node:title]',
    'leader' => '/team/[node:title]',
    'news' => '/news/[node:title]',
    'perspective' => '/perspectives/[node:title]',
    'pipeline_program' => '/pipeline/[node:title]',
    'privacy_policy' => '/policies/[node:title]',
    'quote' => '/quotes/[node:title]',
  ];
}

/** Add the pattern when a supported bundle exists, retaining admin changes. */
function dyne_migrate_title_alias_pattern(string $bundle): void {
  $pattern = dyne_migrate_title_alias_patterns()[$bundle] ?? NULL;
  if (!$pattern || !\Drupal::moduleHandler()->moduleExists('pathauto') || !\Drupal\node\Entity\NodeType::load($bundle) || PathautoPattern::load('dyne_' . $bundle)) {
    return;
  }
  $entity = PathautoPattern::create([
    'id' => 'dyne_' . $bundle,
    'label' => ucwords(str_replace('_', ' ', $bundle)) . ' URLs',
    'type' => 'canonical_entities:node',
    'pattern' => $pattern,
    'weight' => 0,
  ]);
  $entity->addSelectionCondition([
    'id' => 'entity_bundle:node',
    'bundles' => [$bundle => $bundle],
    'negate' => FALSE,
    'context_mapping' => ['node' => 'node'],
  ]);
  $entity->save();
}

/** Configure title-based URLs without regenerating any existing aliases. */
function dyne_migrate_title_alias_schema(): void {
  \Drupal::service('module_installer')->install(['pathauto', 'redirect']);
  foreach (array_keys(dyne_migrate_title_alias_patterns()) as $bundle) {
    dyne_migrate_title_alias_pattern($bundle);
  }
  \Drupal::configFactory()->getEditable('pathauto.settings')->set('update_action', 2)->save();
  \Drupal::configFactory()->getEditable('redirect.settings')
    ->set('auto_redirect', TRUE)->set('default_status_code', 301)->save();
}

/** One-time enrollment: prior forms had no pattern or automatic URL control. */
function dyne_migrate_title_alias_enroll(): void {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  foreach (array_chunk($storage->getQuery()->accessCheck(FALSE)->execute(), 50, TRUE) as $ids) {
    foreach ($storage->loadMultiple($ids) as $node) {
      if (\Drupal::service('pathauto.generator')->getPatternByEntity($node)) {
        $state = $node->get('path')->first()->get('pathauto');
        $state->setValue(PathautoState::CREATE);
        // Save only Pathauto's policy flag, never content, aliases or revisions.
        $state->persist();
      }
    }
    $storage->resetCache(array_values($ids));
  }
}

/** Cache the original checkbox state before core persists its new value. */
function dyne_migrate_title_alias_prepare(NodeInterface $node): void {
  if (!$node->isNew() && ($original = $node->getOriginal()) && $original->hasField('path')) {
    $original->get('path')->first()->get('pathauto')->getValue();
  }
}

/** Source imports own their initial URL; prevent a second automatic alias. */
function dyne_migrate_preserve_import_alias(NodeInterface $node): int {
  $state = (int) $node->get('path')->first()->get('pathauto')->getValue();
  $node->get('path')->first()->set('pathauto', PathautoState::SKIP);
  return $state;
}

/** Restore automatic/manual authoring policy without saving content again. */
function dyne_migrate_restore_import_alias(NodeInterface $node, int $state): void {
  $property = $node->get('path')->first()->get('pathauto');
  $property->setValue($state);
  $property->persist();
}
