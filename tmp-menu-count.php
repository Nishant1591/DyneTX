<?php
$ml = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => 'main']);
echo 'Menu links in main: ' . count($ml) . "\n";
foreach ($ml as $m) {
  echo $m->getTitle() . ' => ' . $m->getUrl()->toString() . "\n";
}
