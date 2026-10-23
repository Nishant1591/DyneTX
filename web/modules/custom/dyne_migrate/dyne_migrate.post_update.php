<?php

/**
 * @file
 * Post-update functions for the Dyne Migrate module.
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Restructures all job content type nodes into clean fields and nested paragraphs.
 */
function dyne_migrate_post_update_restructure_job_content_type(array &$sandbox): ?string {
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  // Initialize sandbox on first run.
  if (!isset($sandbox['nids'])) {
    $sandbox['nids'] = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'job')
      ->sort('nid', 'ASC')
      ->execute();

    $sandbox['total'] = count($sandbox['nids']);
    $sandbox['current'] = 0;
    $sandbox['converted'] = 0;
    $sandbox['skipped'] = 0;

    if (empty($sandbox['total'])) {
      $sandbox['#finished'] = 1;
      return t('No job postings found to restructure.');
    }
  }

  // Process in batches of 10 nodes per run to prevent memory/timeout issues.
  $batch_size = 10;
  $slice = array_slice($sandbox['nids'], $sandbox['current'], $batch_size);

  foreach ($slice as $nid) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $node_storage->load($nid);
    if ($node) {
      $result = _dyne_migrate_restructure_single_job($node);
      if ($result) {
        $sandbox['converted']++;
      } else {
        $sandbox['skipped']++;
      }
    }
    $sandbox['current']++;
  }

  $sandbox['#finished'] = empty($sandbox['total']) ? 1 : ($sandbox['current'] / $sandbox['total']);

  if ($sandbox['#finished'] >= 1) {
    return t('Completed job restructuring: @converted job postings converted into structured fields and nested paragraphs (@skipped already up-to-date).', [
      '@converted' => $sandbox['converted'],
      '@skipped' => $sandbox['skipped'],
    ]);
  }

  return t('Restructuring job postings: @current of @total completed...', [
    '@current' => $sandbox['current'],
    '@total' => $sandbox['total'],
  ]);
}

/**
 * Helper to restructure a single job node into modern fields & nested paragraphs.
 */
function _dyne_migrate_restructure_single_job(Node $node): bool {
  // Check if already fully converted with child subsections.
  $existing_sections = $node->get('field_job_sections')->referencedEntities();
  $has_nested_subsections = FALSE;
  foreach ($existing_sections as $sec) {
    if (!$sec->get('field_job_subsections')->isEmpty()) {
      $has_nested_subsections = TRUE;
      break;
    }
  }

  // If already restructured with nested subsections and fields populated, skip.
  if (!empty($existing_sections) && $has_nested_subsections && !$node->get('field_job_company_overview')->isEmpty()) {
    return FALSE;
  }

  $raw_body = $node->get('field_body_text')->value ?? '';
  if (empty($raw_body)) {
    return FALSE;
  }

  $parsed = _dyne_migrate_parse_greenhouse_job_html($raw_body);

  // 1. Build Parent Sections & Child Subsections
  $section_paragraphs = [];
  foreach ($parsed['sections'] as $sec_data) {
    $parent_paragraph = Paragraph::create([
      'type' => 'dyne_job_section',
      'field_heading' => $sec_data['heading'],
    ]);

    // Check if this section has child sub-sections
    if (!empty($sec_data['subsections'])) {
      $child_paragraphs = [];
      foreach ($sec_data['subsections'] as $sub_data) {
        $child = Paragraph::create([
          'type' => 'dyne_job_subsection',
          'field_heading' => $sub_data['heading'],
          'field_body_text' => [
            'value' => $sub_data['body'],
            'format' => 'dyne_job_html',
          ],
        ]);
        $child->save();
        $child_paragraphs[] = [
          'target_id' => $child->id(),
          'target_revision_id' => $child->getRevisionId(),
        ];
      }
      $parent_paragraph->set('field_job_subsections', $child_paragraphs);
    } elseif (!empty($sec_data['body'])) {
      // Direct body for simple sections like Education Requirements
      $parent_paragraph->set('field_body_text', [
        'value' => $sec_data['body'],
        'format' => 'dyne_job_html',
      ]);
    }

    $parent_paragraph->save();
    $section_paragraphs[] = [
      'target_id' => $parent_paragraph->id(),
      'target_revision_id' => $parent_paragraph->getRevisionId(),
    ];
  }

  $node->set('field_job_sections', $section_paragraphs);

  // 2. Workplace type
  $node->set('field_job_workplace_type', $parsed['workplace']);

  // 3. Company Overview & Role Summary
  if (!empty($parsed['company_overview'])) {
    $node->set('field_job_company_overview', [
      'value' => $parsed['company_overview'],
      'format' => 'dyne_job_html',
    ]);
  }
  if (!empty($parsed['role_summary'])) {
    $node->set('field_job_role_summary', [
      'value' => $parsed['role_summary'],
      'format' => 'dyne_job_html',
    ]);
  }

  // 4. Compensation Package
  $node->set('field_job_pay_title', $parsed['pay_title']);
  $node->set('field_job_pay_currency', $parsed['pay_currency']);
  if (!empty($parsed['pay_min'])) {
    $node->set('field_job_pay_min', $parsed['pay_min']);
  }
  if (!empty($parsed['pay_max'])) {
    $node->set('field_job_pay_max', $parsed['pay_max']);
  }
  if (!empty($parsed['pay_disclaimer'])) {
    $node->set('field_job_pay_disclaimer', [
      'value' => $parsed['pay_disclaimer'],
      'format' => 'dyne_job_html',
    ]);
  }

  // 5. EOE Statement
  if (!empty($parsed['eoe_statement'])) {
    $node->set('field_job_eoe_statement', [
      'value' => $parsed['eoe_statement'],
      'format' => 'dyne_job_html',
    ]);
  }

  // 6. Social Links
  $node->set('field_job_link_website', $parsed['website']);
  $node->set('field_job_link_linkedin', $parsed['linkedin']);
  $node->set('field_job_link_x', $parsed['x']);
  $node->set('field_job_link_facebook', $parsed['facebook']);

  // 7. Save revision — original field_body_text is untouched in DB
  $node->setNewRevision(TRUE);
  $node->setRevisionLogMessage('Automated post-update migration to structured fields and nested paragraphs.');
  $node->save();

  return TRUE;
}

/**
 * Robust DOM & Regex parser for job HTML.
 */
function _dyne_migrate_parse_greenhouse_job_html(string $body): array {
  $data = [
    'workplace' => 'On-site',
    'company_overview' => '',
    'role_summary' => '',
    'sections' => [],
    'pay_min' => '',
    'pay_max' => '',
    'pay_title' => 'Total Compensation Package',
    'pay_currency' => '$',
    'pay_disclaimer' => '',
    'eoe_statement' => '',
    'website' => 'https://www.dyne-tx.com/',
    'linkedin' => 'https://www.linkedin.com/company/dyne-therapeutics/',
    'x' => 'https://x.com/DyneTx',
    'facebook' => 'https://www.facebook.com/DyneTherapeutics/',
  ];

  // 1. Workplace detection
  if (preg_match('/#LI-(Hybrid|Onsite|Remote)/i', $body, $m)) {
    $data['workplace'] = ($m[1] == 'Onsite') ? 'On-site' : ucfirst(strtolower($m[1]));
  } elseif (preg_match('/\b(hybrid|remote|on-site|onsite)\b/i', $body, $m)) {
    $data['workplace'] = (strtolower($m[1]) == 'onsite') ? 'On-site' : ucfirst(strtolower($m[1]));
  }

  // 2. Pay range
  if (preg_match('/\$([0-9]{2,3},[0-9]{3})[^\$0-9]{1,150}\$([0-9]{2,3},[0-9]{3})/', $body, $m)) {
    $data['pay_min'] = $m[1];
    $data['pay_max'] = $m[2];
  }

  // 3. EOE Statement
  if (preg_match('/(Dyne Therapeutics is an equal opportunity employer.*?)(?:<\/p>|<\/div>|$)/is', $body, $m)) {
    $data['eoe_statement'] = '<p><em>' . htmlspecialchars(trim(preg_replace('/[\s\xc2\xa0]+/u', ' ', strip_tags($m[1])))) . '</em></p>';
  }

  // 4. Pay disclaimer
  if (preg_match('/(?:The target salary range|Actual compensation|actual compensation depends on factors).*?(?=(?:Dyne Therapeutics is an equal opportunity|<p[^>]*>\s*<em[^>]*>.*?equal opportunity|<div class="content-conclusion|$))/is', $body, $m)) {
    $clean_disc = trim(strip_tags($m[0], '<p><br><strong><em>'));
    $clean_disc = preg_replace('/<div class="pay-range".*?<\/div>/is', '', $clean_disc);
    $data['pay_disclaimer'] = $clean_disc;
  }

  // 5. DOM Document parsing
  $dom = new DOMDocument();
  libxml_use_internal_errors(true);
  $dom->loadHTML('<?xml encoding="utf-8" ?>' . $body);
  libxml_clear_errors();
  $xpath = new DOMXPath($dom);

  // Extract Company Overview
  $intro = $xpath->query('//div[contains(@class, "content-intro")]');
  if ($intro->length > 0) {
    $intro_html = '';
    foreach ($xpath->query('.//p', $intro->item(0)) as $p) {
      $p_text = trim(preg_replace('/[\s\xc2\xa0]+/u', ' ', $p->textContent));
      if (!empty($p_text)) {
        $intro_html .= '<p>' . htmlspecialchars($p_text) . '</p>';
      }
    }
    $data['company_overview'] = $intro_html;
  }

  // Walk nodes for Role Summary and Sections
  $nodes = $xpath->query('//body/*');
  $current_section = null;
  $collecting_role = false;
  $role_paragraphs = [];
  $sections_map = [];

  foreach ($nodes as $node) {
    $text = trim(preg_replace('/[\s\xc2\xa0]+/u', ' ', $node->textContent));
    if (empty($text)) {
      continue;
    }

    $is_major_heading = false;
    $heading_text = '';

    if (in_array(strtolower($node->nodeName), ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
      $is_major_heading = true;
      $heading_text = $text;
    } else {
      $strongs = $xpath->query('.//strong|.//b', $node);
      if ($strongs->length > 0) {
        $strong_text = trim(preg_replace('/[\s\xc2\xa0]+/u', ' ', $strongs->item(0)->textContent));
        if (strlen($strong_text) > 3 && strlen($strong_text) < 80) {
          // Check if this is an italicized/bold subheading inside Responsibilities
          if ($current_section !== null && (stripos($current_section, 'Responsibilities') !== FALSE || stripos($current_section, 'Duties') !== FALSE)) {
            $ems = $xpath->query('.//em|.//i', $node);
            if ($ems->length > 0 && stripos($strong_text, 'Education') === FALSE && stripos($strong_text, 'Requirement') === FALSE && stripos($strong_text, 'Qualification') === FALSE) {
              $sections_map[$current_section]['items'][] = [
                'type' => 'subheading',
                'title' => $strong_text,
              ];
              continue;
            }
          }
          $is_major_heading = true;
          $heading_text = $strong_text;
        }
      } else {
        $ems = $xpath->query('.//em|.//i', $node);
        if ($ems->length > 0) {
          $em_text = trim(preg_replace('/[\s\xc2\xa0]+/u', ' ', $ems->item(0)->textContent));
          if (strlen($em_text) > 3 && strlen($em_text) < 80) {
            if ($current_section !== null && (stripos($current_section, 'Responsibilities') !== FALSE || stripos($current_section, 'Duties') !== FALSE)) {
              if (stripos($em_text, 'Education') === FALSE && stripos($em_text, 'Requirement') === FALSE && stripos($em_text, 'Qualification') === FALSE) {
                $sections_map[$current_section]['items'][] = [
                  'type' => 'subheading',
                  'title' => $em_text,
                ];
                continue;
              }
            }
            $is_major_heading = true;
            $heading_text = $em_text;
          }
        }
      }
    }

    if ($is_major_heading) {
      $clean_title = rtrim(rtrim(trim(preg_replace('/[\s\xc2\xa0]+/u', ' ', $heading_text))), ':');
      if (empty($clean_title)) {
        continue;
      }
      if (stripos($clean_title, 'Company Overview') !== FALSE) {
        $collecting_role = false;
        continue;
      }
      if (stripos($clean_title, 'Role Summary') !== FALSE || stripos($clean_title, 'Position Summary') !== FALSE) {
        $collecting_role = true;
        continue;
      }
      if (stripos($clean_title, 'Pay') !== FALSE || stripos($clean_title, 'Compensation') !== FALSE || stripos($clean_title, 'Equal Opportunity') !== FALSE) {
        $collecting_role = false;
        $current_section = null;
        continue;
      }

      $collecting_role = false;
      $current_section = $clean_title;
      if (!isset($sections_map[$current_section])) {
        $sections_map[$current_section] = ['items' => []];
      }
      continue;
    }

    if ($collecting_role) {
      if (strtolower($node->nodeName) === 'p' && !empty($text) && stripos($text, '#LI-') === FALSE) {
        $role_paragraphs[] = '<p>' . htmlspecialchars($text) . '</p>';
      }
    }

    if ($current_section !== null) {
      if (in_array(strtolower($node->nodeName), ['ul', 'ol'])) {
        $items_html = '<ul>';
        foreach ($xpath->query('.//li', $node) as $li) {
          $li_text = trim(preg_replace('/[\s\xc2\xa0]+/u', ' ', $li->textContent));
          if (!empty($li_text)) {
            $items_html .= '<li>' . htmlspecialchars($li_text) . '</li>';
          }
        }
        $items_html .= '</ul>';
        $sections_map[$current_section]['items'][] = [
          'type' => 'list',
          'content' => $items_html,
        ];
      } elseif (strtolower($node->nodeName) === 'p') {
        if (stripos($text, 'pay range') === FALSE && stripos($text, '#LI-') === FALSE && stripos($text, 'compensation depends') === FALSE) {
          $sections_map[$current_section]['items'][] = [
            'type' => 'paragraph',
            'content' => '<p>' . htmlspecialchars($text) . '</p>',
          ];
        }
      }
    }
  }

  $data['role_summary'] = implode("\n", $role_paragraphs);

  // Structure sections and child subsections
  foreach ($sections_map as $sec_title => $sec_info) {
    $has_subheadings = FALSE;
    foreach ($sec_info['items'] as $item) {
      if ($item['type'] === 'subheading') {
        $has_subheadings = TRUE;
        break;
      }
    }

    if ($has_subheadings) {
      // Build child subsections
      $subsections = [];
      $current_sub_title = 'Overview';
      $current_sub_body = [];

      foreach ($sec_info['items'] as $item) {
        if ($item['type'] === 'subheading') {
          if (!empty($current_sub_body)) {
            $subsections[] = [
              'heading' => $current_sub_title,
              'body' => implode("\n", $current_sub_body),
            ];
            $current_sub_body = [];
          }
          $current_sub_title = $item['title'];
        } else {
          $current_sub_body[] = $item['content'];
        }
      }

      if (!empty($current_sub_body)) {
        $subsections[] = [
          'heading' => $current_sub_title,
          'body' => implode("\n", $current_sub_body),
        ];
      }

      $data['sections'][] = [
        'heading' => $sec_title,
        'subsections' => $subsections,
        'body' => '',
      ];
    } else {
      // Simple section without subsections
      $body_parts = [];
      foreach ($sec_info['items'] as $item) {
        $body_parts[] = $item['content'];
      }
      $data['sections'][] = [
        'heading' => $sec_title,
        'subsections' => [],
        'body' => implode("\n", $body_parts),
      ];
    }
  }

  return $data;
}
