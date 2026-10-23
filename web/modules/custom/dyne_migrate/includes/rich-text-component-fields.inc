<?php

/**
 * @file
 * Additive formatted controls for the remaining plain component narratives.
 */

/** The original plain fields remain available on historical revisions. */
function dyne_rich_text_component_fields(): array {
  return [
    'dyne_card_item' => ['field_item_text', 'field_item_body'],
    'dyne_text_section' => ['field_item_text', 'field_item_body'],
    'dyne_alert_banner' => ['field_banner_text', 'field_banner_body'],
  ];
}

/** Install authoring controls without changing any existing field storage. */
function dyne_rich_text_component_schema(): void {
  require_once __DIR__ . '/native-components.inc';
  foreach (dyne_rich_text_component_fields() as $bundle => [$plain, $rich]) {
    if (!\Drupal\paragraphs\Entity\ParagraphsType::load($bundle)) continue;
    dyne_component_field('paragraph', $bundle, $rich, 'text_long', $bundle === 'dyne_alert_banner' ? 'Banner text' : 'Text', ['allowed_formats' => ['dyne_legacy_html']]);
    $field = \Drupal\field\Entity\FieldConfig::loadByName('paragraph', $bundle, $rich);
    $field->setSetting('allowed_formats', ['dyne_legacy_html'])->setDescription('Formatted content shown on the website.')->save();
    dyne_component_field('paragraph', $bundle, 'field_component_rich_text_ready', 'boolean', 'Uses formatted component text');
    $form = \Drupal\Core\Entity\Entity\EntityFormDisplay::load('paragraph.' . $bundle . '.default');
    $old = $form->getComponent($plain);
    $form->setComponent($rich, ['type' => 'text_textarea', 'weight' => $old['weight'] ?? 5, 'settings' => ['rows' => 5, 'placeholder' => '']]);
    $form->removeComponent($plain)->removeComponent('field_component_rich_text_ready')->save();
  }
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}

/** Plain source data must never become executable legacy HTML. */
function dyne_rich_text_component_escape(string $plain): string {
  return nl2br(htmlspecialchars($plain, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'), FALSE);
}

/** Seed a component once, preserving later edits and intentionally empty text. */
function dyne_rich_text_component_backfill($paragraph): bool {
  $fields = dyne_rich_text_component_fields()[$paragraph->bundle()] ?? NULL;
  if (!$fields || !$paragraph->hasField($fields[1]) || !$paragraph->hasField('field_component_rich_text_ready') || $paragraph->get('field_component_rich_text_ready')->value) return FALSE;
  [$plain, $rich] = $fields;
  if ($paragraph->get($rich)->isEmpty() && !$paragraph->get($plain)->isEmpty()) $paragraph->set($rich, ['value' => dyne_rich_text_component_escape((string) $paragraph->get($plain)->value), 'format' => 'dyne_legacy_html']);
  $paragraph->set('field_component_rich_text_ready', TRUE);
  return TRUE;
}

/** Import updates follow source only while the rich value is still generated. */
function dyne_rich_text_component_presave($paragraph): void {
  $fields = dyne_rich_text_component_fields()[$paragraph->bundle()] ?? NULL;
  if (!$fields || !$paragraph->hasField($fields[1]) || !$paragraph->hasField('field_component_rich_text_ready')) return;
  if (dyne_rich_text_component_backfill($paragraph)) return;
  $original = $paragraph->getOriginal();
  if (!$original || !$original->hasField($fields[1])) return;
  [$plain, $rich] = $fields;
  $before_plain = (string) $original->get($plain)->value;
  $now_plain = (string) $paragraph->get($plain)->value;
  $before_rich = (string) $original->get($rich)->value;
  $now_rich = (string) $paragraph->get($rich)->value;
  if ($now_plain !== $before_plain && $now_rich === $before_rich && $before_rich === dyne_rich_text_component_escape($before_plain)) $paragraph->set($rich, ['value' => dyne_rich_text_component_escape($now_plain), 'format' => 'dyne_legacy_html']);
}

/** Seed old revisions in the form only; the normal save persists the choice. */
function dyne_rich_text_component_widget_alter(array &$element, $form_state, array $context): void {
  $items = $context['items']; $paragraph = $items->getEntity();
  $fields = dyne_rich_text_component_fields()[$paragraph->bundle()] ?? NULL;
  if (!$fields || $items->getName() !== $fields[1] || !$paragraph->hasField('field_component_rich_text_ready')) return;
  if (!$paragraph->get('field_component_rich_text_ready')->value && $paragraph->get($fields[1])->isEmpty() && !$paragraph->get($fields[0])->isEmpty()) {
    $element['#default_value'] = dyne_rich_text_component_escape((string) $paragraph->get($fields[0])->value);
    $element['#format'] = 'dyne_legacy_html';
  }
  $element['#dyne_rich_text_component'] = $paragraph;
  $element['#element_validate'][] = 'dyne_rich_text_component_element_validate';
}

/** An explicitly submitted empty rich value is authoritative on first save. */
function dyne_rich_text_component_element_validate(array &$element, $form_state, array &$complete_form): void {
  $paragraph = $element['#dyne_rich_text_component'] ?? NULL;
  if ($paragraph && $paragraph->hasField('field_component_rich_text_ready')) $paragraph->set('field_component_rich_text_ready', TRUE);
}
