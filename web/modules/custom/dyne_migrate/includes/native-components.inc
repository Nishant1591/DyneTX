<?php

/**
 * @file
 * Native editorial fields for legacy component collections.
 *
 * Old JSON values remain only on historical revisions as migration evidence.
 * The public renderer reads these native fields whenever the migration marker
 * is set, including intentionally empty collections.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\ParagraphsType;

/** Creates a field and usable editor without replacing existing content. */
function dyne_component_field(string $entity_type, string $bundle, string $name, string $type, string $label, array $settings = [], array $storage_settings = [], int $cardinality = 1): void {
  if (!FieldStorageConfig::loadByName($entity_type, $name)) {
    FieldStorageConfig::create(['entity_type' => $entity_type, 'field_name' => $name, 'type' => $type, 'settings' => $storage_settings, 'cardinality' => $cardinality])->save();
  }
  if (!FieldConfig::loadByName($entity_type, $bundle, $name)) {
    FieldConfig::create(['entity_type' => $entity_type, 'bundle' => $bundle, 'field_name' => $name, 'label' => $label, 'settings' => $settings, 'required' => FALSE])->save();
  }
  $type = FieldStorageConfig::loadByName($entity_type, $name)->getType();
  $display = EntityFormDisplay::load("$entity_type.$bundle.default") ?: EntityFormDisplay::create(['targetEntityType' => $entity_type, 'bundle' => $bundle, 'mode' => 'default', 'status' => TRUE]);
  $widget = match ($type) {
    'text' => 'text_textfield',
    'text_long', 'text_with_summary' => 'text_textarea',
    'string_long' => 'string_textarea',
    'entity_reference_revisions' => 'paragraphs',
    'entity_reference' => ($storage_settings['target_type'] ?? '') === 'media' ? 'media_library_widget' : 'entity_reference_autocomplete',
    'integer' => 'number',
    'boolean' => 'boolean_checkbox',
    'link' => 'link_default',
    'list_string' => 'options_select',
    default => 'string_textfield',
  };
  $widget_settings = $type === 'entity_reference_revisions' ? ['title' => 'Item', 'title_plural' => 'Items', 'edit_mode' => 'open', 'closed_mode' => 'summary', 'autocollapse' => 'none', 'add_mode' => 'dropdown', 'form_display_mode' => 'default', 'default_paragraph_type' => ''] : [];
  if ($name === 'field_native_components') {
    $display->removeComponent($name);
  }
  else {
    $display->setComponent($name, ['type' => $widget, 'settings' => $widget_settings, 'weight' => count($display->getComponents())]);
  }
  $display->save();
  $view = EntityViewDisplay::load("$entity_type.$bundle.default") ?: EntityViewDisplay::create(['targetEntityType' => $entity_type, 'bundle' => $bundle, 'mode' => 'default', 'status' => TRUE]);
  // Parent templates render component data in the original page structure.
  $view->removeComponent($name)->save();
}

/** Installs structured field collections and reusable quote entities. */
function dyne_native_components_schema(): void {
  foreach (['dyne_card_item' => 'Card', 'dyne_pipeline_row' => 'Pipeline row', 'dyne_text_section' => 'Text section', 'dyne_careers_quotes' => 'Careers employee stories'] as $id => $label) {
    if (!ParagraphsType::load($id)) {
      ParagraphsType::create(['id' => $id, 'label' => $label, 'description' => 'Editable item within a Dyne component.'])->save();
    }
  }
  foreach (['dyne_card_item', 'dyne_text_section'] as $bundle) {
    dyne_component_field('paragraph', $bundle, 'field_heading', 'string', 'Title');
    dyne_component_field('paragraph', $bundle, 'field_item_text', 'string_long', 'Text');
  }
  dyne_component_field('paragraph', 'dyne_card_item', 'field_item_icon', 'entity_reference', 'Icon or image', ['handler' => 'default:media', 'handler_settings' => ['target_bundles' => ['image' => 'image']]], ['target_type' => 'media']);
  dyne_component_field('paragraph', 'dyne_card_item', 'field_link', 'link', 'Link', ['link_type' => 17, 'title' => 1]);
  dyne_component_field('paragraph', 'dyne_card_item', 'field_style', 'string', 'Style class');
  foreach (['field_row_disease' => 'Disease', 'field_row_prevalence' => 'Prevalence', 'field_row_target' => 'Target', 'field_row_phase' => 'Phase', 'field_row_status' => 'Status'] as $field => $label) {
    dyne_component_field('paragraph', 'dyne_pipeline_row', $field, 'string_long', $label);
  }
  dyne_component_field('paragraph', 'dyne_pipeline_row', 'field_link', 'link', 'Program link', ['link_type' => 17, 'title' => 1]);
  foreach (['dyne_icon_cards' => 'dyne_card_item', 'dyne_pipeline_table' => 'dyne_pipeline_row', 'dyne_drug_detail' => 'dyne_text_section'] as $bundle => $child) {
    dyne_component_field('paragraph', $bundle, 'field_component_items', 'entity_reference_revisions', 'Items', ['handler' => 'default:paragraph', 'handler_settings' => ['target_bundles' => [$child => $child], 'target_bundles_drag_drop' => [$child => ['enabled' => TRUE, 'weight' => 0]]]], ['target_type' => 'paragraph'], -1);
  }
  dyne_component_field('paragraph', 'dyne_team_grid', 'field_team_members', 'entity_reference', 'Team members (drag to order)', ['handler' => 'default:node', 'handler_settings' => ['target_bundles' => ['leader' => 'leader'], 'auto_create' => FALSE]], ['target_type' => 'node'], -1);
  if (!NodeType::load('quote')) {
    NodeType::create(['type' => 'quote', 'name' => 'Testimonial / Quote', 'description' => 'Reusable employee or patient quotation.', 'new_revision' => TRUE])->save();
  }
  dyne_component_field('node', 'quote', 'field_quote_text', 'text_long', 'Quote');
  dyne_component_field('node', 'quote', 'field_speaker_image', 'entity_reference', 'Speaker image', ['handler' => 'default:media', 'handler_settings' => ['target_bundles' => ['image' => 'image']]], ['target_type' => 'media']);
  dyne_component_field('node', 'quote', 'field_wp_source_id', 'integer', 'WordPress source ID');
  dyne_component_field('paragraph', 'dyne_quote_carousel', 'field_quotes', 'entity_reference', 'Quotes (drag to order)', ['handler' => 'default:node', 'handler_settings' => ['target_bundles' => ['quote' => 'quote'], 'auto_create' => FALSE]], ['target_type' => 'node'], -1);
  dyne_component_field('paragraph', 'dyne_careers_quotes', 'field_body_text', 'text_long', 'Introduction and videos');
  dyne_component_field('paragraph', 'dyne_careers_quotes', 'field_quotes', 'entity_reference', 'Featured employee quotes (drag to order)', ['handler' => 'default:node', 'handler_settings' => ['target_bundles' => ['quote' => 'quote'], 'auto_create' => FALSE]], ['target_type' => 'node'], -1);
  dyne_component_field('paragraph', 'dyne_careers_quotes', 'field_archived_quotes', 'entity_reference', 'Earlier employee quotes (hidden section)', ['handler' => 'default:node', 'handler_settings' => ['target_bundles' => ['quote' => 'quote'], 'auto_create' => FALSE]], ['target_type' => 'node'], -1);
  dyne_component_field('node', 'quote', 'field_quote_width', 'integer', 'Text column width (Bootstrap columns)');
  dyne_component_field('paragraph', 'dyne_news_list', 'field_news_limit', 'integer', 'Number of articles (0 = all)');
  dyne_component_field('paragraph', 'dyne_news_list', 'field_news_year', 'integer', 'Publication year (empty = all years)');
  foreach (['dyne_team_grid', 'dyne_quote_carousel', 'dyne_careers_quotes', 'dyne_news_list', 'dyne_jobs_list', 'dyne_icon_cards', 'dyne_pipeline_table', 'dyne_drug_detail'] as $bundle) {
    dyne_component_field('paragraph', $bundle, 'field_native_components', 'boolean', 'Uses native component fields');
    // Newly added components must use the same native controls automatically.
    FieldConfig::loadByName('paragraph', $bundle, 'field_native_components')->setDefaultValue([['value' => 1]])->save();
    foreach (['form' => EntityFormDisplay::class, 'view' => EntityViewDisplay::class] as $class) {
      if ($display = $class::load("paragraph.$bundle.default")) {
        foreach (['field_items_json', 'field_rows_json', 'field_sections_json'] as $field) {
          $display->removeComponent($field);
        }
        $display->save();
      }
    }
  }
  FieldConfig::loadByName('paragraph', 'dyne_news_list', 'field_news_limit')->setDefaultValue([['value' => 2]])->save();
  if ($dynamic = FieldConfig::loadByName('paragraph', 'dyne_jobs_list', 'field_dynamic_jobs')) {
    $dynamic->setDefaultValue([['value' => 1]])->save();
  }
  if ($components = FieldConfig::loadByName('node', 'dyne_page', 'field_components')) {
    $handler = $components->getSetting('handler_settings') ?: [];
    if (!empty($handler['target_bundles'])) {
      $handler['target_bundles']['dyne_careers_quotes'] = 'dyne_careers_quotes';
      if (isset($handler['target_bundles_drag_drop'])) {
        $handler['target_bundles_drag_drop']['dyne_careers_quotes'] = ['enabled' => TRUE, 'weight' => count($handler['target_bundles_drag_drop'])];
      }
      $components->setSetting('handler_settings', $handler)->save();
    }
  }
  // A quote is editable as a standalone content record as well as reusable.
  $quote_view = EntityViewDisplay::load('node.quote.default');
  $quote_view->setComponent('field_quote_text', ['type' => 'text_default', 'label' => 'hidden'])->save();
  require_once __DIR__ . '/rich-text-component-fields.inc';
  dyne_rich_text_component_schema();
}
