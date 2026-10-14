<?php
foreach (\Drupal::service('plugin.manager.block')->getDefinitions() as $id => $d) {
  print $id . ' => ' . $d['admin_label'] . ' [' . ($d['category'] ?? 'no-cat') . ']\n';
}
