<?php

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

$updates = [
  136 => [
    'node' => 10,
    'alias' => '/dmd',
    'value' => '<p>Living with DMD has profound impacts on every part of people’s lives, including work, social interactions and family relationships. Community members have shared their moving perspectives of living with Duchenne with our team and have told us what it would mean to them to have a potentially transformative therapeutic for their disease. These insights are guiding our efforts to develop therapies that deliver meaningful functional improvement for people living with DMD.</p>',
  ],
  137 => [
    'node' => 10,
    'alias' => '/dmd',
    'value' => <<<'HTML'
<ul class="dyne-story-list">
<li><strong>Meet Catherine and Alan</strong> — Catherine and Alan share their journey living with DMD and their hopes for the future (<a href="https://www.youtube.com/embed/1jIRAJkiXrY">Watch video</a>)</li>
<li><strong>Meet Diana and Jordan</strong> — Diana shares her family’s journey with Duchenne (<a href="https://www.youtube.com/embed/apwkz3L7DVg">Watch video</a>)</li>
<li><strong>Meet Ravi</strong> — “I want to be someone:” Ravi’s journey with DMD (<a href="https://www.dyne-tx.com/dmd-story-ravi">Read story</a>)</li>
</ul>
HTML,
  ],
  139 => [
    'node' => 11,
    'alias' => '/dm1',
    'value' => '<p>Living with DM1 has profound impacts on every part of people’s lives, including work, social interactions and family relationships. Community members have shared their moving perspectives of living with DM1 with our team, including what it would mean to them to have a potentially transformative therapeutic for their disease. These insights are guiding our efforts to develop therapies that deliver meaningful functional improvement for people living with DM1.</p>',
  ],
  140 => [
    'node' => 11,
    'alias' => '/dm1',
    'value' => <<<'HTML'
<ul class="dyne-story-list">
<li><strong>Meet Emily</strong> — I’m Emily, a broadcast journalist turned advocate living with myotonic dystrophy type 1 (DM1). (<a href="https://www.dyne-tx.com/dm1-story-emily/">Read story</a>)</li>
<li><strong>Meet Sarah</strong> — Sarah shares the impact DM1 has on her and her family and why a potentially transformative therapy is urgently needed (<a href="https://youtu.be/5ednXRKHnIc">Watch Video</a>)</li>
<li><strong>Meet BillyDean and Suzette</strong> — Through their resilience and Suzette’s incredible advocacy efforts for her son, BillyDean, they succeeded in getting a DM1 diagnosis and have been learning how to live and thrive ever since (<a href="https://www.dyne-tx.com/dm1-story-billydean">Read story</a>)</li>
<li><strong>Meet Joachim and Tim</strong> — Joachim and Tim share their personal stories on living with DM1 (<a href="https://www.youtube.com/embed/Oov_breRaxs">Watch Video</a>)</li>
<li><strong>Meet Joachim</strong> — “I’m not alone in this:” One person’s journey with DM1 (<a href="https://www.dyne-tx.com/dm1-story-joachim">Read story</a>)</li>
<li><strong>Meet Loraine</strong> — A courageous family battle and devotion in the face of generational adversity (<a href="https://www.dyne-tx.com/dm1-story-loraine">Read story</a>)</li>
</ul>
HTML,
  ],
  141 => [
    'node' => 11,
    'alias' => '/dm1',
    'value' => '<p>We’ll use the information you provide to share updates from Dyne, including disease and product information, education, resources and other relevant updates.</p>',
  ],
  143 => [
    'node' => 12,
    'alias' => '/fshd',
    'value' => '<p>Living with FSHD has profound impacts on every part of peoples’ lives, including work, social interactions and family relationships. Community members have shared their moving perspectives of living with FSHD with our team and have told us what it would mean to them to have a potentially transformative therapeutic for their disease. These insights are guiding our efforts to develop therapies that deliver meaningful functional improvement for people living with FSHD.</p>',
  ],
  144 => [
    'node' => 12,
    'alias' => '/fshd',
    'value' => <<<'HTML'
<ul class="dyne-story-list">
<li><strong>Meet Laura and Chelsea</strong> — Joint perspective of a mother and daughter’s journey with FSHD (<a href="https://www.youtube.com/embed/3ipgGAeYmMg">Watch video</a> · <a href="https://www.dyne-tx.com/fshd-story-chelsea">Read story</a>)</li>
<li><strong>Meet Rachel</strong> — “If there was a treatment for FSHD, it would be life-changing for me.” (<a href="https://www.youtube.com/embed/ETAkHmDghXs">Watch video</a>)</li>
<li><strong>Meet Ian</strong> — “So many therapies in development right now. I’m very, very excited.” (<a href="https://youtube.com/embed/KgDQLs-2Oas">Watch video</a>)</li>
<li><strong>Meet Sam</strong> — “I would love to see some type of therapy that would regrow muscle tissue.” (<a href="https://www.youtube.com/embed/k6fS0ZqSr_4">Watch video</a>)</li>
</ul>
HTML,
  ],
];

$alias_manager = \Drupal::service('path_alias.manager');
$changed = [];
foreach ($updates as $paragraph_id => $update) {
  $node = Node::load($update['node']);
  $paragraph = Paragraph::load($paragraph_id);
  if (!$node || !$paragraph || $node->bundle() !== 'dyne_page' || $paragraph->bundle() !== 'dyne_rich_text') {
    throw new RuntimeException("Unexpected node or Paragraph for {$update['alias']} (Paragraph {$paragraph_id}).");
  }
  if ($alias_manager->getAliasByPath('/node/' . $node->id()) !== $update['alias']) {
    throw new RuntimeException("Route guard failed for node {$node->id()}.");
  }
  $paragraph_ids = array_map(static fn (Paragraph $item): int => (int) $item->id(), $node->get('field_components')->referencedEntities());
  if (!in_array((int) $paragraph_id, $paragraph_ids, TRUE)) {
    throw new RuntimeException("Paragraph {$paragraph_id} is not attached to {$update['alias']}.");
  }

  $body = $paragraph->get('field_body_text');
  if ($body->value === $update['value']) {
    continue;
  }
  $paragraph->set('field_body_text', ['value' => $update['value'], 'format' => $body->format ?: 'full_html']);
  $paragraph->save();
  $changed[] = (string) $paragraph_id;
}

print $changed ? 'Updated Paragraphs: ' . implode(', ', $changed) . ".\n" : "Story index content is already current; no Paragraphs changed.\n";