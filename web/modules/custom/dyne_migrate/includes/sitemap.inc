<?php

/**
 * @file
 * Public-page sitemap configuration and reusable-content exclusions.
 */

use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\xmlsitemap\Entity\XmlSitemap;

/** Configure sitemap indexing without resaving content or running a batch. */
function dyne_migrate_sitemap_setup(): void {
  \Drupal::service('module_installer')->install(['xmlsitemap', 'robotstxt']);

  $included = ['dyne_page', 'job', 'leader', 'news', 'perspective', 'pipeline_program'];
  foreach (array_merge($included, ['quote', 'privacy_policy']) as $bundle) {
    if (!NodeType::load($bundle)) {
      continue;
    }
    $config = \Drupal::config('xmlsitemap.settings.node.' . $bundle);
    xmlsitemap_link_bundle_settings_save('node', $bundle, [
      'status' => in_array($bundle, $included, TRUE),
      'priority' => $config->get('priority') ?? 0.5,
      'changefreq' => $config->get('changefreq') ?? 0,
    ]);
  }

  \Drupal::configFactory()->getEditable('xmlsitemap.settings')
    ->set('disable_cron_regeneration', FALSE)
    ->set('metatag_exclude_noindex', TRUE)
    ->save();

  // Existing deployments may still contain a scaffolded static file. Import
  // exactly the rules that are currently served, then let Drupal own the route.
  $static_robots = DRUPAL_ROOT . '/robots.txt';
  if (is_file($static_robots)) {
    $rules = file_get_contents($static_robots);
    if ($rules === FALSE) {
      throw new \RuntimeException('Cannot read the existing robots.txt crawl rules.');
    }
    \Drupal::configFactory()->getEditable('robotstxt.settings')->set('content', $rules)->save();
    if (!unlink($static_robots)) {
      throw new \RuntimeException('Cannot remove the static robots.txt after preserving its crawl rules.');
    }
  }

  // Use the deployment's configured origin, never an origin from an old DB copy.
  // CLI deployments must supply Drush --uri, just as a web request supplies Host.
  $base_url = rtrim(Settings::get('xmlsitemap_base_url') ?: Url::fromUri('base:', ['absolute' => TRUE])->toString(), '/');
  $parts = parse_url($base_url);
  if (!$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], TRUE) || empty($parts['host']) || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass'])) {
    throw new \InvalidArgumentException('The sitemap base URL must be an absolute HTTP(S) site URL.');
  }
  \Drupal::state()->setMultiple([
    'xmlsitemap_base_url' => $base_url,
    'xmlsitemap_rebuild_needed' => TRUE,
    'xmlsitemap_regenerate_needed' => TRUE,
  ]);

  $sitemap = XmlSitemap::loadByContext();
  if (!$sitemap) {
    $sitemap = XmlSitemap::create(['context' => []]);
  }
  if (!$sitemap->label() || filter_var($sitemap->label(), FILTER_VALIDATE_URL)) {
    $sitemap->setLabel('Default sitemap');
    xmlsitemap_sitemap_save($sitemap);
  }
}

/** Exclude records whose public destination is elsewhere or is archival. */
function dyne_migrate_sitemap_link_alter(array &$link, array $context): void {
  $node = $context['entity'] ?? $context['node'] ?? NULL;
  if (!$node instanceof NodeInterface) {
    return;
  }
  if (!$node->isPublished() || in_array($node->bundle(), ['quote', 'privacy_policy'], TRUE)) {
    $link['access'] = FALSE;
    return;
  }
  if ($node->bundle() === 'news' && $node->hasField('field_news_type') && in_array($node->get('field_news_type')->value, ['url', 'file'], TRUE)) {
    $link['access'] = FALSE;
    return;
  }
  $path = $link['loc'] ?? '';
  if ($path !== '') {
    // XML Sitemap supplies its own root entry. Its adjacent-only de-duplication
    // does not reliably merge the front-page node with that dedicated entry.
    $front_path = \Drupal::service('path_alias.manager')->getPathByAlias(\Drupal::config('system.site')->get('page.front'), $node->language()->getId());
    if ($path === $front_path) {
      $link['access'] = FALSE;
      return;
    }
    $alias = \Drupal::service('path_alias.manager')->getAliasByPath($path, $node->language()->getId());
    if ($alias === '/legacy-archive' || str_starts_with($alias, '/legacy-archive/')) {
      $link['access'] = FALSE;
    }
  }
}
