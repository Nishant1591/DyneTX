<?php

/**
 * @file
 * Repair footer placement and preserve the original public-site link behavior.
 */

use Drupal\block\Entity\Block;

// The source site only displays its exit notice for explicitly marked links
// (Physicians). The contrib module's default wildcard rule duplicates that
// notice and incorrectly intercepts Investors, videos and social links.
if (\Drupal::moduleHandler()->moduleExists('external_link_popup')) {
  $popup = \Drupal::entityTypeManager()->getStorage('external_link_popup')->load('default');
  if ($popup && $popup->status() && $popup->get('domains') === '*') {
    $popup->disable()->save();
    print "Disabled duplicate wildcard external-link popup; source opt-in notice remains in the theme.\n";
  }
}

$storage = \Drupal::entityTypeManager()->getStorage('block');
$existing = $storage->loadByProperties(['theme' => 'dynetx', 'plugin' => 'system_menu_block:footer']);
if (!$existing) {
  Block::create([
    'id' => 'dynetx_footer_menu',
    'theme' => 'dynetx',
    'region' => 'footer',
    'plugin' => 'system_menu_block:footer',
    'status' => TRUE,
    'weight' => 0,
    'settings' => ['id' => 'system_menu_block:footer', 'label' => 'Footer navigation', 'label_display' => FALSE, 'provider' => 'system', 'level' => 1, 'depth' => 1, 'expand_all_items' => FALSE],
    'visibility' => [],
  ])->save();
  print "Added Drupal Footer menu block to the existing footer region.\n";
}
else {
  print "Existing Footer menu placement preserved.\n";
}

$state = \Drupal::state();
if (!$state->get('dyne_migrate.footer_order_repaired')) {
  $weights = ['Privacy Policy' => 0, 'Terms of Service' => 1, 'Consumer Health Data Policy' => 2];
  foreach (\Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => 'footer']) as $link) {
    if (isset($weights[$link->label()])) {
      $link->set('weight', $weights[$link->label()])->save();
    }
  }
  $state->set('dyne_migrate.footer_order_repaired', TRUE);
}
