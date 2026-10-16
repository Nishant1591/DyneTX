<?php
// One-time, guarded layout repair for the two nodes created in this completion.
$root = dirname(DRUPAL_ROOT);
$plan = json_decode(file_get_contents($root . '/output/content-audit/supplement-layout-plan.json'), TRUE);
$fixture = json_decode(file_get_contents($root . '/content-archive/wordpress-supplement.json'), TRUE);
$ledger = \Drupal::state()->get('dyne_migrate.content_completion');
foreach ($fixture['records'] as $record) {
  $id = $record['source_id'];
  if (!isset($plan[$id])) { continue; }
  $key = $record['source_type'] . ':' . $id;
  $hash = hash('sha256', json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
  if ($ledger[$key]['hash'] === $hash) { continue; }
  $node = \Drupal\node\Entity\Node::load($ledger[$key]['nid']);
  if ($ledger[$key]['hash'] !== $plan[$id]['previous_hash'] || $node->get('field_body_text')->value !== $plan[$id]['previous_body']) {
    throw new RuntimeException('Unexpected source or editorial change at ' . $id);
  }
  $transaction = \Drupal::database()->startTransaction();
  try {
    $refs = $node->get('field_components')->getValue();
    foreach ($node->get('field_components')->referencedEntities() as $index => $paragraph) {
      if ($paragraph->bundle() === 'dyne_rich_text') {
        if ($paragraph->get('field_body_text')->value !== $plan[$id]['previous_body']) {
          throw new RuntimeException('Unexpected paragraph content at ' . $id);
        }
        $paragraph->setNewRevision(TRUE);
        $paragraph->set('field_body_text', ['value' => $record['body'], 'format' => 'dyne_legacy_html']);
        $paragraph->save();
        $refs[$index]['target_revision_id'] = $paragraph->getRevisionId();
      }
    }
    $node->setNewRevision(TRUE);
    $node->setRevisionLogMessage('Clear fixed header on restored pages and label the archived video iframe.');
    $node->set('field_body_text', ['value' => $record['body'], 'format' => 'dyne_legacy_html']);
    $node->set('field_components', $refs);
    $node->save();
    $ledger[$key]['hash'] = $hash;
    \Drupal::state()->set('dyne_migrate.content_completion', $ledger);
    unset($transaction);
    echo 'Updated source page ' . $id . PHP_EOL;
  }
  catch (Throwable $error) { $transaction->rollBack(); throw $error; }
}
