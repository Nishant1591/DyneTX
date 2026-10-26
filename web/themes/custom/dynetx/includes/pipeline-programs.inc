<?php

/**
 * @file
 * Render the pipeline from ordered, access-checked native Program records.
 */

use Drupal\Core\Cache\CacheableMetadata;

/** Read an allowed field while collecting both positive and negative access. */
function _dynetx_pipeline_field($entity, string $name, CacheableMetadata $cache) {
  $cache->addCacheableDependency($entity);
  if (!$entity->hasField($name)) return NULL;
  $field = $entity->get($name);
  $access = $field->access('view', NULL, TRUE);
  $cache->addCacheableDependency($access);
  return $access->isAllowed() ? $field : NULL;
}

/** Plain metadata remains escaped by Twig; no source HTML is interpreted. */
function _dynetx_pipeline_value($entity, string $name, CacheableMetadata $cache): string {
  $field = _dynetx_pipeline_field($entity, $name, $cache);
  return $field && !$field->isEmpty() ? (string) ($field->first()->value ?? '') : '';
}

/** Preserve text-format filtering and the filters' own bubbleable metadata. */
function _dynetx_pipeline_text($entity, string $name, CacheableMetadata $cache, bool $inline = FALSE): array {
  $field = _dynetx_pipeline_field($entity, $name, $cache);
  if (!$field || $field->isEmpty()) return [];
  $item = $field->first();
  $format = $item->format ?: 'plain_text';
  if ($configuration = \Drupal\filter\Entity\FilterFormat::load($format)) $cache->addCacheableDependency($configuration);
  $build = ['#type' => 'processed_text', '#text' => (string) $item->value, '#format' => $format, '#langcode' => $entity->language()->getId()];
  if ($inline) {
    // A visual editor wraps a heading in <p>. Adapt that block only after the
    // configured format has filtered it; the enclosing h1/h2 remains valid.
    require_once __DIR__ . '/structured-articles.inc';
    $build['#post_render'][] = static fn($markup, array $element): string => _dynetx_structured_inline_fragment((string) $markup);
  }
  return $build;
}

/** Resolve stored links without permitting active URL schemes in attributes. */
function _dynetx_pipeline_links($entity, string $name, CacheableMetadata $cache): array {
  $field = _dynetx_pipeline_field($entity, $name, $cache);
  $links = [];
  if (!$field) return $links;
  foreach ($field as $item) {
    if ($item->isEmpty()) continue;
    try {
      $url = $item->getUrl();
      if ($url->isExternal() && !in_array(strtolower((string) parse_url($url->getUri(), PHP_URL_SCHEME)), ['http', 'https'], TRUE)) continue;
      $generated = $url->toString(TRUE);
      $cache->addCacheableDependency($generated);
      $links[] = ['url' => $generated->getGeneratedUrl(), 'title' => (string) ($item->title ?? '')];
    }
    catch (\Throwable $error) {
      // A removed internal route must not break the rest of the pipeline.
    }
  }
  return $links;
}

/** Resolve the placement's cover while respecting Media/file field access. */
function _dynetx_pipeline_cover($entity, CacheableMetadata $cache, string $name = 'field_program_video_cover'): array {
  $field = _dynetx_pipeline_field($entity, $name, $cache);
  if (!$field || $field->isEmpty() || !($media = $field->first()->entity)) return [];
  $cache->addCacheableDependency($media);
  $access = $media->access('view', NULL, TRUE);
  $cache->addCacheableDependency($access);
  if (!$access->isAllowed() || (method_exists($media, 'isPublished') && !$media->isPublished())) return [];
  $source = $media->getSource()->getConfiguration()['source_field'] ?? '';
  if (!$source || !($image = _dynetx_pipeline_field($media, $source, $cache)) || $image->isEmpty() || !($file = $image->first()->entity)) return [];
  $cache->addCacheableDependency($file);
  $file_access = $file->access('view', NULL, TRUE);
  $cache->addCacheableDependency($file_access);
  if (!$file_access->isAllowed()) return [];
  $placement_alt = $name === 'field_program_video_cover' ? _dynetx_pipeline_value($entity, 'field_program_video_alt', $cache) : '';
  return ['url' => \Drupal::service('file_url_generator')->generateString($file->getFileUri()), 'alt' => $placement_alt !== '' ? $placement_alt : (string) ($image->first()->alt ?? '')];
}

/** Shared Program data for the chart, mobile panels and standalone pages. */
function _dynetx_pipeline_program_build($node, CacheableMetadata $cache, bool $is_listing = TRUE): array {
  $cache->addCacheableDependency($node);
  $cache->addCacheContexts(['user.permissions', 'user.node_grants:view', 'languages:language_content']);
  $access = $node->access('view', NULL, TRUE);
  $cache->addCacheableDependency($access);
  // Listings never expose unpublished records, including to privileged users.
  if ($node->bundle() !== 'pipeline_program' || (!$node->isPublished() && $is_listing) || !$access->isAllowed()) return [];
  $program = ['id' => $node->id()];
  foreach ([
    'title' => 'title', 'code' => 'field_program_code', 'short_name' => 'field_program_short_name',
    'disease' => 'field_disease_text', 'disease_short' => 'field_disease_short',
    'prevalence_us' => 'field_prevalence_us', 'prevalence_europe' => 'field_prevalence_eu',
    'target' => 'field_target', 'phase' => 'field_phase', 'status' => 'field_status',
    'anchor' => 'field_program_anchor',
  ] as $key => $field) $program[$key] = _dynetx_pipeline_value($node, $field, $cache);
  $program['display_name'] = $program['title'];
  if ($program['short_name'] !== '' && !str_contains(mb_strtolower($program['title']), mb_strtolower($program['short_name']))) $program['display_name'] .= ' (' . $program['short_name'] . ')';
  $program['aliases'] = [];
  if ($aliases = _dynetx_pipeline_field($node, 'field_program_aliases', $cache)) foreach ($aliases as $alias) {
    if (!$alias->isEmpty()) $program['aliases'][] = (string) $alias->value;
  }
  $progress = _dynetx_pipeline_field($node, 'field_program_progress', $cache);
  $program['progress'] = $progress && !$progress->isEmpty() ? max(0, min(100, (int) $progress->first()->value)) : NULL;
  $program['style'] = match (mb_strtoupper(trim($program['disease_short']))) {
    'DM1' => 'program-1', 'DMD' => 'program-2', 'FSHD' => 'program-3', 'POMPE', 'POMPE DISEASE' => 'program-4', default => 'program-other',
  };
  // Keep source anchor spelling (including upper case), but never emit an
  // arbitrary selector or duplicate unsafe attribute from an editorial value.
  if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $program['anchor'])) $program['anchor'] = '';
  foreach ([
    'heading' => 'field_program_heading', 'body' => 'field_body_text',
    'disease_heading' => 'field_disease_heading', 'overview' => 'field_disease_overview',
    'onset' => 'field_disease_onset', 'cause' => 'field_disease_cause',
    'symptoms' => 'field_disease_symptoms', 'unmet_need' => 'field_unmet_need',
  ] as $key => $field) $program[$key] = _dynetx_pipeline_text($node, $field, $cache, in_array($key, ['heading', 'disease_heading'], TRUE));
  $program['trials'] = _dynetx_pipeline_links($node, 'field_trial_links', $cache);
  $video = _dynetx_pipeline_links($node, 'field_program_video', $cache);
  $program['video'] = $video ? $video[0] + ['cover' => _dynetx_pipeline_cover($node, $cache)] : [];
  if ($program['video']) $program['video']['embed_id'] = _dynetx_pipeline_youtube_id($program['video']['url']);
  $program['image'] = _dynetx_pipeline_cover($node, $cache, 'field_program_image');
  $program['trial_class'] = $program['style'] === 'program-1' ? 'btn-blue-light' : 'btn-blue';
  $program['has_detail'] = (bool) ($program['heading'] || $program['body'] || $program['disease_heading'] || $program['overview'] || $program['onset'] || $program['cause'] || $program['symptoms'] || $program['unmet_need'] || $program['trials'] || $program['video']);
  // Newly added detail copy must become reachable without requiring an editor
  // to invent an anchor. Existing three source anchors remain unchanged.
  if ($program['anchor'] === '' && $program['has_detail']) $program['anchor'] = preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $program['code']) ? $program['code'] : 'program-' . $node->id();
  return $program;
}

/** Accept only a real YouTube host and a valid ID for the existing player. */
function _dynetx_pipeline_youtube_id(string $url): string {
  $parts = parse_url($url);
  if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], TRUE)) return '';
  $host = strtolower($parts['host'] ?? '');
  $path = trim($parts['path'] ?? '', '/');
  $id = '';
  if (in_array($host, ['youtu.be', 'www.youtu.be'], TRUE)) $id = explode('/', $path)[0];
  elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], TRUE)) {
    if ($path === 'watch') {
      parse_str($parts['query'] ?? '', $query);
      $id = is_string($query['v'] ?? NULL) ? $query['v'] : '';
    }
    elseif (preg_match('~^(?:embed|shorts|live)/([^/]+)$~D', $path, $match)) $id = $match[1];
  }
  return preg_match('/^[A-Za-z0-9_-]{11}$/D', $id) ? $id : '';
}

/** Merge only adjacent unanchored programs with the same disease. */
function _dynetx_pipeline_program_groups(array $programs): array {
  $groups = [];
  foreach ($programs as $program) {
    $last = array_key_last($groups);
    $previous = $last !== NULL ? $groups[$last]['program'] : NULL;
    if ($previous && $program['anchor'] === '' && $previous['anchor'] === '' && $program['disease'] === $previous['disease'] && $program['disease_short'] === $previous['disease_short'] && $program['prevalence_us'] === $previous['prevalence_us'] && $program['prevalence_europe'] === $previous['prevalence_europe']) {
      $groups[$last]['items'][] = $program;
    }
    else $groups[] = ['program' => $program, 'items' => [$program]];
  }
  return $groups;
}

/** Supply the native Paragraph without reading retained source HTML. */
function _dynetx_pipeline_programs_preprocess(array &$variables): void {
  $paragraph = $variables['paragraph'] ?? NULL;
  if (!$paragraph || $paragraph->bundle() !== 'dyne_program_pipeline') return;
  $cache = CacheableMetadata::createFromObject($paragraph);
  $cache->addCacheContexts(['user.permissions', 'user.node_grants:view', 'languages:language_content']);
  $programs = []; $seen = [];
  $anchors = array_fill_keys(['chart', 'programs', 'EXP', 'collapseEXP'], TRUE);
  if ($references = _dynetx_pipeline_field($paragraph, 'field_pipeline_programs', $cache)) foreach ($references as $reference) {
    if ($reference->target_id) $cache->addCacheTags(['node:' . $reference->target_id]);
    $node = $reference->entity;
    if (!$node || isset($seen[$node->id()])) continue;
    $seen[$node->id()] = TRUE;
    $program = _dynetx_pipeline_program_build($node, $cache);
    if (!$program) continue;
    if ($program['anchor'] !== '') {
      $suffix = 0;
      while (isset($anchors[$program['anchor']]) || isset($anchors['collapse' . $program['anchor']])) {
        $program['anchor'] = 'program-' . $node->id() . ($suffix ? '-' . $suffix : '');
        $suffix++;
      }
      $anchors[$program['anchor']] = $anchors['collapse' . $program['anchor']] = TRUE;
    }
    $programs[] = $program;
  }
  $variables['dyne_pipeline_programs'] = $programs;
  $variables['dyne_pipeline_groups'] = _dynetx_pipeline_program_groups($programs);
  $variables['dyne_pipeline_details'] = array_values(array_filter($programs, static fn(array $program): bool => $program['anchor'] !== '' && $program['has_detail']));
  $variables['dyne_pipeline_intro_heading'] = _dynetx_pipeline_text($paragraph, 'field_pipeline_intro_heading', $cache, TRUE);
  $variables['dyne_pipeline_intro'] = _dynetx_pipeline_text($paragraph, 'field_pipeline_intro', $cache);
  foreach (['heading' => 'field_pipeline_heading', 'expansion_heading' => 'field_expansion_heading', 'expansion_targets' => 'field_expansion_targets', 'expansion_title' => 'field_expansion_title'] as $key => $field) $variables['dyne_pipeline_' . $key] = _dynetx_pipeline_value($paragraph, $field, $cache);
  $variables['dyne_pipeline_expansion_body'] = _dynetx_pipeline_text($paragraph, 'field_expansion_body', $cache);
  $variables['dyne_pipeline_cache'] = ['#markup' => ''];
  $cache->applyTo($variables['dyne_pipeline_cache']);
  $variables['#attached']['library'][] = 'dynetx/global';
}

/** Standalone Program pages use exactly the chart's native content fields. */
function _dynetx_pipeline_program_node_preprocess(array &$variables): void {
  $node = $variables['node'] ?? NULL;
  if (!$node || $node->bundle() !== 'pipeline_program') return;
  $cache = CacheableMetadata::createFromObject($node);
  $variables['dyne_program'] = _dynetx_pipeline_program_build($node, $cache, FALSE);
  $variables['dyne_program_cache'] = ['#markup' => ''];
  $cache->applyTo($variables['dyne_program_cache']);
  $variables['#attached']['library'][] = 'dynetx/global';
}
