<?php
$ml = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => 'main']);
echo 'Menu links in main: ' . count($ml) . "\n";
echo "\n--- Top level (no parent) ---\n";
foreach ($ml as $m) {
  $parent = $m->get('parent')->value;
  if (empty($parent)) {
    echo $m->getTitle() . ' => ' . $m->get('link')->first()?->getValue()['uri'] . "\n";
  }
}
echo "\n--- With parents ---\n";
foreach ($ml as $m) {
  $parent = $m->get('parent')->value;
  if (!empty($parent)) {
    echo $m->getTitle() . ' (parent: ' . $parent . ')\n';
  }
}
