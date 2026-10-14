<?php
$block_config_storage = \Drupal::entityTypeManager()->getStorage('block');
$blocks = $block_config_storage->loadByProperties(['theme' => 'dynetx']);
print "Blocks placed in dynetx theme:\n";
foreach ($blocks as $id => $block) {
  print "  $id => region={$block->getRegion()}, plugin={$block->getPluginId()}\n";
}
