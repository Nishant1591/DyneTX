<?php

/**
 * Repair legacy numeric parent IDs on existing main-menu links.
 *
 * This only updates the parent field; titles, URLs, weights and link entities
 * are left untouched.
 */
$storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
$links = $storage->loadByProperties(['menu_name' => 'main']);
$repaired = 0;

foreach ($links as $link) {
  $parent = (string) $link->get('parent')->value;
  if (!preg_match('/^menu_link_content:(\d+)$/', $parent, $matches)) {
    continue;
  }

  $parent_link = $storage->load($matches[1]);
  if (!$parent_link || $parent_link->getMenuName() !== 'main') {
    continue;
  }

  $link->set('parent', $parent_link->getPluginId());
  $link->save();
  $repaired++;
}

print "Repaired parent references on {$repaired} main-menu links.\n";