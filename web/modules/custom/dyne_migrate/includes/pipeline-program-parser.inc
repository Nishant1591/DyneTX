<?php

/**
 * @file
 * Drupal-independent extraction of the approved pipeline page snapshot.
 *
 * This parser preserves source labels. In particular, a chart label is not a
 * regulatory approval claim. Desktop narratives are authoritative because the
 * duplicated mobile FSHD narrative in the source has a truncated paragraph.
 */

/**
 * Parse native program values without saving or consulting external sources.
 *
 * Programs are keyed by stable DYNE code. Order is one-based; chart_row_index
 * and chart_bar_index are zero-based positions in the original source chart.
 * Headings contain their inner HTML; narrative fields contain paragraph/list
 * HTML without the heading. Empty preclinical detail fields stay empty.
 */
function dyne_pipeline_parse(string $html): array {
  [$document, $root] = dyne_pipeline_document($html);
  $xpath = new DOMXPath($document);
  $class = static fn(string $name): string => dyne_pipeline_class_xpath($name);
  $chart = $xpath->query('.//div[' . $class('pipeline-chart-26') . ']', $root)->item(0);
  if (!$chart instanceof DOMElement) {
    throw new InvalidArgumentException('Pipeline source has no approved pipeline-chart-26 chart.');
  }
  $intro = $xpath->query('.//div[' . $class('pipeline-intro') . ']//div[' . $class('intro') . ']', $root)->item(0);
  $result = [
    'source_sha256' => hash('sha256', $html),
    'intro_heading_html' => '',
    'intro_html' => '',
    'chart_heading' => '',
    'stages' => [],
    'note_html' => '',
    'expansion_html' => '',
    'expansion' => ['heading' => '', 'targets' => '', 'title' => '', 'body_html' => ''],
    'programs' => [],
    'warnings' => [],
  ];
  if ($intro instanceof DOMElement) {
    foreach ($intro->childNodes as $node) {
      if ($node instanceof DOMElement && strtolower($node->tagName) === 'h1') {
        $result['intro_heading_html'] = dyne_pipeline_inner_html($node);
      }
      elseif ($node instanceof DOMElement && strtolower($node->tagName) === 'p') {
        $result['intro_html'] .= $document->saveHTML($node);
      }
    }
  }
  $heading = $xpath->query('.//h3', $chart)->item(0);
  $result['chart_heading'] = $heading ? dyne_pipeline_text($heading->textContent) : '';
  foreach ($xpath->query('.//div[' . $class('stages') . ']//span', $chart) as $stage) {
    $result['stages'][] = dyne_pipeline_text($stage->textContent);
  }
  $headlines = $xpath->query('.//div[' . $class('chart-headline') . ']', $chart);
  foreach ($headlines as $headline) {
    if (!str_contains($headline->textContent, 'Pipeline Expansion Opportunities:')) {
      continue;
    }
    $result['note_html'] = $document->saveHTML($headline);
    $label = $xpath->query('.//span', $headline)->item(0);
    $targets = $xpath->query('.//div[' . $class('ps-lg-2') . ']', $headline)->item(0);
    $result['expansion']['heading'] = $label ? dyne_pipeline_text($label->textContent) : '';
    $result['expansion']['targets'] = $targets ? dyne_pipeline_text($targets->textContent) : '';
  }
  $expansion = $xpath->query('.//*[@id="collapseEXP"]', $chart)->item(0);
  if ($expansion instanceof DOMElement) {
    $result['expansion_html'] = $document->saveHTML($expansion);
    $result['note_html'] .= $result['expansion_html'];
    $title = $xpath->query('.//h2', $expansion)->item(0);
    $result['expansion']['title'] = $title ? dyne_pipeline_text($title->textContent) : '';
    foreach ($xpath->query('.//p', $expansion) as $paragraph) {
      $result['expansion']['body_html'] .= $document->saveHTML($paragraph);
    }
  }

  $groups = [];
  $rows = $xpath->query('.//div[' . $class('chart-body') . ']', $chart);
  foreach ($rows as $row_index => $row) {
    $columns = $xpath->query('./div', $row);
    $disease_column = $columns->item(0);
    if (!$disease_column instanceof DOMElement) {
      throw new UnexpectedValueException('Pipeline row has no disease column.');
    }
    $disease_copy = $disease_column->cloneNode(TRUE);
    foreach (iterator_to_array($disease_copy->childNodes) as $child) {
      if ($child instanceof DOMElement) {
        $disease_copy->removeChild($child);
      }
    }
    $disease = dyne_pipeline_text($disease_copy->textContent);
    preg_match('/\(([A-Z][A-Z0-9]+)\)$/', $disease, $abbreviation);
    $short = $abbreviation[1] ?? (preg_replace('/\s+disease$/i', '', $disease) ?: $disease);
    $group = strtoupper($short);
    $groups[$group] ??= count($groups) + 1;
    $column_text = dyne_pipeline_text($disease_column->textContent);
    preg_match('/U\.S\.\s*:\s*(.+?)(?=\s+Europe\s*:|$)/i', $column_text, $us);
    preg_match('/Europe\s*:\s*(.+)$/i', $column_text, $europe);
    $targets = [];
    $target_column = $xpath->query('./div[' . $class('target') . ']', $row)->item(0);
    if ($target_column) {
      foreach ($xpath->query('./div', $target_column) as $target) {
        $targets[] = dyne_pipeline_text($target->textContent);
      }
      if (!$targets) {
        $targets[] = dyne_pipeline_text($target_column->textContent);
      }
    }
    $detail_link = $xpath->query('.//a[@data-mdb-target]', $disease_column)->item(0);
    $anchor = $detail_link ? ltrim($detail_link->getAttribute('href'), '#') : '';
    $mobile_id = $detail_link ? ltrim($detail_link->getAttribute('data-mdb-target'), '#') : '';
    $desktop = NULL;
    if ($anchor !== '') {
      foreach ($xpath->query('.//div[' . $class('section') . ' and ' . $class('pipeline-description') . ']', $root) as $section) {
        foreach ($xpath->query('./div[@id]', $section) as $section_anchor) {
          if ($section_anchor->getAttribute('id') === $anchor) {
            $desktop = $section;
            break 2;
          }
        }
      }
      if (!$desktop) {
        throw new UnexpectedValueException('Detailed program ' . $anchor . ' has no complete desktop narrative.');
      }
    }
    $detail_heading = $desktop ? $xpath->query('.//h2', $desktop)->item(0) : NULL;
    $detail_text = $detail_heading ? dyne_pipeline_text($detail_heading->textContent) : '';
    $bars = $xpath->query('.//div[' . $class('chart') . ']', $row);
    $milestone = $xpath->query('.//div[' . $class('eta') . ']', $row)->item(0);
    preg_match('/\b(program-\d+)\b/', $row->getAttribute('class'), $source_group);
    foreach ($bars as $bar_index => $bar) {
      $phase = $xpath->query('.//span', $bar)->item(0);
      $name_copy = $bar->cloneNode(TRUE);
      foreach (iterator_to_array($name_copy->getElementsByTagName('span')) as $span) {
        $span->parentNode->removeChild($span);
      }
      $chart_name = dyne_pipeline_text($name_copy->textContent);
      if (!preg_match('/\bDYNE-\d+\b/', $chart_name . ' ' . $detail_text, $code_match)) {
        throw new UnexpectedValueException('Pipeline program has no source-supported stable code: ' . $chart_name);
      }
      $code = $code_match[0];
      if (isset($result['programs'][$code])) {
        throw new UnexpectedValueException('Duplicate pipeline program code: ' . $code);
      }
      $name = $chart_name;
      $short_name = '';
      if (preg_match('/^(.+?)\s*\(([^()]+)\)$/', $chart_name, $names)) {
        $name = trim($names[1]);
        $short_name = trim($names[2]);
      }
      preg_match('/(?:^|;)\s*width\s*:\s*([0-9]+(?:\.[0-9]+)?)%/i', $bar->getAttribute('style'), $width);
      if (!$width || (float) $width[1] > 100) {
        throw new UnexpectedValueException('Pipeline program has an invalid source progress width: ' . $code);
      }
      $row_xpath = '(//div[' . $class('chart-body') . '])[' . ($row_index + 1) . ']';
      $program = [
        'code' => $code,
        'name' => $name,
        'short_name' => $short_name,
        'chart_name' => $chart_name,
        'aliases' => array_values(array_unique(array_filter([$code, $name, $short_name]))),
        'disease' => $disease,
        'disease_short' => $short,
        'prevalence_us' => trim($us[1] ?? ''),
        'prevalence_europe' => trim($europe[1] ?? ''),
        'target' => $targets[$bar_index] ?? '',
        'phase_label' => $phase ? dyne_pipeline_text($phase->textContent) : '',
        'milestone' => count($bars) === 1 && $milestone ? dyne_pipeline_text($milestone->textContent) : '',
        'bar_percent' => (float) $width[1],
        'order' => count($result['programs']) + 1,
        'group' => $group,
        'group_order' => $groups[$group],
        'source_group' => $source_group[1] ?? '',
        'chart_row_index' => $row_index,
        'chart_bar_index' => $bar_index,
        'anchor' => $anchor,
        // These three routes are independently preserved archive records;
        // preclinical programs have no invented detail route.
        'legacy_alias' => match ($code) {
          'DYNE-251' => '/dyne-251-for-dmd',
          'DYNE-101' => '/dyne-101-for-dm1',
          'DYNE-302' => '/dyne-302-for-fshd',
          default => '',
        },
        'html' => array_fill_keys(['program_heading', 'disease_heading', 'introduction', 'disease_overview', 'onset', 'cause', 'symptoms', 'unmet_need', 'additional'], ''),
        'section_labels' => [],
        'cta' => ['url' => '', 'title' => ''],
        'back_link' => ['url' => '', 'title' => ''],
        'video' => ['id' => '', 'title' => '', 'cover' => '', 'alt' => '', 'caption_html' => ''],
        'selectors' => [
          'chart_row_xpath' => $row_xpath,
          'chart_bar_xpath' => '(' . $row_xpath . '//div[' . $class('chart') . '])[' . ($bar_index + 1) . ']',
          'desktop_xpath' => $anchor !== '' ? '//div[' . $class('section') . ' and ' . $class('pipeline-description') . '][div[@id="' . $anchor . '"]]' : '',
          'mobile_xpath' => $mobile_id !== '' ? '//*[@id="' . $mobile_id . '"]' : '',
          'desktop_id' => $anchor,
          'mobile_id' => $mobile_id,
        ],
      ];
      if ($desktop) {
        dyne_pipeline_detail($desktop, $program, $result['warnings']);
      }
      $result['programs'][$code] = $program;
    }
  }
  return $result;
}

/** Extract the six semantic detail sections and separate CTA/video values. */
function dyne_pipeline_detail(DOMElement $section, array &$program, array &$warnings): void {
  $document = $section->ownerDocument;
  $xpath = new DOMXPath($document);
  $video = $xpath->query('.//*[@embd-data-url]', $section)->item(0);
  if ($video instanceof DOMElement) {
    $cover = $xpath->query('.//img', $video)->item(0);
    $program['video'] = [
      'id' => $video->getAttribute('embd-data-url'),
      'title' => $video->getAttribute('embd-data-title'),
      'cover' => $cover ? $cover->getAttribute('src') : '',
      'alt' => $cover ? $cover->getAttribute('alt') : '',
      'caption_html' => '',
    ];
  }
  $current = 'introduction';
  $h2_count = 0;
  foreach ($xpath->query('.//h2 | .//h3 | .//p | .//ul | .//ol | .//blockquote | .//table | .//dl', $section) as $node) {
    $tag = strtolower($node->tagName);
    // A top-level list/table is one complete field value, including nested lists.
    for ($parent = $node->parentNode; $parent && $parent !== $section; $parent = $parent->parentNode) {
      if ($parent instanceof DOMElement && in_array(strtolower($parent->tagName), ['p', 'ul', 'ol', 'blockquote', 'table', 'dl'], TRUE)) {
        continue 2;
      }
    }
    if ($tag === 'h2' || $tag === 'h3') {
      if ($tag === 'h2' && ++$h2_count <= 2) {
        $current = $h2_count === 1 ? 'introduction' : 'disease_overview';
        $program['html'][$h2_count === 1 ? 'program_heading' : 'disease_heading'] = dyne_pipeline_inner_html($node);
        continue;
      }
      $label = dyne_pipeline_text($node->textContent);
      $current = match (TRUE) {
        (bool) preg_match('/\bOnset$/i', $label) => 'onset',
        (bool) preg_match('/\bCause$/i', $label) => 'cause',
        (bool) preg_match('/\bSymptoms$/i', $label) => 'symptoms',
        (bool) preg_match('/\bUnmet Therapeutic Need$/i', $label) => 'unmet_need',
        default => 'additional',
      };
      $program['section_labels'][$current] = dyne_pipeline_inner_html($node);
      if ($current === 'additional') {
        $program['html']['additional'] .= $document->saveHTML($node);
        $warnings[] = $program['code'] . ': unknown detail heading preserved: ' . $label;
      }
      continue;
    }
    $anchors = $xpath->query('.//a', $node);
    if ($anchors->length === 1) {
      $anchor = $anchors->item(0);
      if (str_starts_with($anchor->getAttribute('href'), '/clinical-trials/') && dyne_pipeline_text($node->textContent) === dyne_pipeline_text($anchor->textContent)) {
        $program['cta'] = ['url' => $anchor->getAttribute('href'), 'title' => dyne_pipeline_text($anchor->textContent)];
        continue;
      }
    }
    if ($tag === 'p' && $program['video']['id'] !== '' && dyne_pipeline_text($node->textContent) === dyne_pipeline_text($program['video']['title'])) {
      $program['video']['caption_html'] = dyne_pipeline_inner_html($node);
      continue;
    }
    $program['html'][$current] .= $document->saveHTML($node);
  }
  foreach ($xpath->query('.//a[@href="#chart"]', $section) as $anchor) {
    $program['back_link'] = ['url' => $anchor->getAttribute('href'), 'title' => dyne_pipeline_text($anchor->textContent)];
  }
}

/**
 * Select live composed HTML first, using an explicit archive fallback.
 *
 * Accepts the live-components.json list ({id, revision, bundle, html}), or a
 * snapshot with pages and paragraphs. No file access or Drupal APIs are used.
 */
function dyne_pipeline_source_manifest(array $snapshot, array $fixture = [], string $snapshot_path = 'output/pipeline-programs/live-components.json', string $fixture_path = 'web/modules/custom/dyne_migrate/data/legacy-content.json'): array {
  $candidates = [];
  $collect = static function (array $data, string $pointer = '') use (&$collect, &$candidates): void {
    if (isset($data['html']) && is_string($data['html']) && str_contains($data['html'], 'pipeline-chart-26')) {
      $candidates[] = ['html' => $data['html'], 'pointer' => $pointer . '/html', 'id' => $data['id'] ?? NULL, 'revision' => $data['revision'] ?? NULL];
    }
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $collect($value, $pointer . '/' . $key);
      }
    }
  };
  $collect($snapshot);
  if (count($candidates) > 1) {
    throw new UnexpectedValueException('Live snapshot includes multiple pipeline HTML candidates; select the referenced current component.');
  }
  if ($candidates) {
    $candidate = $candidates[0];
    $html = $candidate['html'];
    $source = ['kind' => 'live_snapshot', 'path' => $snapshot_path, 'json_pointer' => $candidate['pointer'], 'paragraph_id' => $candidate['id'], 'paragraph_revision_id' => $candidate['revision'], 'fallback_reason' => ''];
  }
  else {
    $record = NULL;
    foreach ($fixture['records'] ?? [] as $index => $candidate) {
      if (($candidate['alias'] ?? '') === '/pipeline' && (string) ($candidate['source_id'] ?? '') === '1149') {
        $record = $candidate;
        $record_index = $index;
        break;
      }
    }
    $html = $record['components'][1]['fields']['field_body_text'] ?? '';
    if ($html === '') {
      throw new InvalidArgumentException('Neither live composed pipeline HTML nor source1149 fixture HTML is available.');
    }
    $source = ['kind' => 'archive_fixture', 'path' => $fixture_path, 'json_pointer' => '/records/' . $record_index . '/components/1/fields/field_body_text', 'source_id' => 1149, 'modified' => $record['modified'] ?? '', 'fallback_reason' => 'Live snapshot did not include the composed pipeline body.'];
  }
  return ['source' => $source, 'source_html' => $html] + dyne_pipeline_parse($html);
}

/** Create a UTF-8 fragment DOM without changing global libxml behavior. */
function dyne_pipeline_document(string $html): array {
  $document = new DOMDocument('1.0', 'UTF-8');
  $previous = libxml_use_internal_errors(TRUE);
  try {
    $document->loadHTML('<?xml encoding="UTF-8"><div id="dyne-pipeline-source">' . $html . '</div>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
  }
  finally {
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
  }
  return [$document, $document->getElementById('dyne-pipeline-source')];
}

/** Class-aware XPath predicate for trusted internal selector names. */
function dyne_pipeline_class_xpath(string $class): string {
  if (!preg_match('/^[a-zA-Z0-9_-]+$/', $class)) {
    throw new InvalidArgumentException('Invalid pipeline CSS class.');
  }
  return 'contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")';
}

/** Serialize child markup, excluding the source presentation wrapper. */
function dyne_pipeline_inner_html(DOMNode $node): string {
  $html = '';
  foreach ($node->childNodes as $child) {
    $html .= $node->ownerDocument->saveHTML($child);
  }
  return trim($html);
}

/** Normalize insignificant whitespace without changing Unicode source copy. */
function dyne_pipeline_text(string $text): string {
  return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $text) ?? $text);
}
