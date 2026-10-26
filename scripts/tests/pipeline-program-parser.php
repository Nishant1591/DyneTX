<?php

/**
 * @file
 * Read-only source-fidelity checks; run with PHP including DOM/libxml.
 *
 * ddev exec php scripts/tests/pipeline-program-parser.php
 */

require_once dirname(__DIR__, 2) . '/web/modules/custom/dyne_migrate/includes/pipeline-program-parser.inc';

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
  $checks++;
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$base = dirname(__DIR__, 2);
$fixture = json_decode(file_get_contents($base . '/web/modules/custom/dyne_migrate/data/legacy-content.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$snapshot_path = $base . '/output/pipeline-programs/live-components.json';
$snapshot = is_file($snapshot_path) ? json_decode(file_get_contents($snapshot_path), TRUE, 512, JSON_THROW_ON_ERROR) : [];
$manifest = dyne_pipeline_source_manifest($snapshot, $fixture);
$programs = $manifest['programs'];
$codes = ['DYNE-251', 'DYNE-101', 'DYNE-302', 'DYNE-253', 'DYNE-245', 'DYNE-244', 'DYNE-255', 'DYNE-401'];
$assert(array_keys($programs) === $codes, 'All eight named programs remain in source chart order');
$assert($manifest['warnings'] === [], 'Approved source parses without unknown sections');
$assert($manifest['source_sha256'] === hash('sha256', $manifest['source_html']), 'Manifest identifies the exact authority HTML');
if ($snapshot) {
  $assert($manifest['source']['kind'] === 'live_snapshot', 'Live composed content takes precedence over the old fixture');
  $assert((string) $manifest['source']['paragraph_id'] === '324', 'Current paragraph324 supplies live source');
  $assert((string) $manifest['source']['paragraph_revision_id'] === '998', 'Current source revision998 is identified');
}
$assert(!str_contains($manifest['intro_html'], '<h1'), 'Introduction prose is separate from its editable heading');
$assert(substr_count($manifest['intro_html'], '<p>') === 2, 'Both exact introductory paragraphs extracted');
$assert($manifest['intro_heading_html'] === 'a pipeline driven by purpose with the potential to effect real change', 'Source introduction heading preserved');
$assert($manifest['chart_heading'] === 'Neuromuscular Pipeline Leveraging Clinically Validated Platform', 'Chart heading preserved');
$assert($manifest['stages'] === ['PRECLINICAL', 'CLINICAL', 'REGISTRATIONAL', 'Commercial'], 'All four original stage labels preserved');
$assert($manifest['expansion']['heading'] === 'Pipeline Expansion Opportunities:', 'Expansion label preserved');
$assert($manifest['expansion']['targets'] === 'CNS, Rare skeletal, Cardiac, Metabolic', 'Expansion target copy preserved');
$assert($manifest['expansion']['title'] === 'Expansion Opportunities', 'Expansion detail title preserved');
$assert(str_contains($manifest['expansion']['body_html'], 'By rationally selecting therapeutic payloads'), 'Expansion prose is preserved independently');
$assert(str_contains($manifest['note_html'], 'collapseEXP'), 'Complete footer scaffold retained for audit');

$expected = [
  'DYNE-251' => ['Exon 51', 'Commercial', 74, 'DMD', '~12,000', '~16,000', 'PDUFA Jan 21, 2027', 0, 0],
  'DYNE-101' => ['DMPK', 'Registration', 64, 'DM1', '~40,000', '~55,000', '', 1, 0],
  'DYNE-302' => ['DUX4', 'Clinical', 24, 'FSHD', '~15,000-40,000', '~20,000-50,000', 'IND Cleared', 2, 0],
  'DYNE-253' => ['Exon 53', 'Preclinical', 15, 'DMD', '~12,000', '~16,000', '', 3, 0],
  'DYNE-245' => ['Exon 45', 'Preclinical', 15, 'DMD', '~12,000', '~16,000', '', 3, 1],
  'DYNE-244' => ['Exon 44', 'Preclinical', 15, 'DMD', '~12,000', '~16,000', '', 3, 2],
  'DYNE-255' => ['Exon 55', 'Preclinical', 15, 'DMD', '~12,000', '~16,000', '', 3, 3],
  'DYNE-401' => ['GAA', 'Preclinical', 16, 'POMPE', '~4,500', '~5,500', '', 4, 0],
];
[$document, $root] = dyne_pipeline_document($manifest['source_html']);
$xpath = new DOMXPath($document);
$semantic_fields = ['introduction', 'disease_overview', 'onset', 'cause', 'symptoms', 'unmet_need'];
$signature = static function (string $html): string {
  [, $root] = dyne_pipeline_document($html);
  return dyne_pipeline_text($root->textContent);
};
foreach ($programs as $code => $program) {
  [$target, $phase, $width, $group, $us, $europe, $milestone, $row, $bar] = $expected[$code];
  $assert($program['target'] === $target, $code . ': target is mapped to its own source bar');
  $assert($program['phase_label'] === $phase, $code . ': exact chart phase label retained without medical reinterpretation');
  $assert($program['bar_percent'] === (float) $width, $code . ': source bar width retained');
  $assert($program['group'] === $group, $code . ': disease grouping deterministic');
  $assert($program['prevalence_us'] === $us, $code . ': US prevalence extracted separately');
  $assert($program['prevalence_europe'] === $europe, $code . ': Europe prevalence extracted separately');
  $assert($program['milestone'] === $milestone, $code . ': source milestone retained separately');
  $assert($program['chart_row_index'] === $row && $program['chart_bar_index'] === $bar, $code . ': original grouping and positions preserved');
  $assert($program['order'] === array_search($code, $codes, TRUE) + 1, $code . ': editorial order deterministic');
  $assert(in_array($code, $program['aliases'], TRUE), $code . ': stable code is an alias');
  $selected_bar = $xpath->query($program['selectors']['chart_bar_xpath'])->item(0);
  $assert($selected_bar && str_contains(dyne_pipeline_text($selected_bar->textContent), $program['chart_name']), $code . ': binding selector resolves the correct source bar');
  $assert($program['html']['additional'] === '', $code . ': no unexplained detail content');
  if ($program['anchor'] === '') {
    foreach ($semantic_fields as $field) {
      $assert($program['html'][$field] === '', $code . ': absent detail prose is not fabricated: ' . $field);
    }
    $assert($program['legacy_alias'] === '', $code . ': no invented historical detail route');
    continue;
  }
  foreach ($semantic_fields as $field) {
    $assert($program['html'][$field] !== '', $code . ': separate semantic field populated: ' . $field);
    $assert(!preg_match('/<h[1-6]\b/', $program['html'][$field]), $code . ': section headings are independent of prose: ' . $field);
  }
  $assert(!str_contains($program['html']['program_heading'], '<h2'), $code . ': program heading is inner markup');
  $assert(!str_contains($program['html']['disease_heading'], '<h2'), $code . ': disease heading is inner markup');
  $section = $xpath->query($program['selectors']['desktop_xpath'])->item(0);
  $assert($section instanceof DOMElement, $code . ': complete desktop source selected');
  $assert($xpath->query($program['selectors']['mobile_xpath'])->length === 1, $code . ': duplicated mobile selector recorded');
  $source_blocks = '';
  foreach ($xpath->query('.//p | .//ul | .//ol', $section) as $node) {
    for ($parent = $node->parentNode; $parent && $parent !== $section; $parent = $parent->parentNode) {
      if ($parent instanceof DOMElement && in_array($parent->tagName, ['p', 'ul', 'ol'], TRUE)) {
        continue 2;
      }
    }
    $links = $xpath->query('.//a', $node);
    if ($links->length === 1 && $signature($document->saveHTML($node)) === dyne_pipeline_text($links->item(0)->textContent) && str_starts_with($links->item(0)->getAttribute('href'), '/clinical-trials/')) {
      continue;
    }
    if ($program['video']['id'] !== '' && dyne_pipeline_text($node->textContent) === $program['video']['title']) {
      continue;
    }
    $source_blocks .= $document->saveHTML($node);
  }
  $actual_blocks = implode('', array_map(static fn(string $field): string => $program['html'][$field], $semantic_fields));
  $assert($signature($actual_blocks) === $signature($source_blocks), $code . ': every narrative word is preserved once in source order');
  $actual_links = [];
  [, $parts] = dyne_pipeline_document($actual_blocks);
  foreach ($parts->getElementsByTagName('a') as $link) {
    $actual_links[] = [$link->getAttribute('href'), dyne_pipeline_text($link->textContent)];
  }
  if ($program['cta']['url'] !== '') {
    $actual_links[] = [$program['cta']['url'], $program['cta']['title']];
  }
  $actual_links[] = [$program['back_link']['url'], $program['back_link']['title']];
  $source_links = [];
  foreach ($section->getElementsByTagName('a') as $link) {
    $source_links[] = [$link->getAttribute('href'), dyne_pipeline_text($link->textContent)];
  }
  sort($actual_links);
  sort($source_links);
  $assert($actual_links === $source_links, $code . ': all original detail link destinations and labels preserved');
  $legacy = array_values(array_filter($fixture['records'], static fn(array $row): bool => $row['alias'] === $program['legacy_alias']));
  $assert(count($legacy) === 1, $code . ': historical alias is an actual preserved source record');
}
$assert($programs['DYNE-251']['name'] === 'Zeleciment rostudirsen' && $programs['DYNE-251']['short_name'] === 'z-rostudirsen', 'Full name and short name are separate');
$assert($programs['DYNE-101']['name'] === 'Zeleciment basivarsen' && $programs['DYNE-101']['short_name'] === 'z-basivarsen', 'DM1 names match current source');
$assert(str_starts_with($programs['DYNE-302']['html']['introduction'], '<p class="">DYNE-302 is Dyne'), 'Full desktop FSHD introduction wins over truncated mobile copy');
$assert(str_contains($programs['DYNE-302']['html']['introduction'], 'transferrin receptor 1'), 'FSHD complete source mechanism retained');
$assert($programs['DYNE-101']['video']['id'] === 'gHNBjvgziNA', 'Source video identifier retained');
$expected_cover = $manifest['source']['kind'] === 'live_snapshot' ? '/sites/default/files/legacy/theme-assets/cover-dm1.webp' : '/themes/custom/dynetx/images/cover-dm1.webp';
$assert($programs['DYNE-101']['video']['cover'] === $expected_cover, 'Video cover comes from selected source HTML, including live asset path rewrites');
$assert($programs['DYNE-101']['video']['alt'] === 'more about DM1 and DYNE-101', 'Original video cover alternative text retained');
$assert($programs['DYNE-101']['cta']['url'] === '/clinical-trials/#achieve', 'Source ACHIEVE CTA anchor retained');
$assert($programs['DYNE-251']['cta']['url'] === '/clinical-trials/#deliver', 'Source DELIVER CTA anchor retained');

// Prefer actual live edits, including assets; never quietly substitute archive.
$edited_html = str_replace('cover-dm1.webp', 'editor-selected-cover.webp', $manifest['source_html']);
$edited = dyne_pipeline_source_manifest([['id' => '324', 'revision' => '1001', 'html' => $edited_html]], $fixture);
$assert($edited['source']['kind'] === 'live_snapshot', 'Edited live authority stays selected');
$assert($edited['programs']['DYNE-101']['video']['cover'] === str_replace('cover-dm1.webp', 'editor-selected-cover.webp', $expected_cover), 'Current editor-selected video asset is retained');
$assert($edited['source_sha256'] !== $manifest['source_sha256'], 'Source identity changes when live content changes');
$fallback = dyne_pipeline_source_manifest([], $fixture);
$assert($fallback['source']['kind'] === 'archive_fixture' && $fallback['source']['fallback_reason'] !== '', 'Fallback is explicit and attributable');
$assert(count($fallback['programs']) === 8, 'Original archive fallback covers the same eight named programs');
try {
  dyne_pipeline_parse('<p>Unrecognized page</p>');
  $assert(FALSE, 'Unrecognized sources must fail closed');
}
catch (InvalidArgumentException $exception) {
  $assert(TRUE, 'Unrecognized source cannot silently produce partial migration');
}

print json_encode(['status' => 'passed', 'programs' => count($programs), 'checks' => $checks, 'source' => $manifest['source'], 'source_sha256' => $manifest['source_sha256'], 'codes' => $codes], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
