<?php
/**
 * Remove the pre-existing orphaned Home node (created before create-pages.php)
 * that lost its alias to the new Home node. Deletes its paragraphs too.
 */
$ns = \Drupal::entityTypeManager()->getStorage('node');
$n = $ns->load(2);
if ($n) {
  if ($n->hasField('field_components') && !$n->get('field_components')->isEmpty()) {
    foreach ($n->get('field_components')->referencedEntities() as $p) {
      if ($p instanceof \Drupal\paragraphs\Entity\Paragraph) { $p->delete(); }
    }
  }
  $n->delete();
  print "Deleted orphan Home node 2 and its paragraphs\n";
} else {
  print "Node 2 not found\n";
}