<?php
$storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');

echo "Deleting existing main menu links...\n";
$existing = $storage->loadByProperties(['menu_name' => 'main']);
foreach ($existing as $link) {
  $link->delete();
}
echo "Deleted. Count: " . count($existing) . "\n";

$mainTree = [
  ['About Us', '/about-us', [
    ['Overview', '/about-us'],
    ['Leadership', '/about-us/leadership'],
    ['Board of directors', '/about-us/board-of-directors'],
    ['Scientific advisory board', '/about-us/scientific-advisory-board'],
    ['Corporate responsibility', '/about-us/corporate-responsibility'],
    ['Contact us', '/about-us/contact-us'],
  ]],
  ['FORCE Platform', '/our-forcetm-platform', [
    ['Overview', '/our-forcetm-platform'],
    ['Scientific publications and presentations', '/our-forcetm-publications'],
  ]],
  ['Pipeline', '/pipeline', [
    ['Overview', '/pipeline'],
    ['Zeleciment rostudirsen (DYNE-251)', '/dyne-251-for-dmd'],
    ['Zeleciment basivarsen (DYNE-101)', '/dyne-101-for-dm1'],
    ['DYNE-302', '/dyne-302-for-fshd'],
  ]],
  ['Physicians', '/physicians', [
    ['Commitment', '/our-commitment'],
    ['Clinical trials', '/clinical-trials'],
    ['DMD stories', '/dmd'],
    ['DM1 stories', '/dm1'],
    ['FSHD stories', '/fshd'],
  ]],
  ['Patients', '/patients', [
    ['Commitment', '/our-commitment'],
    ['Clinical trials', '/clinical-trials'],
    ['DMD stories', '/dmd'],
    ['DM1 stories', '/dm1'],
    ['FSHD stories', '/fshd'],
  ]],
  ['Investors and Media', '/investors', [
    ['Company overview', '/about-us'],
    ['News, publications and events', '/news'],
    ['Stock information', '/stock-information'],
    ['Corporate governance', '/corporate-governance'],
    ['Financials and filings', '/financials-filings'],
    ['IR resources', '/ir-resources'],
  ]],
  ['Culture and Careers', '/careers-culture', [
    ['Our culture and values', '/culture-values'],
    ['Hear from our Dynamos', '/dynamos'],
    ['Competitive benefits', '/benefits'],
    ['Open positions', '/jobs'],
  ]],
];

$insert = function(array $tree, $parent = '', $weight = 0) use (&$insert, $storage) {
  foreach ($tree as $item) {
    $title = $item[0];
    $path = $item[1];
    $children = isset($item[2]) ? $item[2] : [];
    
    $parentId = '';
    if ($parent) {
      $parentLinks = $storage->loadByProperties(['title' => $parent, 'menu_name' => 'main']);
      if (!empty($parentLinks)) {
        $parentId = 'menu_link_content:' . reset($parentLinks)->id();
      }
    }
    
    if ($path === '/') {
      $url = \Drupal\Core\Url::fromRoute('<front>');
    } else {
      $url = \Drupal\Core\Url::fromUserInput($path);
    }
    
    $existing = $storage->loadByProperties(['title' => $title, 'menu_name' => 'main']);
    if (empty($existing)) {
      $link = $storage->create([
        'title' => $title,
        'menu_name' => 'main',
        'link' => ['uri' => $url->toString(), 'options' => $url->getOptions()],
        'parent' => $parentId,
        'weight' => $weight++,
      ]);
      $link->save();
      echo "Created: $title (parent: $parent, weight: " . ($weight-1) . ")\n";
    } else {
      echo "Skipped (exists): $title\n";
    }
    
    if ($children) {
      $insert($children, $title, $weight);
    }
  }
};

$insert($mainTree);
echo "Done!\n";
