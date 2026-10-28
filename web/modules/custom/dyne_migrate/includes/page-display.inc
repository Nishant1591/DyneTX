<?php

/**
 * @file
 * Editorial visibility control for preserved page sections.
 */

/** Keep recovered sections available without forcing them onto a public page. */
function dyne_page_display_schema(): void {
  $name = 'field_display_page_content';
  $storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', $name);
  if (!$storage) {
    $storage = \Drupal\field\Entity\FieldStorageConfig::create([
      'field_name' => $name, 'entity_type' => 'node', 'type' => 'boolean', 'cardinality' => 1,
    ]);
    $storage->save();
  }
  if ($storage->getType() !== 'boolean') {
    throw new \RuntimeException('The page display control must remain boolean.');
  }
  if (!\Drupal\field\Entity\FieldConfig::loadByName('node', 'dyne_page', $name)) {
    \Drupal\field\Entity\FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'dyne_page',
      'label' => 'Display page sections',
      'description' => 'Keep this enabled to display the page sections. Turn it off to retain the page URL and hero while preserving its sections for later editorial use.',
      'default_value' => [['value' => 1]],
      'settings' => ['on_label' => 'Display sections', 'off_label' => 'Preserve sections without displaying them'],
    ])->save();
  }
  $form = \Drupal\Core\Entity\Entity\EntityFormDisplay::load('node.dyne_page.default');
  if ($form && !$form->getComponent($name)) {
    $form->setComponent($name, ['type' => 'boolean_checkbox', 'weight' => -8, 'settings' => ['display_label' => TRUE]])->save();
  }
  $view = \Drupal\Core\Entity\Entity\EntityViewDisplay::load('node.dyne_page.default');
  if ($view && $view->getComponent($name)) {
    $view->removeComponent($name)->save();
  }
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}
