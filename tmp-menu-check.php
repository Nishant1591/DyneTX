<?php
$tree = \Drupal::menuTree()->load('main', new \Drupal\Core\Menu\MenuTreeParameters());
foreach ($tree as $item) {
  $link = $item->link;
  echo $link->getTitle() . ' => ' . $link->getUrl()->toString() . "\n";
}
