<?php

namespace Drupal\dyne_migrate\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\State\StateInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Supports WordPress numeric canonical URLs, without hijacking normal aliases.
 */
final class LegacyNumericRedirectSubscriber implements EventSubscriberInterface {

  public function __construct(private StateInterface $state, private EntityTypeManagerInterface $entities, private AliasManagerInterface $aliases) {}

  public static function getSubscribedEvents(): array {
    return [KernelEvents::REQUEST => ['redirect', 35]];
  }

  public function redirect(RequestEvent $event): void {
    $request = $event->getRequest();
    if (!$event->isMainRequest() || !in_array($request->getMethod(), ['GET', 'HEAD'], TRUE)) {
      return;
    }
    $path = rawurldecode($request->getPathInfo());
    $args = $request->query->all();
    if (str_starts_with($path, '/wp-content/uploads/')) {
      $assets = $this->state->get('dyne_migrate.legacy_upload_urls', []);
      $asset = $assets[$path] ?? NULL;
      if ($asset && is_file($asset['uri'])) {
        $url = $asset['target'] . ($args ? '?' . http_build_query($args, '', '&', PHP_QUERY_RFC3986) : '');
        $response = new LocalRedirectResponse($url, 301);
        $response->getCacheableMetadata()->setCacheMaxAge(0);
        $event->setResponse($response);
      }
      return;
    }
    if ($path !== '/') {
      return;
    }
    $param = isset($args['p']) ? 'p' : (isset($args['page_id']) ? 'page_id' : NULL);
    if (!$param || !is_scalar($args[$param]) || !preg_match('/^[1-9][0-9]*$/D', (string) $args[$param])) {
      return;
    }
    $map = $this->state->get('dyne_migrate.legacy_numeric_urls', []);
    $item = $map[$param . ':' . $args[$param]] ?? NULL;
    if (!$item) {
      // Numeric WordPress IDs must not silently display an unrelated homepage.
      $event->setResponse(new Response('Not found', 404, ['Cache-Control' => 'no-store']));
      return;
    }
    $node = $this->entities->getStorage('node')->load($item['nid']);
    if (!$node || !$node->isPublished() || !$node->access('view')) {
      $event->setResponse(new Response('Not found', 404, ['Cache-Control' => 'no-store']));
      return;
    }
    $target = $item['target'] === '/' ? '/' : $this->aliases->getAliasByPath('/node/' . $node->id());
    if (!str_starts_with($target, '/') || str_starts_with($target, '//')) {
      return;
    }
    unset($args['p'], $args['page_id']);
    if ($args) {
      $target .= '?' . http_build_query($args, '', '&', PHP_QUERY_RFC3986);
    }
    $response = new LocalRedirectResponse($target, 301);
    // Entity visibility and alias edits must take effect immediately.
    $response->getCacheableMetadata()->setCacheMaxAge(0);
    $event->setResponse($response);
  }

}
