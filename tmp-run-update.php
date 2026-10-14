<?php
// Directly call the update function to create homepage + fix anything
dyne_migrate_update_8101();
echo "Update 8101 executed.\n";

// Verify
$n = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['title' => 'Home']);
if ($n) {
  foreach ($n as $node) {
    echo 'Homepage node created: ' . $node->id() . ' - ' . $node->getTitle() . "\n";
  }
} else {
  echo "ERROR: Homepage node NOT found!\n";
}
