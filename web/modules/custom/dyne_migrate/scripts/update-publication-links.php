<?php

/**
 * Restore verified presentation links in the existing publications Paragraph.
 *
 * Run with:
 *   ddev drush php:script web/modules/custom/dyne_migrate/scripts/update-publication-links.php
 */
$paragraph = \Drupal\paragraphs\Entity\Paragraph::load(157);
if (!$paragraph || $paragraph->bundle() !== 'dyne_rich_text') {
  throw new \RuntimeException('Expected publications rich-text Paragraph 157.');
}

$html = $paragraph->get('field_body_text')->value;
$links = [
  'FORZETTO, a Phase 3 Study of Z-Rostudirsen in Ambulatory Males with Exon51 Amenable DMD' => 'https://www.dyne-tx.com/wp-content/uploads/Naylor_2026_ICNMD_FORZETTO.pdf',
  'Targeting Tauopathies: Robust and Widespread MAPT Silencing in CNS of Miceand NHP with TfR1-mediated Oligonucleotide Delivery' => 'https://www.dyne-tx.com/wp-content/uploads/Correia_2026_ASGCT_MAPT.pdf',
  'The FORCE™ Platform Does Not Cause Anemiain Repeat-Dose Preclinical Toxicity Studies' => 'https://www.dyne-tx.com/wp-content/uploads/Davis_SOT_March_2026.pdf',
];
$changed = FALSE;

foreach ($links as $title => $url) {
  if (str_contains($html, 'href="' . $url . '"')) {
    continue;
  }
  if (substr_count($html, $title) !== 1) {
    throw new \RuntimeException('Expected one exact publication title match: ' . $title);
  }
  $html = str_replace($title, '<a href="' . $url . '">' . $title . '</a>', $html);
  $changed = TRUE;
}

if ($changed) {
  $paragraph->set('field_body_text', $html);
  $paragraph->save();
  print "Updated publications Paragraph {$paragraph->id()} with verified PDF links.\n";
}
else {
  print "Publications Paragraph {$paragraph->id()} already has all verified PDF links; no save needed.\n";
}
