<?php
/** Read-only render of immutable pre-migration values and preserved revisions. */
$root = dirname(DRUPAL_ROOT);
$directory = $root . '/output/structured-model';
$baseline = json_decode(file_get_contents($directory . '/before-entities.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$manager = \Drupal::entityTypeManager();
$storage = $manager->getStorage('node');
$renderer = \Drupal::service('renderer');
$theme_manager = \Drupal::service('theme.manager');
$old_theme = $theme_manager->getActiveTheme();
$theme_manager->setActiveTheme(\Drupal::service('theme.initialization')->getActiveThemeByName('dynetx'));
$rows = [];
try {
  foreach ($baseline['nodes'] as $nid => $record) {
    if (!$record['status']) continue;
    $current = $storage->load($nid);
    $original = clone $current;
    foreach ($original->getFieldDefinitions() as $name => $definition) {
      if (str_starts_with($name, 'field_') || $name === 'body') $original->set($name, $record['fields'][$name] ?? []);
    }
    $original->set('vid', $record['vid']);
    $original->set('changed', $record['changed']);
    $pair = ['nid' => (int) $nid, 'bundle' => $record['bundle'], 'route' => $record['alias']];
    foreach (['before' => $original, 'after' => $current] as $phase => $node) {
      $build = $manager->getViewBuilder('node')->view($node, 'full');
      // These values are not being saved. Never read/write a canonical node
      // render-cache entry for the reconstructed, in-memory original entity.
      $build['#cache']['max-age'] = 0;
      unset($build['#cache']['keys']);
      $pair[$phase] = '<main id="main-content">' . $renderer->renderInIsolation($build) . '</main>';
    }
    $rows[$nid] = $pair;
    $storage->resetCache([$nid]);
  }
}
finally { $theme_manager->setActiveTheme($old_theme); }
file_put_contents($directory . '/source-rendering-pairs.json', json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
echo json_encode(['published_nodes_rendered_from_baseline_and_current' => count($rows), 'database_content_modified' => FALSE]) . "\n";
