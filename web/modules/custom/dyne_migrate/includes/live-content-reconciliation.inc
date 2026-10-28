<?php

/**
 * @file
 * Native authoring controls used by the reviewed live-content reconciliation.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Separate the current vacancy listing from preserved public job detail pages.
 */
function dyne_live_content_vacancy_schema(): void {
  $name = 'field_current_vacancy';
  $storage = FieldStorageConfig::loadByName('node', $name);
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => 'boolean',
      'cardinality' => 1,
    ]);
    $storage->save();
  }
  if ($storage->getType() !== 'boolean') {
    throw new RuntimeException('The current-vacancy field must remain boolean.');
  }
  if (!FieldConfig::loadByName('node', 'job', $name)) {
    FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'job',
      'label' => 'Include in current vacancies',
      'description' => 'Show this published job in the Careers vacancy list. Uncheck when a position is no longer listed; its public detail page and historical revisions remain available.',
      'default_value' => [['value' => 1]],
      'settings' => ['on_label' => 'Current vacancy', 'off_label' => 'Historical job detail'],
    ])->save();
  }
  $form = EntityFormDisplay::load('node.job.default');
  if ($form && !$form->getComponent($name)) {
    $form->setComponent($name, ['type' => 'boolean_checkbox', 'weight' => -7, 'settings' => ['display_label' => TRUE]])->save();
  }
  $view = EntityViewDisplay::load('node.job.default');
  if ($view && $view->getComponent($name)) {
    $view->removeComponent($name)->save();
  }
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}
