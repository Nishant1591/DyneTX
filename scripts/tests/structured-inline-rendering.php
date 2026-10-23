<?php

/** Pure inline-slot regression: ddev exec php scripts/tests/structured-inline-rendering.php */
require_once dirname(__DIR__, 2) . '/web/themes/custom/dynetx/includes/structured-articles.inc';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
$failures = [];
$cases = [
  ['An unchanged <strong>inline</strong> value', 'An unchanged <strong>inline</strong> value'],
  ["  Existing <em>spacing</em>\n", "  Existing <em>spacing</em>\n"],
  ['<p>A heading</p>', 'A heading'],
  ['<p>A <strong>bold</strong> and <em>italic</em> heading</p>', 'A <strong>bold</strong> and <em>italic</em> heading'],
  ["<p>First</p>\n<p>Second <sup>2</sup></p>", 'First<br>Second <sup>2</sup>'],
  ['<p>Paragraph</p><ul><li>List</li></ul>', 'Paragraph<br>List'],
  ['<p title="A > B"><strong>Text</strong></p>', '<span title="A &gt; B"><strong>Text</strong></span>'],
  ['<h3>Changed heading</h3>', 'Changed heading'],
  ['<p class="accent" style="color:red">Styled heading</p>', '<span class="accent" style="color:red">Styled heading</span>'],
  ['<ul><li>First <strong>item</strong></li><li>Second</li></ul>', 'First <strong>item</strong><br>Second'],
  ['<table><tbody><tr><td>One</td><td>Two</td></tr></tbody></table>', 'One<br>Two'],
];
foreach ($cases as [$input, $expected]) if (_dynetx_structured_inline_fragment($input) !== $expected) $failures[] = ['type' => 'fragment', 'input' => $input];
$slot = '<!--dyne:field_section_body-->';
$template = '<h2>' . $slot . '</h2><div>' . $slot . '</div><p>' . $slot . '</p><a href="/">' . $slot . '</a><span>' . $slot . '</span>';
if ((_dynetx_structured_inline_slots($template)[$slot] ?? []) !== [TRUE, FALSE, TRUE, TRUE, TRUE]) $failures[] = ['type' => 'context detection'];
if (_dynetx_structured_inline_slots('<img src="<!--dyne:field_section_image:url-->">') !== []) $failures[] = ['type' => 'attribute marker'];
if ((_dynetx_structured_inline_slots('<!-- <span> -->' . $slot)[$slot] ?? []) !== [FALSE]) $failures[] = ['type' => 'comment markup'];
$render_cases = [
  ['<h2><!--dyne:field_section_heading--></h2>', '<!--dyne:field_section_heading-->', '<p><strong>Updated</strong> heading</p>', '<h2><strong>Updated</strong> heading</h2>'],
  ['<p><!--dyne:field_story_intro--></p>', '<!--dyne:field_story_intro-->', '<p>Intro with <em>emphasis</em></p>', '<p>Intro with <em>emphasis</em></p>'],
  ['<a href="/">' . $slot . '</a>', $slot, '<p>First</p><p>Second</p>', '<a href="/">First<br>Second</a>'],
  ['<div>' . $slot . '</div>', $slot, '<p>First</p><p>Second</p>', '<div><p>First</p><p>Second</p></div>'],
];
foreach ($render_cases as [$template, $marker, $value, $expected]) {
  $contexts = _dynetx_structured_inline_slots($template);
  $actual = str_replace($marker, ($contexts[$marker][0] ?? FALSE) ? _dynetx_structured_inline_fragment($value) : $value, $template);
  if ($actual !== $expected) $failures[] = ['type' => 'slot rendering', 'template' => $template];
}
echo json_encode(['fragment_cases' => count($cases), 'render_cases' => count($render_cases), 'context_cases' => 3, 'failures' => $failures], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit($failures ? 1 : 0);
