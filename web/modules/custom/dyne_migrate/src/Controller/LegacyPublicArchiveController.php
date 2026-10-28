<?php

namespace Drupal\dyne_migrate\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Preserves only the public legacy shells actually observed during the audit.
 *
 * This does not install WordPress, collect accessibility statistics, or expose
 * operational records. Quote content continues to come from native entities.
 */
final class LegacyPublicArchiveController extends ControllerBase {

  private const STATISTICS = [
    'android' => 'Android', 'chrome' => 'Chrome', 'firefox' => 'Firefox',
    'iphone' => 'iPhone', 'linux' => 'Linux', 'macintosh' => 'Macintosh',
    'safari' => 'Safari', 'view' => 'view', 'windows' => 'Windows',
  ];

  private const QUOTES = [
    'oxana-beskrovnaya' => 1448,
    'deb-feldman' => 1451,
    'tim-weeden' => 1452,
    'cody-desjardins' => 1458,
  ];

  /** Empty archive shell, matching the public source without its WP internals. */
  public function statistics(string $type): array {
    if (!isset(self::STATISTICS[$type])) {
      throw new NotFoundHttpException();
    }
    return $this->shell();
  }

  /** Title metadata remains meaningful while no visible article is invented. */
  public function statisticsTitle(string $type): string {
    if (!isset(self::STATISTICS[$type])) {
      throw new NotFoundHttpException();
    }
    return self::STATISTICS[$type] . ' Archives';
  }

  /** Render the established reusable quote and its managed source portrait. */
  public function quote(string $slug): array {
    $quote = $this->loadQuote($slug);
    $build = $this->shell();
    $cache = CacheableMetadata::createFromRenderArray($build)->merge(CacheableMetadata::createFromObject($quote));
    $cache->addCacheableDependency($quote->access('view', NULL, TRUE));
    if ($quote->hasField('field_speaker_image') && ($media = $quote->get('field_speaker_image')->entity)) {
      $access = $media->access('view', NULL, TRUE);
      $cache->addCacheableDependency($media)->addCacheableDependency($access);
      $source = $media->getSource()->getConfiguration()['source_field'] ?? '';
      if ($access->isAllowed() && $source && ($file = $media->get($source)->entity)) {
        $cache->addCacheableDependency($file);
        $build['hero']['row']['hero']['base'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['hero-bg-base', 'bg-img']],
          'picture' => [
            '#type' => 'html_tag', '#tag' => 'picture',
            'image' => ['#theme' => 'image', '#uri' => $file->getFileUri(), '#alt' => $quote->label()],
          ],
        ];
      }
    }
    $build['quote'] = [
      '#type' => 'processed_text',
      '#text' => (string) $quote->get('field_quote_text')->value,
      '#format' => $quote->get('field_quote_text')->format,
    ];
    $cache->applyTo($build);
    return $build;
  }

  /** Resolve the native quote label for document metadata. */
  public function quoteTitle(string $slug): string {
    return $this->loadQuote($slug)->label();
  }

  /** Restrict the compatibility route to the exact published source records. */
  private function loadQuote(string $slug): NodeInterface {
    if (!isset(self::QUOTES[$slug])) {
      throw new NotFoundHttpException();
    }
    $storage = $this->entityTypeManager()->getStorage('node');
    $matches = $storage->loadByProperties(['type' => 'quote', 'field_wp_source_id' => self::QUOTES[$slug]]);
    $quote = count($matches) === 1 ? reset($matches) : NULL;
    if (!$quote instanceof NodeInterface || !$quote->isPublished() || !$quote->access('view')) {
      throw new NotFoundHttpException();
    }
    return $quote;
  }

  /** Branded empty hero and footer shell; excluded from search indexing. */
  private function shell(): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['wrapper', 'pages', 'dyne-legacy-archive']],
      'hero' => [
        '#type' => 'container', '#attributes' => ['class' => ['container-fluid']],
        'row' => [
          '#type' => 'container', '#attributes' => ['class' => ['row']],
          'hero' => ['#type' => 'container', '#attributes' => ['class' => ['hero', 'hero-secondary'], 'aria-hidden' => 'true']],
        ],
      ],
      '#attached' => ['html_head' => [[['#tag' => 'meta', '#attributes' => ['name' => 'robots', 'content' => 'noindex, follow']], 'dyne_legacy_archive_robots']]],
      '#cache' => ['contexts' => ['url.path'], 'max-age' => 3600],
    ];
  }

}
