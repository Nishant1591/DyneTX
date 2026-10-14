<?php

/**
 * @file
 * Repair default cookie-banner markup and match the archived visual treatment.
 * Run with drush php:script scripts/repair-cookie-banner.php after import.
 */

if (!\Drupal::moduleHandler()->moduleExists('eu_cookie_compliance')) {
  throw new RuntimeException('Enable the existing eu_cookie_compliance module first.');
}
if (!\Drupal\filter\Entity\FilterFormat::load('dyne_legacy_html')) {
  throw new RuntimeException('Run the legacy content importer before this repair.');
}
$state = \Drupal::state();
if ($state->get('dyne_migrate.cookie_banner_repaired')) {
  print "Cookie banner already repaired; keeping subsequent editor settings.\n";
  return;
}
$config = \Drupal::configFactory()->getEditable('eu_cookie_compliance.settings');
$state->set('dyne_migrate.pre_legacy_cookie_banner', $config->getRawData());
foreach (['popup_info', 'mobile_popup_info', 'popup_agreed', 'withdraw_message'] as $key) {
  if ($config->get("$key.format") === 'plain_text' && str_contains((string) $config->get("$key.value"), '<')) {
    $config->set("$key.format", 'dyne_legacy_html');
  }
}
$config
  ->set('popup_bg_hex', 'ffffff')
  ->set('popup_text_hex', '141414')
  ->set('popup_clicking_confirmation', FALSE)
  ->set('popup_scrolling_confirmation', FALSE)
  ->set('popup_agree_button_message', 'Allow all')
  ->set('disagree_button_label', 'Deny')
  ->set('popup_more_info_button_message', 'Show details')
  ->set('popup_link', '/cookie-declaration')
  ->set('popup_link_new_window', FALSE)
  ->set('exclude_admin_theme', TRUE)
  ->set('better_support_for_screen_readers', TRUE)
  ->set('use_olivero_css', FALSE)
  ->save();
$state->set('dyne_migrate.cookie_banner_repaired', TRUE);
print "Cookie banner repaired; original settings retained in Drupal state.\n";
