<?php

/**
 * @file
 * Native editorial schema and additive archive backfill. No request-time fixtures.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\taxonomy\Entity\Term;

/**
 * Ensure a field without changing an existing storage type or editor content.
 */
function dyne_native_field(string $entity_type, string $bundle, string $name, string $type, string $label, array $storage_settings = [], array $settings = [], int $cardinality = 1): void {
  $storage = FieldStorageConfig::loadByName($entity_type, $name);
  if (!$storage) {
    $storage = FieldStorageConfig::create(['field_name' => $name, 'entity_type' => $entity_type, 'type' => $type, 'cardinality' => $cardinality, 'settings' => $storage_settings]);
    $storage->save();
  }
  elseif ($storage->getType() !== $type) {
    throw new RuntimeException("Refusing to change storage type for $entity_type.$name");
  }
  if (!FieldConfig::loadByName($entity_type, $bundle, $name)) {
    FieldConfig::create(['field_storage' => $storage, 'bundle' => $bundle, 'label' => $label, 'required' => FALSE, 'settings' => $settings])->save();
  }
  $form = EntityFormDisplay::load("$entity_type.$bundle.default") ?: EntityFormDisplay::create(['targetEntityType' => $entity_type, 'bundle' => $bundle, 'mode' => 'default', 'status' => TRUE]);
  $widget = match ($type) {
    'text', 'text_long' => $type === 'text' ? 'text_textfield' : 'text_textarea',
    'string_long' => 'string_textarea', 'integer' => 'number',
    'boolean' => 'boolean_checkbox', 'datetime' => 'datetime_default',
    'list_string' => 'options_select', 'link' => 'link_default',
    'image' => 'image_image', 'file' => 'file_generic',
    'entity_reference' => ($storage_settings['target_type'] ?? $storage->getSetting('target_type')) === 'media' ? 'media_library_widget' : 'entity_reference_autocomplete',
    default => 'string_textfield',
  };
  if (!$form->getComponent($name)) {
    $form->setComponent($name, ['type' => $widget, 'weight' => 20])->save();
  }
  // Existing Twig owns placement. Avoid appending metadata to rendered content.
  $view = EntityViewDisplay::load("$entity_type.$bundle.default");
  if ($view && $entity_type === 'node') {
    $view->removeComponent($name)->save();
  }
}

/**
 * Create a Media reference field, retaining existing bundle machine names.
 */
function dyne_native_media_field(string $entity, string $bundle, string $name, string $label, array $bundles = ['image']): void {
  dyne_native_field($entity, $bundle, $name, 'entity_reference', $label, ['target_type' => 'media'], ['handler' => 'default:media', 'handler_settings' => ['target_bundles' => array_combine($bundles, $bundles)]]);
}

/**
 * Enable core Media and create field/form configurations before backfill.
 */
function dyne_native_schema(): void {
  \Drupal::service('module_installer')->install(['media', 'media_library', 'datetime', 'options']);
  foreach ([
    'image' => ['Image', 'image', 'field_media_image', 'image', 'png gif jpg jpeg webp avif'],
    'document' => ['Document', 'file', 'field_media_document', 'file', 'pdf doc docx txt xls xlsx ppt pptx'],
    'video' => ['Video', 'video_file', 'field_media_video_file', 'file', 'mp4 webm mov'],
    'remote_video' => ['Remote video', 'oembed:video', 'field_media_oembed_video', 'string', ''],
  ] as $id => [$label, $source, $field, $field_type, $extensions]) {
    $type = MediaType::load($id);
    if (!$type) {
      $type = MediaType::create(['id' => $id, 'label' => $label, 'source' => $source, 'source_configuration' => ['source_field' => $field], 'field_map' => []]);
      $type->save();
    }
    $settings = $extensions ? ['file_extensions' => $extensions, 'file_directory' => 'media/' . $id] : [];
    if ($field_type === 'image') {
      $settings += ['alt_field' => TRUE, 'alt_field_required' => FALSE, 'title_field' => FALSE];
    }
    dyne_native_field('media', $id, $field, $field_type, $label, $field_type === 'string' ? ['max_length' => 2048] : [], $settings);
    dyne_native_field('media', $id, 'field_wp_attachment_id', 'integer', 'Original WordPress attachment ID');
    dyne_native_field('media', $id, 'field_media_caption', 'string_long', 'Caption');
    dyne_native_field('media', $id, 'field_media_description', 'string_long', 'Description');
    $view = EntityViewDisplay::load("media.$id.default") ?: EntityViewDisplay::create(['targetEntityType' => 'media', 'bundle' => $id, 'mode' => 'default', 'status' => TRUE]);
    $type->getSource()->prepareViewDisplay($type, $view);
    $view->save();
    dyne_native_media_displays($type);
  }
  $bundles = ['dyne_page', 'job', 'leader', 'perspective', 'news'];
  foreach ($bundles as $bundle) {
    foreach ([
      'field_wp_source_id' => ['integer', 'Original WordPress record ID'],
      'field_native_presentation' => ['boolean', 'Use branded page presentation'],
      'field_hero_first_line' => ['text', 'Hero first line'],
      'field_hero_second_line' => ['text', 'Hero second line'],
      'field_hero_third_level' => ['boolean', 'Compact interior hero'],
      'field_hero_classes' => ['string', 'Hero CSS classes'],
      'field_body_class' => ['string_long', 'Body CSS classes'],
      'field_seo_title' => ['string_long', 'Search result title'],
      'field_seo_description' => ['string_long', 'Search result description'],
    ] as $name => [$type, $label]) {
      dyne_native_field('node', $bundle, $name, $type, $label);
    }
    foreach (['field_hero_image' => 'Hero desktop image', 'field_hero_mobile_image' => 'Hero mobile image', 'field_hero_overlay_image' => 'Hero overlay image'] as $name => $label) {
      dyne_native_media_field('node', $bundle, $name, $label);
    }
  }
  foreach (['field_news_date' => ['datetime', 'Publication date'], 'field_external_id' => ['string', 'News feed ID'], 'field_external_url' => ['link', 'External news URL']] as $name => [$type, $label]) {
    dyne_native_field('node', 'news', $name, $type, $label, [], $type === 'link' ? ['link_type' => 17, 'title' => 0] : []);
  }
  dyne_native_field('node', 'news', 'field_news_type', 'list_string', 'News content type', ['allowed_values' => ['html' => 'HTML page', 'url' => 'External URL', 'file' => 'Document']]);
  dyne_native_media_field('node', 'news', 'field_news_file', 'News document', ['document']);
  dyne_native_field('node', 'leader', 'field_position_info', 'string_long', 'Position details');
  dyne_native_field('node', 'leader', 'field_weight', 'integer', 'Presentation order');
  dyne_native_media_field('node', 'leader', 'field_photo', 'Portrait');
  $role = FieldConfig::loadByName('node', 'leader', 'field_role');
  if ($role && $role->label() !== 'Position') {
    $role->setLabel('Position')->save();
  }
  foreach (['field_department' => ['Department', 'department'], 'field_location' => ['Job location', 'job_location']] as $field => [$label, $vid]) {
    dyne_native_field('node', 'job', $field, 'entity_reference', $label, ['target_type' => 'taxonomy_term'], ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => [$vid => $vid]]]);
  }
  dyne_native_field('node', 'job', 'field_greenhouse_id', 'string', 'Greenhouse public job ID');
  dyne_native_field('node', 'job', 'field_greenhouse_internal_id', 'string', 'Greenhouse internal job ID');
  dyne_native_field('node', 'job', 'field_application_link', 'link', 'Application URL', [], ['link_type' => 16, 'title' => 0]);
  foreach (['field_author' => ['string', 'Author'], 'field_subtitle' => ['string_long', 'Subtitle'], 'field_story_date' => ['datetime', 'Publication date'], 'field_external_link' => ['link', 'External story link']] as $name => [$type, $label]) {
    dyne_native_field('node', 'perspective', $name, $type, $label, [], $type === 'link' ? ['link_type' => 17, 'title' => 0] : []);
  }
  dyne_native_media_field('node', 'perspective', 'field_story_image', 'Story image');
  dyne_native_field('node', 'perspective', 'field_story_excerpt', 'text_long', 'Story excerpt');
  dyne_native_editor_displays();
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}

/**
 * Give editors one active control for each rendered value; retain legacy data.
 */
function dyne_native_editor_displays(): void {
  $superseded = [
    'job' => ['field_department_text', 'field_department_group', 'field_location_text', 'field_apply_url'],
    'leader' => ['field_bio', 'field_image'],
    'news' => ['field_date_text', 'field_source_url', 'field_link'],
    'perspective' => ['field_image', 'field_link'],
  ];
  foreach ($superseded as $bundle => $names) {
    $form = EntityFormDisplay::load("node.$bundle.default");
    if ($form) {
      foreach ($names as $name) {
        $form->removeComponent($name);
      }
      $form->save();
    }
  }
  $descriptions = [
    'field_body_text' => 'Main article, biography, or job content. This is the body shown on the website.',
    'field_hero_first_line' => 'First heading line shown in this page’s hero banner.',
    'field_hero_second_line' => 'Second heading line shown in this page’s hero banner.',
    'field_hero_image' => 'Desktop hero image. Choose an image from the Media Library.',
    'field_hero_mobile_image' => 'Hero image used on smaller screens. Choose the portrait crop from the Media Library.',
    'field_hero_overlay_image' => 'Optional transparent decoration layered over the hero background.',
    'field_native_presentation' => 'Use this page’s hero and presentation settings in the branded page template.',
    'field_news_date' => 'Publication date used in chronological news listings.',
    'field_news_type' => 'Choose whether news listings link to the local article, an external URL, or a document.',
    'field_external_url' => 'Destination used when the news content type is External URL.',
    'field_news_file' => 'Media document used when the news content type is Document.',
    'field_external_id' => 'Original feed identifier used to prevent duplicate news records.',
    'field_department' => 'Department used in the careers listing and department filter.',
    'field_location' => 'Location used in the careers listing and location filter.',
    'field_application_link' => 'Application destination used by the job page.',
    'field_greenhouse_id' => 'Public Greenhouse job ID used for feed reconciliation and application URLs.',
    'field_role' => 'Position shown on this person’s profile and team cards.',
    'field_photo' => 'Portrait shown on this profile and team cards. Board and advisory profiles may have no portrait.',
    'field_position_info' => 'Additional credentials or position information shown with the profile.',
    'field_leader_type' => 'Team category used to group leadership, board, and advisory profiles.',
    'field_weight' => 'Order within team listings; lower numbers appear first.',
    'field_story_image' => 'Image shown on perspective cards.',
    'field_author' => 'Byline shown on perspective cards and the article.',
    'field_subtitle' => 'Optional story subtitle or time information.',
    'field_story_date' => 'Publication date used to sort perspectives.',
    'field_story_excerpt' => 'Optional introduction shown on perspective cards; leave blank when no excerpt is intended.',
    'field_external_link' => 'Optional external destination for the perspective card. Leave blank to open this article.',
  ];
  foreach (['dyne_page', 'job', 'leader', 'news', 'perspective'] as $bundle) {
    foreach ($descriptions as $name => $description) {
      if ($field = FieldConfig::loadByName('node', $bundle, $name)) {
        if ($field->getDescription() !== $description) {
          $field->setDescription($description)->save();
        }
      }
    }
  }
}

/**
 * Make programmatically created Media types usable in the library and editor.
 */
function dyne_native_media_displays(MediaType $type): void {
  $form = EntityFormDisplay::load('media.' . $type->id() . '.default');
  if ($form && !$form->getComponent('name')) {
    $form->setComponent('name', ['type' => 'string_textfield', 'weight' => -10])->save();
  }
  \Drupal\media_library\MediaLibraryDisplayManager::configureFormDisplay($type);
  \Drupal\media_library\MediaLibraryDisplayManager::configureViewDisplay($type);
}

/**
 * A term lookup is case-insensitive; preserve full labels from the source.
 */
function dyne_native_term(string $vid, string $name): ?int {
  $name = trim($name);
  if ($name === '') {
    return NULL;
  }
  $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $matches = $storage->loadByProperties(['vid' => $vid, 'name' => $name]);
  $term = $matches ? reset($matches) : Term::create(['vid' => $vid, 'name' => $name]);
  if ($term->isNew()) {
    $term->save();
  }
  return (int) $term->id();
}

/**
 * Register local files as native Media, returning existing entities on reruns.
 */
function dyne_native_media_for_uri(string $uri, array $attachment = []): ?Media {
  if (!str_starts_with($uri, 'public://legacy/') || str_contains($uri, '..') || !is_file($uri)) {
    return NULL;
  }
  $mime = \Drupal::service('file.mime_type.guesser')->guessMimeType($uri);
  $bundle = str_starts_with($mime, 'image/') ? 'image' : (str_starts_with($mime, 'video/') ? 'video' : 'document');
  $source_field = ['image' => 'field_media_image', 'video' => 'field_media_video_file', 'document' => 'field_media_document'][$bundle];
  $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
  $file = $files ? reset($files) : File::create(['uri' => $uri, 'filename' => basename($uri), 'status' => 1, 'uid' => 1]);
  if ($file->isNew()) {
    $file->save();
  }
  $storage = \Drupal::entityTypeManager()->getStorage('media');
  $ids = $storage->getQuery()->accessCheck(FALSE)->condition('bundle', $bundle)->condition($source_field . '.target_id', $file->id())->execute();
  if ($ids) {
    return $storage->load(reset($ids));
  }
  $item = ['target_id' => $file->id()];
  if ($bundle === 'image') {
    // Empty source alt remains empty; do not invent claims about the image.
    $item['alt'] = $attachment['alt'] ?? '';
  }
  $media = Media::create([
    'bundle' => $bundle, 'name' => mb_substr($attachment['title'] ?? basename($uri), 0, 255),
    'uid' => 1, 'status' => 1, $source_field => $item,
    'field_wp_attachment_id' => $attachment['id'] ?? NULL,
    'field_media_caption' => $attachment['caption'] ?? '',
    'field_media_description' => $attachment['description'] ?? '',
  ]);
  if (!empty($attachment['created']) && ($date = strtotime($attachment['created'] . ' UTC')) > 0) {
    $media->setCreatedTime($date);
  }
  $media->save();
  return $media;
}

/**
 * Resolve source URLs only to existing local public files; never fetch a URL.
 */
function dyne_native_media_for_url(string $url): ?Media {
  $path = rawurldecode(parse_url(html_entity_decode($url), PHP_URL_PATH) ?: '');
  if (str_starts_with($path, '/themes/custom/dynetx/images/')) {
    // Theme portraits were content in the source template. Copy only verified
    // local raster images into managed storage; never fetch arbitrary URLs.
    $theme_root = realpath(DRUPAL_ROOT . '/themes/custom/dynetx/images');
    $source = realpath(DRUPAL_ROOT . $path);
    if (!$theme_root || !$source || !str_starts_with($source, $theme_root . '/') || !is_file($source)) {
      return NULL;
    }
    $mime = \Drupal::service('file.mime_type.guesser')->guessMimeType($source);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'], TRUE)) {
      return NULL;
    }
    $uri = 'public://legacy/theme-assets/' . substr($source, strlen($theme_root) + 1);
    $directory = dirname($uri);
    \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
    if (is_file($uri)) {
      if (hash_file('sha256', $source) !== hash_file('sha256', $uri)) {
        throw new RuntimeException('Refusing to overwrite changed managed theme image: ' . $uri);
      }
    }
    elseif (!copy($source, $uri) || hash_file('sha256', $source) !== hash_file('sha256', $uri)) {
      throw new RuntimeException('Could not preserve theme image bytes: ' . $path);
    }
    return dyne_native_media_for_uri($uri);
  }
  if (!str_starts_with($path, '/sites/default/files/legacy/')) {
    return NULL;
  }
  return dyne_native_media_for_uri('public://legacy/' . substr($path, strlen('/sites/default/files/legacy/')));
}

/**
 * Normalize timestamps to Drupal's UTC datetime storage format.
 */
function dyne_native_date($value, string $fallback = ''): ?string {
  $timestamp = is_numeric($value) && (int) $value > 0 ? (int) $value : strtotime((string) ($value ?: $fallback) . ' UTC');
  return $timestamp > 0 ? gmdate('Y-m-d\TH:i:s', $timestamp) : NULL;
}

/**
 * Add native fields/media without replacing existing bodies or component IDs.
 */
function dyne_native_backfill(array $source, array $media_manifest, array $metadata): array {
  $state = \Drupal::state();
  $ledger = $state->get('dyne_migrate.native_content', ['nodes' => [], 'media' => []]);
  $report = ['nodes_updated' => 0, 'nodes_unchanged' => 0, 'media_created' => 0, 'media_existing' => 0, 'missing_attachments' => [], 'nodes' => [], 'attachments' => [], 'source_gaps' => []];
  $media_storage = \Drupal::entityTypeManager()->getStorage('media');
  $before_media_count = (int) $media_storage->getQuery()->accessCheck(FALSE)->count()->execute();
  foreach ($media_manifest['attachments'] as $attachment) {
    $path = $attachment['relative_path'] ?? '';
    $media = $path && !str_starts_with($path, '/') ? dyne_native_media_for_uri('public://legacy/' . $path, $attachment) : NULL;
    if (!$media) {
      $report['missing_attachments'][] = (int) $attachment['id'];
      continue;
    }
    $ledger['media'][$attachment['id']] = $media->id();
    $report['attachments'][$attachment['id']] = ['mid' => $media->id(), 'bundle' => $media->bundle()];
  }
  $after_attachment_count = (int) $media_storage->getQuery()->accessCheck(FALSE)->count()->execute();
  $report['media_existing'] = count($report['attachments']) - ($after_attachment_count - $before_media_count);
  $mapping = array_merge($state->get('dyne_migrate.legacy_import', []), $state->get('dyne_migrate.content_completion', []), $state->get('dyne_migrate.framework_import', []));
  $source_by_id = array_column($source['records'], NULL, 'id');
  $metadata_by_id = array_column($metadata, NULL, 'source_id');
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  foreach ($mapping as $key => $entry) {
    $source_id = (int) substr($key, strrpos($key, ':') + 1);
    $record = $source_by_id[$source_id] ?? NULL;
    $node = $node_storage->load($entry['nid']);
    if (!$record || !$node) {
      throw new RuntimeException("Missing mapped source/node for $key");
    }
    if (!empty($ledger['nodes'][$key])) {
      $report['nodes_unchanged']++;
      continue;
    }
    $meta = $record['meta'];
    $fields = ['field_wp_source_id' => $source_id];
    $page = $metadata_by_id[$source_id] ?? NULL;
    if ($page) {
      $hero = $page['hero'] ?? [];
      // Existing editors may already have changed hero Paragraph headings.
      foreach ($node->get('field_components')->referencedEntities() as $component) {
        if ($component->bundle() === 'dyne_hero') {
          $hero['line1'] = $component->get('field_title_line1')->value ?? '';
          $hero['line2'] = $component->get('field_title_line2')->value ?? '';
          break;
        }
      }
      $fields += [
        'field_native_presentation' => TRUE,
        'field_hero_first_line' => ['value' => $hero['line1'] ?? '', 'format' => 'dyne_legacy_html'],
        'field_hero_second_line' => ['value' => $hero['line2'] ?? '', 'format' => 'dyne_legacy_html'],
        'field_hero_third_level' => !empty($hero['third_level']),
        'field_hero_classes' => $hero['classes'] ?? '',
        'field_body_class' => implode(' ', (array) ($page['classes'] ?? [])),
        'field_seo_title' => $page['seo_title'] ?? '',
        'field_seo_description' => $page['description'] ?? '',
      ];
      foreach (['desktop' => 'field_hero_image', 'mobile' => 'field_hero_mobile_image', 'overlay' => 'field_hero_overlay_image'] as $part => $field) {
        if (!empty($hero[$part])) {
          $image = dyne_native_media_for_url($hero[$part]);
          if (!$image) {
            throw new RuntimeException("Existing hero file could not become managed Media: $key $part");
          }
          $fields[$field] = ['target_id' => $image->id()];
        }
      }
    }
    if ($node->bundle() === 'news') {
      $date = dyne_native_date($meta['news_date'] ?? '', $record['date']);
      $fields += ['field_news_date' => $date, 'field_news_type' => [0 => 'html', 1 => 'url', 2 => 'file'][(int) ($meta['news_content_type'] ?? 0)] ?? 'html'];
      if (!empty($meta['news_external_id'])) {
        $fields['field_external_id'] = $meta['news_external_id'];
      }
      if (!empty($meta['news_url']) && filter_var($meta['news_url'], FILTER_VALIDATE_URL)) {
        $fields['field_external_url'] = ['uri' => $meta['news_url']];
      }
      if (!empty($meta['news_file'])) {
        $attachment_id = is_numeric($meta['news_file']) ? (int) $meta['news_file'] : 0;
        $file_media = $attachment_id ? $media_storage->load($ledger['media'][$attachment_id] ?? 0) : dyne_native_media_for_url(str_replace('/wp-content/uploads/', '/sites/default/files/legacy/', $meta['news_file']));
        if ($file_media && $file_media->bundle() === 'document') {
          $fields['field_news_file'] = ['target_id' => $file_media->id()];
        }
        else {
          $report['source_gaps'][] = ['source_id' => $source_id, 'field' => 'news_file', 'reason' => 'Referenced document unavailable locally'];
        }
      }
    }
    elseif ($node->bundle() === 'leader') {
      $fields += ['field_position_info' => $meta['_position_info'] ?? '', 'field_weight' => $record['order']];
      $photo = $ledger['media'][(int) ($meta['_thumbnail_id'] ?? 0)] ?? NULL;
      if ($photo) {
        $fields['field_photo'] = ['target_id' => $photo];
      }
      $categories = ['leadership-main' => 'Leadership', 'board-of-directors' => 'Board', 'scientific-advisory-board' => 'SAB'];
      foreach ($record['terms'] as $term) {
        if ($term['taxonomy'] === 'team_category') {
          $fields['field_leader_type'] = ['target_id' => dyne_native_term('leader_type', $categories[$term['slug']] ?? $term['name'])];
          break;
        }
      }
    }
    elseif ($node->bundle() === 'job') {
      $department = $node->get('field_department_group')->value ?: ($meta['_department'] ?? '');
      $fields += [
        'field_department' => ['target_id' => dyne_native_term('department', $department)],
        'field_location' => ['target_id' => dyne_native_term('job_location', $meta['_location'] ?? '')],
        'field_greenhouse_id' => $meta['_orig_job_id'] ?? $meta['_job_id'] ?? '',
        'field_greenhouse_internal_id' => $meta['_job_id_new'] ?? '',
      ];
      if (!empty($meta['_apply_url'])) {
        $fields['field_application_link'] = ['uri' => $meta['_apply_url']];
      }
    }
    elseif ($node->bundle() === 'perspective') {
      $fields += [
        'field_author' => $meta['author'] ?? '', 'field_subtitle' => $meta['perspectives_subtitle'] ?? '',
        'field_story_date' => dyne_native_date($meta['perspectives_date'] ?? '', $record['date']),
      ];
      $external = (string) ($meta['perspectives_external_link'] ?? '');
      if ($external === '1') {
        $external = (string) ($meta['perspectives_url'] ?? '');
      }
      if ($external !== '' && filter_var($external, FILTER_VALIDATE_URL)) {
        $fields['field_external_link'] = ['uri' => $external];
      }
      if (!empty($record['excerpt'])) {
        $fields['field_story_excerpt'] = ['value' => $record['excerpt'], 'format' => 'dyne_legacy_html'];
      }
      $photo = $ledger['media'][(int) ($meta['_thumbnail_id'] ?? $meta['image'] ?? 0)] ?? NULL;
      if ($photo) {
        $fields['field_story_image'] = ['target_id' => $photo];
      }
    }
    foreach ($fields as $name => $value) {
      $node->set($name, $value);
    }
    $node->setNewRevision(TRUE);
    $node->setRevisionLogMessage('Add native editorial fields and Media references from preserved WordPress source. Existing bodies and component identities retained.');
    $node->save();
    $ledger['nodes'][$key] = ['nid' => $node->id(), 'revision_id' => $node->getRevisionId(), 'fields' => array_keys($fields)];
    $state->set('dyne_migrate.native_content', $ledger);
    $report['nodes'][$key] = $ledger['nodes'][$key];
    $report['nodes_updated']++;
  }
  $state->set('dyne_migrate.native_content', $ledger);
  $report['media_total'] = (int) $media_storage->getQuery()->accessCheck(FALSE)->count()->execute();
  $report['media_created'] = $report['media_total'] - $before_media_count;
  $report['available_source_attachments'] = count($report['attachments']);
  $report['node_total'] = (int) $node_storage->getQuery()->accessCheck(FALSE)->count()->execute();
  return $report;
}
