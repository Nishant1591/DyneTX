<?php
require '/var/www/html/vendor/autoload.php';
//
//
require "/var/www/html/web/sites/default/settings.php";

$fs = \Drupal::entityTypeManager()->getStorage('field_storage_config');
$fc = \Drupal::entityTypeManager()->getStorage('field_config');

foreach (['field_text', 'field_text_long'] as $f) {
    if ($s = $fs->load($f)) { $s->delete(); echo "Deleted storage: $f\n"; }
    if ($c = $fc->load($f)) { $c->delete(); echo "Deleted config: $f\n"; }
}

foreach (['field_link', 'field_bg_image'] as $f) {
    if ($c = $fc->load($f)) {
        if ($c->getEntityTypeId() === 'paragraph') {
            $c->delete();
            echo "Deleted paragraph config: $f\n";
        }
    }
}

echo "Done.\n";
