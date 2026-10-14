<?php

/**
 * @file
 * Targeted navigation repair. Changes only URLs/targets and preserves entities.
 */

/** Canonicalize first-party page links without inventing missing media files. */
function _dyne_navigation_url(string $value, array $aliases): string {
  static $verified_redirects;
  if ($verified_redirects === NULL) {
    $file = dirname(__DIR__) . '/data/legacy-verified-redirects.json';
    $verified_redirects = is_file($file) ? json_decode(file_get_contents($file), TRUE, 512, JSON_THROW_ON_ERROR)['urls'] : [];
  }
  if (isset($verified_redirects[$value])) {
    return $verified_redirects[$value];
  }
  $media_url = _dyne_navigation_media_url($value);
  if ($media_url !== $value) {
    return $media_url;
  }
  $parts = parse_url($value);
  if ($parts === FALSE) {
    return $value;
  }
  $host = strtolower($parts['host'] ?? '');
  $scheme = strtolower($parts['scheme'] ?? '');
  $path = $parts['path'] ?? '';
  if (in_array($host, ['dyne-tx.com', 'www.dyne-tx.com', 'dynetxdev.wpengine.com'], TRUE) && in_array($scheme, ['', 'http', 'https'], TRUE)) {
    // Missing archive media deliberately retains its real source URL.
    if (str_starts_with($path, '/wp-content/')) {
      return $value;
    }
    return ($path ?: '/') . (isset($parts['query']) ? '?' . $parts['query'] : '') . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
  }
  if ($scheme === '' && $host === '' && $path !== '' && !str_starts_with($path, '/') && isset($aliases[rtrim('/' . $path, '/') ?: '/'])) {
    return '/' . $path . (isset($parts['query']) ? '?' . $parts['query'] : '') . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
  }
  return $value;
}

/** Exact verified media replacements, including URL-encoded source variants. */
function _dyne_navigation_recovered_media(): array {
  static $urls;
  if ($urls === NULL) {
    $urls = [];
    $file = dirname(__DIR__) . '/data/legacy-recovered-media.json';
    $records = is_file($file) ? json_decode(file_get_contents($file), TRUE, 512, JSON_THROW_ON_ERROR)['records'] : [];
    foreach ($records as $record) {
      if (($record['status'] ?? '') !== 'recovered') {
        continue;
      }
      $public = $record['public_path'];
      $local = dirname(__DIR__, 4) . rawurldecode($public);
      if (!str_starts_with($public, '/sites/default/files/legacy/') || str_contains($public, '..') || !is_file($local)
        || filesize($local) !== $record['bytes'] || hash_file('sha256', $local) !== $record['sha256']) {
        throw new RuntimeException('Recovered media is missing or differs from its verified manifest: ' . $public);
      }
      foreach ([$record['source_url'], $record['download_url']] as $source) {
        $urls[rawurldecode(preg_split('/[?#]/', $source, 2)[0])] = $public;
      }
    }
  }
  return $urls;
}

/** Preserve playback query strings and fragments when restoring verified media. */
function _dyne_navigation_media_url(string $value): string {
  $media = _dyne_navigation_recovered_media();
  $parts = preg_split('/([?#].*)/', $value, 2, PREG_SPLIT_DELIM_CAPTURE);
  $key = rawurldecode($parts[0]);
  return isset($media[$key]) ? $media[$key] . ($parts[1] ?? '') : $value;
}

/** Keep internal HTML navigation in the current tab; retain document targets. */
function _dyne_navigation_page(string $value, bool $download = FALSE): bool {
  $parts = parse_url($value);
  return !$download && is_array($parts) && empty($parts['scheme']) && empty($parts['host'])
    && !preg_match('/\.(?:pdf|docx?|xlsx?|pptx?|zip|jpe?g|png|gif|webp|svg|mp4|mp3|webm|wav)$/i', $parts['path'] ?? '');
}

/** Rewrite anchor opening tags without reserializing editorial HTML. */
function _dyne_navigation_html(string $value, array $aliases, array &$counts): string {
  // Restore only exact media URLs backed by the recovery manifest. Other src
  // attributes and unavailable external assets are left untouched.
  $media = _dyne_navigation_recovered_media();
  if ($media) {
    $value = preg_replace_callback('/\b(src|poster|embd-data-url|data-video-url)\s*=\s*("([^"]*)"|\'([^\']*)\')/i', static function (array $match) use ($media, &$counts): string {
      $source = html_entity_decode($match[3] ?: ($match[4] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $replacement = _dyne_navigation_media_url($source);
      if ($replacement === $source) {
        return $match[0];
      }
      $counts['media']++;
      return $match[1] . '="' . htmlspecialchars($replacement, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
    }, $value);
  }
  $cookie = '<a href="https://www.dyne-tx.com/cookie-declaration/">View the current cookie declaration</a>';
  if (str_contains($value, $cookie)) {
    $value = str_replace($cookie, '<a href="/privacy-policy">Read our privacy policy</a>', $value, $replaced);
    $counts['urls'] += $replaced;
  }
  return preg_replace_callback('/<a\b(?:[^\'"<>]|"[^"]*"|\'[^\']*\')*>/i', static function (array $match) use ($aliases, &$counts): string {
    $tag = $match[0];
    $attribute = '/\bhref\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i';
    if (!preg_match($attribute, $tag, $href)) {
      return $tag;
    }
    $old = html_entity_decode($href[1] !== '' ? $href[1] : (($href[2] ?? '') ?: ($href[3] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $new = _dyne_navigation_url($old, $aliases);
    if ($new !== $old) {
      $tag = preg_replace_callback($attribute, static fn(): string => 'href="' . htmlspecialchars($new, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"', $tag, 1);
      $counts['urls']++;
    }
    $download = (bool) preg_match('/\sdownload(?:\s*=|\s|\/?>)/i', $tag);
    $destination = $new;
    if (preg_match('/\bdata-mdb-url\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $tag, $actual)) {
      $source_destination = html_entity_decode($actual[1] ?: ($actual[2] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $destination = _dyne_navigation_url($source_destination, $aliases);
      if ($source_destination !== $destination) {
        $tag = str_replace($actual[0], 'data-mdb-url="' . htmlspecialchars($destination, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"', $tag);
        $counts['urls']++;
      }
    }
    if (_dyne_navigation_page($destination, $download)) {
      $tag = preg_replace('/\s+target\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $tag, -1, $removed);
      $counts['targets'] += $removed;
    }
    return $tag;
  }, $value);
}

/** Normalize URLs inside structured Paragraph JSON without altering other data. */
function _dyne_navigation_value($value, array $aliases, array &$counts, bool $url_context = FALSE) {
  if (is_array($value)) {
    foreach ($value as $key => &$item) {
      $item = _dyne_navigation_value($item, $aliases, $counts, in_array($key, ['href', 'url', 'link'], TRUE) || str_ends_with((string) $key, '_url'));
    }
    return $value;
  }
  if (!is_string($value)) {
    return $value;
  }
  if (str_contains($value, '<')) {
    return _dyne_navigation_html($value, $aliases, $counts);
  }
  $new = $url_context ? _dyne_navigation_url($value, $aliases) : $value;
  if ($new !== $value) {
    $counts['urls']++;
  }
  return $new;
}

/**
 * Update a source node in place, revising referenced Paragraphs with the same IDs.
 *
 * Only navigation tokens are changed. Editor text, images, ordering, fields and
 * configuration are preserved, including changes made after the source import.
 */
function _dyne_navigation_entity($entity, array $aliases, array &$report): bool {
  $changed = FALSE;
  foreach ($entity->getFields() as $name => $field) {
    if (!str_starts_with($name, 'field_')) {
      continue;
    }
    $definition = $field->getFieldDefinition();
    $type = $definition->getType();
    if ($type === 'entity_reference_revisions' && $definition->getSetting('target_type') === 'paragraph') {
      foreach ($field as $item) {
        if ($item->entity && _dyne_navigation_entity($item->entity, $aliases, $report)) {
          $item->target_revision_id = $item->entity->getRevisionId();
          $changed = TRUE;
        }
      }
      continue;
    }
    if (!in_array($type, ['text', 'text_long', 'text_with_summary', 'string', 'string_long', 'link'], TRUE)) {
      continue;
    }
    $values = $field->getValue();
    $old_values = $values;
    foreach ($values as &$item) {
      $key = $type === 'link' ? 'uri' : 'value';
      if (!isset($item[$key]) || !is_string($item[$key])) {
        continue;
      }
      $original = $item[$key];
      if (str_ends_with($name, '_json')) {
        $decoded = json_decode($original, TRUE);
        if (is_array($decoded)) {
          $new = _dyne_navigation_value($decoded, $aliases, $report['links']);
          if ($new !== $decoded) {
            $item[$key] = json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
          }
        }
      }
      else {
        $item[$key] = _dyne_navigation_value($original, $aliases, $report['links'], $type === 'link' || str_ends_with($name, '_url'));
        if ($type === 'link' && $item[$key] !== $original && str_starts_with($item[$key], '/')) {
          $item[$key] = 'internal:' . $item[$key];
        }
      }
    }
    unset($item);
    if ($values !== $old_values) {
      $entity->set($name, $values);
      $changed = TRUE;
      $report['fields'][] = $entity->getEntityTypeId() . ':' . $entity->id() . ':' . $name;
    }
  }
  if ($changed) {
    $entity->setNewRevision(TRUE);
    if (method_exists($entity, 'setRevisionLogMessage')) {
      $entity->setRevisionLogMessage('Repair first-party navigation and restore verified media URLs; preserve all other editorial content.');
    }
    $entity->save();
    $report['entities'][] = $entity->getEntityTypeId() . ':' . $entity->id();
  }
  return $changed;
}
