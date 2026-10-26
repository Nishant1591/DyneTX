<?php

/**
 * @file
 * Revision-preserving adoption of the current pipeline into reusable programs.
 */

require_once __DIR__ . '/pipeline-program-model.inc';
require_once __DIR__ . '/pipeline-program-parser.inc';

/** The page owns introductory copy and the ordered list; programs own facts. */
function dyne_pipeline_component_schema(): void {
  require_once __DIR__ . '/native-components.inc';
  $bundle = 'dyne_program_pipeline';
  if (!\Drupal\paragraphs\Entity\ParagraphsType::load($bundle)) {
    \Drupal\paragraphs\Entity\ParagraphsType::create(['id' => $bundle, 'label' => 'Program pipeline', 'description' => 'Pipeline chart and details from reusable Pipeline program records.'])->save();
  }
  foreach ([
    'field_pipeline_intro_heading' => ['text_long', 'Introduction heading'],
    'field_pipeline_intro' => ['text_long', 'Introduction'],
    'field_pipeline_heading' => ['string', 'Chart heading'],
    'field_expansion_heading' => ['string', 'Expansion opportunities label'],
    'field_expansion_targets' => ['string', 'Expansion opportunities'],
    'field_expansion_title' => ['string', 'Expansion detail heading'],
    'field_expansion_body' => ['text_long', 'Expansion detail text'],
  ] as $name => [$type, $label]) {
    dyne_component_field('paragraph', $bundle, $name, $type, $label, $type === 'text_long' ? ['allowed_formats' => ['dyne_legacy_html']] : []);
  }
  dyne_component_field('paragraph', $bundle, 'field_pipeline_programs', 'entity_reference', 'Pipeline programs (drag to order)', ['handler' => 'default:node', 'handler_settings' => ['target_bundles' => ['pipeline_program' => 'pipeline_program'], 'auto_create' => FALSE]], ['target_type' => 'node'], -1);
  $form = \Drupal\Core\Entity\Entity\EntityFormDisplay::load('paragraph.' . $bundle . '.default');
  foreach (['field_pipeline_intro_heading', 'field_pipeline_intro', 'field_pipeline_heading', 'field_pipeline_programs', 'field_expansion_heading', 'field_expansion_targets', 'field_expansion_title', 'field_expansion_body'] as $weight => $name) {
    $component = $form->getComponent($name);
    $component['weight'] = $weight;
    $form->setComponent($name, $component);
  }
  $form->save();
  $components = \Drupal\field\Entity\FieldConfig::loadByName('node', 'dyne_page', 'field_components');
  if ($components) {
    $handler = $components->getSetting('handler_settings') ?: [];
    // An empty target_bundles list means all bundles are already permitted.
    if (!empty($handler['target_bundles'])) $handler['target_bundles'][$bundle] = $bundle;
    if (isset($handler['target_bundles_drag_drop'])) $handler['target_bundles_drag_drop'][$bundle] = ['enabled' => TRUE, 'weight' => count($handler['target_bundles_drag_drop'])];
    $components->setSetting('handler_settings', $handler)->save();
  }
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}

/** Locate the imported page by source identity, with alias-only fallback. */
function dyne_pipeline_source_page() {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $ids = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'dyne_page')->condition('field_wp_source_id', 1149)->execute();
  if (count($ids) > 1) throw new \RuntimeException('Multiple pages claim the pipeline source identity; no content was changed.');
  if ($ids) return $storage->load(reset($ids));
  $path = \Drupal::service('path_alias.manager')->getPathByAlias('/pipeline');
  return preg_match('~^/node/(\d+)$~D', $path, $match) ? $storage->load($match[1]) : NULL;
}

/** Compose current editorial values rather than silently using an old fixture. */
function dyne_pipeline_migration_plan(): array {
  $page = dyne_pipeline_source_page();
  if (!$page || $page->bundle() !== 'dyne_page' || !$page->hasField('field_components')) return ['available' => FALSE];
  $source = NULL;
  foreach ($page->get('field_components')->referencedEntities() as $delta => $paragraph) {
    if ($paragraph->bundle() === 'dyne_program_pipeline') return ['available' => TRUE, 'complete' => TRUE, 'page_id' => (int) $page->id(), 'component_id' => (int) $paragraph->id()];
    if (!$paragraph->hasField('field_body_text')) continue;
    if ($paragraph->hasField('field_structured_content') && $paragraph->get('field_structured_content')->value) {
      // Migration-time composition only. Public requests use native references.
      require_once DRUPAL_ROOT . '/themes/custom/dynetx/includes/native-metadata.inc';
      require_once DRUPAL_ROOT . '/themes/custom/dynetx/includes/structured-articles.inc';
      $cache = \Drupal\Core\Cache\CacheableMetadata::createFromObject($paragraph);
      $html = _dynetx_structured_article_markup($paragraph, $cache);
    }
    else $html = (string) $paragraph->get('field_body_text')->value;
    if (!str_contains($html, 'pipeline-chart-26')) continue;
    if ($source !== NULL) throw new \RuntimeException('Multiple current pipeline chart components need reviewed reconciliation.');
    $source = ['delta' => $delta, 'id' => (int) $paragraph->id(), 'revision' => (int) $paragraph->getRevisionId(), 'html' => $html];
  }
  if (!$source) throw new \RuntimeException('The current pipeline page has no recognized chart. No archived fixture will replace it.');
  $parsed = dyne_pipeline_parse($source['html']);
  if (!$parsed['programs']) throw new \RuntimeException('The pipeline chart contains no parseable programs.');
  foreach ($parsed['programs'] as $code => $program) {
    if (!empty($program['html']['additional'])) throw new \RuntimeException($code . ' contains an unrecognized detail section. Reconcile it before replacing the page component.');
    if ($program['bar_percent'] < 0 || $program['bar_percent'] > 100 || floor($program['bar_percent']) !== $program['bar_percent']) throw new \RuntimeException($code . ' has a nonintegral chart width requiring reviewed reconciliation.');
    $caption = $program['video']['caption_html'] ?? '';
    if ($caption !== '' && $caption !== htmlspecialchars($program['video']['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') && $caption !== $program['video']['title']) throw new \RuntimeException($code . ' has a formatted video caption requiring reviewed reconciliation.');
  }
  return ['available' => TRUE, 'complete' => FALSE, 'page_id' => (int) $page->id(), 'page_revision' => (int) $page->getRevisionId(), 'source' => $source, 'parsed' => $parsed];
}

/** Backfill once; existing records and later editorial changes are preserved. */
function dyne_pipeline_program_backfill(?array $plan = NULL): array {
  $plan ??= dyne_pipeline_migration_plan();
  if (!$plan['available']) return $plan;
  if (!empty($plan['complete'])) {
    $ledger = \Drupal::state()->get('dyne_migrate.pipeline_programs', []);
    return ($ledger['component_id'] ?? NULL) === $plan['component_id'] ? ['already_complete' => TRUE] + $ledger : $plan;
  }
  $nodes = \Drupal::entityTypeManager()->getStorage('node');
  $page = $nodes->load($plan['page_id']);
  if ((int) $page->getRevisionId() !== $plan['page_revision']) throw new \RuntimeException('The pipeline page changed after planning. Rebuild the migration plan before applying it.');
  $references = $page->get('field_components')->getValue();
  $source = $references[$plan['source']['delta']] ?? [];
  if ((int) ($source['target_id'] ?? 0) !== $plan['source']['id'] || (int) ($source['target_revision_id'] ?? 0) !== $plan['source']['revision']) throw new \RuntimeException('The pipeline component changed after planning.');
  $transaction = \Drupal::database()->startTransaction();
  try {
    $report = ['available' => TRUE, 'complete' => TRUE, 'source_page_id' => (int) $page->id(), 'source_page_revision' => (int) $page->getRevisionId(), 'source_component_id' => $plan['source']['id'], 'source_component_revision' => $plan['source']['revision'], 'source_sha256' => hash('sha256', $plan['source']['html']), 'programs' => [], 'warnings' => $plan['parsed']['warnings'] ?? []];
    $program_refs = [];
    foreach ($plan['parsed']['programs'] as $code => $program) {
      $existing = $nodes->getQuery()->accessCheck(FALSE)->condition('type', 'pipeline_program')->condition('field_program_code', $code)->execute();
      if (count($existing) > 1) throw new \RuntimeException('Duplicate Pipeline program code: ' . $code);
      $created = !$existing;
      $node = $existing ? $nodes->load(reset($existing)) : \Drupal\node\Entity\Node::create(['type' => 'pipeline_program', 'title' => $program['name'], 'uid' => $page->getOwnerId(), 'status' => (int) $page->isPublished(), 'langcode' => $page->language()->getId()]);
      if ($created) {
        $scalars = ['field_program_code' => 'code', 'field_program_short_name' => 'short_name', 'field_disease_text' => 'disease', 'field_disease_short' => 'disease_short', 'field_prevalence_us' => 'prevalence_us', 'field_prevalence_eu' => 'prevalence_europe', 'field_target' => 'target', 'field_phase' => 'phase_label', 'field_status' => 'milestone', 'field_program_progress' => 'bar_percent', 'field_program_anchor' => 'anchor'];
        foreach ($scalars as $field => $key) $node->set($field, $program[$key] ?? '');
        $node->set('field_program_aliases', array_map(static fn($alias) => ['value' => $alias], $program['aliases'] ?? []));
        foreach (['field_program_heading' => 'program_heading', 'field_body_text' => 'introduction', 'field_disease_heading' => 'disease_heading', 'field_disease_overview' => 'disease_overview', 'field_disease_onset' => 'onset', 'field_disease_cause' => 'cause', 'field_disease_symptoms' => 'symptoms', 'field_unmet_need' => 'unmet_need'] as $field => $key) {
          $value = $program['html'][$key] ?? '';
          $node->set($field, $value === '' ? [] : ['value' => $value, 'format' => 'dyne_legacy_html']);
        }
        $links = [];
        $ctas = !empty($program['cta']['url']) ? [$program['cta']] : [];
        foreach ($ctas as $cta) $links[] = ['uri' => str_starts_with($cta['url'], '/') ? 'internal:' . $cta['url'] : $cta['url'], 'title' => $cta['title']];
        $node->set('field_trial_links', $links);
        if (!empty($program['video']['id'])) {
          $node->set('field_program_video', ['uri' => 'https://www.youtube.com/watch?v=' . rawurlencode($program['video']['id']), 'title' => $program['video']['title']]);
          $node->set('field_program_video_alt', $program['video']['alt'] ?? '');
          $cover = dyne_native_media_for_url($program['video']['cover']);
          if (!$cover) throw new \RuntimeException('The approved pipeline video cover could not be resolved.');
          $node->set('field_program_video_cover', ['target_id' => $cover->id()]);
        }
        $node->setRevisionLogMessage('Separate the current pipeline chart and approved narrative into native Pipeline program fields.');
        $node->save();
      }
      $program_refs[] = ['target_id' => $node->id()];
      $report['programs'][$code] = ['id' => (int) $node->id(), 'revision' => (int) $node->getRevisionId(), 'created' => $created];
    }
    $parsed = $plan['parsed'];
    $component = \Drupal\paragraphs\Entity\Paragraph::create(['type' => 'dyne_program_pipeline', 'langcode' => $page->language()->getId(), 'field_pipeline_programs' => $program_refs]);
    foreach (['field_pipeline_intro_heading' => $parsed['intro_heading_html'] ?? '', 'field_pipeline_intro' => $parsed['intro_html'] ?? '', 'field_expansion_body' => $parsed['expansion']['body_html'] ?? ''] as $field => $value) $component->set($field, $value === '' ? [] : ['value' => $value, 'format' => 'dyne_legacy_html']);
    foreach (['field_pipeline_heading' => $parsed['chart_heading'] ?? '', 'field_expansion_heading' => $parsed['expansion']['heading'] ?? '', 'field_expansion_targets' => $parsed['expansion']['targets'] ?? '', 'field_expansion_title' => $parsed['expansion']['title'] ?? ''] as $field => $value) $component->set($field, $value);
    $component->save();
    $references[$plan['source']['delta']] = ['target_id' => $component->id(), 'target_revision_id' => $component->getRevisionId()];
    $page->setNewRevision(TRUE);
    $page->set('field_components', $references);
    $page->setRevisionLogMessage('Connect the pipeline chart and details to eight reusable Pipeline program records; preserve the previous page revision.');
    $page->save();
    $report['component_id'] = (int) $component->id();
    $report['component_revision'] = (int) $component->getRevisionId();
    $report['page_revision'] = (int) $page->getRevisionId();
    \Drupal::state()->set('dyne_migrate.pipeline_programs', $report);
    unset($transaction);
    return $report;
  }
  catch (\Throwable $error) {
    $transaction->rollBack();
    $nodes->resetCache();
    throw $error;
  }
}

/** Plan before configuration/content writes, then perform an additive upgrade. */
function dyne_pipeline_program_upgrade(): array {
  $plan = dyne_pipeline_migration_plan();
  dyne_pipeline_program_model_schema();
  dyne_pipeline_component_schema();
  return dyne_pipeline_program_backfill($plan);
}
