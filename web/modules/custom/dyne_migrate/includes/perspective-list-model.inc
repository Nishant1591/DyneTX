<?php

/**
 * @file
 * Editable controls for the source Perspectives featured/list presentation.
 */

/** Add display controls without replacing existing stories or paragraph values. */
function dyne_perspective_list_schema(): void {
  $bundle = 'dyne_perspective_list';
  if (!\Drupal\paragraphs\Entity\ParagraphsType::load($bundle)) return;
  $definitions = [
    'field_perspective_limit' => ['Maximum perspectives', 'Maximum published perspectives shown, including the featured story. Older public article records remain available.', 10, 1],
    'field_perspective_visible' => ['Cards before Additional content', 'Number of cards shown before the expandable section, excluding the featured story.', 6, 0],
  ];
  $form = \Drupal\Core\Entity\Entity\EntityFormDisplay::load('paragraph.' . $bundle . '.default');
  $view = \Drupal\Core\Entity\Entity\EntityViewDisplay::load('paragraph.' . $bundle . '.default');
  foreach ($definitions as $name => [$label, $description, $default, $minimum]) {
    $storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('paragraph', $name);
    if (!$storage) {
      $storage = \Drupal\field\Entity\FieldStorageConfig::create(['field_name' => $name, 'entity_type' => 'paragraph', 'type' => 'integer', 'cardinality' => 1]);
      $storage->save();
    }
    if ($storage->getType() !== 'integer') throw new \RuntimeException($name . ' must remain an integer field.');
    if (!\Drupal\field\Entity\FieldConfig::loadByName('paragraph', $bundle, $name)) {
      \Drupal\field\Entity\FieldConfig::create(['field_storage' => $storage, 'bundle' => $bundle, 'label' => $label, 'description' => $description, 'default_value' => [['value' => $default]], 'settings' => ['min' => $minimum, 'max' => 100]])->save();
    }
    if ($form && !$form->getComponent($name)) $form->setComponent($name, ['type' => 'number', 'weight' => $default === 10 ? -10 : -9])->save();
    if ($view && $view->getComponent($name)) $view->removeComponent($name)->save();
  }
  $name = 'field_story_category';
  $storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', $name);
  if (!$storage) {
    $storage = \Drupal\field\Entity\FieldStorageConfig::create(['field_name' => $name, 'entity_type' => 'node', 'type' => 'string', 'cardinality' => 1, 'settings' => ['max_length' => 128]]);
    $storage->save();
  }
  if ($storage->getType() !== 'string') throw new \RuntimeException('Perspective category must remain a text label.');
  if (!\Drupal\field\Entity\FieldConfig::loadByName('node', 'perspective', $name)) {
    \Drupal\field\Entity\FieldConfig::create(['field_storage' => $storage, 'bundle' => 'perspective', 'label' => 'Listing category', 'description' => 'Optional label beside the publication date, such as Blog, Podcast or panel.'])->save();
  }
  $form = \Drupal\Core\Entity\Entity\EntityFormDisplay::load('node.perspective.default');
  if ($form && !$form->getComponent($name)) $form->setComponent($name, ['type' => 'string_textfield', 'weight' => -5])->save();
  $view = \Drupal\Core\Entity\Entity\EntityViewDisplay::load('node.perspective.default');
  if ($view && $view->getComponent($name)) $view->removeComponent($name)->save();
  // The same native link carries its optional card-action label; no second
  // hard-coded list of video/podcast titles is needed in presentation code.
  $link = \Drupal\field\Entity\FieldConfig::loadByName('node', 'perspective', 'field_external_link');
  if ($link && (int) $link->getSetting('title') === 0) $link->setSetting('title', 1)->save();
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}
