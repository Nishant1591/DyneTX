<?php
/** Read-only real FormBuilder coverage for all eight native content bundles. */
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/structured-content.inc';
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(\Drupal\user\Entity\User::load(1));
$checks = [];
$assert = static function (bool $ok, string $message) use (&$checks): void {
  if (!$ok) throw new RuntimeException($message);
  $checks[] = $message;
  echo '[check] ' . $message . "\n";
};
try {
  $forms = [];
  $expected = [
    'job' => array_merge(array_keys(dyne_structured_job_fields()), ['field_department', 'field_location', 'field_application_link']),
    'news' => array_keys(dyne_structured_article_fields()['news']),
    'perspective' => array_keys(dyne_structured_article_fields()['perspective']),
    'privacy_policy' => ['field_article_intro', 'field_article_sections', 'field_policy_updated'],
    'leader' => ['field_role', 'field_body_text', 'field_photo'],
    'dyne_page' => ['field_components'],
    'pipeline_program' => ['field_program_code', 'field_disease_text', 'field_prevalence_us', 'field_prevalence_eu', 'field_target', 'field_phase', 'field_status', 'field_body_text', 'field_disease_overview', 'field_disease_onset', 'field_disease_cause', 'field_disease_symptoms', 'field_unmet_need', 'field_trial_links'],
    'quote' => ['field_quote_text', 'field_speaker_image', 'field_quote_width'],
  ];
  foreach ($expected as $bundle => $fields) {
    echo '[form] ' . $bundle . "\n";
    $node = \Drupal\node\Entity\Node::create(['type' => $bundle, 'title' => 'Unsaved read-only form verification']);
    $form = \Drupal::service('entity.form_builder')->getForm($node);
    foreach ($fields as $field) $assert(isset($form[$field]['widget']) && ($form[$field]['#access'] ?? TRUE) !== FALSE, $bundle . ' exposes ' . $field . ' as a real widget.');
    if (in_array($bundle, ['job', 'news', 'perspective', 'privacy_policy'], TRUE)) $assert(!isset($form['field_body_text']) || ($form['field_body_text']['#access'] ?? TRUE) === FALSE, $bundle . ' hides the preserved merged source body.');
    $forms[$bundle] = $form;
  }
  foreach (['field_company_overview' => 'dyne_company', 'field_role_summary' => 'dyne_role', 'field_pay_range_min' => 'dyne_pay'] as $field => $group) $assert(($forms['job'][$field]['#group'] ?? '') === $group && isset($forms['job'][$group]), $field . ' belongs to ' . $group . '.');
  foreach ([['field_company_overview', 'field_company_website', 'field_company_twitter', 'field_company_linkedin', 'field_company_facebook'], ['field_role_summary', 'field_responsibilities', 'field_education_skills', 'field_additional_job_content']] as $ordered) {
    $weights = array_map(static fn($name) => $forms['job'][$name]['#weight'], $ordered);
    $sorted = $weights; sort($sorted);
    $assert($weights === $sorted && count(array_unique($weights)) === count($weights), 'Processed semantic form order: ' . implode(', ', $ordered));
  }
  $assert($forms['job']['field_department']['#weight'] < $forms['job']['dyne_company']['#weight'] && $forms['job']['field_application_link']['#weight'] < $forms['job']['dyne_company']['#weight'], 'Job identity and application controls appear above Company information.');
  $report = ['passed' => TRUE, 'bundles' => count($expected), 'checks' => $checks, 'entities_saved' => 0];
  file_put_contents(dirname(DRUPAL_ROOT) . '/output/structured-model/form-verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
  echo json_encode(['passed' => TRUE, 'bundles' => count($expected), 'checks' => count($checks), 'entities_saved' => 0]) . "\n";
}
finally { $switcher->switchBack(); }
