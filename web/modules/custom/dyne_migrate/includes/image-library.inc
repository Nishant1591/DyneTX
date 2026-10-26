<?php

/**
 * @file
 * Reusable Media controls for the remaining rendered File-image placements.
 *
 * Original File fields remain stored for historical revisions and imports.
 * The Media image source upload widget is intentionally unchanged.
 */

/** One native picker per rendered image placement. */
function dyne_image_library_fields(): array {
  return [
    'dyne_alert_banner' => ['legacy' => 'field_banner_logo', 'media' => 'field_banner_logo_media', 'label' => 'Banner logo', 'alt' => 'field_banner_logo_alt'],
    'dyne_cta' => ['legacy' => 'field_bg', 'media' => 'field_bg_media', 'label' => 'Image'],
    'dyne_video' => ['legacy' => 'field_poster', 'media' => 'field_poster_media', 'label' => 'Video poster'],
  ];
}

/** Add image-only Media pickers without converting existing field storage. */
function dyne_image_library_schema(): void {
  require_once __DIR__ . '/native-components.inc';
  if (!\Drupal\media\Entity\MediaType::load('image')) {
    throw new \RuntimeException('The Image Media type must exist before configuring the image library controls.');
  }
  foreach (dyne_image_library_fields() as $bundle => $definition) {
    if (!\Drupal\paragraphs\Entity\ParagraphsType::load($bundle)) continue;
    $legacy_field = \Drupal\field\Entity\FieldConfig::loadByName('paragraph', $bundle, $definition['legacy']);
    $translatable = $legacy_field ? $legacy_field->isTranslatable() : FALSE;
    $form = \Drupal\Core\Entity\Entity\EntityFormDisplay::load("paragraph.$bundle.default");
    $old_widget = $form ? $form->getComponent($definition['legacy']) : NULL;
    $existing_widget = $form ? $form->getComponent($definition['media']) : NULL;
    $weight = $old_widget['weight'] ?? $existing_widget['weight'] ?? 0;
    dyne_component_field('paragraph', $bundle, $definition['media'], 'entity_reference', $definition['label'], ['handler' => 'default:media', 'handler_settings' => ['target_bundles' => ['image' => 'image']]], ['target_type' => 'media']);
    $media_field = \Drupal\field\Entity\FieldConfig::loadByName('paragraph', $bundle, $definition['media']);
    $media_field->setTranslatable($translatable)->setDescription('Choose an existing image from the library or upload a new image.');
    if ($bundle === 'dyne_cta') $media_field->setDefaultValueCallback('dyne_image_library_cta_default_value');
    $media_field->save();
    dyne_component_field('paragraph', $bundle, 'field_media_images_ready', 'boolean', 'Uses Media image placements');
    \Drupal\field\Entity\FieldConfig::loadByName('paragraph', $bundle, 'field_media_images_ready')->setTranslatable($translatable)->save();
    $form = \Drupal\Core\Entity\Entity\EntityFormDisplay::load("paragraph.$bundle.default");
    $form->setComponent($definition['media'], ['type' => 'media_library_widget', 'weight' => $weight, 'settings' => ['media_types' => ['image']]]);
    if (isset($definition['alt'])) {
      dyne_component_field('paragraph', $bundle, $definition['alt'], 'string', 'Banner logo alternative text', [], ['max_length' => 255]);
      $alt_field = \Drupal\field\Entity\FieldConfig::loadByName('paragraph', $bundle, $definition['alt']);
      $alt_field->setTranslatable($translatable)->setDescription('Alternative text for this logo placement. Leave empty when the image is decorative.')->save();
      // Reload after the shared helper updates the same form configuration.
      $form = \Drupal\Core\Entity\Entity\EntityFormDisplay::load("paragraph.$bundle.default");
      $form->setComponent($definition['media'], ['type' => 'media_library_widget', 'weight' => $weight, 'settings' => ['media_types' => ['image']]]);
      // Make room once for the new placement control, retaining other order.
      if ($old_widget) {
        foreach ($form->getComponents() as $name => $component) {
          if (!in_array($name, [$definition['legacy'], $definition['media'], $definition['alt'], 'field_media_images_ready'], TRUE) && ($component['weight'] ?? 0) > $weight) {
            $component['weight'] = ($component['weight'] ?? 0) + 1;
            $form->setComponent($name, $component);
          }
        }
      }
      $form->setComponent($definition['alt'], ['type' => 'string_textfield', 'weight' => $weight + 1, 'settings' => ['size' => 60, 'placeholder' => '']]);
    }
    $form->removeComponent($definition['legacy'])->removeComponent('field_media_images_ready')->save();
  }
  // These unused archival controls have no current public placement.
  foreach (['node.dyne_page.default' => 'field_image', 'paragraph.dyne_hero.default' => 'field_banner_logo'] as $id => $name) {
    if ($form = \Drupal\Core\Entity\Entity\EntityFormDisplay::load($id)) $form->removeComponent($name)->save();
  }
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
}

/** Resolve a File to one Image Media; never fetch URLs or replace file bytes. */
function dyne_image_library_media_for_file(int $fid, bool $create = TRUE): ?\Drupal\media\MediaInterface {
  if (!$fid || !($type = \Drupal\media\Entity\MediaType::load('image'))) return NULL;
  $source = $type->getSource();
  if ($source->getPluginId() !== 'image') return NULL;
  $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
  if (!$file) return NULL;
  $uri = $file->getFileUri();
  // This automatic bridge is limited to public, local raster images. Private
  // or temporary assets require an explicit editorial Media workflow.
  if (!str_starts_with($uri, 'public://') || !is_file($uri) || !in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'], TRUE) || !\Drupal::service('image.factory')->get($uri)->isValid()) return NULL;
  $source_name = $source->getSourceFieldDefinition($type)->getName();
  $storage = \Drupal::entityTypeManager()->getStorage('media');
  $ids = $storage->getQuery()->accessCheck(FALSE)->condition('bundle', 'image')->condition($source_name . '.target_id', $fid)->sort('mid')->range(0, 1)->execute();
  if ($ids) return $storage->load(reset($ids));
  if (!$create) return NULL;
  $media = \Drupal\media\Entity\Media::create([
    'bundle' => 'image',
    'name' => mb_substr($file->getFilename(), 0, 255),
    'uid' => $file->getOwnerId(),
    'status' => 1,
    // Placement alternative text belongs to the Paragraph, not shared Media.
    $source_name => ['target_id' => $fid, 'alt' => ''],
  ]);
  $media->save();
  return $media;
}

/** Look up the existing culture asset; create it only during a real save. */
function dyne_image_library_cta_media(bool $create = FALSE): ?\Drupal\media\MediaInterface {
  if (!$create) {
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => 'public://legacy/theme-assets/home-culture.webp']);
    return $files ? dyne_image_library_media_for_file((int) reset($files)->id(), FALSE) : NULL;
  }
  require_once __DIR__ . '/native-content.inc';
  return dyne_native_media_for_url('/themes/custom/dynetx/images/home-culture.webp');
}

/** Populate a default only if already managed; rendering never creates Media. */
function dyne_image_library_cta_default_value($entity, $definition): array {
  if ($entity->bundle() !== 'dyne_cta' || $definition->getName() !== 'field_bg_media' || ($entity->hasField('field_media_images_ready') && $entity->get('field_media_images_ready')->value) || ($entity->hasField('field_bg') && !$entity->get('field_bg')->isEmpty())) return [];
  $media = dyne_image_library_cta_media();
  return $media ? [['target_id' => $media->id()]] : [];
}

/** Check whether a native choice still represents its original source File. */
function dyne_image_library_matches_source($paragraph, array $definition): bool {
  $fid = (int) $paragraph->get($definition['legacy'])->target_id;
  $items = $paragraph->get($definition['media']);
  if (!$fid) return $items->isEmpty();
  $media = $items->entity;
  if (!$media || $media->bundle() !== 'image') return FALSE;
  $type = \Drupal\media\Entity\MediaType::load('image');
  if (!$type || $type->getSource()->getPluginId() !== 'image') return FALSE;
  $source_name = $type->getSource()->getSourceFieldDefinition($type)->getName();
  return $media->hasField($source_name) && (int) $media->get($source_name)->target_id === $fid;
}

/**
 * Seed once, then follow raw import changes only while fields stay generated.
 *
 * This changes the supplied Paragraph only; its owner performs the normal save.
 * A missing or unsupported legacy File keeps the pre-conversion fallback.
 */
function dyne_image_library_paragraph_presave($paragraph): void {
  $definition = dyne_image_library_fields()[$paragraph->bundle()] ?? NULL;
  if (!$definition || !$paragraph->hasField($definition['legacy']) || !$paragraph->hasField($definition['media']) || !$paragraph->hasField('field_media_images_ready')) return;
  $legacy = $definition['legacy'];
  $native = $definition['media'];
  if (!$paragraph->get('field_media_images_ready')->value) {
    if ($paragraph->get($native)->isEmpty() && !$paragraph->get($legacy)->isEmpty()) {
      $media = dyne_image_library_media_for_file((int) $paragraph->get($legacy)->target_id);
      if (!$media) return;
      $paragraph->set($native, ['target_id' => $media->id()]);
      if (isset($definition['alt']) && $paragraph->hasField($definition['alt']) && $paragraph->get($definition['alt'])->isEmpty()) {
        $paragraph->set($definition['alt'], (string) $paragraph->get($legacy)->alt);
      }
    }
    elseif ($paragraph->bundle() === 'dyne_cta' && $paragraph->get($native)->isEmpty()) {
      // The old CTA renders this image even with an empty File field. Retain
      // that displayed default on first save, then allow intentional clearing.
      $media = dyne_image_library_cta_media(TRUE);
      if (!$media) return;
      $paragraph->set($native, ['target_id' => $media->id()]);
    }
    $paragraph->set('field_media_images_ready', TRUE);
    return;
  }
  $original = $paragraph->isNew() ? NULL : $paragraph->getOriginal();
  if ($original && method_exists($original, 'hasTranslation') && $original->hasTranslation($paragraph->language()->getId())) {
    $original = $original->getTranslation($paragraph->language()->getId());
  }
  // An empty native field on a ready revision is always an explicit choice,
  // including a cleared CTA default; later raw imports cannot resurrect it.
  if (!$original || !$original->hasField($native) || $original->get($native)->isEmpty() || $paragraph->get($legacy)->equals($original->get($legacy)) || !$paragraph->get($native)->equals($original->get($native)) || !dyne_image_library_matches_source($original, $definition)) return;
  $fid = (int) $paragraph->get($legacy)->target_id;
  $media = $fid ? dyne_image_library_media_for_file($fid) : NULL;
  if ($fid && !$media) return;
  $paragraph->set($native, $media ? ['target_id' => $media->id()] : []);
  if (isset($definition['alt']) && $paragraph->hasField($definition['alt'])) {
    $alt = $definition['alt'];
    $before_alt = (string) $original->get($alt)->value;
    if ((string) $paragraph->get($alt)->value === $before_alt && $before_alt === (string) $original->get($legacy)->alt) {
      $paragraph->set($alt, (string) $paragraph->get($legacy)->alt);
    }
  }
}
