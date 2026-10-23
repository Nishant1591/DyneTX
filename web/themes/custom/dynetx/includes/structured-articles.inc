<?php

/**
 * @file
 * Render structured article values inside preserved presentation templates.
 */

/** Find each slot's phrasing-content context without interpreting attributes. */
function _dynetx_structured_inline_slots(string $template): array {
  $inline = ['p', 'a', 'span', 'strong', 'em', 'b', 'i', 'u', 's', 'small', 'sub', 'sup', 'code', 'cite', 'mark', 'label', 'button', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
  $void = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
  $stack = []; $slots = [];
  preg_match_all('~<!--dyne:([a-z_]+)(?::([0-9]+|url|text))?-->|<!--[\s\S]*?-->|<(/?)([a-zA-Z][a-zA-Z0-9:-]*)\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>~', $template, $matches, PREG_SET_ORDER);
  foreach ($matches as $match) {
    if (($match[1] ?? '') !== '') {
      $slots[$match[0]][] = $stack && in_array(end($stack), $inline, TRUE);
      continue;
    }
    if (!isset($match[4])) continue;
    $tag = strtolower($match[4]);
    if ($match[3] === '/') {
      for ($i = count($stack) - 1; $i >= 0; $i--) if ($stack[$i] === $tag) { $stack = array_slice($stack, 0, $i); break; }
    }
    elseif (!in_array($tag, $void, TRUE) && !str_ends_with($match[0], '/>')) $stack[] = $tag;
  }
  return $slots;
}

/** Adapt visual-editor blocks to a template's existing inline text context. */
function _dynetx_structured_inline_fragment(string $markup): string {
  $blocks = ['p', 'div', 'section', 'article', 'aside', 'main', 'header', 'footer', 'nav', 'address', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'blockquote', 'pre', 'figure', 'figcaption', 'table', 'caption', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'details', 'summary', 'fieldset', 'legend', 'hr'];
  if (!preg_match('~<(?:' . implode('|', $blocks) . ')\b~i', $markup)) return $markup;
  // This runs after check_markup(). Keep emphasis and attributes, while mapping
  // block boundaries to line breaks inside the existing heading/link/paragraph.
  $document = \Drupal\Component\Utility\Html::load($markup);
  $separator = '<!--dyne:inline-break-->';
  $trim = static fn(string $text): string => preg_replace('~^(?:\s*' . preg_quote($separator, '~') . ')+\s*|(?:\s*' . preg_quote($separator, '~') . ')+\s*$~', '', $text);
  $render = static function (\DOMNode $node) use (&$render, $document, $blocks, $separator, $trim): string {
    if (!$node instanceof \DOMElement) return $document->saveHTML($node);
    $tag = strtolower($node->tagName);
    $inner = '';
    foreach ($node->childNodes as $child) $inner .= $render($child);
    $inner = $trim($inner);
    if (in_array($tag, $blocks, TRUE)) {
      $attributes = '';
      foreach ($node->attributes as $attribute) {
        $attributes .= ' ' . $attribute->name . '="' . htmlspecialchars($attribute->value, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
      }
      if ($attributes !== '') $inner = '<span' . $attributes . '>' . $inner . '</span>';
      return $separator . $inner . $separator;
    }
    if (!$node->hasChildNodes()) return $document->saveHTML($node);
    $shell = $document->saveHTML($node->cloneNode(FALSE));
    return substr($shell, 0, strpos($shell, '>') + 1) . $inner . '</' . $node->tagName . '>';
  };
  $result = '';
  foreach ($document->getElementsByTagName('body')->item(0)->childNodes as $child) $result .= $render($child);
  $result = $trim($result);
  $result = preg_replace('~(?:\s*' . preg_quote($separator, '~') . '\s*)+~', $separator, $result);
  return str_replace($separator, '<br>', $result);
}

/** Assemble editable values recursively and collect every cache dependency. */
function _dynetx_structured_article_markup($entity, \Drupal\Core\Cache\CacheableMetadata $cache): string {
  $cache->addCacheableDependency($entity);
  $field = $entity->hasField('field_content_template') ? 'field_content_template' : 'field_section_template';
  $template = $entity->hasField($field) ? (string) ($entity->get($field)->value ?? '') : '';
  if ($template === '') {
    if ($entity->bundle() === 'dyne_article_section') {
      if ($entity->hasField('field_section_heading') && !$entity->get('field_section_heading')->isEmpty()) $template .= '<h2><!--dyne:field_section_heading--></h2>';
      $template .= '<!--dyne:field_section_body-->';
      if ($entity->hasField('field_section_image') && !$entity->get('field_section_image')->isEmpty()) $template .= '<img src="<!--dyne:field_section_image:url-->" alt="<!--dyne:field_section_image_alt-->" loading="lazy">';
      if ($entity->hasField('field_section_link') && !$entity->get('field_section_link')->isEmpty()) {
        $template .= '<p><a href="<!--dyne:field_section_link:url-->"><!--dyne:field_section_link:text--></a></p>';
      }
    }
    else {
      if ($entity->bundle() === 'perspective' && $entity->hasField('field_story_heading') && $entity->get('field_story_heading')->isEmpty()) {
        $template .= '<h1>' . htmlspecialchars($entity->label(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</h1>';
      }
      foreach (['field_story_mission', 'field_story_heading', 'field_story_date_label', 'field_story_byline', 'field_story_intro', 'field_article_intro', 'field_article_body', 'field_company_overview', 'field_forward_looking', 'field_news_contacts'] as $name) {
        if ($entity->hasField($name) && !$entity->get($name)->isEmpty()) {
          $tag = $name === 'field_story_heading' ? 'h1' : 'div';
          $template .= '<' . $tag . '><!--dyne:' . $name . '--></' . $tag . '>';
        }
      }
      if ($entity->hasField('field_story_portrait') && !$entity->get('field_story_portrait')->isEmpty()) $template .= '<img src="<!--dyne:field_story_portrait:url-->" alt="" loading="lazy">';
    }
  }
  // Initial migration revisions used the text field for a link's caption.
  // Honor a subsequent Link text edit while retaining any original inline
  // formatting until the editor explicitly changes that caption.
  if ($entity->bundle() === 'dyne_article_section' && $entity->hasField('field_section_link') && !$entity->get('field_section_link')->isEmpty() && str_contains($template, '<!--dyne:field_section_link:url-->') && str_contains($template, '<!--dyne:field_section_body-->')) {
    $body = (string) ($entity->get('field_section_body')->value ?? '');
    $title = (string) ($entity->get('field_section_link')->title ?? '');
    if (strip_tags($body) === $body || ($title !== '' && $title !== trim(strip_tags($body)))) $template = str_replace('<!--dyne:field_section_body-->', '<!--dyne:field_section_link:text-->', $template);
  }
  // Optional fields are useful after migration too. If an editor fills one
  // that the old page never had, give it a visible canonical placement.
  $text_fields = $entity->bundle() === 'dyne_article_section'
    ? ['field_section_heading', 'field_section_body']
    : ['field_story_mission', 'field_story_heading', 'field_story_date_label', 'field_story_byline', 'field_story_intro', 'field_article_intro', 'field_article_body', 'field_company_overview', 'field_forward_looking', 'field_news_contacts'];
  foreach ($text_fields as $name) {
    if (!$entity->hasField($name) || $entity->get($name)->isEmpty() || str_contains($template, '<!--dyne:' . $name)) continue;
    if ($name === 'field_section_body' && str_contains($template, '<!--dyne:field_section_link:text-->')) continue;
    $tag = in_array($name, ['field_section_heading', 'field_story_heading'], TRUE) ? ($name === 'field_section_heading' ? 'h2' : 'h1') : 'div';
    $template .= '<' . $tag . '><!--dyne:' . $name . '--></' . $tag . '>';
  }
  foreach (['field_section_image', 'field_story_portrait'] as $name) {
    if ($entity->hasField($name) && !$entity->get($name)->isEmpty() && !str_contains($template, '<!--dyne:' . $name)) $template .= '<img src="<!--dyne:' . $name . ':url-->" alt="' . ($name === 'field_section_image' ? '<!--dyne:field_section_image_alt-->' : '') . '" loading="lazy">';
  }
  if ($entity->bundle() === 'dyne_article_section') {
    if ($entity->hasField('field_section_bg_image') && !$entity->get('field_section_bg_image')->isEmpty() && !str_contains($template, '<!--dyne:field_section_bg_image:url-->')) $template = '<div style="background-image: url(&quot;<!--dyne:field_section_bg_image:url-->&quot;)">' . $template . '</div>';
    $img_pattern = '~<img\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>~i';
    // An originally absent alt attribute stays absent until an editor supplies
    // placement text. Existing empty attributes continue to mean decorative.
    if ($entity->hasField('field_section_image_alt') && !$entity->get('field_section_image_alt')->isEmpty() && !str_contains($template, '<!--dyne:field_section_image_alt-->')) {
      $template = preg_replace_callback($img_pattern, static function ($m): string {
        if (!str_contains($m[0], '<!--dyne:field_section_image:url-->')) return $m[0];
        return preg_replace('~\s*/?>$~', ' alt="<!--dyne:field_section_image_alt-->">', $m[0]);
      }, $template);
    }
    $new_sources = '';
    foreach (['field_section_mobile_image' => '(max-width: 767.98px)', 'field_section_retina_image' => '(min-resolution: 1.5dppx)'] as $name => $media_query) {
      if (!$entity->hasField($name) || $entity->get($name)->isEmpty()) {
        // Clearing a variant restores the fallback image at this breakpoint.
        $template = preg_replace_callback('~<source\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>~i', static fn($m) => str_contains($m[0], '<!--dyne:' . $name . ':url-->') ? '' : $m[0], $template);
        continue;
      }
      if (!str_contains($template, '<!--dyne:' . $name . ':url-->')) $new_sources .= '<source srcset="<!--dyne:' . $name . ':url-->" media="' . $media_query . '">';
    }
    if ($new_sources !== '') {
      $has_picture = str_contains(strtolower($template), '<picture');
      $has_direct_image = str_contains($template, '<!--dyne:field_section_image:url-->');
      $template = preg_replace_callback($img_pattern, static function ($m) use ($new_sources, $has_picture): string {
        if (!str_contains($m[0], '<!--dyne:field_section_image:url-->')) return $m[0];
        return $has_picture ? $new_sources . $m[0] : '<picture>' . $new_sources . $m[0] . '</picture>';
      }, $template);
      if (!$has_direct_image && substr_count(strtolower($template), '<picture') === 1 && preg_match('/<!--dyne:field_article_sections:[0-9]+-->/', $template)) $template = preg_replace('~(<picture\b[^>]*>)~i', '$1' . $new_sources, $template, 1);
    }
  }
  if ($entity->hasField('field_section_link') && !$entity->get('field_section_link')->isEmpty() && !str_contains($template, '<!--dyne:field_section_link')) $template .= '<p><a href="<!--dyne:field_section_link:url-->"><!--dyne:field_section_link:text--></a></p>';
  preg_match_all('/<!--dyne:field_article_sections:([0-9]+)-->/', $template, $used);
  // Existing slot numbers follow current field order. Additional editor-created
  // sections render too, and a removed reference simply empties its old slot.
  if ($entity->hasField('field_article_sections')) foreach ($entity->get('field_article_sections') as $delta => $item) {
    if (!in_array((string) $delta, $used[1], TRUE)) $template .= '<!--dyne:field_article_sections:' . $delta . '-->';
  }
  $inline_slots = _dynetx_structured_inline_slots($template);
  return preg_replace_callback('/<!--dyne:([a-z_]+)(?::([0-9]+|url|text))?-->/', static function ($match) use ($entity, $cache, &$inline_slots): string {
    $inline = isset($inline_slots[$match[0]]) ? array_shift($inline_slots[$match[0]]) : FALSE;
    $name = $match[1]; $suffix = $match[2] ?? '';
    if (!$entity->hasField($name)) return '';
    $list = $entity->get($name);
    if ($list->isEmpty()) return '';
    $access = $list->access('view', NULL, TRUE);
    $cache->addCacheableDependency($access);
    if (!$access->isAllowed()) return '';
    if ($name === 'field_article_sections') {
      $item = $list->get((int) $suffix);
      return $item && $item->entity ? _dynetx_structured_article_markup($item->entity, $cache) : '';
    }
    $item = $list->first();
    if ($suffix === 'text' && $list->getFieldDefinition()->getType() === 'link') {
      if ($item->title !== NULL && $item->title !== '') return htmlspecialchars($item->title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      if ($entity->hasField('field_section_body') && !$entity->get('field_section_body')->isEmpty()) {
        $body = $entity->get('field_section_body')->first();
        $body_access = $entity->get('field_section_body')->access('view', NULL, TRUE);
        $cache->addCacheableDependency($body_access);
        if (!$body_access->isAllowed()) return '';
        $format = $body->format ?: 'dyne_legacy_html';
        if ($configuration = \Drupal\filter\Entity\FilterFormat::load($format)) $cache->addCacheableDependency($configuration);
        return _dynetx_structured_inline_fragment((string) check_markup($body->value, $format, $entity->language()->getId()));
      }
      return htmlspecialchars((string) t('Read more'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if ($suffix === 'url') {
      if ($list->getFieldDefinition()->getType() === 'link') {
        try { return htmlspecialchars($item->getUrl()->toString(), ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
        catch (\Throwable $error) { return ''; }
      }
      if ($media = $item->entity) {
        $cache->addCacheableDependency($media);
        $source = $media->getSource()->getConfiguration()['source_field'] ?? '';
        if ($source && ($file = $media->get($source)->entity)) $cache->addCacheableDependency($file);
      }
      return htmlspecialchars(_dynetx_native_media_url($entity, $name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $value = (string) ($item->value ?? '');
    if (in_array($list->getFieldDefinition()->getType(), ['text', 'text_long', 'text_with_summary'], TRUE)) {
      $format = $item->format ?: 'dyne_legacy_html';
      if ($configuration = \Drupal\filter\Entity\FilterFormat::load($format)) $cache->addCacheableDependency($configuration);
      $markup = (string) check_markup($value, $format, $entity->language()->getId());
      return $inline ? _dynetx_structured_inline_fragment($markup) : $markup;
    }
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  }, $template);
}

/** A drop-in replacement for the original body render array. */
function _dynetx_structured_article_body($entity): array {
  if (!$entity->hasField('field_structured_content') || !$entity->get('field_structured_content')->value) return [];
  $cache = \Drupal\Core\Cache\CacheableMetadata::createFromObject($entity);
  $cache->addCacheContexts(['user.permissions', 'languages:language_content']);
  $markup = _dynetx_structured_article_markup($entity, $cache);
  $format = $entity->hasField('field_body_text') && !$entity->get('field_body_text')->isEmpty() ? ($entity->get('field_body_text')->format ?: 'dyne_legacy_html') : 'dyne_legacy_html';
  $build = ['#type' => 'processed_text', '#text' => $markup, '#format' => $format, '#langcode' => $entity->language()->getId()];
  $cache->applyTo($build);
  return $build;
}
