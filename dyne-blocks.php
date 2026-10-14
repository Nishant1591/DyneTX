<?php
require '/var/www/html/vendor/autoload.php';
$_SERVER['HTTP_USER_AGENT'] = 'cli';
$kernel = new Drupal\Core\DrupalKernel('prod', false);
$kernel->boot();
$kernel->preHandle(new Symfony\Component\HttpFoundation\Request());
$blocks = Drupal::service('plugin.manager.block');
$defs = $blocks->getDefinitions();
foreach ($defs as $id => $d) {
  if (strpos($d['admin_label'], 'Menu') !== false || strpos($d['admin_label'], 'Primary') !== false) {
    echo "$id => $d[admin_label]\n";
  }
}
