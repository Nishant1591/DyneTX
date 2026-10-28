<?php

/**
 * @file
 * Prepare the publicly verified OneTrust/Cookiebot IDs for the audit repair.
 *
 * Preview by default. Apply with DYNE_AUDIT_INTEGRATIONS_APPLY=1. Loading either
 * provider also requires DYNE_AUDIT_PROVIDER_APPROVED=1 and its explicit flag:
 * DYNE_AUDIT_ENABLE_FORMS=1 / DYNE_AUDIT_ENABLE_COOKIEBOT=1. Approval concerns
 * the provider receiving this deployment's URL/domain and browser metadata.
 * This script never submits a form or calls an external service.
 */

$forms = getenv('DYNE_AUDIT_ENABLE_FORMS') === '1';
$cookiebot = getenv('DYNE_AUDIT_ENABLE_COOKIEBOT') === '1';
$apply = getenv('DYNE_AUDIT_INTEGRATIONS_APPLY') === '1';
if ($apply && ($forms || $cookiebot) && getenv('DYNE_AUDIT_PROVIDER_APPROVED') !== '1') {
  throw new RuntimeException('Provider loading requires explicit approval for this deployment URL/domain before it can be enabled.');
}

// Verified 2026-10-28 against the public live HTML and OneTrust cp/list
// response for https://www.dyne-tx.com/dm1/. Preview definitions exist for both.
$values = [
  'enabled' => $forms,
  'environment' => 'staging',
  'prod_id' => '5c7d29d6-5208-4f7a-b1b5-b9aae9d54670',
  'staging_id' => '5c7d29d6-5208-4f7a-b1b5-b9aae9d54670-test',
  'collection_point_id' => 'd37d89f4-02ef-4834-8033-678cd54cd22a',
  'dm1_prod_id' => '5c7d29d6-5208-4f7a-b1b5-b9aae9d54670',
  'dm1_staging_id' => '5c7d29d6-5208-4f7a-b1b5-b9aae9d54670-test',
  'dm1_collection_point_id' => '7b853126-3f3e-4835-913f-06dfd33301fe',
  'data_source_prod' => 'Production: Signup Form',
  'data_source_stage' => 'Staging: Signup Form test',
  // No analytics event is enabled by the local audit verification.
  'gtm_event' => '',
  'cookiebot_enabled' => $cookiebot,
  'cookiebot_id' => '60486e5f-6638-432e-b626-4e9883af4245',
];
$config = \Drupal::configFactory()->getEditable('ot_webform.settings');
$changes = [];
foreach ($values as $name => $value) {
  if ($config->get($name) !== $value) {
    $changes[$name] = ['before' => $config->get($name), 'after' => $value];
    if ($apply) {
      $config->set($name, $value);
    }
  }
}
if ($apply && $changes) {
  $config->save();
}

// Keep a working native consent manager available while external service
// activation/domain registration is pending. The copy is from the live dialog,
// but it remains explicitly Drupal consent, without Cookiebot branding.
$copy = '<h2>This website uses cookies</h2><p>We use cookies to personalise content and ads, to provide social media features and to analyse our traffic. We also share information about your use of our site with our social media, advertising and analytics partners who may combine it with other information that you’ve provided to them or that they’ve collected from your use of their services.</p>';
$native_values = [
  'popup_info.value' => $copy,
  'mobile_popup_info.value' => $copy,
  'popup_agree_button_message' => 'Allow all',
  'popup_more_info_button_message' => 'Show details',
  'disagree_button_label' => 'Deny',
  'popup_position' => FALSE,
  'popup_clicking_confirmation' => FALSE,
  'popup_scrolling_confirmation' => FALSE,
  'method' => 'opt_in',
  'withdraw_enabled' => TRUE,
  'settings_tab_enabled' => TRUE,
  'withdraw_tab_button_label' => 'Cookie settings',
  'withdraw_action_button_label' => 'Withdraw your consent',
  'withdraw_message.value' => '<h2>This website uses cookies</h2><p>You can withdraw your consent and review your cookie preferences.</p>',
];
$native = \Drupal::configFactory()->getEditable('eu_cookie_compliance.settings');
$native_changes = [];
foreach ($native_values as $name => $value) {
  if ($native->get($name) !== $value) {
    $native_changes[$name] = ['before' => $native->get($name), 'after' => $value];
    if ($apply) {
      $native->set($name, $value);
    }
  }
}
if ($apply && $native_changes) {
  $native->save();
}
print json_encode(['applied' => $apply, 'environment' => 'staging', 'forms_enabled' => $forms, 'cookiebot_enabled' => $cookiebot, 'changes' => $changes, 'native_consent_changes' => $native_changes, 'submissions' => 0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
