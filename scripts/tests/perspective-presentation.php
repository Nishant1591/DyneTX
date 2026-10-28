<?php

/**
 * @file
 * Read-only external-perspective rendering and authored-content guard checks.
 * Run: ddev drush php:script scripts/tests/perspective-presentation.php
 */

require_once DRUPAL_ROOT . '/themes/custom/dynetx/dynetx.theme';
require_once DRUPAL_ROOT . '/themes/custom/dynetx/includes/perspective-presentation.inc';

$report = ['passed' => FALSE, 'checks' => 0, 'failures' => [], 'current_records' => [], 'entity_saves' => 0, 'config_saves' => 0];
$assert = static function (bool $condition, string $message) use (&$report): void {
  $report['checks']++;
  if (!$condition) $report['failures'][] = $message;
};
$storage = \Drupal::entityTypeManager()->getStorage('node');
$section = \Drupal\paragraphs\Entity\Paragraph::create(['type' => 'dyne_article_section']);
$card = $storage->create([
  'type' => 'perspective',
  'title' => 'A publisher perspective',
  'field_native_presentation' => TRUE,
  'field_structured_content' => TRUE,
  'field_external_link' => ['uri' => 'https://publisher.example/article'],
  'field_author' => 'By Example Author',
  'field_content_template' => '<!--dyne:field_article_sections:0-->',
  'field_article_sections' => [$section],
]);
$markup = '<section class="container-fluid section perspective-article"><div class="container"><h1>A publisher perspective</h1><p>By Example Author</p><p><a href="https://publisher.example/article">Read the perspective</a></p></div></section>';
$is_card = static fn($node, string $body): bool => _dynetx_perspective_is_generated_external_card($node, $body);
$assert($is_card($card, $markup), 'Recognize the exact migration-generated external card.');
$assert($is_card($card, str_replace('<p><a', '<p></p><a', str_replace('</a></p>', '</a>', $markup))), 'Recognize the equivalent legacy empty-paragraph serialization.');
$assert(!$is_card($card, ''), 'An unknown empty render array is not classified as a generated card.');
$assert(!$is_card($card, str_replace('</div></section>', '<p>A newly authored narrative.</p></div></section>', $markup)), 'Additional authored narrative remains visible.');
$assert(!$is_card($card, str_replace('</div></section>', '<h2>A local article section</h2></div></section>', $markup)), 'Additional article headings remain visible.');
$assert(!$is_card($card, str_replace('</div></section>', '<img src="/example.png" alt=""></div></section>', $markup)), 'A newly authored image remains visible.');
$assert(!$is_card($card, str_replace('Read the perspective', 'Read this newly authored introduction', $markup)), 'An edited call-to-action remains visible.');
$assert(!$is_card($card, str_replace('href="https://publisher.example/article"', 'href="https://publisher.example/other"', $markup)), 'An edited destination remains visible.');
$assert(!$is_card($card, str_replace('A publisher perspective</h1>', 'A new local heading</h1>', $markup)), 'An edited local heading remains visible.');

foreach (['field_story_heading', 'field_story_mission', 'field_story_byline', 'field_story_intro', 'field_article_intro', 'field_article_body'] as $name) {
  if (!$card->hasField($name)) continue;
  $clone = clone $card;
  $clone->set($name, ['value' => 'New editorial content', 'format' => 'dyne_legacy_html']);
  $assert(!$is_card($clone, $markup), $name . ' makes authored content visible.');
}
$clone = clone $card; $clone->set('field_external_link', []);
$assert(!$is_card($clone, $markup), 'Removing the external destination restores the local article.');
$clone = clone $card; $clone->set('field_native_presentation', FALSE);
$assert(!$is_card($clone, $markup), 'Non-migrated local records remain visible.');
$clone = clone $card; $clone->set('field_structured_content', FALSE);
$assert(!$is_card($clone, $markup), 'Unstructured local records remain visible.');
$clone = clone $card; $clone->set('field_content_template', '<div><!--dyne:field_article_sections:0--></div>');
$assert(!$is_card($clone, $markup), 'A customized page template remains visible.');
$clone = clone $card; $clone->set('field_article_sections', [$section, \Drupal\paragraphs\Entity\Paragraph::create(['type' => 'dyne_article_section'])]);
$assert(!$is_card($clone, $markup), 'Additional referenced sections remain visible.');
$clone = clone $card; $clone->setTitle('An editor changed this title');
$assert(!$is_card($clone, $markup), 'A changed record title does not suppress unmatched preserved content.');

$variables = ['node' => $card, 'content' => ['field_body_text' => ['#text' => $markup, '#cache' => ['tags' => ['paragraph:comparison_probe'], 'contexts' => ['languages:language_content']]]]];
_dynetx_perspective_preprocess($variables);
$assert($variables['dyne_perspective_external_shell'], 'Preprocess exposes the verified external-shell decision.');
$assert(in_array('paragraph:comparison_probe', $variables['dyne_perspective_presentation_cache']['#cache']['tags'] ?? [], TRUE), 'Suppressed cards retain paragraph cache dependencies.');
$assert(in_array('user.permissions', $variables['dyne_perspective_presentation_cache']['#cache']['contexts'] ?? [], TRUE), 'The rendering decision retains permission cache context.');

$inline = _dynetx_perspective_inline_label('A <br class="unsafe">line <sup onclick="bad()">TM</sup><img src=x onerror="bad()"><a href="javascript:bad()">label</a>');
$assert(str_contains($inline['#markup'], '<br>') && str_contains($inline['#markup'], '<sup>TM</sup>'), 'Listing labels retain the source line-break and superscript tags.');
$assert(!preg_match('/<(?:img|a)\b|onclick|onerror|class=|javascript:/i', $inline['#markup']), 'Listing labels discard unsupported tags, attributes and active links.');

// Use unsaved entities throughout: the legacy fallback and its invalidation
// dependencies must work without creating test content in the application.
$legacy_file = \Drupal\file\Entity\File::create(['uri' => 'public://legacy/perspective-fallback-check.png']);
$legacy_file->addCacheTags(['file:legacy_fallback_probe']);
$legacy = $storage->create(['type' => 'perspective', 'nid' => 987654321, 'title' => 'Legacy field-only perspective', 'status' => 1]);
$legacy->set('field_image', ['entity' => $legacy_file, 'alt' => 'Preserved legacy portrait']);
$legacy->set('field_link', ['uri' => 'https://publisher.example/legacy', 'title' => 'The original article headline']);
$legacy_cache = new \Drupal\Core\Cache\CacheableMetadata();
$legacy_photo = _dynetx_perspective_list_photo($legacy, $legacy_cache);
$assert(($legacy_photo['url'] ?? '') === \Drupal::service('file_url_generator')->generateString($legacy_file->getFileUri()), 'A file-only legacy perspective retains its image URL.');
$assert(($legacy_photo['alt'] ?? '') === 'Preserved legacy portrait', 'The legacy image retains its editable alternative text.');
$assert(in_array('file:legacy_fallback_probe', $legacy_cache->getCacheTags(), TRUE), 'A legacy image change invalidates the listing.');
$legacy_destination = _dynetx_perspective_list_destination($legacy, $legacy_cache);
$assert($legacy_destination['url'] === 'https://publisher.example/legacy' && $legacy_destination['external'], 'A legacy-only external link retains its publisher destination and external behavior.');
$assert($legacy_destination['action'] === (string) t('Read more'), 'A legacy article headline does not become an oversized button caption.');
$legacy->set('field_link', ['uri' => 'internal:/perspectives']);
$legacy_destination = _dynetx_perspective_list_destination($legacy, $legacy_cache);
$assert(!$legacy_destination['external'] && str_ends_with($legacy_destination['url'], '/perspectives'), 'A legacy internal link retains same-site behavior.');
$legacy->set('field_external_link', ['uri' => 'https://publisher.example/native', 'title' => 'Watch']);
$preferred_destination = _dynetx_perspective_list_destination($legacy, $legacy_cache);
$assert($preferred_destination['url'] === 'https://publisher.example/native' && $preferred_destination['action'] === 'Watch', 'The dedicated native destination and action take precedence over the legacy link.');
$native_file = \Drupal\file\Entity\File::create(['uri' => 'public://legacy/perspective-native-check.png']);
$native_media = \Drupal\media\Entity\Media::create(['bundle' => 'image', 'field_media_image' => ['entity' => $native_file, 'alt' => 'Native Media portrait']]);
$legacy->set('field_story_image', ['entity' => $native_media]);
$preferred_photo = _dynetx_perspective_list_photo($legacy, $legacy_cache);
$assert(($preferred_photo['url'] ?? '') === \Drupal::service('file_url_generator')->generateString($native_file->getFileUri()) && ($preferred_photo['alt'] ?? '') === 'Native Media portrait', 'Native Media takes precedence when a legacy image also exists.');

$list = \Drupal\paragraphs\Entity\Paragraph::create(['type' => 'dyne_perspective_list']);
$list_variables = ['paragraph' => $list];
_dynetx_perspective_list_preprocess($list_variables);
$published_count = (int) $storage->getQuery()->accessCheck(TRUE)->condition('type', 'perspective')->condition('status', 1)->count()->execute();
$expected_count = min(10, $published_count);
$actual_count = (int) !empty($list_variables['dyne_perspective_featured']) + count($list_variables['dyne_perspective_visible']) + count($list_variables['dyne_perspective_additional']);
$assert($actual_count === $expected_count, 'The default listing limits current published records without deleting older article entities.');
$assert(count($list_variables['dyne_perspective_visible']) === min(6, max(0, $expected_count - 1)), 'The default initial card group contains at most six cards after the featured record.');
$assert(count($list_variables['dyne_perspective_additional']) === max(0, $expected_count - 7), 'Remaining published cards use the expandable group.');
$assert(in_array('node_list:perspective', $list_variables['dyne_perspective_list_cache']['#cache']['tags'] ?? [], TRUE), 'Newly created and unpublished records invalidate the listing cache.');
$list->set('field_perspective_limit', 4); $list->set('field_perspective_visible', 2);
$list_variables = ['paragraph' => $list]; _dynetx_perspective_list_preprocess($list_variables);
$assert(count($list_variables['dyne_perspective_visible']) === min(2, max(0, min(4, $published_count) - 1)), 'An unsaved editorial limit change controls the visible group.');
$assert(count($list_variables['dyne_perspective_additional']) === max(0, min(4, $published_count) - 3), 'An unsaved editorial limit change controls the additional group.');
$list->set('field_perspective_visible', 0);
$list_variables = ['paragraph' => $list]; _dynetx_perspective_list_preprocess($list_variables);
$assert($list_variables['dyne_perspective_visible'] === [], 'Zero initial cards is honored rather than treated as an empty default.');

foreach ($storage->loadByProperties(['type' => 'perspective']) as $node) {
  $before = serialize($node->toArray());
  $build = _dynetx_structured_article_body($node);
  $report['current_records'][] = ['id' => $node->id(), 'path' => $node->toUrl()->toString(), 'external_shell' => $is_card($node, (string) ($build['#text'] ?? ''))];
  $assert($before === serialize($node->toArray()), 'Classification leaves perspective ' . $node->id() . ' unchanged.');
}
$report['passed'] = !$report['failures'];
$directory = dirname(DRUPAL_ROOT) . '/output/perspective-presentation';
if (!is_dir($directory)) mkdir($directory, 0775, TRUE);
file_put_contents($directory . '/verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
print json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if (!$report['passed']) throw new \RuntimeException('Perspective presentation checks failed.');
