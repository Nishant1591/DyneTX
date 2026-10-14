<?php

/**
 * Repair and verify default edit displays for Dyne Paragraph types.
 *
 * Run with:
 *   ddev drush php:script web/modules/custom/dyne_migrate/scripts/repair-paragraph-displays.php
 */

include_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/dyne_migrate.install';
_dyne_migrate_paragraph_displays();

$type_storage = \Drupal::entityTypeManager()->getStorage('paragraphs_type');
$field_storage = \Drupal::entityTypeManager()->getStorage('field_config');
$widget_by_type = [
  'string' => 'string_textfield',
  'string_long' => 'string_textarea',
  'text' => 'text_textfield',
  'text_long' => 'text_textarea',
  'link' => 'link_default',
  'image' => 'image_image',
  'entity_reference' => 'entity_reference_autocomplete',
  'entity_reference_revisions' => 'entity_reference_paragraphs',
];
$errors = [];
foreach ($type_storage->loadMultiple() as $bundle => $paragraph_type) {
  $display = \Drupal\Core\Entity\Entity\EntityFormDisplay::load("paragraph.$bundle.default");
  if (!$display) {
    $errors[] = "$bundle: default form display is missing";
    continue;
  }
  $hidden = $display->get('hidden') ?: [];
  $hidden_names = array_is_list($hidden) ? $hidden : array_keys($hidden);
  printf(
    "%s: editable_fields=%d hidden_fields=%s\n",
    $bundle,
    count($display->getComponents()),
    $hidden_names ? implode(',', $hidden_names) : 'none'
  );
  foreach ($field_storage->loadByProperties([
    'entity_type' => 'paragraph',
    'bundle' => $bundle,
  ]) as $field) {
    $field_name = $field->getName();
    if (isset($widget_by_type[$field->getType()]) && (in_array($field_name, $hidden_names, TRUE) || !$display->getComponent($field_name))) {
      $errors[] = "$bundle.$field_name: editable widget is missing";
    }
  }
}

if ($errors) {
  throw new \RuntimeException(implode("\n", $errors));
}
print "All Paragraph bundles have visible default edit widgets.\n";