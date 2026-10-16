<?php

/** Standalone contract regressions: php web/modules/custom/dyne_content_sync/tests/test-feed-payload.php. */
require dirname(__DIR__, 4) . '/autoload.php';
require dirname(__DIR__) . '/src/FeedPayload.php';
use Drupal\dyne_content_sync\FeedPayload;

$assertions = 0;
$check = static function (bool $pass, string $label) use (&$assertions): void {
  if (!$pass) { throw new RuntimeException($label); }
  $assertions++;
};
$reject = static function (callable $call, string $label) use ($check): void {
  try { $call(); }
  catch (UnexpectedValueException) { $check(TRUE, $label); return; }
  $check(FALSE, $label);
};
$job = ['id' => 123, 'title' => 'Clinical Scientist', 'absolute_url' => 'https://job-boards.greenhouse.io/dynetherapeutics/jobs/123', 'content' => '&lt;p&gt;Join Dyne &lt;script&gt;alert(1)&lt;/script&gt;&lt;a href="https://www.dyne-tx.com/about-us/"&gt;About&lt;/a&gt;&lt;/p&gt;', 'departments' => [['name' => 'Clinical']], 'location' => ['name' => 'Waltham, MA']];
$news = ['id' => 'release-1', 'title' => 'Dyne Therapeutics Reports Results', 'releaseDate' => ['date' => '2026-10-16T09:00:00Z'], 'link' => ['hostedUrl' => 'https://investors.dyne-tx.com/news-releases/example'], '_resolved_body' => '<p>Release details</p>'];
$normalized = FeedPayload::normalize('greenhouse', ['jobs' => [$job]]);
$check($normalized[0]['id'] === '123', 'Remote IDs normalized to strings');
$check(!str_contains($normalized[0]['body'], '<script'), 'Executable script markup removed');
$check(str_contains($normalized[0]['body'], 'href="/about-us/"'), 'Internal first-party link localized');
$check(FeedPayload::normalize('gcs', ['data' => [$news]])[0]['date'] === '2026-10-16T09:00:00', 'GCS publication timestamp retained');
$reject(fn() => FeedPayload::normalize('greenhouse', ['jobs' => []]), 'Empty job feeds rejected before writes');
$reject(fn() => FeedPayload::normalize('greenhouse', ['jobs' => [$job, $job]]), 'Duplicate IDs rejected');
$reject(fn() => FeedPayload::normalize('greenhouse', ['jobs' => [array_replace($job, ['content' => ''])]]), 'Empty descriptions rejected');
$reject(fn() => FeedPayload::normalize('greenhouse', ['jobs' => [array_replace($job, ['absolute_url' => 'https://job-boards.greenhouse.io/anothercompany/jobs/123'])]]), 'Other employer rejected');
$reject(fn() => FeedPayload::normalize('gcs', ['data' => [array_replace($news, ['title' => 'Jazz Pharmaceuticals Reports Results'])]]), 'Historical Jazz feed must not publish');
$reject(fn() => FeedPayload::normalize('gcs', ['data' => [array_replace($news, ['_resolved_body' => ''])]]), 'Unavailable release body rejected');
$reject(fn() => FeedPayload::normalize('gcs', ['data' => [array_replace($news, ['releaseDate' => ['date' => 'invalid']])]]), 'Bad date rejected');
$check(!FeedPayload::allowedUrl('https://user@clientapi.gcs-web.com/data/test', ['clientapi.gcs-web.com']), 'Credentials in remote URL rejected');
$check(!FeedPayload::allowedUrl('http://clientapi.gcs-web.com/data/test', ['clientapi.gcs-web.com']), 'Non-TLS remote URL rejected');
$check(!FeedPayload::allowedUrl('https://clientapi.gcs-web.com.evil.test/data/test', ['clientapi.gcs-web.com']), 'Lookalike remote hostname rejected');
print json_encode(['assertions' => $assertions, 'failures' => 0], JSON_PRETTY_PRINT) . "\n";
