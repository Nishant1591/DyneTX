<?php

/** Read-only regressions for the Word-export inline responsibilities boundary. */
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/web/themes/custom/dynetx/includes/structured-content.inc';
$checks = 0; $failures = [];
$assert = static function (bool $passed, string $message) use (&$checks, &$failures): void { $checks++; if (!$passed) $failures[] = $message; };
$role = '<!--dyne:field_role_summary-->'; $responsibilities = '<!--dyne:field_responsibilities-->';
foreach (['qms' => '<span data-ccp-props="source-metadata">&nbsp;</span>', 'medical' => ''] as $kind => $suffix) {
  $heading = $kind === 'qms' ? '<strong><span>Primary Responsibilities</span><span> Include:</span></strong>' : '<strong>Primary Responsibilities Include:</strong>';
  $intro = '<p>Current authored role narrative.</p><p data-source="word"><span>This role is based in Waltham, MA.</span><br>&nbsp;<br>';
  $source = $intro . $heading . $suffix . '</p><p>Current responsibilities.</p>';
  $template = $role . '<p>' . $heading . '</p>' . $responsibilities;
  $original = [$role => $intro . '</p>', $responsibilities => '<p data-source="word">' . $suffix . '</p><p>Current responsibilities.</p>'];
  $slots = $original; $restored = _dynetx_restore_inline_job_heading($template, $slots, $source);
  $assert(strtr($restored, $slots) === $source, $kind . ': restore the exact original paragraph markup without changing current native words or links.');
  $assert(substr_count(strtr($restored, $slots), '<p') === 3, $kind . ': the empty clone and heading wrappers add no paragraphs.');
  $unchanged = static function (array $candidate, string $scaffold, string $archive) use ($assert, $kind): void {
    $before = $candidate; $returned = _dynetx_restore_inline_job_heading($scaffold, $candidate, $archive);
    $assert($returned === $scaffold && $candidate === $before, $kind . ': an unproven or authored section boundary remains unchanged.');
  };
  $unchanged($original, $template, '');
  $unchanged($original, $template, str_replace($heading, '</p><p>' . $heading, $source));
  $unchanged($original, str_replace('<p>' . $heading, '<p class="authored">' . $heading, $template), $source);
  $unchanged($original, str_replace('Include:', 'Changed by an editor:', $template), $source);
  $edited = $original; $edited[$role] .= '<p>New separate role paragraph.</p>'; $unchanged($edited, $template, $source);
  $edited = $original; $edited[$responsibilities] = '<p>A new authored responsibilities introduction.</p>' . $edited[$responsibilities]; $unchanged($edited, $template, $source);
  $edited = $original; $edited[$role] = str_replace('<br>&nbsp;<br>', '<br>', $edited[$role]); $unchanged($edited, $template, $source);
  $edited = $original; $edited[$responsibilities] = str_replace($suffix . '</p>', $suffix . '<img src="/authored.png"></p>', $edited[$responsibilities]); $unchanged($edited, $template, $source);
}
if (!empty($argv[1])) {
  $evidence = json_decode(file_get_contents($argv[1]), TRUE, 512, JSON_THROW_ON_ERROR);
  foreach ($evidence['jobs'] as $job) {
    if (!in_array($job['id'], [304, 305], TRUE)) continue;
    $fields = $job['fields']; $template = $fields['field_content_template']['value'];
    $slots = [$role => $fields['field_role_summary']['value'], $responsibilities => $fields['field_responsibilities']['value']];
    $source = $fields['field_body_text']['value'];
    $restored = _dynetx_restore_inline_job_heading($template, $slots, $source);
    $assert($restored !== $template, 'Actual native job ' . $job['id'] . ' restores its proven imported boundary.');
    $find = static function (string $markup): string {
      $document = \Drupal\Component\Utility\Html::load($markup);
      foreach ($document->getElementsByTagName('strong') as $strong) {
        if (str_contains($strong->textContent, 'Primary Responsibilities') && $strong->parentNode instanceof \DOMElement && $strong->parentNode->tagName === 'p' && str_contains($strong->parentNode->textContent, 'Waltham')) return preg_replace('/[\s\x{00a0}]+/u', ' ', trim($strong->parentNode->textContent));
      }
      return '';
    };
    $assert($find($source) !== '' && $find(strtr($restored, $slots)) === $find($source), 'Actual native job ' . $job['id'] . ' has the source paragraph text and heading together.');
  }
}
$result = ['passed' => !$failures, 'checks' => $checks, 'failures' => $failures, 'entity_saves' => 0, 'config_saves' => 0];
print json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($failures ? 1 : 0);
