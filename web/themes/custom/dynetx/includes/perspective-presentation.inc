<?php

/**
 * @file
 * Keep external-only perspective permalinks consistent with the source site.
 */

/** Normalize plain/editorial labels only for comparison, never for output. */
function _dynetx_perspective_comparison_text(string $value): string {
  $value = preg_replace('~<br\s*/?>~i', ' ', $value);
  return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

/**
 * Recognize only the small external-link cards synthesized by the migration.
 *
 * The live external-story permalinks have the Perspectives hero and footer but
 * no article body; the listing links directly to the external publisher. The
 * importer synthesized a title/byline/read-link card for those empty bodies.
 * Keep every stored field and revision, and reveal any subsequently authored
 * narrative automatically instead of hiding everything with an external URL.
 */
function _dynetx_perspective_is_generated_external_card($node, string $markup): bool {
  if (!$node instanceof \Drupal\node\NodeInterface || $node->bundle() !== 'perspective') return FALSE;
  foreach (['field_native_presentation', 'field_structured_content'] as $name) {
    if (!$node->hasField($name) || !$node->get($name)->value) return FALSE;
  }
  if (!$node->hasField('field_external_link') || $node->get('field_external_link')->isEmpty()) return FALSE;
  $external = (string) $node->get('field_external_link')->uri;
  if (!preg_match('~^https?://~i', $external)) return FALSE;
  foreach (['field_story_mission', 'field_story_heading', 'field_story_byline', 'field_story_intro', 'field_story_date_label', 'field_story_portrait', 'field_article_intro', 'field_article_body'] as $name) {
    if ($node->hasField($name) && !$node->get($name)->isEmpty()) return FALSE;
  }
  if (!$node->hasField('field_content_template') || trim((string) $node->get('field_content_template')->value) !== '<!--dyne:field_article_sections:0-->') return FALSE;
  if (!$node->hasField('field_article_sections') || $node->get('field_article_sections')->count() !== 1 || $markup === '') return FALSE;

  $document = \Drupal\Component\Utility\Html::load($markup);
  $xpath = new \DOMXPath($document);
  $body = $document->getElementsByTagName('body')->item(0);
  if (!$body) return FALSE;
  $allowed = ['section', 'div', 'h1', 'p', 'a', 'span', 'em', 'strong', 'b', 'i', 'br', 'sup', 'sub'];
  foreach ($xpath->query('.//*', $body) as $element) {
    if (!in_array(strtolower($element->tagName), $allowed, TRUE)) return FALSE;
  }
  $sections = $xpath->query('.//section', $body);
  $headings = $xpath->query('.//h1', $body);
  $links = $xpath->query('.//a', $body);
  if ($sections->length !== 1 || $headings->length !== 1 || $links->length !== 1) return FALSE;
  $section = $sections->item(0);
  if (!in_array('perspective-article', preg_split('/\s+/', trim($section->getAttribute('class'))), TRUE)) return FALSE;
  $link = $links->item(0);
  if ($link->getAttribute('href') !== $external || _dynetx_perspective_comparison_text($link->textContent) !== 'Read the perspective') return FALSE;

  $text = static function (\DOMNode $element) use ($xpath): string {
    $parts = [];
    foreach ($xpath->query('.//text()[normalize-space()]', $element) as $part) $parts[] = $part->textContent;
    return _dynetx_perspective_comparison_text(implode(' ', $parts));
  };
  $title = $text($headings->item(0));
  $titles = [_dynetx_perspective_comparison_text($node->label())];
  if ($node->hasField('field_legacy_title') && !$node->get('field_legacy_title')->isEmpty()) $titles[] = _dynetx_perspective_comparison_text((string) $node->get('field_legacy_title')->value);
  if (!in_array($title, $titles, TRUE)) return FALSE;
  $author = $node->hasField('field_author') ? _dynetx_perspective_comparison_text((string) $node->get('field_author')->value) : '';
  $expected = _dynetx_perspective_comparison_text(implode(' ', array_filter([$title, $author, 'Read the perspective'], static fn(string $part): bool => $part !== '')));
  return $text($body) === $expected;
}

/** Attach the decision and all body dependencies, including suppressed cards. */
function _dynetx_perspective_preprocess(array &$variables): void {
  $variables['dyne_perspective_external_shell'] = FALSE;
  $variables['dyne_perspective_presentation_cache'] = ['#markup' => ''];
  $node = $variables['node'] ?? NULL;
  if (!$node instanceof \Drupal\node\NodeInterface || $node->bundle() !== 'perspective') return;
  $body = $variables['content']['field_body_text'] ?? [];
  $cache = \Drupal\Core\Cache\CacheableMetadata::createFromRenderArray($body);
  $cache->addCacheableDependency($node)->addCacheContexts(['user.permissions', 'languages:language_content']);
  $cache->applyTo($variables['dyne_perspective_presentation_cache']);
  $variables['dyne_perspective_external_shell'] = _dynetx_perspective_is_generated_external_card($node, (string) ($body['#text'] ?? ''));
}

/** Safe inline presentation for plain editorial labels that retain source <br>. */
function _dynetx_perspective_inline_label(string $value): array {
  $allowed = ['br', 'sup', 'sub', 'em', 'strong', 'b', 'i'];
  $markup = \Drupal\Component\Utility\Xss::filter($value, $allowed);
  // These labels support phrasing tags, never arbitrary attributes or links.
  $markup = preg_replace_callback('~<(/?)(br|sup|sub|em|strong|b|i)\b[^>]*>~i', static fn(array $match): string => '<' . $match[1] . strtolower($match[2]) . '>', $markup);
  return ['#markup' => $markup];
}

/** Keep legacy file-backed images available until an editor selects Media. */
function _dynetx_perspective_list_photo($story, \Drupal\Core\Cache\CacheableMetadata $cache): array {
  $photo = _dynetx_layout_media($story, 'field_story_image', $cache);
  if (!$photo && $story->hasField('field_image') && !$story->get('field_image')->isEmpty()) {
    $field = $story->get('field_image');
    $access = $field->access('view', NULL, TRUE);
    $cache->addCacheableDependency($access);
    if ($access->isAllowed() && ($file = $field->entity)) {
      $cache->addCacheableDependency($file);
      $photo = ['url' => \Drupal::service('file_url_generator')->generateString($file->getFileUri()), 'alt' => $field->alt ?? ''];
    }
  }
  return $photo;
}

/** Preserve both native and legacy destinations without changing precedence. */
function _dynetx_perspective_list_destination($story, \Drupal\Core\Cache\CacheableMetadata $cache): array {
  $result = ['url' => $story->toUrl()->toString(), 'external' => FALSE, 'action' => (string) t('Read more')];
  foreach (['field_external_link', 'field_link'] as $name) {
    if (!$story->hasField($name) || $story->get($name)->isEmpty()) continue;
    $field = $story->get($name);
    $access = $field->access('view', NULL, TRUE);
    $cache->addCacheableDependency($access);
    if (!$access->isAllowed()) continue;
    $link = $field->first(); $url = $link->getUrl();
    $result['url'] = $url->toString(); $result['external'] = $url->isExternal();
    // The dedicated native link owns its optional action label. Legacy link
    // titles may contain article headlines, so retain their Read more action.
    if ($name === 'field_external_link' && $link->title !== NULL && $link->title !== '') $result['action'] = $link->title;
    break;
  }
  return $result;
}

/** Build the featured story and source card groups from current native fields. */
function _dynetx_perspective_list_preprocess(array &$variables): void {
  $paragraph = $variables['paragraph'] ?? NULL;
  if (!$paragraph || $paragraph->bundle() !== 'dyne_perspective_list') return;
  $limit = $paragraph->hasField('field_perspective_limit') && !$paragraph->get('field_perspective_limit')->isEmpty()
    ? (int) $paragraph->get('field_perspective_limit')->value : 10;
  $visible = $paragraph->hasField('field_perspective_visible') && !$paragraph->get('field_perspective_visible')->isEmpty()
    ? (int) $paragraph->get('field_perspective_visible')->value : 6;
  $limit = min(100, max(1, $limit)); $visible = min(100, max(0, $visible));
  $cache = \Drupal\Core\Cache\CacheableMetadata::createFromObject($paragraph);
  $cache->addCacheTags(['node_list', 'node_list:perspective'])->addCacheContexts(['user.permissions', 'user.node_grants:view', 'languages:language_content']);
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $ids = $storage->getQuery()->accessCheck(TRUE)->condition('type', 'perspective')->condition('status', 1)->sort('field_story_date', 'DESC')->sort('nid', 'ASC')->range(0, $limit)->execute();
  $records = [];
  foreach ($storage->loadMultiple($ids) as $story) {
    $access = $story->access('view', NULL, TRUE);
    $cache->addCacheableDependency($story)->addCacheableDependency($access);
    if (!$access->isAllowed()) continue;
    $photo = _dynetx_perspective_list_photo($story, $cache);
    $destination = _dynetx_perspective_list_destination($story, $cache);
    $date = (string) ($story->get('field_story_date')->value ?? '');
    $timestamp = $date !== '' ? strtotime($date . ' UTC') : FALSE;
    $date_label = '';
    if ($timestamp !== FALSE) {
      // The established listing spells out April and abbreviates the other
      // month names; the featured story uses the full date in every month.
      $pattern = (int) gmdate('n', $timestamp) === 4 ? 'F j, Y' : 'M j, Y';
      $date_label = \Drupal::service('date.formatter')->format($timestamp, 'custom', $pattern, 'UTC');
    }
    $category = '';
    if ($story->hasField('field_story_category') && !$story->get('field_story_category')->isEmpty()) {
      $field_access = $story->get('field_story_category')->access('view', NULL, TRUE);
      $cache->addCacheableDependency($field_access);
      if ($field_access->isAllowed()) $category = (string) $story->get('field_story_category')->value;
    }
    $records[] = [
      'id' => $story->id(),
      'title' => _dynetx_perspective_inline_label(_dynetx_native_title($story)),
      'author' => _dynetx_perspective_inline_label((string) ($story->get('field_author')->value ?? '')),
      'category' => _dynetx_perspective_inline_label($category),
      'date' => $date_label,
      'featured_date' => $timestamp !== FALSE ? \Drupal::service('date.formatter')->format($timestamp, 'custom', 'F j, Y', 'UTC') : '',
      'photo' => $photo,
      'url' => $destination['url'],
      'external' => $destination['external'],
      'action' => $destination['action'],
      'summary' => _dynetx_layout_text($story, 'field_story_excerpt'),
    ];
  }
  $variables['dyne_perspective_featured'] = $records ? array_shift($records) : NULL;
  $variables['dyne_perspective_visible'] = array_slice($records, 0, $visible);
  $variables['dyne_perspective_additional'] = array_slice($records, $visible);
  $variables['dyne_perspective_collapse_id'] = 'dyne-perspectives-more-' . $paragraph->id();
  $variables['dyne_perspective_list_cache'] = ['#markup' => ''];
  $cache->applyTo($variables['dyne_perspective_list_cache']);
}
