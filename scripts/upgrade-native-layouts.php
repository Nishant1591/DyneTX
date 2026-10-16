<?php
/** Run after upgrade-native-content.php and upgrade-native-components.php. */
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/native-layouts.inc';
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/native-lists.inc';
dyne_layout_schema();
$report = dyne_native_lists_upgrade();
$report['acf_layout_types'] = count(dyne_layout_definitions());
$path = dirname(DRUPAL_ROOT) . '/output/document-6-review/native-layouts.json';
file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
