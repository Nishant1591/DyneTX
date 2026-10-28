<?php

namespace Drupal\dyne_migrate\EventSubscriber;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Preserves the source site's standalone video destinations.
 *
 * These ten records are media destinations, not marketing-page layouts. The
 * source uses five permanent redirects and five full-viewport media frames.
 * The existing node remains the authority for publication, access and title.
 */
final class LegacyVideoDestinationSubscriber implements EventSubscriberInterface {

  private const DESTINATIONS = [
    1724 => ['mode' => 'frame', 'file' => 'Dyne-APR9-ASOs.mp4'],
    1928 => ['mode' => 'redirect', 'file' => 'RARE-DISEASE-DAY_2022.mp4'],
    1936 => ['mode' => 'redirect', 'file' => 'RARE-DISEASE-DAY_2022_AR.mp4'],
    1984 => ['mode' => 'redirect', 'file' => 'FSHD_Chelsea_Moeller_Story_R1_V1.mp4'],
    2192 => ['mode' => 'redirect', 'file' => 'Duchenne_Diana_Johnson_Story_R2.mp4'],
    2219 => ['mode' => 'redirect', 'file' => 'DM1_Joachim_Tim_Story_R3.mp4'],
    2235 => ['mode' => 'frame', 'file' => 'COMMITMENT_TO_PATIENTS_ALT_V2.mp4', 'autoplay' => TRUE],
    2294 => ['mode' => 'frame', 'file' => 'Dyne Service Day 2022_V6.mp4'],
    2309 => ['mode' => 'frame', 'file' => 'rare_disease_day_2023.mp4'],
    2342 => ['mode' => 'frame', 'file' => 'Dyne_Culture_Video.mp4'],
  ];

  public function __construct(private FileUrlGeneratorInterface $fileUrls) {}

  public static function getSubscribedEvents(): array {
    // Run after routing and parameter conversion, so normal node access and
    // aliases still apply. Do not intercept POST or administrative edit routes.
    return [KernelEvents::REQUEST => ['respond', 0]];
  }

  public function respond(RequestEvent $event): void {
    $request = $event->getRequest();
    if (!$event->isMainRequest() || !in_array($request->getMethod(), ['GET', 'HEAD'], TRUE) || $request->attributes->get('_route') !== 'entity.node.canonical') {
      return;
    }
    $node = $request->attributes->get('node');
    if (!$node instanceof NodeInterface || $node->bundle() !== 'dyne_page' || !$node->hasField('field_wp_source_id')) {
      return;
    }
    $destination = self::DESTINATIONS[(int) $node->get('field_wp_source_id')->value] ?? NULL;
    if (!$destination || !$node->isPublished() || !$node->access('view')) {
      return;
    }
    $uri = 'public://legacy/' . $destination['file'];
    if (!is_file($uri)) {
      // Leave the normal page available if a deployment omitted its media.
      // Never redirect visitors to a missing file or an external substitute.
      return;
    }
    $url = $this->fileUrls->generateString($uri);
    if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
      return;
    }
    if ($destination['mode'] === 'redirect') {
      $response = new LocalRedirectResponse($url, 301);
    }
    else {
      $query = !empty($destination['autoplay']) ? ['autoplay' => 1] : [];
      $query['visit'] = 'Content-Mask-1.8.4.1';
      $frame_url = $url . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
      $title = Html::escape($node->label() . ' - Dyne Therapeutics');
      $label = Html::escape($node->label());
      $src = Html::escape($frame_url);
      $html = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . $title . '</title><style>html,body{margin:0;padding:0;background:#000}iframe{display:block;border:0;height:100vh;width:100%;box-sizing:border-box}</style>'
        . '</head><body><iframe id="content-mask-frame" title="' . $label . '" src="' . $src . '" allowfullscreen></iframe></body></html>';
      $response = new CacheableResponse($request->isMethod('HEAD') ? '' : $html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
    // A removed media file or changed publication state takes effect promptly.
    $cache = CacheableMetadata::createFromObject($node)->setCacheMaxAge(0);
    $response->addCacheableDependency($cache);
    $event->setResponse($response);
  }

}
