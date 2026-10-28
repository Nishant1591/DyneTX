<?php

/**
 * @file
 * Reusable policies and live perspective listings, preserving current markup.
 */

function dyne_native_lists_schema(): void {
  if (!\Drupal\node\Entity\NodeType::load('privacy_policy')) {
    \Drupal\node\Entity\NodeType::create(['type' => 'privacy_policy', 'name' => 'Privacy policy', 'description' => 'Reusable policy blocks displayed on the privacy page.'])->save();
  }
  foreach (['dyne_policy_list' => 'Privacy policies', 'dyne_perspective_list' => 'Perspectives listing'] as $id => $label) {
    if (!\Drupal\paragraphs\Entity\ParagraphsType::load($id)) \Drupal\paragraphs\Entity\ParagraphsType::create(['id' => $id, 'label' => $label])->save();
  }
  dyne_layout_field('node', 'privacy_policy', 'field_body_text', 'text_long', 'Policy text');
  dyne_layout_field('node', 'privacy_policy', 'field_wp_source_id', 'integer', 'Original WordPress record ID');
  dyne_layout_field('node', 'privacy_policy', 'field_policy_updated', 'datetime', 'Last updated', [], 1, ['datetime_type' => 'date']);
  dyne_layout_field('node', 'privacy_policy', 'field_policy_anchor', 'string', 'Section anchor');
  dyne_layout_field('paragraph', 'dyne_policy_list', 'field_policies', 'entity_reference', 'Policies (display order)', ['handler' => 'default:node', 'handler_settings' => ['target_bundles' => ['privacy_policy' => 'privacy_policy']]], -1, ['target_type' => 'node']);
  foreach (['field_list_intro' => 'Introduction', 'field_list_outro' => 'Following content'] as $field => $label) {
    dyne_layout_field('paragraph', 'dyne_perspective_list', $field, 'text_long', $label);
  }
  dyne_layout_field('node', 'perspective', 'field_story_excerpt', 'text_long', 'Listing summary');
  $view = \Drupal\Core\Entity\Entity\EntityViewDisplay::load('node.privacy_policy.default');
  $view->setComponent('field_body_text', ['type' => 'text_default', 'label' => 'hidden'])->save();
  $components = \Drupal\field\Entity\FieldConfig::loadByName('node', 'dyne_page', 'field_components');
  $settings = $components->getSetting('handler_settings');
  if (!empty($settings['target_bundles'])) {
    foreach (['dyne_policy_list', 'dyne_perspective_list'] as $id) $settings['target_bundles'][$id] = $id;
    $components->setSetting('handler_settings', $settings)->save();
  }
  require_once __DIR__ . '/perspective-list-model.inc';
  dyne_perspective_list_schema();
}

/** One-time conversion; subsequent runs preserve editorial changes. */
function dyne_native_lists_upgrade(): array {
  dyne_native_lists_schema();
  $state = \Drupal::state();
  $ledger = $state->get('dyne_migrate.native_lists', []);
  $report = ['policies_created' => 0, 'pages_changed' => [], 'already_complete' => []];
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  foreach (['privacy-policy', 'perspectives'] as $slug) {
    if (!empty($ledger[$slug])) { $report['already_complete'][] = $slug; continue; }
    $path = \Drupal::service('path_alias.manager')->getPathByAlias('/' . $slug);
    if (!preg_match('#^/node/(\d+)$#', $path, $match)) throw new \RuntimeException("Missing page $slug");
    $node = $storage->load($match[1]);
    $components = $node->get('field_components')->referencedEntities();
    $replacements = [];
    foreach ($components as $delta => $component) {
      if ($component->bundle() !== 'dyne_rich_text') continue;
      $body = (string) $component->get('field_body_text')->value;
      if ($slug === 'privacy-policy' && str_contains($body, 'id="dyne-privacy-statement"')) {
        $pattern = '#<section id="([^"]+)" class="container-fluid section page-intro"><div class="container"><h([12]) class="h2 h2-new-sm h2-new-thin">(.*?)</h\2><p><i>Last Updated: ([^<]+)</i></p>(.*?)</div></section>#s';
        preg_match_all($pattern, $body, $policies, PREG_SET_ORDER);
        if (count($policies) !== 2 || implode('', array_column($policies, 0)) !== $body) throw new \RuntimeException('Privacy markup changed; refusing a partial conversion.');
        $refs = [];
        foreach ($policies as $policy) {
          $source_id = $policy[1] === 'dyne-privacy-statement' ? 3790 : 3792;
          $matches = $storage->loadByProperties(['type' => 'privacy_policy', 'field_wp_source_id' => $source_id]);
          $entity = $matches ? reset($matches) : $storage->create([
            'type' => 'privacy_policy', 'title' => html_entity_decode(strip_tags($policy[3]), ENT_QUOTES | ENT_HTML5), 'status' => 1, 'uid' => 1,
            'field_wp_source_id' => $source_id, 'field_policy_updated' => $policy[4], 'field_policy_anchor' => $policy[1],
            'field_body_text' => ['value' => $policy[5], 'format' => 'dyne_legacy_html'],
          ]);
          if ($entity->isNew()) { $entity->save(); $report['policies_created']++; }
          $refs[] = ['target_id' => $entity->id()];
        }
        $replacement = \Drupal\paragraphs\Entity\Paragraph::create(['type' => 'dyne_policy_list', 'field_policies' => $refs]);
        $replacement->save(); $replacements[$delta] = $replacement;
      }
      elseif ($slug === 'perspectives' && str_contains($body, 'class="container-fluid section perspective-articles"')) {
        $pattern = '#(<section class="container-fluid section perspective-articles"><div class="container"><div class="row">)(.*?)(</div></div></section>)#s';
        if (!preg_match($pattern, $body, $cards, PREG_OFFSET_CAPTURE)) throw new \RuntimeException('Perspective cards could not be isolated.');
        $replacement = \Drupal\paragraphs\Entity\Paragraph::create([
          'type' => 'dyne_perspective_list',
          'field_list_intro' => ['value' => substr($body, 0, $cards[0][1]), 'format' => 'dyne_legacy_html'],
          'field_list_outro' => ['value' => substr($body, $cards[0][1] + strlen($cards[0][0])), 'format' => 'dyne_legacy_html'],
        ]);
        $replacement->save(); $replacements[$delta] = $replacement;
      }
    }
    if (count($replacements) !== 1) throw new \RuntimeException("Expected one replaceable rich-text component on $slug.");
    $refs = [];
    foreach ($components as $delta => $component) {
      $p = $replacements[$delta] ?? $component;
      $refs[] = ['target_id' => $p->id(), 'target_revision_id' => $p->getRevisionId()];
    }
    $node->setNewRevision(TRUE); $node->setRevisionLogMessage('Replace duplicated policy/perspective markup with native reusable records.');
    $node->set('field_components', $refs); $node->save();
    $ledger[$slug] = ['nid' => $node->id(), 'revision' => $node->getRevisionId()];
    $state->set('dyne_migrate.native_lists', $ledger); $report['pages_changed'][] = $slug;
  }
  return $report;
}
