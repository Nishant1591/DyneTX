<?php

/** Native field rendering; no JSON fixtures or legacy database access. */
function _dynetx_layout_text($entity, string $field): array {
  if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) return [];
  $item = $entity->get($field)->first();
  return ['#type' => 'processed_text', '#text' => $item->value, '#format' => $item->format ?: 'dyne_legacy_html'];
}

function _dynetx_layout_media($entity, string $field, \Drupal\Core\Cache\CacheableMetadata $cache): array {
  if (!$entity->hasField($field) || $entity->get($field)->isEmpty() || !($media = $entity->get($field)->entity)) return [];
  $cache->addCacheableDependency($media);
  $source = $media->getSource()->getConfiguration()['source_field'];
  $item = $media->get($source)->first();
  if ($media->bundle() === 'remote_video') return ['render' => \Drupal::entityTypeManager()->getViewBuilder('media')->view($media)];
  if (!$item || !($file = $item->entity)) return [];
  $cache->addCacheableDependency($file);
  return ['url' => \Drupal::service('file_url_generator')->generateString($file->getFileUri()), 'alt' => $item->alt ?? '', 'render' => \Drupal::entityTypeManager()->getViewBuilder('media')->view($media)];
}

function _dynetx_native_lists_preprocess(array &$variables): void {
  $p = $variables['paragraph']; $bundle = $p->bundle();
  if (!in_array($bundle, ['dyne_policy_list', 'dyne_perspective_list'], TRUE)) return;
  $cache = \Drupal\Core\Cache\CacheableMetadata::createFromObject($p);
  $variables['dyne_records'] = [];
  if ($bundle === 'dyne_policy_list') {
    foreach ($p->get('field_policies')->referencedEntities() as $policy) {
      $access = $policy->access('view', NULL, TRUE); $cache->addCacheableDependency($access)->addCacheableDependency($policy);
      if (!$access->isAllowed()) continue;
      $variables['dyne_records'][] = ['title' => $policy->label(), 'anchor' => $policy->get('field_policy_anchor')->value, 'date' => $policy->get('field_policy_updated')->value, 'body' => _dynetx_layout_text($policy, 'field_body_text')];
    }
  }
  else {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()->accessCheck(TRUE)->condition('type', 'perspective')->condition('status', 1)->sort('field_story_date', 'DESC')->sort('nid', 'ASC')->execute();
    $cache->addCacheTags(['node_list', 'node_list:perspective'])->addCacheContexts(['user.permissions', 'user.node_grants:view', 'languages:language_content']);
    foreach ($storage->loadMultiple($ids) as $story) {
      $cache->addCacheableDependency($story);
      $photo = _dynetx_layout_media($story, 'field_story_image', $cache);
      // The migration preserves the existing field_image file for compatibility.
      if (!$photo && $story->hasField('field_image') && ($file = $story->get('field_image')->entity)) {
        $cache->addCacheableDependency($file); $photo = ['url' => \Drupal::service('file_url_generator')->generateString($file->getFileUri()), 'alt' => $story->get('field_image')->alt ?? ''];
      }
      $url = $story->toUrl()->toString();
      if ($story->hasField('field_external_link') && !$story->get('field_external_link')->isEmpty()) $url = $story->get('field_external_link')->first()->getUrl()->toString();
      elseif ($story->hasField('field_link') && !$story->get('field_link')->isEmpty()) $url = $story->get('field_link')->first()->getUrl()->toString();
      $variables['dyne_records'][] = ['title' => _dynetx_native_title($story), 'author' => $story->get('field_author')->value ?? '', 'photo' => $photo, 'url' => $url, 'summary' => _dynetx_layout_text($story, 'field_story_excerpt')];
    }
    $variables['dyne_list_intro'] = _dynetx_layout_text($p, 'field_list_intro');
    $variables['dyne_list_outro'] = _dynetx_layout_text($p, 'field_list_outro');
  }
  $variables['dyne_list_cache'] = ['#markup' => '']; $cache->applyTo($variables['dyne_list_cache']);
}

function _dynetx_native_layout_preprocess(array &$variables): void {
  $p = $variables['paragraph'];
  if (!str_starts_with($p->bundle(), 'dyne_acf_')) return;
  $cache = \Drupal\Core\Cache\CacheableMetadata::createFromObject($p);
  $get = static fn($key) => $p->hasField('field_layout_' . $key) ? ($p->get('field_layout_' . $key)->value ?? '') : '';
  $layout = ['type' => $p->bundle(), 'heading' => $get('heading'), 'caption' => $get('caption'), 'anchor' => $get('anchor'), 'alignment' => $get('alignment'), 'background' => $get('background'), 'gene' => $get('gene'), 'hide_dna' => $get('hide_dna'), 'show_form' => $get('show_form'), 'disable_accordion' => $get('disable_accordion'), 'sidebar' => $get('sidebar')];
  foreach (['body', 'symptoms', 'treatment', 'sidebar_body'] as $field) $layout[$field] = _dynetx_layout_text($p, 'field_layout_' . $field);
  foreach (['image', 'background_image', 'video'] as $field) $layout[$field] = _dynetx_layout_media($p, 'field_layout_' . $field, $cache);
  foreach (['link', 'links', 'map'] as $field) {
    $layout[$field] = [];
    if ($p->hasField('field_layout_' . $field)) foreach ($p->get('field_layout_' . $field) as $link) {
      if ($link->uri) $layout[$field][] = ['url' => $link->getUrl()->toString(), 'title' => $link->title ?: $link->uri];
    }
  }
  $layout['items'] = [];
  if ($p->hasField('field_layout_items')) foreach ($p->get('field_layout_items')->referencedEntities() as $item) {
    $cache->addCacheableDependency($item);
    $link = $item->get('field_layout_link')->first();
    $layout['items'][] = ['heading' => $item->get('field_layout_heading')->value, 'body' => _dynetx_layout_text($item, 'field_layout_body'), 'image' => _dynetx_layout_media($item, 'field_layout_image', $cache), 'caption' => $item->get('field_layout_caption')->value, 'link' => $link && $link->uri ? ['url' => $link->getUrl()->toString(), 'title' => $link->title ?: 'Read more'] : []];
  }
  $layout['records'] = [];
  $nodes = [];
  foreach (['team', 'quotes'] as $field) if ($p->hasField('field_layout_' . $field)) $nodes = $p->get('field_layout_' . $field)->referencedEntities();
  if (in_array($p->bundle(), ['dyne_acf_latest_news', 'dyne_acf_press_releases', 'dyne_acf_leadership'], TRUE)) {
    $storage = \Drupal::entityTypeManager()->getStorage('node'); $leader = $p->bundle() === 'dyne_acf_leadership';
    $query = $storage->getQuery()->accessCheck(TRUE)->condition('status', 1)->condition('type', $leader ? 'leader' : 'news');
    if ($leader) {
      if (!$p->get('field_layout_category')->isEmpty()) $query->condition('field_leader_type', $p->get('field_layout_category')->target_id);
      $query->sort('field_weight', 'ASC');
    }
    else $query->sort('field_news_date', 'DESC')->range(0, max(1, min(100, (int) ($get('count') ?: 2))));
    $nodes = $storage->loadMultiple($query->execute());
    $cache->addCacheTags(['node_list'])->addCacheContexts(['user.permissions', 'user.node_grants:view']);
  }
  foreach ($nodes as $node) {
    $access = $node->access('view', NULL, TRUE); $cache->addCacheableDependency($node)->addCacheableDependency($access);
    if (!$access->isAllowed()) continue;
    $url = $node->toUrl()->toString();
    if ($node->bundle() === 'news') {
      $type = $node->get('field_news_type')->value;
      $url = ($type === 'url' ? _dynetx_component_link($node, 'field_external_url') : ($type === 'file' ? _dynetx_native_media_url($node, 'field_news_file') : '')) ?: $url;
      if ($node->get('field_news_file')->entity) $cache->addCacheableDependency($node->get('field_news_file')->entity);
    }
    $layout['records'][] = ['title' => _dynetx_native_title($node), 'url' => $url, 'body' => _dynetx_layout_text($node, $node->bundle() === 'quote' ? 'field_quote_text' : 'field_body_text'), 'image' => _dynetx_layout_media($node, $node->bundle() === 'quote' ? 'field_speaker_image' : 'field_photo', $cache), 'role' => $node->hasField('field_role') ? $node->get('field_role')->value : '', 'date' => $node->hasField('field_news_date') ? substr($node->get('field_news_date')->value ?? '', 0, 10) : ''];
  }
  if ($p->bundle() === 'dyne_acf_open_positions') {
    $layout['jobs'] = _dynetx_job_groups()['groups'];
    $cache->addCacheTags(['node_list', 'taxonomy_term_list'])->addCacheContexts(['user.permissions', 'user.node_grants:view']);
  }
  $variables['dyne_layout'] = $layout;
  $variables['dyne_layout_cache'] = ['#markup' => '']; $cache->applyTo($variables['dyne_layout_cache']);
}
