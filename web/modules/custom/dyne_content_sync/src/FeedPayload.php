<?php

namespace Drupal\dyne_content_sync;

use Drupal\Component\Utility\Xss;

/**
 * Strict provider contract validation, separate from transport and writes.
 */
final class FeedPayload {

  public static function normalize(string $provider, array $payload): array {
    $items = $payload[$provider === 'greenhouse' ? 'jobs' : 'data'] ?? NULL;
    if (!in_array($provider, ['greenhouse', 'gcs'], TRUE) || !is_array($items) || !$items || !array_is_list($items)) {
      throw new \UnexpectedValueException('Missing or empty feed list; no content was changed.');
    }
    $out = [];
    foreach ($items as $item) {
      if (!is_array($item) || !is_scalar($item['id'] ?? NULL) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/D', (string) $item['id'])) {
        throw new \UnexpectedValueException('Feed contains an invalid remote ID.');
      }
      $id = (string) $item['id'];
      $title = trim(strip_tags(html_entity_decode((string) ($item['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
      if (!$title || isset($out[$id])) {
        throw new \UnexpectedValueException('Feed contains an empty title or duplicate remote ID.');
      }
      if ($provider === 'greenhouse') {
        $url = (string) ($item['absolute_url'] ?? '');
        if (!self::allowedUrl($url, ['boards.greenhouse.io', 'job-boards.greenhouse.io']) || !str_starts_with(parse_url($url, PHP_URL_PATH) ?? '', '/dynetherapeutics/jobs/')) {
          throw new \UnexpectedValueException('Job application URL is outside the Dyne Greenhouse board.');
        }
        $body = html_entity_decode((string) ($item['content'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!trim(strip_tags($body))) {
          throw new \UnexpectedValueException('Job description is empty; existing content was preserved.');
        }
        $out[$id] = ['provider' => $provider, 'id' => $id, 'title' => $title, 'body' => self::cleanBody($body), 'url' => $url, 'department' => trim((string) ($item['departments'][0]['name'] ?? '')), 'location' => trim((string) ($item['location']['name'] ?? ''))];
      }
      else {
        $url = (string) ($item['link']['hostedUrl'] ?? '');
        if (!str_contains(mb_strtolower($title), 'dyne') || !self::allowedUrl($url, ['investors.dyne-tx.com', 'www.globenewswire.com', 'globenewswire.com'])) {
          throw new \UnexpectedValueException('GCS record is not a verified Dyne release; nothing was queued.');
        }
        $date = strtotime((string) ($item['releaseDate']['date'] ?? ''));
        if (!$date || $date < 946684800) {
          throw new \UnexpectedValueException('GCS publication date is invalid.');
        }
        $body = $item['_resolved_body'] ?? NULL;
        if (!is_string($body) || !trim(strip_tags($body))) {
          throw new \UnexpectedValueException('GCS release body is unavailable; existing content was preserved.');
        }
        $out[$id] = ['provider' => $provider, 'id' => $id, 'title' => $title, 'body' => self::cleanBody($body), 'url' => $url, 'date' => gmdate('Y-m-d\TH:i:s', $date)];
      }
    }
    return array_values($out);
  }

  public static function allowedUrl(string $url, array $hosts): bool {
    $parts = parse_url($url);
    return is_array($parts) && ($parts['scheme'] ?? '') === 'https' && in_array(strtolower($parts['host'] ?? ''), $hosts, TRUE) && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['port']);
  }

  private static function cleanBody(string $body): string {
    $body = Xss::filterAdmin($body);
    // First-party marketing links stay on this Drupal site.
    return preg_replace('#https?://(?:www\.)?dyne-tx\.com(?=[/"\s<]|$)#i', '', $body);
  }

}
