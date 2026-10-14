<?php
$tables = \Drupal::service('database')->query('SHOW TABLES')->fetchCol();
foreach ($tables as $t) {
  if (strpos($t, 'block') !== false) print $t . "\n";
}
