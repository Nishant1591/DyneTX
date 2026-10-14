<?php
$n = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['title' => 'Home']);
if ($n) {
  foreach ($n as $node) {
    echo $node->id() . ' ' . $node->getTitle() . ' (' . $node->bundle() . ")\n";
    echo 'Access: ' . ($node->access('view') ? 'YES' : 'NO') . "\n";
    echo 'Type: ' . $node->bundle() . "\n";
    $components = $node->get('field_components')->getValue();
    echo 'Components: ' . count($components) . "\n";
  }
} else {
  echo "NOT FOUND\n";
}
