<?php

// Read-only source/thumbnail inventory; no entity/config/state saves.
$manager = \Drupal::entityTypeManager();
$storage = $manager->getStorage('media');
$report = ['image_count' => 0, 'published' => 0, 'source_missing' => 0, 'source_missing_dimensions' => 0, 'thumbnail_missing' => 0, 'thumbnail_missing_dimensions' => 0, 'thumbnail_differs_from_source' => 0, 'samples' => []];
$ids = $storage->getQuery()->accessCheck(FALSE)->condition('bundle', 'image')->sort('mid', 'DESC')->execute();
foreach ($storage->loadMultiple($ids) as $media) {
  $report['image_count']++;
  $report['published'] += (int) $media->isPublished();
  $image = $media->get('field_media_image')->first();
  $thumbnail = $media->get('thumbnail')->first();
  $report['source_missing'] += (int) (!$image || !$image->entity);
  $report['source_missing_dimensions'] += (int) (!$image || !$image->width || !$image->height);
  $report['thumbnail_missing'] += (int) (!$thumbnail || !$thumbnail->entity);
  $report['thumbnail_missing_dimensions'] += (int) (!$thumbnail || !$thumbnail->width || !$thumbnail->height);
  $report['thumbnail_differs_from_source'] += (int) ($image && $thumbnail && $image->target_id !== $thumbnail->target_id);
  if (count($report['samples']) < 3 || (int) $media->id() === 479) {
    $report['samples'][$media->id()] = ['name' => $media->label(), 'status' => $media->isPublished()];
    foreach (['source' => $image, 'thumbnail' => $thumbnail] as $name => $item) {
      $file = $item?->entity;
      $report['samples'][$media->id()][$name] = ['fid' => $item?->target_id, 'width' => $item?->width, 'height' => $item?->height, 'uri' => $file?->getFileUri(), 'exists' => $file ? file_exists($file->getFileUri()) : FALSE];
    }
  }
}
$report['active_view_display'] = \Drupal::config('core.entity_view_display.media.image.media_library')->getRawData();
$administrator = $manager->getStorage('user')->load(1);
\Drupal::service('account_switcher')->switchTo($administrator);
$theme_manager = \Drupal::theme();
$previous_theme = $theme_manager->getActiveTheme();
try {
  $theme_manager->setActiveTheme(\Drupal::service('theme.initialization')->initTheme('claro'));
  foreach (array_keys($report['samples']) as $id) {
    $media = $storage->load($id);
    $build = $manager->getViewBuilder('media')->view($media, 'media_library');
    $build['#cache']['max-age'] = 0;
    $markup = (string) \Drupal::service('renderer')->renderInIsolation($build);
    $report['samples'][$id]['rendered_image_count'] = substr_count($markup, '<img');
    $report['samples'][$id]['rendered_markup'] = $markup;
  }
}
finally {
  $theme_manager->setActiveTheme($previous_theme);
  \Drupal::service('account_switcher')->switchBack();
}
file_put_contents(dirname(DRUPAL_ROOT) . '/output/image-library/preview-audit.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
unset($report['active_view_display']);
foreach ($report['samples'] as &$sample) unset($sample['rendered_markup']);
print json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
