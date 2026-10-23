<?php

/**
 * @file
 * Read-only editor verification: ddev drush php:script scripts/tests/rich-text-editors.php
 *
 * This script never saves entities, configuration, state, or forms. It renders
 * individual real field widgets rather than building recursive content forms.
 * The only file it writes is output/ckeditor/verification.json.
 */

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Element\TextFormat;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatRepositoryInterface;

$directory = dirname(DRUPAL_ROOT) . '/output/ckeditor';
$report = ['passed' => FALSE, 'checks' => 0, 'failures' => [], 'formats' => [], 'field_coverage' => [], 'widgets' => [], 'entity_saves' => 0, 'config_saves' => 0, 'state_writes' => 0];
$assert = static function (bool $condition, string $message) use (&$report): void {
  $report['checks']++;
  if (!$condition) {
    $report['failures'][] = $message;
  }
};
$switched = FALSE;

try {
  foreach (['editor', 'ckeditor5'] as $module) {
    $assert(\Drupal::moduleHandler()->moduleExists($module), $module . ' module must be enabled.');
  }
  if ($report['failures']) {
    throw new RuntimeException('Required editor modules are not enabled.');
  }
  $manager = \Drupal::service('plugin.manager.ckeditor5.plugin');
  foreach (['dyne_legacy_html', 'dyne_job_html'] as $id) {
    $editor = Editor::load($id);
    $format = FilterFormat::load($id);
    $assert($editor !== NULL && $editor->getEditor() === 'ckeditor5', $id . ' must use CKEditor 5.');
    $assert($format !== NULL && $format->status(), $id . ' format must be active.');
    if (!$editor || !$format) {
      continue;
    }
    $violations = [];
    foreach (CKEditor5::validatePair($editor, $format) as $violation) {
      $violations[] = ['path' => $violation->getPropertyPath(), 'message' => (string) $violation->getMessage()];
    }
    $assert($violations === [], $id . ': editor and format fail core compatibility validation.');
    $enabled = array_keys($manager->getEnabledDefinitions($editor));
    $toolbar = $editor->getSettings()['toolbar']['items'];
    foreach (['bold', 'italic', 'link', 'bulletedList', 'numberedList', 'sourceEditing'] as $button) {
      $assert(in_array($button, $toolbar, TRUE), $id . ': missing essential toolbar button ' . $button . '.');
    }
    if ($id === 'dyne_legacy_html') {
      $assert($format->getHtmlRestrictions() === FALSE, 'Trusted legacy format must remain unrestricted.');
      $assert(in_array('ckeditor5_arbitraryHtmlSupport', $enabled, TRUE), 'Legacy editor must preserve arbitrary HTML attributes, classes and styles.');
    }
    $report['formats'][$id] = ['editor' => $editor->getEditor(), 'violations' => $violations, 'toolbar' => $toolbar, 'enabled_plugins' => $enabled];
  }
  $assert(Editor::load('plain_text') === NULL, 'plain_text must remain editor-free.');
  // Webform installs its own optional editor when CKEditor 5 becomes available.
  // WebformHooks::modulesInstalled() calls _webform_update_html_editor(); that
  // internal configuration is expected and is not a Dyne content editor.
  if ($webform_editor = Editor::load('webform_default')) {
    $report['internal_webform_editor'] = [
      'editor' => $webform_editor->getEditor(),
      'provider' => 'webform',
      'source' => 'web/modules/contrib/webform/config/optional/ckeditor5/editor.editor.webform_default.yml',
    ];
  }

  $administrator = \Drupal::entityTypeManager()->getStorage('user')->load(1);
  $assert($administrator !== NULL, 'An existing administrator is needed for read-only widget permission checks.');
  if (!$administrator) {
    throw new RuntimeException('Administrator account unavailable.');
  }
  \Drupal::service('account_switcher')->switchTo($administrator);
  $switched = TRUE;
  $available = array_keys(\Drupal::service(FilterFormatRepositoryInterface::class)->getFormatsForAccount($administrator));
  $editor_formats = [];
  foreach (Editor::loadMultiple($available) as $id => $editor) {
    if ($editor->getEditor() === 'ckeditor5') {
      $editor_formats[] = $id;
    }
  }
  $assert($editor_formats !== [], 'Administrator must have an eligible CKEditor text format.');

  $field_manager = \Drupal::service('entity_field.manager');
  $textarea_fields = 0;
  $short_fields = 0;
  foreach (EntityFormDisplay::loadMultiple() as $display) {
    $entity_type = $display->getTargetEntityTypeId();
    if (!in_array($entity_type, ['node', 'paragraph'], TRUE) || !$display->status()) {
      continue;
    }
    $bundle = $display->getTargetBundle();
    $definitions = $field_manager->getFieldDefinitions($entity_type, $bundle);
    foreach ($display->getComponents() as $name => $component) {
      if (!isset($definitions[$name]) || !in_array($definitions[$name]->getType(), ['text', 'text_long', 'text_with_summary'], TRUE)) {
        continue;
      }
      $widget = $component['type'] ?? '';
      if ($widget === 'text_textfield') {
        $short_fields++;
        continue;
      }
      if (!in_array($widget, ['text_textarea', 'text_textarea_with_summary'], TRUE)) {
        continue;
      }
      $allowed = $definitions[$name]->getSetting('allowed_formats') ?: $available;
      $eligible = array_values(array_intersect($allowed, $editor_formats));
      $key = $display->id() . ':' . $name;
      $assert($eligible !== [], $key . ' has no accessible CKEditor format.');
      $report['field_coverage'][$key] = ['widget' => $widget, 'eligible_formats' => $eligible];
      $textarea_fields++;
    }
  }
  $assert($textarea_fields > 0, 'At least one visible formatted textarea must be covered.');
  $report['formatted_textarea_fields'] = $textarea_fields;
  $report['short_formatted_textfields'] = $short_fields;

  // Use current native values and real configured widgets. This exercises core
  // TextFormat processing, editor attachment, and render metadata without the
  // expensive recursive Paragraphs form or any form submission/cache writes.
  $representatives = [
    ['node', 'job', 'field_role_summary', FALSE],
    ['node', 'leader', 'field_body_text', FALSE],
    ['node', 'news', 'field_article_body', FALSE],
    ['node', 'privacy_policy', 'field_article_intro', FALSE],
    ['node', 'perspective', 'field_story_intro', FALSE],
    ['paragraph', 'dyne_article_section', 'field_section_body', FALSE],
    ['paragraph', 'dyne_card_item', 'field_item_body', TRUE],
    ['paragraph', 'dyne_text_section', 'field_item_body', TRUE],
    ['paragraph', 'dyne_alert_banner', 'field_banner_body', TRUE],
  ];
  foreach ($representatives as [$entity_type, $bundle, $name, $optional]) {
    $key = $entity_type . '.' . $bundle . ':' . $name;
    if (!isset($field_manager->getFieldDefinitions($entity_type, $bundle)[$name])) {
      $assert($optional, $key . ': required formatted field schema is missing.');
      $report['widgets'][$key] = ['schema_not_present' => TRUE];
      continue;
    }
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $bundle_key = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('bundle');
    $ids = $storage->getQuery()->accessCheck(FALSE)->condition($bundle_key, $bundle)->range(0, $entity_type === 'paragraph' ? 10 : 1)->execute();
    $entity = NULL;
    foreach ($storage->loadMultiple($ids) as $candidate) {
      // Link-only sections intentionally hide their archival text control.
      if ($entity_type === 'paragraph' && $candidate->hasField('field_content_template')) {
        $template = (string) $candidate->get('field_content_template')->value;
        if (str_starts_with(strtolower(ltrim($template)), '<a ') && preg_match('~</a>\s*$~i', $template)) {
          continue;
        }
      }
      $entity = $candidate;
      break;
    }
    if (!$entity && $optional) {
      $entity = $storage->create([$bundle_key => $bundle]);
    }
    $assert($entity !== NULL, $key . ': a representative entity must be present.');
    if (!$entity) {
      continue;
    }
    // WidgetBase normally receives an initialized item from full form
    // construction. Initialize a clone so empty optional fields produce the
    // same real widget without notices or changing the loaded entity object.
    $entity = clone $entity;
    if (count($entity->get($name)) === 0) {
      $entity->get($name)->appendItem();
    }
    $display = EntityFormDisplay::load($entity_type . '.' . $bundle . '.default');
    $widget = $display?->getRenderer($name);
    $assert($widget !== NULL, $key . ': visible configured widget is required.');
    if (!$widget) {
      continue;
    }
    $state = new FormState();
    $shell = ['#parents' => []];
    $built = $widget->form($entity->get($name), $shell, $state, 0);
    $element = $built['widget'][0] ?? [];
    $assert(($element['#type'] ?? '') === 'text_format', $key . ': actual widget must be a formatted element.');
    $assert(($element['#base_type'] ?? 'textarea') === 'textarea', $key . ': actual widget must support CKEditor textarea attachment.');
    if (($element['#access'] ?? TRUE) === FALSE) {
      // A selected link-only section intentionally hides its archival body;
      // inspect another section body rather than override the access rule.
      $report['widgets'][$key] = ['entity_id' => $entity->id(), 'intentionally_hidden' => TRUE];
      continue;
    }
    $element += \Drupal::service('element_info')->getInfo('text_format');
    $element['#parents'] = [$name, 0];
    $element['#id'] = 'dyne-readonly-' . str_replace('_', '-', $bundle . '-' . $name);
    $element = TextFormat::processFormat($element, $state, $shell);
    $element['value']['#id'] = $element['#id'] . '-value';
    $element['value']['#name'] = $name . '[0][value]';
    $element['value']['#value'] = $element['value']['#default_value'] ?? '';
    $element['format']['format']['#id'] = $element['#id'] . '-format';
    $element['format']['format']['#name'] = $name . '[0][format]';
    $element['format']['format']['#value'] = $element['format']['format']['#default_value'];
    $element = \Drupal::service('element.editor')->preRenderTextFormat($element);
    $attachments = $element['#attached'] ?? [];
    $libraries = $attachments['library'] ?? [];
    $formats = $attachments['drupalSettings']['editor']['formats'] ?? [];
    $active = $element['format']['format']['#value'];
    $assert(in_array('editor/drupal.editor', $libraries, TRUE), $key . ': core editor behavior library must attach.');
    $assert(isset($formats[$active]) && $formats[$active]['editor'] === 'ckeditor5', $key . ': selected format must attach CKEditor drupalSettings.');
    $assert(in_array('ckeditor5/internal.drupal.ckeditor5', $libraries, TRUE), $key . ': CKEditor initialization library must attach.');
    $element['#cache']['max-age'] = 0;
    unset($element['#pre_render']);
    $markup = (string) \Drupal::service('renderer')->renderInIsolation($element);
    $assert(str_contains($markup, 'data-editor-for="' . $element['value']['#id'] . '"'), $key . ': rendered format selector/hidden input must target the actual textarea.');
    $assert(str_contains($markup, '<textarea'), $key . ': actual widget must render a textarea.');
    $report['widgets'][$key] = ['entity_id' => $entity->id(), 'active_format' => $active, 'editor_formats_attached' => array_keys($formats), 'libraries' => $libraries, 'rendered_bytes' => strlen($markup)];
  }
}
catch (Throwable $error) {
  $report['failures'][] = get_class($error) . ': ' . $error->getMessage();
}
finally {
  if ($switched) {
    \Drupal::service('account_switcher')->switchBack();
  }
}

$report['passed'] = $report['failures'] === [];
if (!is_dir($directory)) {
  mkdir($directory, 0775, TRUE);
}
file_put_contents($directory . '/verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
$rendered_widgets = array_filter($report['widgets'], static fn(array $widget): bool => isset($widget['rendered_bytes']));
print json_encode(['passed' => $report['passed'], 'checks' => $report['checks'], 'formatted_textarea_fields' => $report['formatted_textarea_fields'] ?? 0, 'widgets_rendered' => count($rendered_widgets), 'widgets_requested' => count($representatives ?? []), 'failures' => $report['failures'], 'report' => $directory . '/verification.json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
if (!$report['passed']) {
  throw new RuntimeException('Read-only CKEditor verification failed; inspect output/ckeditor/verification.json.');
}
