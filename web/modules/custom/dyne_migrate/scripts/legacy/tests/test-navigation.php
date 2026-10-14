<?php
/** Focused plain-PHP checks for the in-place navigation repair. */
require dirname(__DIR__, 2) . '/navigation-normalizer.inc';
$aliases = ['/story' => TRUE, '/resources' => TRUE];
$cases = [
  'http://www.dyne-tx.com' => '/',
  'https://dyne-tx.com?utm=example#contact' => '/?utm=example#contact',
  '//www.dyne-tx.com/about-us/?x=1#contact' => '/about-us/?x=1#contact',
  'https://dynetxdev.wpengine.com/privacy-policy/' => '/privacy-policy/',
  'story?view=1#details' => '/story?view=1#details',
  'https://investors.dyne-tx.com/news' => 'https://investors.dyne-tx.com/news',
  'https://job-boards.greenhouse.io/dynetx/jobs/123' => 'https://job-boards.greenhouse.io/dynetx/jobs/123',
  'https://www.dyne-tx.com/wp-content/uploads/missing.mp4' => 'https://www.dyne-tx.com/wp-content/uploads/missing.mp4',
];
foreach ($cases as $source => $expected) {
  if (_dyne_navigation_url($source, $aliases) !== $expected) {
    throw new RuntimeException('Unexpected URL normalization: ' . $source);
  }
}
$counts = ['urls' => 0, 'targets' => 0];
$html = '<section id="resources"><p>Edited editorial text remains intact.</p><a href="https://www.dyne-tx.com/story?x=1#details" target="_new">Edited label</a><a href="/report.pdf" target="_blank">PDF</a><a href="/download" download target="_blank">Download</a><a href="#!" data-mdb-url="https://provider.example/" target="_blank">Provider</a><a href="https://investors.dyne-tx.com/" target="_blank">Investors</a></section>';
$result = _dyne_navigation_html($html, $aliases, $counts);
$expected = str_replace('<a href="https://www.dyne-tx.com/story?x=1#details" target="_new">', '<a href="/story?x=1#details">', $html);
if ($result !== $expected || $counts !== ['urls' => 1, 'targets' => 1]) {
  throw new RuntimeException('HTML repair altered something other than the intended link.');
}
$data = ['anchor' => 'resources', 'title' => 'story', 'url' => 'story?x=1#details'];
if (_dyne_navigation_value($data, $aliases, $counts) !== ['anchor' => 'resources', 'title' => 'story', 'url' => '/story?x=1#details']) {
  throw new RuntimeException('Structured data repair altered a non-URL property.');
}
$verified = json_decode(file_get_contents(dirname(__DIR__, 3) . '/data/legacy-verified-redirects.json'), TRUE)['urls'];
foreach ($verified as $source => $destination) {
  if (_dyne_navigation_url($source, $aliases) !== $destination) {
    throw new RuntimeException('Verified tracker was not normalized.');
  }
}
$counts = ['urls' => 0, 'targets' => 0, 'media' => 0];
$source = 'https://www.dyne-tx.com/wp-content/uploads/COMMITMENT_TO_PATIENTS_ALT_V2.mp4?autoplay=1#t=4';
$video = '<section id="story"><video controls poster="/editor-photo.jpg"><source src="' . $source . '"></video><a embd-data-url="' . $source . '" data-video-url="' . $source . '" href="/report.pdf" target="_blank">Edited video label</a></section>';
$expected = str_replace($source, '/sites/default/files/legacy/COMMITMENT_TO_PATIENTS_ALT_V2.mp4?autoplay=1#t=4', $video);
if (_dyne_navigation_html($video, $aliases, $counts) !== $expected || $counts !== ['urls' => 0, 'targets' => 0, 'media' => 3]) {
  throw new RuntimeException('Media repair lost query/fragment or changed editorial markup.');
}
$counts = ['urls' => 0, 'targets' => 0, 'media' => 0];
if (_dyne_navigation_html($expected, $aliases, $counts) !== $expected || array_sum($counts) !== 0) {
  throw new RuntimeException('Media repair must be idempotent.');
}
print "Navigation normalizer: all checks passed.\n";
