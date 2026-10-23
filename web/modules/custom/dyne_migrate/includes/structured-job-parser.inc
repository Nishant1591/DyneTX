<?php

/**
 * @file
 * Lossless, Drupal-independent extraction of editorial job fields.
 *
 * HTML fields replace <!--dyne:field_name--> slots. Link fields replace
 * {{dyne:field_name}} tokens inside href attributes. The template retains
 * headings, labels, punctuation, and presentation wrappers, never job copy.
 */

/**
 * Split a job description into semantic values and a presentation scaffold.
 */
function dyne_job_parse(string $html): array {
  $html_fields = [
    'field_company_overview', 'field_role_summary', 'field_responsibilities',
    'field_education_skills', 'field_compensation_notes', 'field_job_disclaimer',
    'field_equal_opportunity', 'field_additional_job_content',
  ];
  $link_fields = [
    'field_company_website', 'field_company_twitter',
    'field_company_linkedin', 'field_company_facebook',
  ];
  $scalar_fields = [
    'field_pay_range_label', 'field_pay_range_min', 'field_pay_range_max',
    'field_pay_range_currency', 'field_workplace_arrangement',
  ];
  $result = [
    'fields' => array_fill_keys(array_merge($html_fields, $link_fields, $scalar_fields), ''),
    'html_fields' => $html_fields,
    'link_fields' => $link_fields,
    'scalar_fields' => $scalar_fields,
    'unknown_headings' => [],
    'warnings' => [],
    'template' => '',
  ];
  [$document, $root] = dyne_job_document($html);
  dyne_job_split_inline_headings($root);
  $seen = [];
  $failed = FALSE;
  $result['template'] = dyne_job_parse_container($root, $result, $seen, $failed);
  // Future provider formats must satisfy the same preservation contract as the
  // source archive. Unrecognized layouts remain editable without silent loss.
  $preserved = dyne_job_signature(dyne_job_render_parts($result)) === dyne_job_signature($html);
  if ($failed || !$preserved) {
    // A field cannot occur twice in a scaffold without duplicating edited copy.
    // Keep unusual provider markup intact for an editor instead of guessing.
    $result['fields'] = array_fill_keys(array_keys($result['fields']), '');
    $result['fields']['field_additional_job_content'] = $html;
    $result['template'] = '<!--dyne:field_additional_job_content-->';
    $result['warnings'][] = $failed
      ? 'Interleaved repeated sections preserved in Additional Job Content for editorial review.'
      : 'Unrecognized section layout preserved in Additional Job Content for editorial review.';
  }
  // DOM textContent joins adjacent blocks without a delimiter (for example
  // "#LI-OnsiteMA Pay Range"). Preserve tag boundaries for metadata matching.
  $source_text = dyne_job_text(preg_replace('/<[^>]+>/', ' ', $html));
  if (preg_match('/#LI-(Onsite|On-site|Remote|Hybrid)\b/i', $source_text, $match)) {
    $result['fields']['field_workplace_arrangement'] = match (strtolower($match[1])) {
      'remote' => 'Remote', 'hybrid' => 'Hybrid', default => 'On-site',
    };
  }
  return $result;
}

/**
 * Reconstruct parsed values without Drupal (for round-trip verification).
 *
 * Production callers must process formatted text with Drupal's configured
 * text format before inserting it. This helper intentionally does not sanitize.
 */
function dyne_job_render_parts(array $parsed): string {
  $replace = [];
  foreach ($parsed['fields'] as $name => $value) {
    if (in_array($name, ['field_pay_range_min', 'field_pay_range_max'], TRUE) && $value !== '') {
      $number = (float) $value;
      $value = number_format($number, floor($number) === $number ? 0 : 2, '.', ',');
    }
    $replace['<!--dyne:' . $name . '-->'] = in_array($name, $parsed['html_fields'], TRUE)
      ? (string) $value : htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $replace['{{dyne:' . $name . '}}'] = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
  return strtr($parsed['template'], $replace);
}

/**
 * Create a UTF-8 fragment container without changing libxml's global setting.
 */
function dyne_job_document(string $html): array {
  $document = new DOMDocument('1.0', 'UTF-8');
  $previous = libxml_use_internal_errors(TRUE);
  try {
    $document->loadHTML('<?xml encoding="UTF-8"><div id="dyne-job-source">' . $html . '</div>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
  }
  finally {
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
  }
  return [$document, $document->getElementById('dyne-job-source')];
}

/**
 * Parse sibling sections, keeping their source order in the scaffold.
 */
function dyne_job_parse_container(DOMElement $container, array &$result, array &$seen, bool &$failed): string {
  $template = '';
  $field = NULL;
  $content = '';
  $flush = static function () use (&$template, &$field, &$content, &$result, &$seen, &$failed): void {
    if ($field !== NULL && trim($content) !== '') {
      if (isset($seen[$field])) {
        $failed = TRUE;
      }
      $seen[$field] = TRUE;
      if ($field === 'field_company_overview') {
        [$content, $links] = dyne_job_company_parts($content, $result);
      }
      else {
        $links = '';
      }
      $result['fields'][$field] .= $content;
      $template .= '<!--dyne:' . $field . '-->' . $links;
    }
    else {
      $template .= $content;
    }
    $content = '';
  };
  foreach ($container->childNodes as $node) {
    $serialized = $container->ownerDocument->saveHTML($node);
    if ($node instanceof DOMElement && dyne_job_has_class($node, 'content-pay-transparency')) {
      $flush();
      $template .= dyne_job_pay_parts($node, $result);
      continue;
    }
    if ($node instanceof DOMElement && dyne_job_has_class($node, 'content-conclusion')) {
      $flush();
      $template .= dyne_job_conclusion_parts($node, $result, $seen, $failed);
      continue;
    }
    $heading = $node instanceof DOMElement ? dyne_job_heading($node) : NULL;
    if ($heading !== NULL) {
      [$next_field, $label] = $heading;
      if ($next_field === 'field_additional_job_content') {
        $result['unknown_headings'][] = $label;
      }
      if ($next_field === $field && $content !== '') {
        // Repeated adjacent headings remain editable in the same section.
        $content .= $serialized;
        continue;
      }
      $flush();
      $field = $next_field;
      if ($next_field === 'field_additional_job_content') {
        $content = $serialized;
      }
      else {
        $template .= $serialized;
      }
      continue;
    }
    // Provider wrappers containing multiple sections are presentation only.
    if ($node instanceof DOMElement && in_array(strtolower($node->tagName), ['div', 'section', 'article', 'main'], TRUE) && dyne_job_contains_heading($node)) {
      $flush();
      $outer = $node->cloneNode(FALSE);
      $outer_html = $container->ownerDocument->saveHTML($outer);
      $close = '</' . $node->tagName . '>';
      $open = substr($outer_html, 0, -strlen($close));
      $template .= $open . dyne_job_parse_container($node, $result, $seen, $failed) . $close;
      continue;
    }
    if ($field === NULL && trim(dyne_job_text($node->textContent)) !== '') {
      $field = 'field_additional_job_content';
    }
    $content .= $serialized;
  }
  $flush();
  return $template;
}

/**
 * Recognize whole block headings; inline bold/list subheadings stay intact.
 */
function dyne_job_heading(DOMElement $node): ?array {
  if (!preg_match('/^(?:h[1-6]|p)$/i', $node->tagName)) {
    return NULL;
  }
  $label = dyne_job_text($node->textContent);
  $normalized = strtolower(rtrim($label, ': '));
  $known = [
    'company overview' => 'field_company_overview',
    'about dyne' => 'field_company_overview',
    'role summary' => 'field_role_summary',
    'position summary' => 'field_role_summary',
    'primary responsibilities include' => 'field_responsibilities',
    'primary responsibilities' => 'field_responsibilities',
    'responsibilities' => 'field_responsibilities',
    'education and skills requirements' => 'field_education_skills',
    'experience and skills requirements' => 'field_education_skills',
    'education and experience' => 'field_education_skills',
    'qualifications' => 'field_education_skills',
    'compensation' => 'field_compensation_notes',
    'equal opportunity' => 'field_equal_opportunity',
  ];
  if (isset($known[$normalized])) {
    return [$known[$normalized], $label];
  }
  // Word exports use p > strong > span rather than heading elements.
  if ($label !== '' && (preg_match('/^h[1-6]$/i', $node->tagName)
    || (strlen($label) < 100 && str_ends_with($label, ':') && $node->getElementsByTagName('strong')->length))) {
    return ['field_additional_job_content', $label];
  }
  return NULL;
}

function dyne_job_contains_heading(DOMElement $node): bool {
  foreach ($node->childNodes as $child) {
    if ($child instanceof DOMElement && (dyne_job_heading($child) !== NULL || dyne_job_contains_heading($child))) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Word sometimes appends the next section heading to the preceding paragraph.
 */
function dyne_job_split_inline_headings(DOMElement $root): void {
  foreach (iterator_to_array($root->getElementsByTagName('p')) as $paragraph) {
    foreach (iterator_to_array($paragraph->getElementsByTagName('strong')) as $strong) {
      $heading = $paragraph->ownerDocument->createElement('p');
      $heading->appendChild($strong->cloneNode(TRUE));
      $recognized = dyne_job_heading($heading);
      if ($recognized === NULL || $recognized[0] === 'field_additional_job_content'
        || dyne_job_text($paragraph->textContent) === dyne_job_text($strong->textContent)) {
        continue;
      }
      [$before, $after] = dyne_job_split_around($paragraph, $strong);
      $parent = $paragraph->parentNode;
      $parent->insertBefore($before, $paragraph);
      $parent->insertBefore($heading, $paragraph);
      $parent->insertBefore($after, $paragraph);
      $parent->removeChild($paragraph);
      // The suffix may contain another embedded section heading.
      dyne_job_split_inline_headings($parent);
      break;
    }
  }
}

/**
 * Clone the content before/after a descendant without losing ancestor markup.
 */
function dyne_job_split_around(DOMElement $node, DOMElement $target): array {
  $before = $node->cloneNode(FALSE);
  $after = $node->cloneNode(FALSE);
  $passed = FALSE;
  foreach ($node->childNodes as $child) {
    if ($child === $target) {
      $passed = TRUE;
      continue;
    }
    $contains = FALSE;
    if ($child instanceof DOMElement) {
      foreach ($child->getElementsByTagName('*') as $descendant) {
        if ($descendant === $target) {
          $contains = TRUE;
          break;
        }
      }
    }
    if ($contains) {
      [$left, $right] = dyne_job_split_around($child, $target);
      $before->appendChild($left);
      $after->appendChild($right);
      $passed = TRUE;
    }
    else {
      ($passed ? $after : $before)->appendChild($child->cloneNode(TRUE));
    }
  }
  return [$before, $after];
}

/**
 * Separate company narrative and social link destinations without resolving URLs.
 */
function dyne_job_company_parts(string $html, array &$result): array {
  [$document, $root] = dyne_job_document($html);
  $links = '';
  foreach ($root->getElementsByTagName('p') as $paragraph) {
    foreach ($paragraph->childNodes as $child) {
      if ($child instanceof DOMText && preg_match('/Learn more at\s*/ui', $child->nodeValue, $match, PREG_OFFSET_CAPTURE)) {
        $offset = $match[0][1];
        $tail = $document->createElement('p');
        $tail->appendChild($document->createTextNode(substr($child->nodeValue, $offset)));
        $child->nodeValue = substr($child->nodeValue, 0, $offset);
        while ($child->nextSibling) {
          $tail->appendChild($child->nextSibling);
        }
        // Keep exactly the source link labels and punctuation in the scaffold.
        foreach ($tail->getElementsByTagName('a') as $anchor) {
          $label = strtolower(dyne_job_text($anchor->textContent));
          $field = match ($label) {
            'x', 'twitter', 'x / twitter' => 'field_company_twitter',
            'linkedin' => 'field_company_linkedin',
            'facebook' => 'field_company_facebook',
            default => preg_match('#^(?:https?://)?(?:www\.)?dyne-tx\.com/?$#i', $label) ? 'field_company_website' : NULL,
          };
          if ($field !== NULL && $anchor->hasAttribute('href') && $result['fields'][$field] === '') {
            $result['fields'][$field] = $anchor->getAttribute('href');
            $anchor->setAttribute('href', '{{dyne:' . $field . '}}');
          }
        }
        // libxml percent-encodes braces in URI attributes while serializing.
        $links .= preg_replace('/%7B%7Bdyne:(field_[a-z_]+)%7D%7D/i', '{{dyne:$1}}', $document->saveHTML($tail));
        // Only one standard corporate footer is extracted per paragraph.
        break;
      }
    }
  }
  return [dyne_job_inner_html($root), $links];
}

/**
 * Expose pay labels and amounts while preserving currency symbols and grouping.
 */
function dyne_job_pay_parts(DOMElement $node, array &$result): string {
  $clone = $node->cloneNode(TRUE);
  foreach ($clone->getElementsByTagName('div') as $child) {
    if (dyne_job_has_class($child, 'title')) {
      $result['fields']['field_pay_range_label'] = $child->textContent;
      while ($child->firstChild) {
        $child->removeChild($child->firstChild);
      }
      $child->appendChild($child->ownerDocument->createComment('dyne:field_pay_range_label'));
    }
    if (dyne_job_has_class($child, 'pay-range')) {
      $amounts = [];
      foreach ($child->getElementsByTagName('span') as $span) {
        if (preg_match('/([\d,]+(?:\.\d+)?)\s*([A-Z]{3})?/', $span->textContent, $match)) {
          $amounts[] = [$span, $match];
        }
      }
      if (count($amounts) === 2) {
        foreach ($amounts as $index => [$span, $match]) {
          $field = $index === 0 ? 'field_pay_range_min' : 'field_pay_range_max';
          $result['fields'][$field] = str_replace(',', '', $match[1]);
          $text = $span->textContent;
          $text = str_replace($match[1], '<!--dyne:' . $field . '-->', $text);
          if (!empty($match[2])) {
            $result['fields']['field_pay_range_currency'] = $match[2];
            $text = str_replace($match[2], '<!--dyne:field_pay_range_currency-->', $text);
          }
          while ($span->firstChild) {
            $span->removeChild($span->firstChild);
          }
          // Reparse comments as nodes, rather than escape the placeholders.
          [$parsed, $root] = dyne_job_document($text);
          foreach (iterator_to_array($root->childNodes) as $part) {
            $span->appendChild($span->ownerDocument->importNode($part, TRUE));
          }
        }
      }
    }
  }
  return $node->ownerDocument->saveHTML($clone);
}

/**
 * Split compensation policy, job scope disclaimer, and equal opportunities.
 */
function dyne_job_conclusion_parts(DOMElement $node, array &$result, array &$seen, bool &$failed): string {
  $clone = $node->cloneNode(FALSE);
  $outer = $node->ownerDocument->saveHTML($clone);
  $close = '</' . $node->tagName . '>';
  $template = substr($outer, 0, -strlen($close));
  foreach ($node->childNodes as $child) {
    $text = dyne_job_text($child->textContent);
    if ($text === '') {
      $template .= $node->ownerDocument->saveHTML($child);
      continue;
    }
    $field = preg_match('/^(?:The pay range|Individual compensation)/i', $text)
      ? 'field_compensation_notes'
      : (preg_match('/equal opportunity|will not discriminate/i', $text)
        ? 'field_equal_opportunity'
        : (preg_match('/^(?:The statements contained|Individuals may perform)/i', $text)
          ? 'field_job_disclaimer' : 'field_additional_job_content'));
    if (isset($seen[$field])) {
      // Adjacent same-kind paragraphs share a single editable field.
      if (!str_ends_with($template, '<!--dyne:' . $field . '-->')) {
        $failed = TRUE;
      }
    }
    else {
      $seen[$field] = TRUE;
      $template .= '<!--dyne:' . $field . '-->';
    }
    $result['fields'][$field] .= $node->ownerDocument->saveHTML($child);
  }
  return $template . $close;
}

function dyne_job_has_class(DOMElement $node, string $class): bool {
  return in_array($class, preg_split('/\s+/', trim($node->getAttribute('class'))), TRUE);
}

function dyne_job_inner_html(DOMElement $node): string {
  $html = '';
  foreach ($node->childNodes as $child) {
    $html .= $node->ownerDocument->saveHTML($child);
  }
  return $html;
}

function dyne_job_text(string $text): string {
  return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

/**
 * Ordered visible copy and links are the minimum lossless parsing contract.
 */
function dyne_job_signature(string $html): array {
  [, $root] = dyne_job_document($html);
  $links = [];
  foreach ($root->getElementsByTagName('a') as $anchor) {
    $links[] = [$anchor->getAttribute('href'), $anchor->textContent];
  }
  return ['text' => dyne_job_text($root->textContent), 'links' => $links];
}
