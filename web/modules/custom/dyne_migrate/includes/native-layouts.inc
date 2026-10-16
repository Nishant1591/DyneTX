<?php

/**
 * @file
 * Native authoring controls for the 21 source ACF layout definitions.
 *
 * These are reusable layouts, not instructions to replace today's page content
 * with the older ACF values still present in the WordPress database.
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\paragraphs\Entity\ParagraphsType;

/** Source layout => [bundle, label, controls]. */
function dyne_layout_definitions(): array {
  return [
    'homepage_hero_band' => ['dyne_acf_home_hero', 'Homepage hero', ['heading', 'body', 'image', 'alignment']],
    '2_column_homepage_boxes' => ['dyne_acf_promo_boxes', 'Two-column promotional boxes', ['items']],
    'latest_news_band' => ['dyne_acf_latest_news', 'Latest news', ['heading', 'count']],
    'interior_page_header_band' => ['dyne_acf_interior_header', 'Interior page header', ['heading', 'body', 'image']],
    'centered_info_band' => ['dyne_acf_centered_info', 'Centered information', ['heading', 'body']],
    'wysiwyg_band' => ['dyne_acf_wysiwyg', 'Formatted text', ['body']],
    'program_focus_band' => ['dyne_acf_program_focus', 'Program focus', ['heading', 'body', 'gene', 'symptoms', 'treatment', 'link', 'hide_dna', 'anchor']],
    'team_highlights_band' => ['dyne_acf_team_highlights', 'Team highlights', ['heading', 'team']],
    'leadership_team_band' => ['dyne_acf_leadership', 'Leadership by category', ['heading', 'category']],
    'investors_band' => ['dyne_acf_investors', 'Investor information', ['body', 'links']],
    'challenge_vs_vision_band' => ['dyne_acf_challenge_vision', 'Challenge and vision', ['heading', 'items']],
    'video_solutions_band' => ['dyne_acf_video_solutions', 'Video solutions', ['heading', 'video', 'body', 'disable_accordion']],
    'testimonials_band' => ['dyne_acf_testimonials', 'Testimonials', ['heading', 'quotes']],
    'open_positions_band' => ['dyne_acf_open_positions', 'Open positions', ['heading', 'body']],
    'contact_us_band' => ['dyne_acf_contact', 'Contact information', ['heading', 'body', 'map']],
    'press_releases_band' => ['dyne_acf_press_releases', 'Press releases', ['heading', 'count', 'sidebar', 'sidebar_body']],
    'in_the_news_band' => ['dyne_acf_in_the_news', 'In the news', ['heading', 'background', 'items']],
    'stay_connected' => ['dyne_acf_stay_connected', 'Stay connected', ['heading', 'body', 'show_form']],
    'text_image_band' => ['dyne_acf_text_image', 'Text and image', ['heading', 'body', 'image', 'caption']],
    'resources_band' => ['dyne_acf_resources', 'Resources', ['heading', 'body', 'items']],
    '2_column_text_image_band' => ['dyne_acf_two_column', 'Two-column text and image', ['heading', 'body', 'image', 'background_image', 'items']],
  ];
}

/** Create a native field and its editing widget without changing existing data. */
function dyne_layout_field(string $entity, string $bundle, string $name, string $type, string $label, array $settings = [], int $cardinality = 1, array $storage_settings = []): void {
  if (!FieldStorageConfig::loadByName($entity, $name)) {
    FieldStorageConfig::create(['field_name' => $name, 'entity_type' => $entity, 'type' => $type, 'cardinality' => $cardinality, 'settings' => $storage_settings])->save();
  }
  if (!FieldConfig::loadByName($entity, $bundle, $name)) {
    FieldConfig::create(['field_name' => $name, 'entity_type' => $entity, 'bundle' => $bundle, 'label' => $label, 'settings' => $settings])->save();
  }
  $id = "$entity.$bundle.default";
  $form = EntityFormDisplay::load($id) ?: EntityFormDisplay::create(['targetEntityType' => $entity, 'bundle' => $bundle, 'mode' => 'default', 'status' => TRUE]);
  $widget = match ($type) {
    'text_long', 'text_with_summary' => 'text_textarea',
    'string_long' => 'string_textarea',
    'boolean' => 'boolean_checkbox',
    'integer' => 'number',
    'datetime' => 'datetime_default',
    'list_string' => 'options_select',
    'link' => 'link_default',
    'entity_reference_revisions' => 'paragraphs',
    'entity_reference' => ($storage_settings['target_type'] ?? '') === 'media' ? 'media_library_widget' : 'entity_reference_autocomplete',
    default => 'string_textfield',
  };
  $form->setComponent($name, ['type' => $widget, 'weight' => count($form->getComponents())])->save();
  // Custom layouts own markup; auxiliary fields must not leak into node pages.
  $view = EntityViewDisplay::load($id) ?: EntityViewDisplay::create(['targetEntityType' => $entity, 'bundle' => $bundle, 'mode' => 'default', 'status' => TRUE]);
  $view->removeComponent($name)->save();
}

function dyne_layout_schema(): void {
  $controls = [
    'heading' => ['string', 'Heading'], 'body' => ['text_long', 'Text'], 'sidebar_body' => ['text_long', 'Sidebar content'],
    'gene' => ['string', 'Gene'], 'symptoms' => ['text_long', 'Symptoms'], 'treatment' => ['text_long', 'Treatment'],
    'caption' => ['string_long', 'Caption'], 'anchor' => ['string', 'Section anchor'],
    'hide_dna' => ['boolean', 'Hide DNA decoration'], 'disable_accordion' => ['boolean', 'Show video text expanded'],
    'show_form' => ['boolean', 'Show configured OneTrust form'],
    'count' => ['integer', 'Number of news items'],
    'map' => ['link', 'Google Maps link'], 'link' => ['link', 'Call to action'], 'links' => ['link', 'Links'],
    'alignment' => ['list_string', 'Alignment'], 'sidebar' => ['list_string', 'Sidebar position'], 'background' => ['list_string', 'Background'],
  ];
  $definitions = dyne_layout_definitions();
  $definitions['item'] = ['dyne_layout_item', 'Layout item / card', ['heading', 'body', 'image', 'link', 'caption']];
  foreach ($definitions as [$bundle, $label, $fields]) {
    if (!ParagraphsType::load($bundle)) ParagraphsType::create(['id' => $bundle, 'label' => $label])->save();
    foreach ($fields as $control) {
      $name = 'field_layout_' . $control;
      if (in_array($control, ['image', 'background_image', 'video', 'team', 'quotes', 'category'], TRUE)) {
        $target = match ($control) {'team', 'quotes' => 'node', 'category' => 'taxonomy_term', default => 'media'};
        $bundles = match ($control) {'team' => ['leader'], 'quotes' => ['quote'], 'category' => ['leader_type'], 'video' => ['video', 'remote_video'], default => ['image']};
        dyne_layout_field('paragraph', $bundle, $name, 'entity_reference', ucfirst(str_replace('_', ' ', $control)), ['handler' => 'default:' . $target, 'handler_settings' => ['target_bundles' => array_combine($bundles, $bundles)]], in_array($control, ['team', 'quotes'], TRUE) ? -1 : 1, ['target_type' => $target]);
      }
      elseif ($control === 'items') {
        dyne_layout_field('paragraph', $bundle, $name, 'entity_reference_revisions', 'Items', ['handler' => 'default:paragraph', 'handler_settings' => ['target_bundles' => ['dyne_layout_item' => 'dyne_layout_item']]], -1, ['target_type' => 'paragraph']);
      }
      else {
        [$type, $label] = $controls[$control];
        $storage = [];
        if ($type === 'list_string') $storage['allowed_values'] = match ($control) {
          'alignment' => ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'],
          'sidebar' => ['none' => 'None', 'left' => 'Left', 'right' => 'Right'],
          default => ['' => 'Default', 'bg-wht-blue' => 'White / blue', 'bg-cyan-blue' => 'Cyan / blue'],
        };
        dyne_layout_field('paragraph', $bundle, $name, $type, $label, $type === 'link' ? ['title' => 1, 'link_type' => 17] : [], $control === 'links' ? -1 : 1, $storage);
      }
    }
  }
  // Allow these native layouts in the existing page component stack.
  $components = FieldConfig::loadByName('node', 'dyne_page', 'field_components');
  if ($components) {
    $settings = $components->getSetting('handler_settings');
    if (!empty($settings['target_bundles'])) {
      foreach ($definitions as [$bundle]) if ($bundle !== 'dyne_layout_item') $settings['target_bundles'][$bundle] = $bundle;
      $components->setSetting('handler_settings', $settings)->save();
    }
  }
}
