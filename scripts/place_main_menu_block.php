<?php
/**
 * Place the main menu block in the dynetx theme's primary_menu region.
 */
// Check if already exists.
$layout_storage = \Drupal::entityTypeManager()->getStorage('block_layout');
$existing = $layout_storage->loadByProperties(['theme' => 'dynetx', 'plugin' => 'system_menu_block:main']);
if (!empty($existing)) {
  $block = reset($existing);
  if ($block->getRegion() !== 'primary_menu') {
    $block->setRegion('primary_menu');
    $block->save();
    print "Updated region for system_menu_block:main to primary_menu\n";
  } else {
    print "system_menu_block:main already in primary_menu\n";
  }
} else {
  // Create the block layout.
  $layout_storage->create([
    'theme' => 'dynetx',
    'blocks' => [
      'system_menu_block:main' => [
        'plugin' => 'system_menu_block:main',
        'label' => 'Main navigation',
        'settings' => ['menu' => 'main'],
        'theme' => 'dynetx',
        'region' => 'primary_menu',
        'weight' => 0,
      ],
    ],
  ])->save();
  print "Created system_menu_block:main in primary_menu region\n";
}
