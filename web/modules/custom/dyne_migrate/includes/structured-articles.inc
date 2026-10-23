<?php

/**
 * @file
 * Lossless, additive extraction of editorial values from legacy article layouts.
 *
 * Plans are pure arrays. Backfill creates referenced Paragraphs but leaves the
 * parent save/revision to its caller. Original body fields are never replaced.
 */

/** Return element offsets without serializing or normalizing source HTML. */
function dyne_structured_html_tree(string $html): array {
  $nodes = [['tag' => '#root', 'start' => 0, 'open_end' => 0, 'close_start' => strlen($html), 'end' => strlen($html), 'children' => [], 'parent' => NULL, 'open' => '']];
  $stack = [0];
  preg_match_all('~<!--[\s\S]*?-->|<![^>]*>|</?[a-zA-Z][^>"\x27]*(?:(?:"[^"]*"|\x27[^\x27]*\x27)[^>"\x27]*)*>~', $html, $matches, PREG_OFFSET_CAPTURE);
  $void = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
  foreach ($matches[0] as [$token, $offset]) {
    if (!preg_match('~^<(/?)([a-zA-Z][a-zA-Z0-9:-]*)\b~', $token, $parts)) continue;
    $tag = strtolower($parts[2]);
    if ($parts[1] === '/') {
      for ($i = count($stack) - 1; $i > 0; $i--) {
        $id = $stack[$i];
        if ($nodes[$id]['tag'] !== $tag) continue;
        // An implicitly closed/malformed descendant remains opaque.
        for ($j = count($stack) - 1; $j > $i; $j--) $nodes[$stack[$j]]['invalid'] = TRUE;
        $nodes[$id]['close_start'] = $offset;
        $nodes[$id]['end'] = $offset + strlen($token);
        $stack = array_slice($stack, 0, $i);
        break;
      }
      continue;
    }
    $parent = end($stack); $id = count($nodes);
    $nodes[] = ['tag' => $tag, 'start' => $offset, 'open_end' => $offset + strlen($token), 'close_start' => $offset + strlen($token), 'end' => $offset + strlen($token), 'children' => [], 'parent' => $parent, 'open' => $token];
    $nodes[$parent]['children'][] = $id;
    if (!in_array($tag, $void, TRUE) && !str_ends_with($token, '/>')) $stack[] = $id;
  }
  foreach (array_slice($stack, 1) as $id) $nodes[$id]['invalid'] = TRUE;
  return $nodes;
}

/** Attribute values are decoded for comparisons; originals remain in templates. */
function dyne_structured_html_attribute(string $open, string $name): string {
  if (preg_match('~\b' . preg_quote($name, '~') . '\s*=\s*(?:"([^"]*)"|\x27([^\x27]*)\x27|([^\s>]+))~i', $open, $m)) {
    $value = $m[1] ?? '';
    if ($value === '') $value = ($m[2] ?? '') ?: ($m[3] ?? '');
    return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }
  return '';
}

function dyne_structured_html_class(array $node, string $class): bool {
  return in_array($class, preg_split('/\s+/', dyne_structured_html_attribute($node['open'], 'class')), TRUE);
}

/** Apply disjoint replacements in descending order, keeping every other byte. */
function dyne_structured_html_replace(string $html, array $replacements): string {
  usort($replacements, static fn($a, $b) => $b[0] <=> $a[0]);
  $boundary = strlen($html) + 1;
  foreach ($replacements as [$start, $end, $value]) {
    if ($start < 0 || $end < $start || $end > $boundary) throw new RuntimeException('Overlapping structured content extraction.');
    $html = substr_replace($html, $value, $start, $end - $start); $boundary = $start;
  }
  return $html;
}

/** Parse a layout recursively into headings, prose groups, images and links. */
function dyne_structured_segment_html(string $html): array {
  $nodes = dyne_structured_html_tree($html);
  $flow = ['p', 'ul', 'ol', 'blockquote', 'table', 'dl', 'address'];
  $inline = ['a', 'abbr', 'b', 'br', 'cite', 'code', 'em', 'i', 'mark', 'small', 'span', 'strong', 'sub', 'sup', 'u'];
  $walk = function (int $parent) use (&$walk, $nodes, $html, $flow, $inline): array {
    $container = $nodes[$parent]; $replacements = []; $sections = [];
    $children = $container['children'];
    for ($i = 0; $i < count($children); $i++) {
      $node = $nodes[$children[$i]];
      if (!empty($node['invalid']) || in_array($node['tag'], ['script', 'style', 'noscript'], TRUE)) continue;
      $start = $node['start']; $end = $node['end'];
      $heading = preg_match('/^h[1-6]$/', $node['tag']);
      $contains_media = preg_match('~<(?:img|iframe|video)\b~i', substr($html, $node['open_end'], $node['close_start'] - $node['open_end']));
      if ($heading || (in_array($node['tag'], $flow, TRUE) && !$contains_media)) {
        $spec = ['fields' => [], 'template' => '', 'sections' => []];
        if ($heading) {
          $spec['fields']['field_section_heading'] = substr($html, $node['open_end'], $node['close_start'] - $node['open_end']);
          $spec['template'] = $node['open'] . '<!--dyne:field_section_heading-->' . substr($html, $node['close_start'], $node['end'] - $node['close_start']);
          $body_start = $end;
        }
        else $body_start = $start;
        // Each heading owns the following adjacent prose, not a page layout.
        while (isset($children[$i + 1])) {
          $next = $nodes[$children[$i + 1]];
          if (!empty($next['invalid']) || !in_array($next['tag'], $flow, TRUE) || preg_match('~<(?:img|iframe|video)\b~i', substr($html, $next['open_end'], $next['close_start'] - $next['open_end'])) || trim(substr($html, $end, $next['start'] - $end)) !== '') break;
          $end = $next['end']; $i++;
        }
        $body = substr($html, $body_start, $end - $body_start);
        if (trim($body) !== '') {
          $spec['fields']['field_section_body'] = $body;
          $spec['template'] .= '<!--dyne:field_section_body-->';
        }
      }
      elseif ($node['tag'] === 'img') {
        $url = dyne_structured_html_attribute($node['open'], 'src');
        if ($url === '') continue;
        $spec = ['fields' => [], 'template' => $node['open'], 'sections' => [], 'image_url' => $url];
        $spec['template'] = preg_replace('~(\bsrc\s*=\s*)("[^"]*"|\x27[^\x27]*\x27|[^\s>]+)~i', '$1"<!--dyne:field_section_image:url-->"', $spec['template'], 1);
      }
      elseif (in_array($node['tag'], ['iframe', 'video'], TRUE) && ($url = dyne_structured_html_attribute($node['open'], 'src')) !== '') {
        $spec = ['fields' => ['field_section_link' => ['uri' => $url, 'title' => dyne_structured_html_attribute($node['open'], 'title')]], 'template' => substr($html, $start, $end - $start), 'sections' => []];
        $spec['template'] = preg_replace('~(\bsrc\s*=\s*)("[^"]*"|\x27[^\x27]*\x27|[^\s>]+)~i', '$1"<!--dyne:field_section_link:url-->"', $spec['template'], 1);
      }
      else {
        // A text-only/inline container is one editable text value. Structural
        // containers recurse so columns never become one giant text field.
        $only_inline = TRUE;
        foreach ($node['children'] as $child) if (!in_array($nodes[$child]['tag'], $inline, TRUE)) $only_inline = FALSE;
        $inner = substr($html, $node['open_end'], $node['close_start'] - $node['open_end']);
        if ($node['tag'] === 'a' && ($url = dyne_structured_html_attribute($node['open'], 'href')) !== '') {
          $plain = strip_tags($inner) === $inner;
          $spec = ['fields' => ['field_section_link' => ['uri' => $url, 'title' => $plain ? html_entity_decode($inner, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '']], 'template' => '', 'sections' => []];
          $open = preg_replace('~(\bhref\s*=\s*)("[^"]*"|\x27[^\x27]*\x27|[^\s>]+)~i', '$1"<!--dyne:field_section_link:url-->"', $node['open'], 1);
          if (!$plain) $spec['fields']['field_section_body'] = $inner;
          $spec['template'] = $open . '<!--dyne:field_section_link:text-->' . substr($html, $node['close_start'], $node['end'] - $node['close_start']);
        }
        elseif ($only_inline && trim(strip_tags($inner)) !== '') {
          $spec = ['fields' => ['field_section_body' => $inner], 'template' => $node['open'] . '<!--dyne:field_section_body-->' . substr($html, $node['close_start'], $node['end'] - $node['close_start']), 'sections' => []];
        }
        else {
          $nested = $walk($children[$i]);
          if (!$nested['sections']) continue;
          $spec = ['fields' => [], 'sections' => $nested['sections'], 'template' => $node['open'] . $nested['template'] . substr($html, $node['close_start'], $node['end'] - $node['close_start'])];
        }
      }
      // A solitary structural wrapper has no editorial meaning. Fold its
      // scaffold around its one real child instead of nesting empty widgets.
      while (!$spec['fields'] && empty($spec['image_url']) && count($spec['sections']) === 1) {
        $child = $spec['sections'][0];
        $child['template'] = str_replace('<!--dyne:field_article_sections:0-->', $child['template'], $spec['template']);
        $spec = $child;
      }
      $placements = dyne_structured_image_template_plan($spec['template']);
      $spec['template'] = $placements['template'];
      $spec['fields'] += $placements['fields'];
      if ($placements['variant_urls']) $spec['variant_urls'] = ($spec['variant_urls'] ?? []) + $placements['variant_urls'];
      $delta = count($sections); $sections[] = $spec;
      $replacements[] = [$start - $container['open_end'], $end - $container['open_end'], '<!--dyne:field_article_sections:' . $delta . '-->'];
    }
    // Legacy snippets sometimes use unwrapped prose between block elements.
    // Expose that text too, retaining surrounding indentation in the template.
    $cursor = $container['open_end'];
    foreach (array_merge($children, [NULL]) as $child) {
      $next_start = $child === NULL ? $container['close_start'] : $nodes[$child]['start'];
      $raw = substr($html, $cursor, max(0, $next_start - $cursor));
      if (trim(strip_tags($raw)) !== '') {
        $delta = count($sections);
        $sections[] = ['fields' => ['field_section_body' => $raw], 'template' => '<!--dyne:field_section_body-->', 'sections' => []];
        $replacements[] = [$cursor - $container['open_end'], $next_start - $container['open_end'], '<!--dyne:field_article_sections:' . $delta . '-->'];
      }
      if ($child !== NULL) $cursor = $nodes[$child]['end'];
    }
    // Keep editing order identical to the source even for unwrapped prose.
    usort($replacements, static fn($a, $b) => $a[0] <=> $b[0]);
    $ordered = [];
    foreach ($replacements as &$replacement) {
      preg_match('/:([0-9]+)-->$/', $replacement[2], $m);
      $ordered[] = $sections[(int) $m[1]];
      $replacement[2] = '<!--dyne:field_article_sections:' . (count($ordered) - 1) . '-->';
    }
    unset($replacement);
    $sections = $ordered;
    $inner = substr($html, $container['open_end'], $container['close_start'] - $container['open_end']);
    return ['template' => dyne_structured_html_replace($inner, $replacements), 'sections' => $sections];
  };
  return $walk(0);
}

/** Pure semantic extraction, usable by fixture tests without Drupal bootstrap. */
function dyne_structured_article_plan(string $bundle, string $html): array {
  $plan = ['template' => $html, 'fields' => [], 'sections' => []];
  if ($html === '' || str_contains($html, '<!--dyne:')) return $plan;
  if (in_array($bundle, ['dyne_rich_text', 'dyne_careers_quotes'], TRUE)) return dyne_structured_segment_html($html) + ['fields' => []];
  $nodes = dyne_structured_html_tree($html);
  if ($bundle === 'news') {
    $parts = [];
    foreach ($nodes as $node) {
      if (!empty($node['invalid']) || !in_array($node['tag'], ['p', 'h2', 'h3', 'h4', 'strong', 'b'], TRUE)) continue;
      // Prefer the surrounding paragraph to its nested strong element.
      if (in_array($node['tag'], ['strong', 'b'], TRUE) && ($nodes[$node['parent']]['tag'] ?? '') === 'p') continue;
      $inner = substr($html, $node['open_end'], $node['close_start'] - $node['open_end']);
      if ($node['tag'] === 'p' && !preg_match('~^\s*<(?:strong|b)\b[^>]*>[\s\S]*</(?:strong|b)>\s*$~', $inner)) continue;
      $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
      $field = preg_match('/^About Dyne Therapeutics\s*[:.]?$/iu', $text) ? 'field_company_overview' : (preg_match('/^Forward[\s–-]*Looking Statements\s*[:.]?$/iu', $text) ? 'field_forward_looking' : (preg_match('/^(?:Contacts?|Investor(?:s)? Contact|Media Contact)\s*:?(?:\s*Investors)?$/iu', $text) ? 'field_news_contacts' : ''));
      if ($field) $parts[] = ['start' => $node['start'], 'field' => $field];
    }
    usort($parts, static fn($a, $b) => $a['start'] <=> $b['start']);
    // Multiple contact subheadings belong to the single contacts section.
    $filtered = [];
    foreach ($parts as $part) {
      if ($filtered && end($filtered)['field'] === $part['field']) continue;
      if (in_array($part['field'], array_column($filtered, 'field'), TRUE)) return $plan;
      $filtered[] = $part;
    }
    if (!$filtered) return ['template' => '<!--dyne:field_article_body-->', 'fields' => ['field_article_body' => $html], 'sections' => []];
    $plan['fields']['field_article_body'] = substr($html, 0, $filtered[0]['start']);
    $plan['template'] = '<!--dyne:field_article_body-->';
    foreach ($filtered as $i => $part) {
      $end = $filtered[$i + 1]['start'] ?? strlen($html);
      $plan['fields'][$part['field']] = substr($html, $part['start'], $end - $part['start']);
      $plan['template'] .= '<!--dyne:' . $part['field'] . '-->';
    }
    return $plan;
  }
  if ($bundle === 'privacy_policy') {
    $segments = dyne_structured_segment_html($html);
    return $segments + ['fields' => []];
  }
  if ($bundle !== 'perspective') return $plan;
  $replacements = [];
  foreach ($nodes as $id => $node) {
    if (!empty($node['invalid'])) continue;
    $field = '';
    if (dyne_structured_html_class($node, 'hero-subtitle-xs')) $field = 'field_story_mission';
    elseif (dyne_structured_html_class($node, 'pub-date')) $field = 'field_story_date_label';
    elseif (dyne_structured_html_class($node, 'pub-announce')) $field = 'field_story_heading';
    elseif ($node['tag'] === 'p') {
      $parent = $node['parent']; $in_header = FALSE;
      while ($parent) { if (dyne_structured_html_class($nodes[$parent], 'article-head')) $in_header = TRUE; $parent = $nodes[$parent]['parent']; }
      if ($in_header) {
        $text = trim(strip_tags(substr($html, $node['open_end'], $node['close_start'] - $node['open_end'])));
        $field = preg_match('/^By\s/i', $text) ? 'field_story_byline' : 'field_story_intro';
      }
    }
    if ($field && !array_key_exists($field, $plan['fields'])) {
      $inner = substr($html, $node['open_end'], $node['close_start'] - $node['open_end']);
      $plan['fields'][$field] = $inner;
      $replacements[] = [$node['open_end'], $node['close_start'], '<!--dyne:' . $field . '-->'];
    }
    if (dyne_structured_html_class($node, 'article-body')) {
      $inner = substr($html, $node['open_end'], $node['close_start'] - $node['open_end']);
      $segments = dyne_structured_segment_html($inner);
      $plan['sections'] = $segments['sections'];
      $replacements[] = [$node['open_end'], $node['close_start'], $segments['template']];
    }
    if (dyne_structured_html_class($node, 'autor-pic')) {
      $style = dyne_structured_html_attribute($node['open'], 'style');
      if (preg_match('~background-image\s*:\s*url\([\x27"]?([^\x27")]+)[\x27"]?\)~i', $style, $m)) {
        $plan['portrait_url'] = $m[1];
        // Preserve encoding/quotes around the URL; replace just that attribute
        // occurrence after verifying a managed file exists during backfill.
        $encoded = htmlspecialchars($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $original = $node['open'];
        $updated = str_replace([$m[1], $encoded], '<!--dyne:field_story_portrait:url-->', $original);
        if ($updated !== $original) $plan['portrait_replacement'] = [$node['start'], $node['open_end'], $updated];
      }
    }
  }
  if (!$replacements) {
    // External perspectives are small generated cards. Their actual heading,
    // byline and link are still content, so expose them through native sections.
    return dyne_structured_segment_html($html) + ['fields' => []];
  }
  $plan['replacements'] = $replacements;
  $plan['template'] = dyne_structured_html_replace($html, $replacements);
  return $plan;
}

/** Materialize a pure section plan; retain unavailable image markup unchanged. */
/** Store relative destinations in Drupal's native internal-link URI scheme. */
function dyne_structured_article_uri(string $uri): string {
  // Protocol-relative destinations keep their original host.
  if (str_starts_with($uri, '//')) return 'https:' . $uri;
  if ($uri !== '' && in_array($uri[0], ['/', '#', '?'], TRUE)) return 'internal:' . $uri;
  return $uri;
}

function dyne_structured_article_section(array $spec, string $format): \Drupal\paragraphs\Entity\Paragraph {
  $values = ['type' => 'dyne_article_section', 'field_structured_content' => 1];
  $template = $spec['template'];
  foreach ($spec['fields'] as $field => $value) {
    if ($field === 'field_section_link' && is_array($value)) $value['uri'] = dyne_structured_article_uri((string) ($value['uri'] ?? ''));
    $values[$field] = is_array($value) || $field === 'field_section_image_alt' ? $value : ['value' => $value, 'format' => $format];
  }
  if (!empty($spec['image_url'])) {
    $media = dyne_native_media_for_url($spec['image_url']);
    if ($media && $media->bundle() === 'image') $values['field_section_image'] = ['target_id' => $media->id()];
    else $template = str_replace('<!--dyne:field_section_image:url-->', htmlspecialchars($spec['image_url'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), $template);
  }
  foreach ($spec['variant_urls'] ?? [] as $field => $url) {
    $media = dyne_native_media_for_url($url);
    if ($media && $media->bundle() === 'image') $values[$field] = ['target_id' => $media->id()];
    else $template = str_replace('<!--dyne:' . $field . ':url-->', htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $template);
  }
  $refs = [];
  foreach ($spec['sections'] as $child) {
    $paragraph = dyne_structured_article_section($child, $format);
    $refs[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
  }
  if ($refs) $values['field_article_sections'] = $refs;
  $values['field_content_template'] = $template;
  $paragraph = \Drupal\paragraphs\Entity\Paragraph::create($values);
  $paragraph->save();
  return $paragraph;
}

/** Pure extraction of placement alt and the verified simple picture variants. */
function dyne_structured_image_template_plan(string $template): array {
  $plan = ['template' => $template, 'fields' => [], 'variant_urls' => [], 'ambiguous_backgrounds' => []];
  $tag_pattern = '~<(?:img|source)\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>~i';
  $plan['template'] = preg_replace_callback($tag_pattern, static function ($match) use (&$plan, $template): string {
    $tag = $match[0];
    if (preg_match('/^<img\b/i', $tag)) {
      if (!str_contains($tag, '<!--dyne:field_section_image:url-->')) return $tag;
      if (preg_match('~(?<![-:\w])alt\s*=\s*(?:"[^"]*"|\x27[^\x27]*\x27|[^\s>]+)~i', $tag) && !str_contains($tag, '<!--dyne:field_section_image_alt-->')) {
        $plan['fields']['field_section_image_alt'] = dyne_structured_html_attribute($tag, 'alt');
        return preg_replace('~((?<![-:\w])alt\s*=\s*)(?:"[^"]*"|\x27[^\x27]*\x27|[^\s>]+)~i', '$1"<!--dyne:field_section_image_alt-->"', $tag, 1);
      }
      return $tag;
    }
    // Only these source fixtures use a single URL and a recognized art-direction
    // query. Unknown source lists stay unchanged rather than losing candidates.
    $direct_image = str_contains($template, '<!--dyne:field_section_image:url-->');
    $nested_image = substr_count(strtolower($template), '<picture') === 1 && preg_match('/<!--dyne:field_article_sections:[0-9]+-->/', $template);
    if (!$direct_image && !$nested_image) return $tag;
    $url = dyne_structured_html_attribute($tag, 'srcset');
    $media = dyne_structured_html_attribute($tag, 'media');
    if ($url === '' || preg_match('/\s|,|<!--dyne:/', $url)) return $tag;
    $field = str_contains($media, 'max-width') ? 'field_section_mobile_image' : (str_contains($media, 'min-resolution') ? 'field_section_retina_image' : '');
    if ($field === '' || isset($plan['variant_urls'][$field])) return $tag;
    $plan['variant_urls'][$field] = $url;
    return preg_replace('~((?<![-:\w])srcset\s*=\s*)(?:"[^"]*"|\x27[^\x27]*\x27|[^\s>]+)~i', '$1"<!--dyne:' . $field . ':url-->"', $tag, 1);
  }, $template);
  $backgrounds = [];
  preg_match_all('~<[a-zA-Z][a-zA-Z0-9:-]*\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>~', $plan['template'], $tags);
  foreach ($tags[0] as $tag) {
    $style = dyne_structured_html_attribute($tag, 'style');
    if (!preg_match('~background-image\s*:\s*url\([\x27"]?([^\x27")]+)[\x27"]?\)~i', $style, $m)) continue;
    $url = trim($m[1]);
    if (str_starts_with($url, '/themes/custom/dynetx/images/') || str_starts_with($url, '/sites/default/files/legacy/')) $backgrounds[] = ['tag' => $tag, 'url' => $url];
  }
  $urls = array_unique(array_column($backgrounds, 'url'));
  if (count($urls) > 1 || ($urls && str_contains($plan['template'], '<!--dyne:field_section_bg_image:url-->'))) {
    $plan['ambiguous_backgrounds'] = array_values($urls);
    return $plan;
  }
  if ($urls) {
    $plan['variant_urls']['field_section_bg_image'] = reset($urls);
    foreach ($backgrounds as $background) {
      $updated = preg_replace_callback('~((?<![-:\w])style\s*=\s*)("[^"]*"|\x27[^\x27]*\x27)~i', static function ($m) use ($background): string {
        $value = str_replace([$background['url'], htmlspecialchars($background['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8')], '<!--dyne:field_section_bg_image:url-->', $m[2]);
        return $m[1] . $value;
      }, $background['tag']);
      $plan['template'] = str_replace($background['tag'], $updated, $plan['template']);
    }
  }
  return $plan;
}

/** Upgrade image placement attributes only; callers save/revise the parent. */
function dyne_structured_image_placements($entity): bool {
  if ($entity->bundle() !== 'dyne_article_section' || !$entity->hasField('field_content_template')) return FALSE;
  $original = (string) $entity->get('field_content_template')->value;
  $plan = dyne_structured_image_template_plan($original);
  $template = $plan['template']; $changed = FALSE;
  foreach ($plan['fields'] as $field => $value) {
    if (!$entity->hasField($field)) throw new RuntimeException('Image placement schema missing ' . $field);
    $entity->set($field, $value); $changed = TRUE;
  }
  foreach ($plan['variant_urls'] as $field => $url) {
    $media = dyne_native_media_for_url($url);
    if ($media && $media->bundle() === 'image') {
      $entity->set($field, ['target_id' => $media->id()]); $changed = TRUE;
    }
    else $template = str_replace('<!--dyne:' . $field . ':url-->', htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $template);
  }
  if ($template !== $original) { $entity->set('field_content_template', $template); $changed = TRUE; }
  return $changed;
}

/** Populate modeled values once; callers own the parent revision and save. */
function dyne_structured_article_backfill($entity): bool {
  if (!$entity->hasField('field_structured_content') || $entity->get('field_structured_content')->value || !$entity->hasField('field_body_text') || $entity->get('field_body_text')->isEmpty()) return FALSE;
  $body = $entity->get('field_body_text')->first();
  $plan = dyne_structured_article_plan($entity->bundle(), (string) $body->value);
  if (!$plan['fields'] && !$plan['sections']) return FALSE;
  $format = $body->format ?: 'dyne_legacy_html';
  foreach ($plan['fields'] as $field => $value) {
    if (!$entity->hasField($field)) throw new RuntimeException('Structured article schema missing ' . $entity->bundle() . '.' . $field);
    $entity->set($field, $field === 'field_story_date_label' ? $value : ['value' => $value, 'format' => $format]);
  }
  if (!empty($plan['portrait_url']) && ($media = dyne_native_media_for_url($plan['portrait_url'])) && $media->bundle() === 'image' && !empty($plan['portrait_replacement'])) {
    $entity->set('field_story_portrait', ['target_id' => $media->id()]);
    $plan['template'] = dyne_structured_html_replace((string) $body->value, array_merge($plan['replacements'], [$plan['portrait_replacement']]));
  }
  $refs = [];
  foreach ($plan['sections'] as $section) {
    $paragraph = dyne_structured_article_section($section, $format);
    $refs[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
  }
  if ($refs) $entity->set('field_article_sections', $refs);
  $entity->set('field_content_template', $plan['template']);
  $entity->set('field_structured_content', 1);
  return TRUE;
}
