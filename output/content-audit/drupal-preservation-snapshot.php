<?php
// Read-only full entity snapshot for additive migration preservation checks.
$manager=\Drupal::entityTypeManager();
$report=['generated_at'=>date(DATE_ATOM),'modules'=>[],'entity_schema'=>[],'nodes'=>[],'paragraphs'=>[],'files'=>[],'media'=>[],'menus'=>[],'aliases'=>[],'legacy_ledger'=>\Drupal::state()->get('dyne_migrate.legacy_import',[])];
foreach (['file','image','media','media_library','content_moderation','workflows','migrate','migrate_plus','dyne_migrate'] as $module) $report['modules'][$module]=\Drupal::moduleHandler()->moduleExists($module);
foreach (['node','paragraph','file','media'] as $type) {
 if (!$manager->hasDefinition($type)) {$report['entity_schema'][$type]=NULL;continue;}
 $definition=$manager->getDefinition($type);
 $bundles=\Drupal::service('entity_type.bundle.info')->getBundleInfo($type);
 $schema=['keys'=>$definition->getKeys(),'base_table'=>$definition->getBaseTable(),'data_table'=>$definition->getDataTable(),'revision_table'=>$definition->getRevisionTable(),'bundles'=>[]];
 foreach ($bundles as $bundle=>$info) {
  $fields=[];
  foreach (\Drupal::service('entity_field.manager')->getFieldDefinitions($type,$bundle) as $name=>$field) $fields[$name]=['type'=>$field->getType(),'cardinality'=>$field->getFieldStorageDefinition()->getCardinality(),'required'=>$field->isRequired(),'settings'=>$field->getSettings()];
  $schema['bundles'][$bundle]=['label'=>$info['label'],'fields'=>$fields];
 }
 $report['entity_schema'][$type]=$schema;
}
foreach (['node'=>'nodes','paragraph'=>'paragraphs','file'=>'files','menu_link_content'=>'menus','path_alias'=>'aliases','media'=>'media'] as $type=>$key) {
 if (!$manager->hasDefinition($type)) continue;
 foreach ($manager->getStorage($type)->loadMultiple() as $id=>$entity) $report[$key][$id]=$entity->toArray();
}
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
