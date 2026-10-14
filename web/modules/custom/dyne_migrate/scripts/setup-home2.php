<?php

$ns = \Drupal::entityTypeManager()->getStorage('node');
$nodes = $ns->loadMultiple();

// Update existing node or create new
if (!empty($nodes)) {
  $node = reset($nodes);
  echo "Updating existing node " . $node->id() . PHP_EOL;
} else {
  $node = \Drupal\node\Entity\Node::create(['type' => 'dyne_page', 'uid' => 1]);
  echo "Creating new node" . PHP_EOL;
}

$mk = function (string $type, array $fields) {
  $p = \Drupal\paragraphs\Entity\Paragraph::create(['type' => $type] + $fields);
  $p->save();
  return $p;
};

$paras = [];
$paras[] = $mk('dyne_hero', [
  'field_text' => 'The muscle to',
  'field_text_long' => 'keep life moving',
  'field_banner_text' => 'On September 7, we join the Duchenne community for World Duchenne Awareness Day, making a difference by focusing on therapies that are designed to deliver functional improvement for individuals living with DMD.',
]);
$paras[] = $mk('dyne_rich_text', [
  'field_heading' => 'Dyne Therapeutics is trailblazing the development of therapies to deliver functional improvement for people living with genetically driven neuromuscular diseases',
  'field_body_text' => ['value' => '<p>We are on a mission to deliver functional improvement for people living with genetically driven neuromuscular diseases through targeted therapies enabled by our FORCE platform. We have assembled an exceptional team with deep experience in neuromuscular disease, rare disorders, drug development, and manufacturing and commercialization of novel therapeutics. We have also established a scientific advisory board comprised of leading experts in neuromuscular disease drug discovery and development.</p>', 'format' => 'full_html'],
  'field_style' => 'h1-lede',
]);
$paras[] = $mk('dyne_icon_cards', [
  'field_heading' => 'What we do',
  'field_items_json' => json_encode([
    ['icon' => '/themes/custom/dynetx/images/icons/ico-cells.svg', 'title' => 'Pioneering approach', 'text' => 'Utilizing our FORCE platform to develop therapeutics that address the root cause of disease.', 'link' => '/our-forcetm-platform', 'style' => 'red-one'],
    ['icon' => '/themes/custom/dynetx/images/icons/ico-muscle.svg', 'title' => 'Pipeline', 'text' => 'Developing a broad portfolio of therapeutics for genetically driven neuromuscular diseases', 'link' => '/pipeline', 'style' => 'blue-one'],
    ['icon' => '/themes/custom/dynetx/images/icons/ico-dna-vert.svg', 'title' => 'Find a Dyne clinical trial', 'text' => 'Conducting clinical trials for neuromuscular diseases', 'link' => '/clinical-trials', 'style' => 'mid-blue-one'],
    ['icon' => '/themes/custom/dynetx/images/icons/ico-commitment.svg', 'title' => 'Commitment', 'text' => 'Working to deliver functional improvement for people living with neuromuscular diseases', 'link' => '/our-commitment', 'style' => 'dark-gray-one'],
  ]),
]);
$paras[] = $mk('dyne_cta', [
  'field_heading' => 'Culture and careers',
  'field_body_text' => ['value' => 'Our commitment to people living with neuromuscular diseases is our greatest strength. Hear our Dynamos share their perspective on what makes Dyne a special place to work.', 'format' => 'full_html'],
  'field_link' => ['uri' => 'internal:/careers-culture', 'title' => 'Learn more'],
]);
$paras[] = $mk('dyne_news_list', [
  'field_heading' => 'Latest news',
  'field_items_json' => json_encode([
    ['date' => 'September 08, 2026', 'title' => 'ACHIEVE Trial one-year data at medical meetings', 'url' => 'https://investors.dyne-tx.com/'],
    ['date' => 'September 03, 2026', 'title' => 'Preclinical DMD data at NMSG Scientific Meeting', 'url' => 'https://investors.dyne-tx.com/'],
  ]),
]);

$refs = [];
foreach ($paras as $p) {
  $refs[] = ['target_id' => $p->id(), 'target_revision_id' => $p->getRevisionId()];
}

$node->setTitle('Home');
$node->set('field_components', $refs);
$node->save();
echo "Node " . $node->id() . " updated with title: " . $node->getTitle() . PHP_EOL;

// Set path alias
\Drupal\path_alias\Entity\PathAlias::create(['path' => '/node/' . $node->id(), 'alias' => '/home'])->save();
echo "Path alias /home -> /node/" . $node->id() . " created" . PHP_EOL;
