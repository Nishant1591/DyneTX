<?php
$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
$states = \Drupal::keyValue('pathauto_state.node')->getAll();
$groups = [];
$details = [];
$aliases = \Drupal::service('path_alias.manager');
foreach ($nodes as $node) {
  $alias = $aliases->getAliasByPath('/node/' . $node->id(), $node->language()->getId());
  $state = $states[$node->id()] ?? NULL;
  $group =& $groups[$node->bundle()];
  $group['count'] = ($group['count'] ?? 0) + 1;
  $group['stored_states'][$state === NULL ? 'unset' : (string) $state] = ($group['stored_states'][$state === NULL ? 'unset' : (string) $state] ?? 0) + 1;
  $prefix = str_contains(trim($alias, '/'), '/') ? dirname($alias) : '/';
  $group['prefixes'][$prefix] = ($group['prefixes'][$prefix] ?? 0) + 1;
  if (count($group['examples'] ?? []) < 4) $group['examples'][] = ['id' => $node->id(), 'title' => $node->label(), 'alias' => $alias];
  $details[$node->id()] = ['bundle' => $node->bundle(), 'title' => $node->label(), 'alias' => $alias, 'stored_state' => $state];
}
$report = ['patterns' => array_keys(\Drupal::entityTypeManager()->getStorage('pathauto_pattern')->loadMultiple()), 'groups' => $groups, 'front' => \Drupal::config('system.site')->get('page.front'), 'nodes' => $details];
file_put_contents(dirname(DRUPAL_ROOT) . '/output/aliases/before.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
unset($report['nodes']);
print json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
