<?php

$wanted_aliases = ['/dmd', '/dm1', '/fshd'];
$alias_manager = \Drupal::service('path_alias.manager');
$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'dyne_page']);

foreach ($nodes as $node) {
  $alias = $alias_manager->getAliasByPath('/node/' . $node->id());
  if (!in_array($alias, $wanted_aliases, TRUE)) {
    continue;
  }

  echo "ROUTE {$alias} nid={$node->id()} title={$node->label()}\n";
  foreach ($node->get('field_components')->referencedEntities() as $paragraph) {
    echo "PARAGRAPH {$paragraph->bundle()} pid={$paragraph->id()}\n";
    foreach (['field_heading', 'field_body_text', 'field_title_line1', 'field_banner_text'] as $field_name) {
      if ($paragraph->hasField($field_name) && !$paragraph->get($field_name)->isEmpty()) {
        echo strtoupper($field_name) . ': ' . $paragraph->get($field_name)->value . "\n";
      }
    }
  }
}