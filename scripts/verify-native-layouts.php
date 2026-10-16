<?php
/** Exercise each native source layout with real fields; roll back test content. */
require_once DRUPAL_ROOT . '/modules/custom/dyne_migrate/includes/native-layouts.inc';
dyne_layout_schema();
$manager=\Drupal::entityTypeManager();
$transaction=\Drupal::database()->startTransaction();
$before=(int)$manager->getStorage('paragraph')->getQuery()->accessCheck(FALSE)->count()->execute();
$checks=[];
try {
 foreach(dyne_layout_definitions() as $source=>[$bundle,$label,$controls]) {
  $p=\Drupal\paragraphs\Entity\Paragraph::create(['type'=>$bundle]);
  foreach(['heading'=>'Native layout check','caption'=>'Caption','gene'=>'Gene'] as $key=>$value)if($p->hasField('field_layout_'.$key))$p->set('field_layout_'.$key,$value);
  foreach(['body','symptoms','treatment','sidebar_body'] as $key)if($p->hasField('field_layout_'.$key))$p->set('field_layout_'.$key,['value'=>'<p>Native formatted text.</p>','format'=>'dyne_legacy_html']);
  if($p->hasField('field_layout_link'))$p->set('field_layout_link',['uri'=>'internal:/about-us','title'=>'Read more']);
  if($p->hasField('field_layout_count'))$p->set('field_layout_count',2);
  $p->save();
  $view=$manager->getViewBuilder('paragraph')->view($p);
  $html=(string)\Drupal::service('renderer')->renderInIsolation($view);
  if(!str_contains($html,'dyne-native-layout'))throw new RuntimeException('Native layout template was not selected: '.$bundle);
  $form=\Drupal\Core\Entity\Entity\EntityFormDisplay::load('paragraph.'.$bundle.'.default');
  foreach($controls as $key)if(!$form->getComponent('field_layout_'.$key))throw new RuntimeException('Missing editing widget '.$bundle.':'.$key);
  $checks[]=['source_layout'=>$source,'paragraph_type'=>$bundle,'controls'=>count($controls),'rendered'=>TRUE,'native_widgets'=>TRUE];
 }
} finally { $transaction->rollBack(); }
$after=(int)$manager->getStorage('paragraph')->getQuery()->accessCheck(FALSE)->count()->execute();
if($after!==$before)throw new RuntimeException('Test content was not rolled back.');
$report=['layouts'=>count($checks),'paragraphs_before'=>$before,'paragraphs_after'=>$after,'checks'=>$checks];
file_put_contents(dirname(DRUPAL_ROOT).'/output/document-6-review/native-layout-verification.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
