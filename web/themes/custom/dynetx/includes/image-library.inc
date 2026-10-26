<?php

/**
 * @file
 * Native Media image placements with safe historical File-field fallback.
 */

use Drupal\Core\Cache\CacheableMetadata;

/** Return a viewable field and preserve the cacheability of denied access. */
function _dynetx_library_image_field($entity, string $name, CacheableMetadata $cache) {
  $cache->addCacheableDependency($entity);
  if (!$entity->hasField($name)) return NULL;
  $field = $entity->get($name);
  $access = $field->access('view', NULL, TRUE);
  $cache->addCacheableDependency($access);
  return $access->isAllowed() ? $field : NULL;
}

/** Resolve an image File only when its entity is viewable. */
function _dynetx_library_image_file($item, CacheableMetadata $cache): string {
  if (!$item) return '';
  if ($item->target_id) $cache->addCacheTags(['file:' . $item->target_id]);
  $file = $item->entity;
  if (!$file instanceof \Drupal\file\FileInterface) return '';
  $cache->addCacheableDependency($file);
  $access = $file->access('view', NULL, TRUE);
  $cache->addCacheableDependency($access);
  if (!$access->isAllowed() || !str_starts_with((string) $file->getMimeType(), 'image/')) return '';
  try {
    return \Drupal::service('file_url_generator')->generateString($file->getFileUri());
  }
  catch (\Throwable $error) {
    return '';
  }
}

/**
 * Resolve one placement without resurrecting deliberately cleared images.
 *
 * A populated native reference owns output immediately. The hidden readiness
 * flag keeps that ownership after an editor clears it. Only revisions which
 * have not adopted native Media may read their original image File field.
 */
function _dynetx_library_image($entity, string $media_field, string $legacy_field, CacheableMetadata $cache, string $alt_field = ''): array {
  $cache->addCacheableDependency($entity);
  // This is an internal ownership marker, not displayed content. Its policy
  // must still suppress legacy fallback if a field-access hook hides the flag.
  $ready = $entity->hasField('field_media_images_ready') && (bool) $entity->get('field_media_images_ready')->value;
  $has_native = $entity->hasField($media_field) && !$entity->get($media_field)->isEmpty();
  $result = ['url' => '', 'alt' => '', 'managed' => $ready || $has_native, 'ready' => $ready];
  $field = _dynetx_library_image_field($entity, $result['managed'] ? $media_field : $legacy_field, $cache);
  if (!$field || $field->isEmpty()) return $result;

  if (!$result['managed']) {
    $item = $field->first();
    $result['url'] = _dynetx_library_image_file($item, $cache);
    $result['alt'] = (string) ($item->alt ?? '');
    // Preserve the original banner template's descriptive default when an old
    // revision has no per-placement alternative text.
    if ($result['alt'] === '' && $entity->bundle() === 'dyne_alert_banner' && $legacy_field === 'field_banner_logo') $result['alt'] = 'Duchenne Awareness Day logo';
    return $result;
  }

  $reference = $field->first();
  if ($reference->target_id) $cache->addCacheTags(['media:' . $reference->target_id]);
  $media = $reference->entity;
  if (!$media instanceof \Drupal\media\MediaInterface) return $result;
  $cache->addCacheableDependency($media);
  $access = $media->access('view', NULL, TRUE);
  $cache->addCacheableDependency($access);
  if (!$access->isAllowed() || !$media->isPublished() || $media->getSource()->getPluginId() !== 'image') return $result;
  $source = $media->getSource()->getConfiguration()['source_field'] ?? '';
  if (!$source || !($image = _dynetx_library_image_field($media, $source, $cache)) || $image->isEmpty()) return $result;
  $item = $image->first();
  $result['url'] = _dynetx_library_image_file($item, $cache);
  $result['alt'] = (string) ($item->alt ?? '');
  if ($alt_field !== '' && $entity->hasField($alt_field)) {
    $placement_alt = _dynetx_library_image_field($entity, $alt_field, $cache);
    // An explicit empty placement value means decorative. Never replace it
    // with global Media alt text after an editor has intentionally cleared it.
    $result['alt'] = $placement_alt ? (string) ($placement_alt->value ?? '') : '';
  }
  return $result;
}

/** Supply image bindings only for the three newly migrated component types. */
function _dynetx_library_images_preprocess(array &$variables): void {
  $entity = $variables['paragraph'] ?? NULL;
  $fields = [
    'dyne_alert_banner' => ['field_banner_logo_media', 'field_banner_logo', 'field_banner_logo_alt'],
    'dyne_cta' => ['field_bg_media', 'field_bg', ''],
    'dyne_video' => ['field_poster_media', 'field_poster', ''],
  ];
  if (!$entity || !isset($fields[$entity->bundle()])) return;
  [$media, $legacy, $alt] = $fields[$entity->bundle()];
  $cache = CacheableMetadata::createFromObject($entity);
  $cache->addCacheContexts(['user.permissions', 'languages:language_content']);
  $variables['dyne_library_image'] = _dynetx_library_image($entity, $media, $legacy, $cache, $alt);
  $variables['dyne_library_image_cache'] = ['#markup' => ''];
  $cache->applyTo($variables['dyne_library_image_cache']);
}
