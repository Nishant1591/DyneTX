<?php

/**
 * Read-only inventory: ddev drush php:script output/image-library/audit.php.
 * No configuration/content writes or entity-form submissions.
 */

use Drupal\Core\Session\UserSession;

$manager = \Drupal::entityTypeManager();
$fields = \Drupal::service('entity_field.manager');
$database = \Drupal::database();
$report = ['modules' => [], 'node_counts' => [], 'widgets' => [], 'hidden_image_fields' => []];
foreach (['media', 'media_library', 'views', 'image', 'file'] as $module) {
  $report['modules'][$module] = \Drupal::moduleHandler()->moduleExists($module);
}
$node_query = $database->select('node_field_data', 'n')->fields('n', ['type']);
$node_query->addExpression('COUNT(*)', 'count');
foreach ($node_query->groupBy('type')->execute() as $row) {
  $report['node_counts'][$row->type] = (int) $row->count;
}
$bundle_info = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
foreach ($manager->getStorage('entity_form_display')->loadMultiple() as $display) {
  if ($display->getMode() !== 'default' || !in_array($display->getTargetEntityTypeId(), ['node', 'paragraph', 'media'], TRUE)) {
    continue;
  }
  $entity_type = $display->getTargetEntityTypeId();
  $bundle = $display->getTargetBundle();
  foreach ($fields->getFieldDefinitions($entity_type, $bundle) as $name => $definition) {
    $storage = $definition->getFieldStorageDefinition();
    $type = $definition->getType();
    $is_media = $type === 'entity_reference' && $definition->getSetting('target_type') === 'media';
    if (!$is_media && !in_array($type, ['image', 'file'], TRUE)) {
      continue;
    }
    $component = $display->getComponent($name);
    $row = [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'bundle_label' => (string) ($bundle_info[$entity_type][$bundle]['label'] ?? $bundle),
      'field' => $name,
      'label' => (string) $definition->getLabel(),
      'field_type' => $type,
      'cardinality' => $storage->getCardinality(),
    ];
    if (!$component) {
      $report['hidden_image_fields'][] = $row;
      continue;
    }
    $row['widget'] = $component['type'] ?? NULL;
    $row['target_bundles'] = $is_media ? ($definition->getSetting('handler_settings')['target_bundles'] ?? NULL) : NULL;
    $row['widget_media_types'] = $is_media ? ($component['settings']['media_types'] ?? []) : NULL;
    if (!$is_media) {
      $row['file_extensions'] = $definition->getSetting('file_extensions');
      $row['alt_field'] = $definition->getSetting('alt_field');
      $row['alt_required'] = $definition->getSetting('alt_field_required');
    }
    $report['widgets'][] = $row;
  }
}
usort($report['widgets'], static fn($a, $b) => [$a['entity_type'], $a['bundle'], $a['field']] <=> [$b['entity_type'], $b['bundle'], $b['field']]);
$report['widget_totals'] = [];
foreach ($report['widgets'] as $row) {
  $key = $row['entity_type'] . ':' . $row['widget'];
  $report['widget_totals'][$key] = ($report['widget_totals'][$key] ?? 0) + 1;
}
$report['media_types'] = [];
$mapped_files = [];
$duplicate_files = [];
foreach ($manager->getStorage('media_type')->loadMultiple() as $id => $type) {
  $source = $type->getSource();
  $source_field = $source->getSourceFieldDefinition($type)->getName();
  $data = ['label' => $type->label(), 'source' => $source->getPluginId(), 'source_field' => $source_field, 'total' => 0, 'published' => 0, 'missing_files' => [], 'image_thumbnail_matches_source' => 0, 'missing_thumbnails' => [], 'thumbnail_differs' => []];
  foreach ($manager->getStorage('media')->loadByProperties(['bundle' => $id]) as $media) {
    $data['total']++;
    $data['published'] += (int) $media->isPublished();
    if ($source->getPluginId() === 'image') {
      $thumbnail = $media->get('thumbnail')->entity;
      if (!$thumbnail || !file_exists($thumbnail->getFileUri())) {
        $data['missing_thumbnails'][] = (int) $media->id();
      }
      foreach ($media->get($source_field)->referencedEntities() as $file) {
        $fid = (int) $file->id();
        $mapped_files[$fid][] = (int) $media->id();
        if ($thumbnail && (int) $thumbnail->id() === $fid) {
          $data['image_thumbnail_matches_source']++;
        }
        else {
          $data['thumbnail_differs'][] = ['mid' => $media->id(), 'source_fid' => $fid, 'thumbnail_fid' => $thumbnail ? $thumbnail->id() : NULL];
        }
        if (!file_exists($file->getFileUri())) {
          $data['missing_files'][] = ['mid' => $media->id(), 'fid' => $fid, 'uri' => $file->getFileUri()];
        }
      }
    }
  }
  $form = $manager->getStorage('entity_form_display')->load("media.$id.media_library");
  $data['source_upload_widget'] = $form ? ($form->getComponent($source_field)['type'] ?? NULL) : NULL;
  $report['media_types'][$id] = $data;
}
foreach ($mapped_files as $fid => $mids) {
  if (count($mids) > 1) {
    $duplicate_files[$fid] = $mids;
  }
}
$report['files'] = ['image_total' => 0, 'permanent' => 0, 'temporary' => 0, 'mapped_to_image_media' => count($mapped_files), 'mimes' => [], 'unregistered' => [], 'duplicates' => $duplicate_files];
$managed_image_uris = [];
$image_ids = $manager->getStorage('file')->getQuery()->accessCheck(FALSE)->condition('filemime', 'image/%', 'LIKE')->execute();
foreach ($manager->getStorage('file')->loadMultiple($image_ids) as $fid => $file) {
  $report['files']['image_total']++;
  $managed_image_uris[$file->getFileUri()] = TRUE;
  $mime = $file->getMimeType();
  $report['files']['mimes'][$mime] = ($report['files']['mimes'][$mime] ?? 0) + 1;
  $report['files'][$file->isPermanent() ? 'permanent' : 'temporary']++;
  if (!isset($mapped_files[$fid])) {
    $usage = $database->select('file_usage', 'u')->fields('u', ['module', 'type', 'id', 'count'])->condition('fid', $fid)->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $report['files']['unregistered'][] = ['fid' => $fid, 'name' => $file->getFilename(), 'mime' => $file->getMimeType(), 'uri' => $file->getFileUri(), 'permanent' => $file->isPermanent(), 'exists' => file_exists($file->getFileUri()), 'usage' => $usage];
  }
}
$legacy_root = \Drupal::service('file_system')->realpath('public://legacy');
$report['physical_legacy_images'] = ['total' => 0, 'not_managed_exact_uri' => 0, 'unmanaged_size_suffix' => 0, 'unmanaged_other' => 0, 'unmanaged_other_samples' => []];
if ($legacy_root && is_dir($legacy_root)) {
  foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($legacy_root, \FilesystemIterator::SKIP_DOTS)) as $physical) {
    if (!$physical->isFile() || !preg_match('/\.(?:jpe?g|png|gif|webp|avif|svg)$/i', $physical->getFilename())) {
      continue;
    }
    $report['physical_legacy_images']['total']++;
    $uri = 'public://legacy/' . substr($physical->getPathname(), strlen($legacy_root) + 1);
    if (!isset($managed_image_uris[$uri])) {
      $report['physical_legacy_images']['not_managed_exact_uri']++;
      if (preg_match('/-\d+x\d+\.[^.]+$/', $physical->getFilename())) {
        $report['physical_legacy_images']['unmanaged_size_suffix']++;
      }
      else {
        $report['physical_legacy_images']['unmanaged_other']++;
        if (count($report['physical_legacy_images']['unmanaged_other_samples']) < 20) {
          $report['physical_legacy_images']['unmanaged_other_samples'][] = $uri;
        }
      }
    }
  }
}
$report['roles'] = [];
$media_access = $manager->getAccessControlHandler('media');
foreach ($manager->getStorage('user_role')->loadMultiple() as $rid => $role) {
  $account = new UserSession(['uid' => $rid === 'anonymous' ? 0 : 999999, 'roles' => $rid === 'anonymous' ? ['anonymous'] : array_unique(['authenticated', $rid])]);
  $report['roles'][$rid] = [
    'admin_role' => $role->isAdmin(),
    'media_permissions' => array_values(array_filter($role->getPermissions(), static fn($p) => str_contains($p, 'media'))),
    'can_view_media' => $account->hasPermission('view media'),
    'can_create_image_media' => $media_access->createAccess('image', $account),
  ];
}
$admin = $manager->getStorage('user')->load(1);
$report['uid1'] = $admin ? ['active' => $admin->isActive(), 'can_view_media' => $admin->hasPermission('view media'), 'can_create_image_media' => $media_access->createAccess('image', $admin)] : NULL;
$report['direct_image_values'] = [];
foreach ($report['widgets'] as $widget) {
  if ($widget['entity_type'] === 'media' || $widget['field_type'] !== 'image') {
    continue;
  }
  $entity_type = $widget['entity_type'];
  $bundle = $widget['bundle'];
  $field_name = $widget['field'];
  $summary = ['total_entities' => 0, 'nonempty' => 0, 'published_nonempty' => 0, 'values' => []];
  foreach ($manager->getStorage($entity_type)->loadByProperties(['type' => $bundle]) as $entity) {
    $summary['total_entities']++;
    if ($entity->get($field_name)->isEmpty()) {
      continue;
    }
    $summary['nonempty']++;
    $summary['published_nonempty'] += (int) $entity->isPublished();
    foreach ($entity->get($field_name) as $item) {
      $file = $item->entity;
      $summary['values'][] = ['id' => (int) $entity->id(), 'revision' => (int) $entity->getRevisionId(), 'published' => $entity->isPublished(), 'fid' => (int) $item->target_id, 'alt' => $item->alt, 'uri' => $file ? $file->getFileUri() : NULL, 'exists' => $file ? file_exists($file->getFileUri()) : FALSE, 'image_media' => $mapped_files[$item->target_id] ?? []];
    }
  }
  $report['direct_image_values']["$entity_type.$bundle.$field_name"] = $summary;
}
$report['media_library_settings'] = \Drupal::config('media_library.settings')->getRawData();
$view = \Drupal::config('views.view.media_library');
$report['media_library_view'] = ['status' => $view->get('status')];
foreach (['default', 'widget', 'widget_table'] as $id) {
  $options = $view->get("display.$id.display_options") ?? [];
  $filter_summary = [];
  foreach ($options['filters'] ?? [] as $key => $filter) {
    $filter_summary[$key] = array_intersect_key($filter, array_flip(['plugin_id', 'value', 'operator', 'exposed']));
  }
  $report['media_library_view'][$id] = array_intersect_key($options, array_flip(['access', 'style', 'pager', 'path', 'defaults'])) + ['filters' => $filter_summary];
}
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
