<?php
/**
 * Place the main menu block in the dynetx theme's primary_menu region.
 */
$block_config_storage = \Drupal::entityTypeManager()->getStorage('block');

// Check if already exists.
$existing = $block_config_storage->loadByProperties(['theme' => 'dynetx', 'plugin' => 'system_menu_block:main']);
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
  // Create the block.
  $block = \Drupal\block\Entity\Block::create([
    'id' => 'dynetx_main_menu',
    'theme' => 'dynetx',
    'region' => 'primary_menu',
    'plugin' => 'system_menu_block:main',
    'label' => 'Main navigation',
    'settings' => [
      'id' => 'system_menu_block:main',
      'label' => 'Main navigation',
      'provider' => 'core',
      'menu' => 'main',
      'title' => '',
      'collapse' => 0,
    ],
    'weight' => 0,
  ]);
  $block->save();
  print "Created dynetx_main_menu block in primary_menu region\n";
}
