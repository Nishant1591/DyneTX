<?php

/**
 * Update only the existing homepage news Paragraph from the live reference.
 *
 * Run with:
 *   ddev drush php:script web/modules/custom/dyne_migrate/scripts/update-homepage-news.php
 */
$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
  'type' => 'dyne_page',
  'title' => 'Home',
]);

if (count($nodes) !== 1) {
  throw new \RuntimeException('Expected exactly one dyne_page titled Home.');
}

$home = reset($nodes);
$news = NULL;
foreach ($home->get('field_components')->referencedEntities() as $paragraph) {
  if ($paragraph->bundle() === 'dyne_news_list') {
    $news = $paragraph;
    break;
  }
}

if (!$news) {
  throw new \RuntimeException('Homepage news Paragraph was not found.');
}

$items = [
  [
    'date' => 'September 29, 2026',
    'title' => 'Dyne Therapeutics Announces Additional One-Year Clinical Data from Phase 1/2 ACHIEVE Trial of Z-Basivarsen (DYNE-101) for Myotonic Dystrophy Type 1 (DM1)',
    'url' => 'https://investors.dyne-tx.com/news-releases/news-release-details/dyne-therapeutics-announces-additional-one-year-clinical-data-0',
  ],
  [
    'date' => 'September 17, 2026',
    'title' => 'Dyne Therapeutics Reports Inducement Grants Under Nasdaq Listing Rule 5635(c)(4)',
    'url' => 'https://investors.dyne-tx.com/news-releases/news-release-details/dyne-therapeutics-reports-inducement-grants-under-nasdaq-6',
  ],
];

$news->set('field_items_json', json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
$news->save();
print "Updated homepage news Paragraph {$news->id()}.\n";