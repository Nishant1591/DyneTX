<?php

/**
 * @file
 * Entity-backed adapters retaining the original component markup.
 */

use Drupal\Core\Cache\CacheableMetadata;

/** Full source headlines are retained only while core title is its truncation. */
function _dynetx_native_title($node): string {
  $title = $node->label();
  if ($node->hasField('field_legacy_title') && mb_strlen($title) >= 254 && str_ends_with($title, '…')) {
    $full = (string) $node->get('field_legacy_title')->value;
    if (str_starts_with($full, mb_substr($title, 0, -1))) {
      return $full;
    }
  }
  return $title;
}

/** Resolves a link field without deriving or guessing external routes. */
function _dynetx_component_link($entity, string $field): string {
  return $entity->hasField($field) && !$entity->get($field)->isEmpty() ? $entity->get($field)->first()->getUrl()->toString() : '';
}

/** Uses Drupal field formatting for biographies instead of raw unfiltered text. */
function _dynetx_component_text($entity, string $field) {
  if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
    return '';
  }
  $item = $entity->get($field)->first();
  return check_markup((string) $item->value, $item->format ?: 'dyne_legacy_html', $entity->language()->getId());
}

/** Referenced file changes must also invalidate arrays built from Media. */
function _dynetx_component_media_cache($entity, string $field, CacheableMetadata $cache): void {
  if (!$entity->hasField($field) || !$media = $entity->get($field)->entity) {
    return;
  }
  $cache->addCacheableDependency($media);
  $source = $media->getSource()->getConfiguration()['source_field'] ?? '';
  if ($source && $media->hasField($source) && $file = $media->get($source)->entity) {
    $cache->addCacheableDependency($file);
  }
}

/** Keep changes to the configured text filtering policy cache-safe. */
function _dynetx_component_format_cache($entity, string $field, CacheableMetadata $cache): void {
  if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
    $format = $entity->get($field)->format ?: 'dyne_legacy_html';
    if ($configuration = \Drupal\filter\Entity\FilterFormat::load($format)) {
      $cache->addCacheableDependency($configuration);
    }
  }
}

/** Apply native values and cache dependencies after the general preprocess. */
function _dynetx_native_components(array &$variables): void {
  $paragraph = $variables['paragraph'];
  if (!$paragraph->hasField('field_native_components') || !$paragraph->get('field_native_components')->value) {
    return;
  }
  $cache = CacheableMetadata::createFromObject($paragraph);
  $cache->addCacheContexts(['user.permissions', 'user.node_grants:view', 'languages:language_content']);
  $variables['dyne_items'] = [];
  $variables['dyne_rows'] = [];
  $variables['dyne_sections'] = [];
  if ($paragraph->bundle() === 'dyne_team_grid') {
    foreach ($paragraph->get('field_team_members')->referencedEntities() as $member) {
      $access = $member->access('view', NULL, TRUE);
      $cache->addCacheableDependency($member)->addCacheableDependency($access);
      if (!$access->isAllowed()) {
        continue;
      }
      _dynetx_component_media_cache($member, 'field_photo', $cache);
      _dynetx_component_format_cache($member, 'field_body_text', $cache);
      $variables['dyne_items'][] = [
        'name' => _dynetx_native_title($member),
        'role' => (string) $member->get('field_role')->value,
        'photo' => _dynetx_native_media_url($member, 'field_photo'),
        'bio' => _dynetx_component_text($member, 'field_body_text'),
      ];
    }
  }
  elseif (in_array($paragraph->bundle(), ['dyne_quote_carousel', 'dyne_careers_quotes'], TRUE)) {
    $variables['dyne_archived_quotes'] = [];
    foreach (['field_quotes' => 'dyne_items', 'field_archived_quotes' => 'dyne_archived_quotes'] as $field => $target) {
      if (!$paragraph->hasField($field)) {
        continue;
      }
      foreach ($paragraph->get($field)->referencedEntities() as $quote) {
      $access = $quote->access('view', NULL, TRUE);
      $cache->addCacheableDependency($quote)->addCacheableDependency($access);
      if ($access->isAllowed()) {
        _dynetx_component_media_cache($quote, 'field_speaker_image', $cache);
        $variables[$target][] = ['author' => $quote->label(), 'quote' => trim(strip_tags((string) $quote->get('field_quote_text')->value)), 'photo' => _dynetx_native_media_url($quote, 'field_speaker_image'), 'width' => min(12, max(1, (int) ($quote->get('field_quote_width')->value ?: 7)))];
      }
      }
    }
    if ($paragraph->bundle() === 'dyne_careers_quotes') {
      _dynetx_component_format_cache($paragraph, 'field_body_text', $cache);
      $variables['dyne_quotes_intro'] = _dynetx_component_text($paragraph, 'field_body_text');
    }
  }
  elseif ($paragraph->bundle() === 'dyne_news_list') {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $query = $storage->getQuery()->accessCheck(TRUE)->condition('type', 'news')->condition('status', 1)->sort('field_news_date', 'DESC')->sort('nid', 'DESC');
    $year = (int) $paragraph->get('field_news_year')->value;
    if ($year > 1900 && $year < 3000) {
      $query->condition('field_news_date', sprintf('%04d-01-01T00:00:00', $year), '>=')->condition('field_news_date', sprintf('%04d-01-01T00:00:00', $year + 1), '<');
    }
    $limit = (int) $paragraph->get('field_news_limit')->value;
    if ($limit > 0) {
      $query->range(0, $limit);
    }
    $cache->addCacheTags(\Drupal::entityTypeManager()->getDefinition('node')->getListCacheTags());
    foreach ($storage->loadMultiple($query->execute()) as $article) {
      $cache->addCacheableDependency($article);
      _dynetx_component_media_cache($article, 'field_news_file', $cache);
      $type = $article->get('field_news_type')->value;
      $url = $type === 'url' ? _dynetx_component_link($article, 'field_external_url') : ($type === 'file' ? _dynetx_native_media_url($article, 'field_news_file') : '');
      $url = $url ?: $article->toUrl('canonical')->toString();
      $date = $article->get('field_news_date')->value;
      $variables['dyne_items'][] = [
        'title' => _dynetx_native_title($article),
        'url' => $url,
        'date' => $date ? \Drupal::service('date.formatter')->format(strtotime($date . ' UTC'), 'custom', 'F d, Y', 'UTC') : '',
      ];
    }
  }
  elseif ($paragraph->hasField('field_component_items')) {
    foreach ($paragraph->get('field_component_items')->referencedEntities() as $item) {
      $cache->addCacheableDependency($item);
      if ($item->bundle() === 'dyne_card_item') {
        _dynetx_component_media_cache($item, 'field_item_icon', $cache);
        $variables['dyne_items'][] = ['title' => $item->get('field_heading')->value, 'text' => $item->get('field_item_text')->value, 'icon' => _dynetx_native_media_url($item, 'field_item_icon'), 'style' => $item->get('field_style')->value, 'link' => _dynetx_component_link($item, 'field_link')];
      }
      elseif ($item->bundle() === 'dyne_pipeline_row') {
        $row = ['url' => _dynetx_component_link($item, 'field_link')];
        foreach (['disease', 'prevalence', 'target', 'phase', 'status'] as $key) {
          $row[$key] = $item->get('field_row_' . $key)->value;
        }
        $variables['dyne_rows'][] = $row;
      }
      elseif ($item->bundle() === 'dyne_text_section') {
        $variables['dyne_sections'][] = ['title' => $item->get('field_heading')->value, 'body' => $item->get('field_item_text')->value];
      }
    }
  }
  $variables['dyne_component_cache'] = ['#markup' => ''];
  $cache->applyTo($variables['dyne_component_cache']);
}
