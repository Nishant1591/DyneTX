<?php

/** Render existing job layouts using the current native editorial values. */
function _dynetx_structured_job_body($node): array {
  $template = (string) $node->get('field_content_template')->value;
  $cache = \Drupal\Core\Cache\CacheableMetadata::createFromObject($node);
  $slots = [];
  foreach ($node->getFieldDefinitions() as $name => $definition) {
    if (!str_starts_with($name, 'field_') || in_array($name, ['field_body_text', 'field_content_template'], TRUE)) {
      continue;
    }
    $field = $node->get($name);
    $slots['<!--dyne:' . $name . '-->'] = '';
    $slots['{{dyne:' . $name . '}}'] = '';
    $access = $field->access('view', NULL, TRUE);
    $cache->addCacheableDependency($access);
    if (!$access->isAllowed()) {
      continue;
    }
    $type = $definition->getType();
    $html = '';
    if (!$field->isEmpty()) {
      if (in_array($type, ['text', 'text_long', 'text_with_summary'], TRUE)) {
        $part = ['#type' => 'processed_text', '#text' => $field->value, '#format' => $field->format];
        $html = (string) \Drupal::service('renderer')->renderInIsolation($part);
        $cache = $cache->merge(\Drupal\Core\Cache\CacheableMetadata::createFromRenderArray($part));
      }
      elseif ($type === 'link') {
        $html = \Drupal\Component\Utility\Html::escape($field->first()->getUrl()->toString());
      }
      elseif ($type === 'decimal') {
        $number = (float) $field->value;
        $html = number_format($number, $number == floor($number) ? 0 : 2);
      }
      elseif (in_array($type, ['string', 'string_long'], TRUE)) {
        $html = \Drupal\Component\Utility\Html::escape((string) $field->value);
      }
    }
    $slots['<!--dyne:' . $name . '-->'] = $html;
    $slots['{{dyne:' . $name . '}}'] = $html;
  }
  if ($template !== '') {
    $html = strtr($template, $slots);
    foreach ([
      'field_company_overview' => 'Company Overview', 'field_role_summary' => 'Role Summary',
      'field_responsibilities' => 'Primary Responsibilities', 'field_education_skills' => 'Education, Experience and Skills Requirements',
      'field_compensation_notes' => 'Compensation and Benefits', 'field_job_disclaimer' => 'Job Description Disclaimer',
      'field_equal_opportunity' => 'Equal Opportunity', 'field_additional_job_content' => 'Additional Information',
    ] as $name => $label) {
      $token = '<!--dyne:' . $name . '-->';
      if (!str_contains($template, $token) && ($slots[$token] ?? '') !== '') {
        $html .= '<section><h3>' . $label . '</h3>' . $slots[$token] . '</section>';
      }
    }
    foreach (['field_company_website' => 'Company website', 'field_company_twitter' => 'X', 'field_company_linkedin' => 'LinkedIn', 'field_company_facebook' => 'Facebook'] as $name => $label) {
      $token = '{{dyne:' . $name . '}}';
      if (!str_contains($template, $token) && ($slots[$token] ?? '') !== '') {
        $html .= '<p><a href="' . $slots[$token] . '">' . $label . '</a></p>';
      }
    }
    if (!str_contains($template, '<!--dyne:field_pay_range_min-->') && !str_contains($template, '<!--dyne:field_pay_range_max-->') && (($slots['<!--dyne:field_pay_range_min-->'] ?? '') !== '' || ($slots['<!--dyne:field_pay_range_max-->'] ?? '') !== '')) {
      $html .= '<p class="pay-range">' . ($slots['<!--dyne:field_pay_range_label-->'] ?? '') . ' ' . ($slots['<!--dyne:field_pay_range_currency-->'] ?? '') . ' ' . ($slots['<!--dyne:field_pay_range_min-->'] ?? '') . ' – ' . ($slots['<!--dyne:field_pay_range_max-->'] ?? '') . '</p>';
    }
  }
  else {
    // New jobs need no archived HTML to produce a complete public page.
    $html = '';
    foreach ([
      'field_company_overview' => 'Company Overview',
      'field_role_summary' => 'Role Summary',
      'field_responsibilities' => 'Primary Responsibilities',
      'field_education_skills' => 'Education, Experience and Skills Requirements',
      'field_compensation_notes' => 'Compensation and Benefits',
      'field_job_disclaimer' => 'Job Description Disclaimer',
      'field_equal_opportunity' => 'Equal Opportunity',
      'field_additional_job_content' => 'Additional Information',
    ] as $field => $label) {
      if (!$node->get($field)->isEmpty()) {
        $html .= '<section><h3>' . \Drupal\Component\Utility\Html::escape($label) . '</h3>' . $slots['<!--dyne:' . $field . '-->'] . '</section>';
      }
    }
    if (!$node->get('field_pay_range_min')->isEmpty() || !$node->get('field_pay_range_max')->isEmpty()) {
      $html .= '<p class="pay-range">' . $slots['<!--dyne:field_pay_range_label-->'] . ' ' . $slots['<!--dyne:field_pay_range_currency-->'] . ' ' . $slots['<!--dyne:field_pay_range_min-->'] . ' – ' . $slots['<!--dyne:field_pay_range_max-->'] . '</p>';
    }
    foreach (['field_company_website' => 'Company website', 'field_company_twitter' => 'X', 'field_company_linkedin' => 'LinkedIn', 'field_company_facebook' => 'Facebook'] as $field => $label) {
      if (!$node->get($field)->isEmpty()) {
        $html .= '<p><a href="' . $slots['{{dyne:' . $field . '}}'] . '">' . $label . '</a></p>';
      }
    }
  }
  $build = ['#markup' => \Drupal\Core\Render\Markup::create($html)];
  $cache->applyTo($build);
  return $build;
}
