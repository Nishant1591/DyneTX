<?php
foreach (\Drupal::service('plugin.manager.block')->getDefinitions() as $id => $d) {
  if (strpos($d['admin_label'], 'Menu') !== false || strpos($d['admin_label'], 'Primary') !== false) {
    print $id . ' => ' . $d['admin_label'] . "\n";
  }
}
