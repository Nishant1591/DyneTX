<?php

/**
 * Restore only a source-proven paragraph boundary split by the job importer.
 *
 * The preserved source proves layout only. All output comes from the current,
 * processed native fields and template; an edited layout fails open unchanged.
 */
function _dynetx_restore_inline_job_heading(string $template, array &$slots, string $source): string {
  $role = '<!--dyne:field_role_summary-->';
  $responsibilities = '<!--dyne:field_responsibilities-->';
  $pattern = '~' . preg_quote($role, '~') . '<p>(?<heading><strong\b[^>]*>[\s\S]*?</strong>)</p>' . preg_quote($responsibilities, '~') . '~i';
  if (preg_match_all($pattern, $template, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) !== 1) return $template;
  $match = $matches[0]; $heading = $match['heading'][0];
  $text = static fn(string $value): string => trim(preg_replace('/[\s\x{00a0}]+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
  if ($text($heading) !== 'Primary Responsibilities Include:') return $template;
  $double_break = '~<br\s*/?>(?:[\s\x{00a0}]|&nbsp;|&#160;|&#x0*a0;)*<br\s*/?>\s*\z~iu';
  $role_html = $slots[$role] ?? ''; $responsibilities_html = $slots[$responsibilities] ?? '';
  if (!preg_match('~</p>\s*\z~i', $role_html) || !preg_match($double_break, preg_replace('~</p>\s*\z~i', '', $role_html))) return $template;
  if (!preg_match('~\A\s*<p\b[^>]*>(?<suffix>(?:(?!</?p\b)[\s\S])*?)</p>~i', $responsibilities_html, $prefix)) return $template;
  $suffix = $prefix['suffix'];
  if ($text($suffix) !== '' || preg_match('~<(?!/?span\b)[^>]+>~i', $suffix)) return $template;

  $document = \Drupal\Component\Utility\Html::load($source);
  $proven = FALSE;
  foreach ($document->getElementsByTagName('strong') as $strong) {
    $paragraph = $strong->parentNode;
    if (!$paragraph instanceof \DOMElement || strtolower($paragraph->tagName) !== 'p' || $text($strong->textContent) !== $text($heading)) continue;
    $before = ''; $after = ''; $found = FALSE;
    foreach ($paragraph->childNodes as $child) {
      if ($child === $strong) { $found = TRUE; continue; }
      if ($found) $after .= $document->saveHTML($child);
      else $before .= $document->saveHTML($child);
    }
    if ($text($before) !== '' && preg_match($double_break, $before) && $text($after) === '' && !preg_match('~<(?!/?span\b)[^>]+>~i', $after)) { $proven = TRUE; break; }
  }
  if (!$proven) return $template;
  $slots[$role] = preg_replace('~</p>\s*\z~i', '', $role_html);
  $slots[$responsibilities] = substr($responsibilities_html, strlen($prefix[0]));
  return substr_replace($template, $role . $heading . $suffix . '</p>' . $responsibilities, $match[0][1], strlen($match[0][0]));
}

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
    // The migration separates editable company copy from its native link fields.
    // Restore their original single-paragraph flow only for the exact generated
    // scaffold and a single overview paragraph. Authored extra paragraphs retain
    // their boundaries and the underlying editorial values remain unchanged.
    $company_token = '<!--dyne:field_company_overview-->';
    $company_start = '<div class="content-intro"><h3>Company Overview:</h3>';
    $generated_join = $company_start . $company_token . '<p>Learn more at';
    if (str_starts_with($template, $generated_join)
      && str_contains($template, 'href="{{dyne:field_company_website}}"')
      && preg_match('~\A\s*<p\b[^>]*>(?:(?!</?p\b)[\s\S])*</p>\s*\z~i', $slots[$company_token] ?? '')) {
      $template = $company_start . $company_token . 'Learn more at' . substr($template, strlen($generated_join));
      $slots[$company_token] = preg_replace('~</p>\s*\z~i', ' ', $slots[$company_token]);
    }
    $template = _dynetx_restore_inline_job_heading($template, $slots, (string) ($node->get('field_body_text')->value ?? ''));
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
