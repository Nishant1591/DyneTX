<?php

/**
 * Standalone regression checks: ddev exec php scripts/tests/structured-job-parser.php
 */

require_once dirname(__DIR__, 2) . '/web/modules/custom/dyne_migrate/includes/structured-job-parser.inc';

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
  $checks++;
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$text = static function (string $html): string {
  [, $root] = dyne_job_document($html);
  return dyne_job_text($root->textContent);
};
$links = static function (string $html): array {
  [, $root] = dyne_job_document($html);
  $links = [];
  foreach ($root->getElementsByTagName('a') as $anchor) {
    $links[] = [$anchor->getAttribute('href'), $anchor->textContent];
  }
  return $links;
};
$roundtrip = static function (string $html, array $parsed, string $label) use ($assert, $text, $links): void {
  $rendered = dyne_job_render_parts($parsed);
  $assert($text($rendered) === $text($html), $label . ': all source copy remains in order');
  $assert($links($rendered) === $links($html), $label . ': link destinations and labels preserved; got ' . json_encode(array_map(static fn(array $link): string => substr($link[0], 0, 90), $links($rendered))));
  $assert(!preg_match('/<!--dyne:|\{\{dyne:/', $rendered), $label . ': no unresolved scaffold tokens');
};

$archive = json_decode(file_get_contents(dirname(__DIR__, 2) . '/web/modules/custom/dyne_migrate/data/legacy-content.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$jobs = array_filter($archive['records'], static fn(array $row): bool => $row['bundle'] === 'job');
$assert(count($jobs) === 49, 'Complete 49-job source archive is tested');
$arrangements = ['On-site' => 0, 'Remote' => 0, '' => 0];
foreach ($jobs as $job) {
  $parsed = dyne_job_parse($job['body']);
  $label = 'Job ' . $job['source_id'];
  $assert(!$parsed['warnings'], $label . ': recognized without fallback');
  foreach (['field_company_overview', 'field_role_summary', 'field_responsibilities', 'field_education_skills', 'field_compensation_notes', 'field_job_disclaimer', 'field_equal_opportunity', 'field_pay_range_label', 'field_pay_range_min', 'field_pay_range_max', 'field_pay_range_currency'] as $field) {
    $assert($parsed['fields'][$field] !== '', $label . ': semantic field populated: ' . $field);
  }
  $assert($parsed['fields']['field_company_website'] === '/', $label . ': original local company URL');
  $assert(str_starts_with($parsed['fields']['field_company_twitter'], 'https://nam11.safelinks.'), $label . ': opaque social URL retained without guessing');
  $assert(!str_contains($parsed['fields']['field_company_overview'], '<a '), $label . ': corporate links separated from overview');
  $assert(preg_match('/^\d+(?:\.\d+)?$/', $parsed['fields']['field_pay_range_min']) === 1, $label . ': salary is a numeric decimal string');
  preg_match('/#LI-(Onsite|Remote)\b/i', $job['body'], $marker);
  $expected_arrangement = empty($marker) ? '' : (strtolower($marker[1]) === 'remote' ? 'Remote' : 'On-site');
  $assert($parsed['fields']['field_workplace_arrangement'] === $expected_arrangement, $label . ': workplace marker extracted across block boundaries, absent markers remain empty');
  $arrangements[$parsed['fields']['field_workplace_arrangement']]++;
  $roundtrip($job['body'], $parsed, $label);
}
$assert($arrangements === ['On-site' => 29, 'Remote' => 14, '' => 6], 'All 43 workplace markers extracted; six unspecified arrangements preserved');

$fixture = '<h3>Role Summary:</h3><p>Résumé of a role &amp; collaboration.</p><p><strong>Primary Responsibilities Include:</strong></p><ul><li>Lead<ul><li><strong>Clinical Strategy</strong> &amp; study design</li></ul></li></ul><p><strong>Primary Responsibilities Include:</strong></p><p>Repeated section stays editable.</p><h4>Travel and accessibility:</h4><p>Travel 20% <a href="/support?a=1&amp;b=2">support</a>.</p>';
$parsed = dyne_job_parse($fixture);
$assert(!$parsed['warnings'], 'Adjacent repeated headings do not trigger fallback');
$assert(str_contains($parsed['fields']['field_responsibilities'], '<ul><li>Lead<ul>'), 'Nested list hierarchy remains intact');
$assert(str_contains($parsed['fields']['field_responsibilities'], 'Repeated section stays editable'), 'Repeated section contents captured');
$assert($parsed['unknown_headings'] === ['Travel and accessibility:'], 'Unknown section reported');
$assert(str_contains($parsed['fields']['field_additional_job_content'], '<h4>Travel'), 'Unknown heading remains editable with its content');
$roundtrip($fixture, $parsed, 'Unknown and repeated sections');

$fixture = '<h3>Role Summary:</h3><p>First.</p><h3>Responsibilities:</h3><p>Second.</p><h3>Role Summary:</h3><p>Third.</p>';
$parsed = dyne_job_parse($fixture);
$assert(count($parsed['warnings']) === 1, 'Interleaved repeated fields request editorial review');
$assert($parsed['fields']['field_additional_job_content'] === $fixture, 'Fallback preserves unfamiliar markup in editable content');
$roundtrip($fixture, $parsed, 'Interleaved repeated sections');

$fixture = '<p>Single description without headings.</p>';
$parsed = dyne_job_parse($fixture);
$assert(str_contains($parsed['fields']['field_additional_job_content'], 'Single description'), 'Unsectioned provider description is never discarded');
$roundtrip($fixture, $parsed, 'Unsectioned description');

$job = reset($jobs);
$parsed = dyne_job_parse($job['body']);
$parsed['fields']['field_role_summary'] = '<p>Edited role summary.</p>';
$parsed['fields']['field_company_twitter'] = 'https://example.test/new?a=1&b=2';
$parsed['fields']['field_pay_range_min'] = '250000';
$parsed['fields']['field_pay_range_currency'] = 'EUR';
$rendered = dyne_job_render_parts($parsed);
$assert(str_contains($rendered, 'Edited role summary.'), 'Editor changes appear through semantic field slots');
$assert(str_contains($rendered, 'href="https://example.test/new?a=1&amp;b=2"'), 'Edited link attribute is escaped');
$assert(str_contains($rendered, '$250,000'), 'Edited normalized salary is formatted for display');
$assert(str_contains($rendered, 'EUR'), 'Edited currency affects output');

print json_encode(['status' => 'passed', 'jobs' => count($jobs), 'checks' => $checks], JSON_PRETTY_PRINT) . PHP_EOL;
