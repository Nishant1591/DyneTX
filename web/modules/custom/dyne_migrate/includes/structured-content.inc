<?php

/**
 * @file
 * Semantic editorial fields and a revision-preserving migration of current copy.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\paragraphs\Entity\ParagraphsType;

require_once __DIR__ . '/native-content.inc';
require_once __DIR__ . '/native-components.inc';
require_once __DIR__ . '/structured-job-parser.inc';
require_once __DIR__ . '/structured-articles.inc';

/** Fields that describe an article, rather than its archived HTML layout. */
function dyne_structured_article_fields(): array {
  return [
    'news' => [
      'field_article_body' => ['text_long', 'News article'],
      'field_company_overview' => ['text_long', 'About the company'],
      'field_forward_looking' => ['text_long', 'Forward-looking statements'],
      'field_news_contacts' => ['text_long', 'Press and investor contacts'],
    ],
    'perspective' => [
      'field_story_heading' => ['text_long', 'Article heading'],
      'field_story_date_label' => ['string', 'Displayed publication date'],
      'field_story_intro' => ['text_long', 'Article introduction'],
      'field_story_byline' => ['text_long', 'Article byline'],
      'field_story_mission' => ['text_long', 'Banner introduction'],
      'field_story_portrait' => ['entity_reference', 'Author portrait'],
      'field_article_body' => ['text_long', 'Article text'],
      'field_article_sections' => ['entity_reference_revisions', 'Article sections'],
    ],
    'privacy_policy' => [
      'field_article_intro' => ['text_long', 'Policy introduction'],
      'field_article_sections' => ['entity_reference_revisions', 'Policy sections'],
    ],
  ];
}

/** Include child revisions so imported HTML cannot overwrite edited sections. */
function dyne_structured_editorial_hash($entity, int $depth = 0): string {
  if ($depth > 32) {
    throw new RuntimeException('Content sections exceed the supported nesting depth.');
  }
  $fields = $entity->getEntityTypeId() === 'paragraph'
    ? ['field_section_heading', 'field_section_body', 'field_section_image', 'field_section_image_alt', 'field_section_mobile_image', 'field_section_retina_image', 'field_section_bg_image', 'field_section_link', 'field_article_sections']
    : array_keys($entity->bundle() === 'job' ? dyne_structured_job_fields() : (dyne_structured_article_fields()[$entity->bundle()] ?? []));
  if ($entity->getEntityTypeId() === 'node') {
    $fields = array_merge($fields, ['title', 'field_department', 'field_location', 'field_application_link', 'field_news_date', 'field_news_type', 'field_external_url', 'field_news_file', 'field_story_date', 'field_author', 'field_subtitle', 'field_story_image', 'field_external_link']);
  }
  $values = [];
  foreach ($fields as $name) {
    if (!$entity->hasField($name)) {
      continue;
    }
    $list = $entity->get($name);
    $values[$name] = [];
    foreach ($list as $item) {
      $value = [];
      foreach ($item->getProperties(FALSE) as $property => $data) {
        $raw = $data->getValue();
        if ($raw === NULL || $raw === []) {
          continue;
        }
        if (in_array($property, ['target_id', 'target_revision_id'], TRUE)) {
          $raw = (int) $raw;
        }
        elseif ($property === 'value' && $list->getFieldDefinition()->getType() === 'decimal') {
          $raw = number_format((float) $raw, (int) $list->getFieldDefinition()->getFieldStorageDefinition()->getSetting('scale'), '.', '');
        }
        $value[$property] = $raw;
      }
      ksort($value);
      $values[$name][] = $value;
    }
    if ($name === 'field_article_sections') {
      foreach ($entity->get($name) as $item) {
        $values['children'][] = $item->entity ? dyne_structured_editorial_hash($item->entity, $depth + 1) : NULL;
      }
    }
  }
  return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
}

/** Native job fields: narrative sections stay formatted; URLs and pay are typed. */
function dyne_structured_job_fields(): array {
  return [
    'field_company_overview' => ['text_long', 'Company overview'],
    'field_company_website' => ['link', 'Company website URL'],
    'field_company_twitter' => ['link', 'X / Twitter URL'],
    'field_company_linkedin' => ['link', 'LinkedIn URL'],
    'field_company_facebook' => ['link', 'Facebook URL'],
    'field_workplace_arrangement' => ['string', 'Workplace arrangement'],
    'field_role_summary' => ['text_long', 'Role summary'],
    'field_responsibilities' => ['text_long', 'Primary responsibilities'],
    'field_education_skills' => ['text_long', 'Education, experience and skills requirements'],
    'field_pay_range_label' => ['string', 'Pay range label'],
    'field_pay_range_min' => ['decimal', 'Minimum base salary'],
    'field_pay_range_max' => ['decimal', 'Maximum base salary'],
    'field_pay_range_currency' => ['string', 'Salary currency / symbol'],
    'field_compensation_notes' => ['text_long', 'Compensation and benefits information'],
    'field_job_disclaimer' => ['text_long', 'Job description disclaimer'],
    'field_equal_opportunity' => ['text_long', 'Equal opportunity statement'],
    'field_additional_job_content' => ['text_long', 'Additional job information'],
  ];
}

/** Add fields without changing any existing field storage or replacing copy. */
function dyne_structured_schema(): void {
  $article_fields = dyne_structured_article_fields();
  $definitions = ['job' => dyne_structured_job_fields()] + $article_fields;
  foreach ($definitions as $bundle => $fields) {
    foreach ($fields as $name => [$type, $label]) {
      if ($type === 'entity_reference_revisions') {
        continue;
      }
      $storage = match ($type) {
        'decimal' => ['precision' => 12, 'scale' => 2],
        'entity_reference' => ['target_type' => 'media'],
        default => [],
      };
      $settings = match ($type) {
        'link' => ['link_type' => 17, 'title' => 0],
        'entity_reference' => ['handler' => 'default:media', 'handler_settings' => ['target_bundles' => ['image' => 'image']]],
        default => [],
      };
      dyne_native_field('node', $bundle, $name, $type, $label, $storage, $settings);
    }
  }
  if (!ParagraphsType::load('dyne_article_section')) {
    ParagraphsType::create(['id' => 'dyne_article_section', 'label' => 'Content section', 'description' => 'A heading, formatted text, image or link within an article or page section.'])->save();
  }
  foreach (['field_section_heading' => ['text_long', 'Section heading'], 'field_section_body' => ['text_long', 'Section text'], 'field_section_template' => ['string_long', 'Preserved section layout']] as $name => [$type, $label]) {
    dyne_component_field('paragraph', 'dyne_article_section', $name, $type, $label);
  }
  dyne_native_media_field('paragraph', 'dyne_article_section', 'field_section_image', 'Section image');
  dyne_component_field('paragraph', 'dyne_article_section', 'field_section_image_alt', 'string_long', 'Alternative text for this placement');
  dyne_native_media_field('paragraph', 'dyne_article_section', 'field_section_mobile_image', 'Mobile image');
  dyne_native_media_field('paragraph', 'dyne_article_section', 'field_section_retina_image', 'High resolution image');
  dyne_native_media_field('paragraph', 'dyne_article_section', 'field_section_bg_image', 'Background image');
  dyne_component_field('paragraph', 'dyne_article_section', 'field_section_link', 'link', 'Section link', ['link_type' => 17, 'title' => 1]);
  $targets = [
    'node' => array_keys($definitions),
    'paragraph' => ['dyne_rich_text', 'dyne_careers_quotes', 'dyne_article_section'],
  ];
  foreach ($targets as $entity => $bundles) {
    foreach ($bundles as $bundle) {
      dyne_component_field($entity, $bundle, 'field_structured_content', 'boolean', 'Uses structured content');
      dyne_component_field($entity, $bundle, 'field_content_template', 'string_long', 'Preserved content layout');
      if ($entity === 'paragraph' || in_array($bundle, ['perspective', 'privacy_policy'], TRUE)) {
        dyne_component_field($entity, $bundle, 'field_article_sections', 'entity_reference_revisions', 'Content sections (drag to order)', ['handler' => 'default:paragraph', 'handler_settings' => ['target_bundles' => ['dyne_article_section' => 'dyne_article_section']]], ['target_type' => 'paragraph'], -1);
      }
      $form = EntityFormDisplay::load("$entity.$bundle.default");
      foreach (['field_structured_content', 'field_content_template', 'field_section_template'] as $hidden) {
        $form->removeComponent($hidden);
      }
      // Keep the original body on the entity and historical revisions. The
      // current form exposes only the fields the public renderer actually uses.
      if ($entity === 'node' || in_array($bundle, ['dyne_rich_text', 'dyne_careers_quotes'], TRUE)) {
        $form->removeComponent('field_body_text');
      }
      if ($entity === 'node' && $bundle !== 'dyne_page') {
        $form->removeComponent('field_components');
      }
      if ($entity === 'node' && $bundle === 'news') {
        $form->removeComponent('field_image');
      }
      foreach ($form->getComponents() as $name => $component) {
        $field = FieldConfig::loadByName($entity, $bundle, $name);
        if ($field && $field->getType() === 'decimal') {
          $form->setComponent($name, ['type' => 'number', 'weight' => $component['weight'] ?? 20]);
        }
      }
      if ($component = $form->getComponent('field_article_sections')) {
        $component['settings'] = array_replace($component['settings'] ?? [], [
          'title' => 'Section', 'title_plural' => 'Sections',
          'default_paragraph_type' => '_none', 'edit_mode' => 'closed',
          'closed_mode' => 'summary', 'autocollapse' => 'all',
        ]);
        $form->setComponent('field_article_sections', $component);
      }
      $form->save();
    }
  }
  // Profiles contain one biography, rather than layout components or duplicate
  // image/source fields. Retain their established native controls.
  $leader = EntityFormDisplay::load('node.leader.default');
  $leader->removeComponent('field_components')->removeComponent('field_link')->save();
  EntityFormDisplay::load('node.dyne_page.default')->removeComponent('field_body_text')->save();
  $pipeline = EntityFormDisplay::load('node.pipeline_program.default');
  $pipeline_view = \Drupal\Core\Entity\Entity\EntityViewDisplay::load('node.pipeline_program.default');
  foreach (['field_disease_text', 'field_prevalence', 'field_target', 'field_phase', 'field_status'] as $weight => $name) {
    if ($field = FieldConfig::loadByName('node', 'pipeline_program', $name)) {
      $pipeline->setComponent($name, ['type' => $field->getType() === 'string_long' ? 'string_textarea' : 'string_textfield', 'weight' => $weight]);
      $pipeline_view->setComponent($name, ['type' => 'string', 'label' => 'above', 'weight' => $weight]);
    }
  }
  $pipeline->removeComponent('field_components')->save();
  $pipeline_view->removeComponent('field_components')->save();
  // EntityFormDisplay::processForm applies these weights after form alters.
  // Persist the editorial order so grouped fields do not revert to alphabetic.
  $orders = [
    ['field_company_overview', 'field_company_website', 'field_company_twitter', 'field_company_linkedin', 'field_company_facebook'],
    ['field_role_summary', 'field_responsibilities', 'field_education_skills', 'field_additional_job_content'],
    ['field_pay_range_label', 'field_pay_range_currency', 'field_pay_range_min', 'field_pay_range_max', 'field_compensation_notes', 'field_job_disclaimer', 'field_equal_opportunity'],
    ['field_story_heading', 'field_story_date_label', 'field_story_intro', 'field_story_byline', 'field_story_portrait', 'field_story_mission', 'field_article_intro', 'field_article_body', 'field_article_sections'],
    ['field_forward_looking', 'field_news_contacts'],
  ];
  foreach (array_keys($definitions) as $bundle) {
    $form = EntityFormDisplay::load("node.$bundle.default");
    foreach ($orders as $order) {
      foreach ($order as $weight => $name) {
        if ($component = $form->getComponent($name)) {
          $component['weight'] = $weight;
          $form->setComponent($name, $component);
        }
      }
    }
    if ($bundle === 'job') {
      foreach (['title', 'field_department', 'field_location', 'field_workplace_arrangement', 'field_application_link'] as $weight => $name) {
        if ($component = $form->getComponent($name)) {
          $component['weight'] = $weight - 10;
          $form->setComponent($name, $component);
        }
      }
      $form->removeComponent('field_image')->removeComponent('field_link');
    }
    $form->save();
  }
  foreach (['leader' => ['field_body_text' => 'Biography', 'field_role' => 'Position'], 'perspective' => ['field_article_body' => 'Article text'], 'news' => ['field_article_body' => 'News article']] as $bundle => $labels) {
    foreach ($labels as $name => $label) {
      if ($field = FieldConfig::loadByName('node', $bundle, $name)) {
        $field->setLabel($label)->save();
      }
    }
  }
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}

/** Split one job; skip completed records so reruns preserve editorial changes. */
function dyne_structured_job_backfill($node, bool $force = FALSE): bool {
  if ($node->bundle() !== 'job' || !$node->hasField('field_structured_content') || (!$force && $node->get('field_structured_content')->value)) {
    return FALSE;
  }
  $parsed = dyne_job_parse((string) $node->get('field_body_text')->value);
  // An explicit location is stronger evidence than a conflicting LinkedIn tag.
  $location = $node->hasField('field_location') && $node->get('field_location')->entity ? $node->get('field_location')->entity->label() : '';
  if (strcasecmp(trim($location), 'Remote') === 0) {
    $parsed['fields']['field_workplace_arrangement'] = 'Remote';
  }
  foreach (dyne_structured_job_fields() as $name => [$type]) {
    $value = $parsed['fields'][$name] ?? '';
    if ($type === 'link') {
      $value = $value === '' ? [] : ['uri' => str_starts_with($value, '/') ? 'internal:' . $value : $value];
    }
    elseif ($type === 'text_long') {
      $value = $value === '' ? [] : ['value' => $value, 'format' => $node->get('field_body_text')->format ?: 'dyne_job_html'];
    }
    elseif ($value === '') {
      $value = [];
    }
    elseif ($type === 'decimal') {
      $value = str_replace(',', '', $value);
    }
    $node->set($name, $value);
  }
  $node->set('field_content_template', $parsed['template']);
  $node->set('field_structured_content', TRUE);
  return TRUE;
}

/** Upgrade all current entities; old bodies, aliases and revisions stay intact. */
function dyne_structured_backfill(): array {
  $report = ['nodes' => [], 'paragraphs' => 0, 'unchanged' => 0, 'source_conflicts' => []];
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $ids = $storage->getQuery()->accessCheck(FALSE)->sort('nid')->execute();
  foreach ($ids as $id) {
    $node = $storage->load($id);
    if ($node->bundle() === 'job' && !$node->get('field_structured_content')->value) {
      $parsed = dyne_job_parse((string) $node->get('field_body_text')->value);
      $location = $node->get('field_location')->entity?->label() ?? '';
      if (strcasecmp(trim($location), 'Remote') === 0 && ($parsed['fields']['field_workplace_arrangement'] ?? '') === 'On-site') {
        $report['source_conflicts'][] = ['nid' => (int) $id, 'field' => 'field_workplace_arrangement', 'location' => $location, 'source_tag' => '#LI-Onsite', 'selected' => 'Remote'];
      }
      if ($parsed['warnings']) {
        $report['source_conflicts'][] = ['nid' => (int) $id, 'warnings' => $parsed['warnings']];
      }
    }
    $changed = dyne_structured_job_backfill($node) || dyne_structured_article_backfill($node);
    if ($node->bundle() === 'dyne_page' && $node->hasField('field_components')) {
      $refs = $node->get('field_components')->getValue();
      foreach ($node->get('field_components') as $delta => $item) {
        $paragraph = $item->entity;
        if ($paragraph && in_array($paragraph->bundle(), ['dyne_rich_text', 'dyne_careers_quotes'], TRUE) && dyne_structured_article_backfill($paragraph)) {
          $paragraph->setNewRevision(TRUE);
          $paragraph->save();
          $refs[$delta] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
          $report['paragraphs']++;
          $changed = TRUE;
        }
      }
      if ($changed) {
        $node->set('field_components', $refs);
      }
    }
    if ($changed) {
      $node->setNewRevision(TRUE);
      $node->setRevisionLogMessage('Separate existing content into semantic editorial fields; preserve source copy.');
      $node->setSyncing(TRUE);
      $node->save();
      $report['nodes'][$node->bundle()] = ($report['nodes'][$node->bundle()] ?? 0) + 1;
    }
    else {
      $report['unchanged']++;
    }
    if ($node->hasField('field_structured_content') && $node->get('field_structured_content')->value) {
      $key = 'dyne_migrate.structured_baseline.' . $node->id();
      if (!\Drupal::state()->get($key)) {
        \Drupal::state()->set($key, dyne_structured_editorial_hash($node));
      }
    }
    $storage->resetCache([$id]);
  }
  return $report;
}

/** Complete workplace metadata once, without reimporting any edited narratives. */
function dyne_structured_complete_workplaces(): array {
  if (\Drupal::state()->get('dyne_migrate.structured_workplaces_v1')) {
    return ['updated' => 0, 'already_complete' => TRUE];
  }
  $report = ['updated' => 0, 'source_conflicts' => []];
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'job')->execute();
  foreach ($storage->loadMultiple($ids) as $node) {
    $parsed = dyne_job_parse((string) $node->get('field_body_text')->value);
    $value = $parsed['fields']['field_workplace_arrangement'];
    $location = $node->get('field_location')->entity?->label() ?? '';
    if (strcasecmp(trim($location), 'Remote') === 0) {
      if ($value === 'On-site') {
        $report['source_conflicts'][] = ['nid' => (int) $node->id(), 'source_id' => (int) $node->get('field_wp_source_id')->value, 'location' => $location, 'source_tag' => '#LI-Onsite', 'selected' => 'Remote'];
      }
      $value = 'Remote';
    }
    if ($value !== '' && $node->get('field_workplace_arrangement')->isEmpty()) {
      $before = dyne_structured_editorial_hash($node);
      $node->set('field_workplace_arrangement', $value);
      $node->setNewRevision(TRUE);
      $node->setSyncing(TRUE);
      $node->setRevisionLogMessage('Complete workplace arrangement from the existing source metadata.');
      $node->save();
      $key = 'dyne_migrate.structured_baseline.' . $node->id();
      if (\Drupal::state()->get($key) === $before) {
        \Drupal::state()->set($key, dyne_structured_editorial_hash($node));
      }
      $report['updated']++;
    }
  }
  \Drupal::state()->set('dyne_migrate.structured_workplaces_v1', TRUE);
  return $report;
}

/** Complete image controls on sections created by an earlier migration pass. */
function dyne_structured_complete_images(): array {
  if (\Drupal::state()->get('dyne_migrate.structured_images_v2')) {
    return ['updated' => 0, 'already_complete' => TRUE];
  }
  $nodes = \Drupal::entityTypeManager()->getStorage('node');
  $before = [];
  foreach ($nodes->loadMultiple($nodes->getQuery()->accessCheck(FALSE)->exists('field_structured_content')->execute()) as $node) {
    $key = 'dyne_migrate.structured_baseline.' . $node->id();
    if ($node->get('field_structured_content')->value && \Drupal::state()->get($key) === dyne_structured_editorial_hash($node)) {
      $before[$node->id()] = $key;
    }
  }
  $storage = \Drupal::entityTypeManager()->getStorage('paragraph');
  $ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'dyne_article_section')->execute();
  $count = 0;
  foreach ($storage->loadMultiple($ids) as $section) {
    if (dyne_structured_image_placements($section)) {
      $section->setNewRevision(FALSE);
      $section->save();
      $count++;
    }
  }
  $storage->resetCache();
  $nodes->resetCache();
  foreach ($before as $nid => $key) {
    \Drupal::state()->set($key, dyne_structured_editorial_hash($nodes->load($nid)));
  }
  \Drupal::state()->set('dyne_migrate.structured_images_v2', TRUE);
  return ['updated' => $count];
}
