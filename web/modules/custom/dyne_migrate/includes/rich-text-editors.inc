<?php

/**
 * @file
 * CKEditor configuration shared by new installs and the existing-site update.
 */

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;

/** Enable visual authoring without rewriting stored text or trusted HTML. */
function dyne_migrate_enable_rich_text_editors(): void {
  \Drupal::service('module_installer')->install(['ckeditor5']);

  foreach (['dyne_legacy_html', 'dyne_job_html'] as $id) {
    $format = FilterFormat::load($id);
    // Respect any editor configuration subsequently customized by an admin.
    if (!$format || Editor::load($id)) {
      continue;
    }
    $restricted = $id === 'dyne_job_html';
    if ($restricted) {
      $filter = $format->filters('filter_html')->getConfiguration();
      // CKEditor requires line breaks; numbered lists need their container.
      // Retain every existing allowed tag and attribute.
      foreach (['br', 'ol'] as $tag) {
        if (!preg_match('/<' . $tag . '(?:\s|>)/i', $filter['settings']['allowed_html'])) {
          $filter['settings']['allowed_html'] .= ' <' . $tag . '>';
        }
      }
      $format->setFilterConfig('filter_html', $filter);
    }
    $settings = [
      'toolbar' => ['items' => $restricted
        ? ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'link', '|', 'bulletedList', 'numberedList', '|', 'sourceEditing']
        : ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'removeFormat', '|', 'link', '|', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'horizontalLine', '|', 'sourceEditing'],
      ],
      'plugins' => [
        'ckeditor5_heading' => ['enabled_headings' => $restricted ? ['heading3'] : ['heading2', 'heading3', 'heading4', 'heading5', 'heading6']],
        'ckeditor5_list' => [
          'properties' => ['reversed' => !$restricted, 'startIndex' => !$restricted, 'styles' => !$restricted],
          'multiBlock' => TRUE,
        ],
        // Unrestricted formats automatically get core's Arbitrary HTML Support.
        // It retains imported classes, styles and data attributes. Enumerating
        // these in Source Editing would be redundant and fail validation.
        'ckeditor5_sourceEditing' => ['allowed_tags' => $restricted
          ? ['<section class>', '<p class>', '<a target rel title>']
          : [],
        ],
      ],
    ];
    $editor = Editor::create([
      'format' => $id,
      'editor' => 'ckeditor5',
      'settings' => $settings,
      // Images continue to use the content model's managed Media/image fields.
      'image_upload' => ['status' => FALSE],
    ]);
    $violations = CKEditor5::validatePair($editor, $format);
    if (count($violations)) {
      throw new \RuntimeException('Invalid CKEditor configuration for ' . $id . ': ' . (string) $violations);
    }
    if ($restricted) {
      $format->save();
    }
    $editor->save();
  }
  require_once __DIR__ . '/rich-text-component-fields.inc';
  dyne_rich_text_component_schema();
  dyne_migrate_rich_text_format_order();
}

/** Empty fields need an editor format before the editor-free fallback. */
function dyne_migrate_rich_text_format_order(): void {
  $restricted = FilterFormat::load('dyne_job_html');
  $plain = FilterFormat::load('plain_text');
  if (!$restricted || !$plain) return;
  // Drupal resolves equal weights alphabetically. Generic labels must not
  // cause an empty field to select Plain text instead of an HTML editor.
  $weight = min((int) $restricted->get('weight'), (int) $plain->get('weight') - 1);
  if ((int) $restricted->get('weight') !== $weight) $restricted->set('weight', $weight)->save();
}
