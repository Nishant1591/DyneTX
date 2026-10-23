<?php

/** Pure component state tests: ddev exec php scripts/tests/rich-text-component-fields.php */
require_once dirname(__DIR__, 2) . '/web/modules/custom/dyne_migrate/includes/rich-text-component-fields.inc';
require_once dirname(__DIR__, 2) . '/web/themes/custom/dynetx/includes/native-components.inc';
class RichComponentTestValue {
  public $value;
  public $format;
  public function __construct($input) { $this->value = is_array($input) ? ($input['value'] ?? NULL) : $input; $this->format = is_array($input) ? ($input['format'] ?? NULL) : NULL; }
  public function isEmpty(): bool { return $this->value === NULL || $this->value === ''; }
}
class RichComponentTestParagraph {
  public array $values;
  public $original = NULL;
  public function __construct(public string $type = 'dyne_card_item') {
    [$plain, $rich] = dyne_rich_text_component_fields()[$type];
    $this->values = [$plain => '', $rich => [], 'field_component_rich_text_ready' => FALSE];
  }
  public function bundle(): string { return $this->type; }
  public function hasField($field): bool { return array_key_exists($field, $this->values); }
  public function get($field): RichComponentTestValue { return new RichComponentTestValue($this->values[$field]); }
  public function set($field, $value): void { $this->values[$field] = $value; }
  public function getOriginal() { return $this->original; }
}
class RichComponentTestItems {
  public function __construct(public RichComponentTestParagraph $entity, public string $name) {}
  public function getEntity() { return $this->entity; }
  public function getName() { return $this->name; }
}
$failures = []; $cases = 0;
$assert = static function ($condition, $name) use (&$failures, &$cases): void { $cases++; if (!$condition) $failures[] = $name; };
foreach (dyne_rich_text_component_fields() as $bundle => [$plain, $rich]) {
  $entity = new RichComponentTestParagraph($bundle);
  $entity->set($plain, "<b>Literal & text</b>\nSecond line");
  dyne_rich_text_component_presave($entity);
  $assert($entity->get($rich)->value === "&lt;b&gt;Literal &amp; text&lt;/b&gt;<br>\nSecond line", $bundle . ': escaped source');
  $assert($entity->get($plain)->value === "<b>Literal & text</b>\nSecond line", $bundle . ': original retained');
  $assert($entity->get('field_component_rich_text_ready')->value === TRUE, $bundle . ': readiness');
  $entity->original = clone $entity;
  $entity->set($rich, []);
  dyne_rich_text_component_presave($entity);
  $assert($entity->get($rich)->isEmpty() && _dynetx_component_has_rich_text($entity, $rich), $bundle . ': clearing is authoritative');
  $entity->original = clone $entity;
  $entity->set($plain, 'Later source import');
  dyne_rich_text_component_presave($entity);
  $assert($entity->get($rich)->isEmpty(), $bundle . ': later source cannot resurrect cleared text');
}
$entity = new RichComponentTestParagraph();
$entity->set('field_item_text', 'Original'); dyne_rich_text_component_presave($entity);
$entity->original = clone $entity; $entity->set('field_item_text', 'Imported change'); dyne_rich_text_component_presave($entity);
$assert($entity->get('field_item_body')->value === 'Imported change', 'unmodified generated text follows source');
$entity->original = clone $entity; $entity->set('field_item_body', ['value' => '<p>Editorial override</p>', 'format' => 'dyne_legacy_html']); $entity->set('field_item_text', 'Another source change'); dyne_rich_text_component_presave($entity);
$assert($entity->get('field_item_body')->value === '<p>Editorial override</p>', 'source does not overwrite editor changes');
$entity = new RichComponentTestParagraph('dyne_alert_banner'); $entity->set('field_banner_text', '<Literal old text>');
$element = ['#type' => 'text_format'];
dyne_rich_text_component_widget_alter($element, NULL, ['items' => new RichComponentTestItems($entity, 'field_banner_body')]);
$assert(($element['#default_value'] ?? '') === '&lt;Literal old text&gt;' && !$entity->get('field_component_rich_text_ready')->value, 'old revision widget seed has no persistent mutation');
$complete_form = []; dyne_rich_text_component_element_validate($element, NULL, $complete_form);
$entity->set('field_banner_body', []); dyne_rich_text_component_presave($entity);
$assert($entity->get('field_banner_body')->isEmpty() && _dynetx_component_has_rich_text($entity, 'field_banner_body'), 'cleared first submission stays empty');
echo json_encode(['cases' => $cases, 'failures' => $failures], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit($failures ? 1 : 0);
