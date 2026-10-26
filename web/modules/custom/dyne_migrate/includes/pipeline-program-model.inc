<?php

/**
 * @file
 * Native, additive editorial fields for pipeline assets and disease details.
 *
 * This file defines configuration only. Migration and presentation consumers
 * decide which approved program records belong to a particular pipeline page.
 */

/** Stable field definitions shared with migration and rendering consumers. */
function dyne_pipeline_program_model_fields(): array {
  return [
    'field_program_code' => ['type' => 'string', 'label' => 'Program code', 'description' => 'Stable program identifier, for example DYNE-251.', 'required' => TRUE],
    'field_program_short_name' => ['type' => 'string', 'label' => 'Short program name', 'description' => 'Optional short name displayed with the full program name.'],
    'field_program_aliases' => ['type' => 'string', 'label' => 'Other program names', 'description' => 'Additional names or aliases for this same program.', 'cardinality' => -1],
    'field_disease_text' => ['type' => 'string', 'label' => 'Disease', 'description' => 'Full disease name shown in the pipeline.'],
    'field_disease_short' => ['type' => 'string', 'label' => 'Disease abbreviation', 'description' => 'Short disease label used with the program, for example DMD or DM1.'],
    'field_prevalence_us' => ['type' => 'string', 'label' => 'U.S. prevalence', 'description' => 'Preserve the published estimate, range, and approximation symbols.'],
    'field_prevalence_eu' => ['type' => 'string', 'label' => 'European prevalence', 'description' => 'Preserve the published estimate, range, and approximation symbols.'],
    'field_target' => ['type' => 'string', 'label' => 'Target', 'description' => 'Molecular target or exon shown for this program.'],
    'field_phase' => ['type' => 'string', 'label' => 'Chart phase label', 'description' => 'Text displayed on the program bar in the pipeline chart.'],
    'field_status' => ['type' => 'string', 'label' => 'Milestone or status', 'description' => 'Optional milestone text displayed beside the program bar.'],
    'field_program_progress' => ['type' => 'integer', 'label' => 'Chart bar width (%)', 'description' => 'Presentation width of the program bar, from 0 to 100. Leave empty when no width is specified.', 'settings' => ['min' => 0, 'max' => 100]],
    'field_program_anchor' => ['type' => 'string', 'label' => 'Detail section anchor', 'description' => 'Optional existing detail anchor, such as DMD or DM1, without a leading #.'],
    'field_program_heading' => ['type' => 'text_long', 'label' => 'Program heading', 'description' => 'Full heading displayed above the program description, including any inline emphasis.'],
    'field_body_text' => ['type' => 'text_long', 'label' => 'Program description', 'description' => 'Introduction describing this program and its therapeutic approach.'],
    'field_disease_heading' => ['type' => 'text_long', 'label' => 'Disease overview heading', 'description' => 'Heading displayed above the disease overview.'],
    'field_disease_overview' => ['type' => 'text_long', 'label' => 'Disease overview', 'description' => 'Overview of the disease associated with this program.'],
    'field_disease_onset' => ['type' => 'text_long', 'label' => 'Disease onset', 'description' => 'When the disease or its symptoms typically begin.'],
    'field_disease_cause' => ['type' => 'text_long', 'label' => 'Disease cause', 'description' => 'Genetic cause or disease mechanism.'],
    'field_disease_symptoms' => ['type' => 'text_long', 'label' => 'Disease symptoms', 'description' => 'Symptoms and functional effects, including formatted lists where needed.'],
    'field_unmet_need' => ['type' => 'text_long', 'label' => 'Unmet therapeutic need', 'description' => 'Current treatment limitations or unmet need described for the disease.'],
    'field_trial_links' => ['type' => 'link', 'label' => 'Clinical trial links', 'description' => 'Optional trial destinations and the link text shown to readers.', 'cardinality' => -1, 'settings' => ['link_type' => 17, 'title' => 1]],
    'field_program_image' => ['type' => 'entity_reference', 'label' => 'Program image', 'description' => 'Optional managed image used with this program.', 'media_bundles' => ['image']],
    'field_program_video' => ['type' => 'link', 'label' => 'Program video URL', 'description' => 'Optional video destination and display title.', 'settings' => ['link_type' => 17, 'title' => 1]],
    'field_program_video_cover' => ['type' => 'entity_reference', 'label' => 'Video cover image', 'description' => 'Optional managed cover image displayed for the program video.', 'media_bundles' => ['image']],
    'field_program_video_alt' => ['type' => 'string', 'label' => 'Video cover alternative text', 'description' => 'Alternative text for this video cover placement. Leave empty to use the Media image alternative text.'],
  ];
}

/** Persist useful editorial order as well as the visual form groups. */
function dyne_pipeline_program_model_groups(): array {
  return [
    'identity' => ['Program identity', ['title', 'field_program_code', 'field_program_short_name', 'field_program_aliases', 'field_disease_text', 'field_disease_short']],
    'chart' => ['Pipeline chart', ['field_prevalence_us', 'field_prevalence_eu', 'field_target', 'field_phase', 'field_status', 'field_program_progress', 'field_program_anchor']],
    'program' => ['Program description and trials', ['field_program_heading', 'field_body_text', 'field_trial_links']],
    'disease' => ['Disease details', ['field_disease_heading', 'field_disease_overview', 'field_disease_onset', 'field_disease_cause', 'field_disease_symptoms', 'field_unmet_need']],
    'media' => ['Images and video', ['field_program_image', 'field_program_video', 'field_program_video_cover', 'field_program_video_alt']],
  ];
}

/** Create native controls without changing storage types or existing values. */
function dyne_pipeline_program_model_schema(): void {
  require_once __DIR__ . '/native-content.inc';
  // Fresh installs need the managed image type as well as the Media modules.
  // Existing types, their source settings and shared image metadata are kept.
  if (!\Drupal\media\Entity\MediaType::load('image')) {
    $image_type = \Drupal\media\Entity\MediaType::create(['id' => 'image', 'label' => 'Image', 'source' => 'image', 'source_configuration' => ['source_field' => 'field_media_image'], 'field_map' => []]);
    $image_type->save();
    dyne_native_field('media', 'image', 'field_media_image', 'image', 'Image', [], ['file_extensions' => 'png gif jpg jpeg webp avif', 'file_directory' => 'media/image', 'alt_field' => TRUE, 'alt_field_required' => FALSE, 'title_field' => FALSE]);
    dyne_native_media_displays($image_type);
  }
  $bundle = 'pipeline_program';
  $type = \Drupal\node\Entity\NodeType::load($bundle);
  if (!$type) throw new \RuntimeException('The Pipeline program content type must exist before its editorial model is configured.');
  $type->set('description', 'Reusable neuromuscular pipeline program with chart facts, disease details and clinical trial links.')->save();
  foreach (dyne_pipeline_program_model_fields() as $name => $definition) {
    $type = $definition['type'];
    $storage = $type === 'entity_reference' ? ['target_type' => 'media'] : ($type === 'string' ? ['max_length' => 255] : []);
    $settings = $definition['settings'] ?? [];
    if ($type === 'text_long') $settings['allowed_formats'] = ['dyne_legacy_html'];
    if ($type === 'entity_reference') {
      $bundles = $definition['media_bundles'];
      $settings += ['handler' => 'default:media', 'handler_settings' => ['target_bundles' => array_combine($bundles, $bundles)]];
    }
    dyne_native_field('node', $bundle, $name, $type, $definition['label'], $storage, $settings, $definition['cardinality'] ?? 1);
    $field = \Drupal\field\Entity\FieldConfig::loadByName('node', $bundle, $name);
    $field->setLabel($definition['label'])->setDescription($definition['description'])->setRequired($definition['required'] ?? FALSE);
    foreach ($settings as $key => $value) $field->setSetting($key, $value);
    $field->save();
  }
  $form = \Drupal\Core\Entity\Entity\EntityFormDisplay::load('node.' . $bundle . '.default');
  $weight = -30;
  foreach (dyne_pipeline_program_model_groups() as [, $fields]) {
    foreach ($fields as $name) {
      $component = $form->getComponent($name);
      if (!$component) continue;
      $component['weight'] = $weight++;
      if (in_array($name, ['field_program_heading', 'field_disease_heading', 'field_body_text', 'field_disease_overview', 'field_disease_onset', 'field_disease_cause', 'field_disease_symptoms', 'field_unmet_need'], TRUE)) $component['settings'] = ['rows' => in_array($name, ['field_program_heading', 'field_disease_heading'], TRUE) ? 2 : 5, 'placeholder' => ''];
      $form->setComponent($name, $component);
    }
  }
  // Old combined/source controls remain stored for historical revisions.
  foreach (['field_prevalence', 'field_legacy_title', 'field_components', 'field_image', 'field_link'] as $name) $form->removeComponent($name);
  $form->save();
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}

/** Group the native widgets on Pipeline program add and edit forms. */
function dyne_pipeline_program_form_alter(array &$form, $form_state): void {
  $node = $form_state->getFormObject()->getEntity();
  if ($node->getEntityTypeId() !== 'node' || $node->bundle() !== 'pipeline_program') return;
  $weight = -30;
  foreach (dyne_pipeline_program_model_groups() as $id => [$label, $fields]) {
    if (!array_filter($fields, static fn($name) => isset($form[$name]))) continue;
    $key = 'dyne_pipeline_' . $id;
    $form[$key] = ['#type' => 'details', '#title' => t($label), '#open' => $id !== 'media', '#weight' => $weight++];
    foreach ($fields as $delta => $name) if (isset($form[$name])) {
      $form[$name]['#group'] = $key;
      $form[$name]['#weight'] = $delta;
    }
  }
  if (isset($form['title']['widget'][0]['value'])) $form['title']['widget'][0]['value']['#title'] = t('Program name');
}
