<?php

/**
 * @file
 * Entity-backed presentation; archive fixtures are never read during requests.
 */

/**
 * Resolve the original local asset URL from a native Media reference field.
 */
function _dynetx_native_media_url($entity, string $field): string {
  if (!$entity || !$entity->hasField($field) || $entity->get($field)->isEmpty()) {
    return '';
  }
  $media = $entity->get($field)->entity;
  if (!$media instanceof \Drupal\media\MediaInterface) {
    return '';
  }
  $source_field = $media->getSource()->getConfiguration()['source_field'] ?? '';
  if (!$source_field || !$media->hasField($source_field)) {
    return '';
  }
  if ($media->bundle() === 'remote_video') {
    return (string) $media->get($source_field)->value;
  }
  $file = $media->get($source_field)->entity;
  return $file instanceof \Drupal\file\FileInterface ? \Drupal::service('file_url_generator')->generateString($file->getFileUri()) : '';
}

/**
 * Return the established theme metadata shape from the loaded node alone.
 */
function _dynetx_entity_metadata($node): array {
  if (!$node instanceof \Drupal\node\NodeInterface || !$node->hasField('field_native_presentation') || !$node->get('field_native_presentation')->value) {
    return [];
  }
  $value = static fn(string $name): string => $node->hasField($name) ? (string) ($node->get($name)->value ?? '') : '';
  return [
    'source_id' => (int) $value('field_wp_source_id'),
    'title' => $node->label(),
    'description' => $value('field_seo_description'),
    'seo_title' => $value('field_seo_title'),
    'classes' => preg_split('/\s+/', trim($value('field_body_class')), -1, PREG_SPLIT_NO_EMPTY),
    'hero' => [
      'desktop' => _dynetx_native_media_url($node, 'field_hero_image'),
      'mobile' => _dynetx_native_media_url($node, 'field_hero_mobile_image'),
      'overlay' => _dynetx_native_media_url($node, 'field_hero_overlay_image'),
      'line1' => $value('field_hero_first_line'),
      'line2' => $value('field_hero_second_line'),
      'third_level' => (bool) $value('field_hero_third_level'),
      'classes' => $value('field_hero_classes'),
    ],
  ];
}

/**
 * Attach node, Media and File dependencies to the render array being themed.
 *
 * Call for page/html/node preprocess variables. Rendering the empty dependency
 * in Twig makes tags bubble even when fields are consumed as scalar strings.
 */
function _dynetx_native_metadata_cache($node): array {
  $build = ['#markup' => ''];
  if (!$node instanceof \Drupal\node\NodeInterface) {
    return $build;
  }
  $cache = \Drupal\Core\Cache\CacheableMetadata::createFromObject($node);
  foreach (['field_hero_image', 'field_hero_mobile_image', 'field_hero_overlay_image', 'field_photo', 'field_news_file', 'field_story_image'] as $name) {
    if ($node->hasField($name) && ($media = $node->get($name)->entity)) {
      $cache->addCacheableDependency($media);
      $source = $media->getSource()->getConfiguration()['source_field'] ?? '';
      if ($source && ($file = $media->get($source)->entity)) {
        $cache->addCacheableDependency($file);
      }
    }
  }
  $cache->applyTo($build);
  return $build;
}

/**
 * Supply profile and news detail presentation from native fields.
 */
function _dynetx_native_node_details($node): array {
  if (!$node instanceof \Drupal\node\NodeInterface) {
    return [];
  }
  $details = [];
  if ($node->bundle() === 'leader') {
    $details['photo'] = ['url' => _dynetx_native_media_url($node, 'field_photo'), 'alt' => ''];
    if (($media = $node->get('field_photo')->entity) && $media->bundle() === 'image') {
      $field = $media->getSource()->getConfiguration()['source_field'];
      $details['photo']['alt'] = $media->get($field)->alt ?? '';
    }
    $details['position_info'] = $node->get('field_position_info')->value ?? '';
  }
  elseif ($node->bundle() === 'news') {
    $details['date'] = $node->get('field_news_date')->value ?? '';
    $details['type'] = $node->get('field_news_type')->value ?: 'html';
    $details['url'] = '';
    if ($details['type'] === 'url' && !$node->get('field_external_url')->isEmpty()) {
      $details['url'] = $node->get('field_external_url')->first()->getUrl()->toString();
    }
    elseif ($details['type'] === 'file') {
      $details['url'] = _dynetx_native_media_url($node, 'field_news_file');
    }
  }
  return $details;
}
