<?php
/** Temporarily edit and restore entity fields to verify cached native rendering. */
$storage = \Drupal::entityTypeManager()->getStorage('node');
$find = static function (string $alias) use ($storage) { $path = \Drupal::service('path_alias.manager')->getPathByAlias($alias); return $storage->load(substr($path, 6)); };
$policies = $storage->loadByProperties(['type' => 'privacy_policy', 'field_wp_source_id' => 3790]);
$stories = $storage->getQuery()->accessCheck(FALSE)->condition('type', 'perspective')->condition('status', 1)->sort('field_story_date', 'DESC')->range(0,1)->execute();
$checks = [
 ['entity' => $find('/about-us'), 'field' => 'field_hero_first_line', 'path' => '/about-us', 'label' => 'Hero heading'],
 ['entity' => reset($policies), 'field' => 'field_body_text', 'path' => '/privacy-policy', 'label' => 'Reusable privacy policy'],
 ['entity' => $storage->load(reset($stories)), 'field' => 'field_author', 'path' => '/perspectives', 'label' => 'Perspective author'],
];
$get = static function ($path) {
 $response = \Drupal::httpClient()->get('http://localhost'.$path, ['headers'=>['Host'=>'dyne-tx-master.ddev.site'], 'timeout'=>30]);
 return (string) $response->getBody();
};
$report=[];
foreach ($checks as $check) {
 $node=$check['entity']; $original=$node->get($check['field'])->getValue(); $marker='NATIVE_PRESENTATION_CHECK_'.bin2hex(random_bytes(5));
 $get($check['path']);$get($check['path']);
 try {
  \Drupal::service('cache_tags.invalidator.checksum')->reset();
  $changed=$original;$changed[0]['value']=($changed[0]['value']??'').' '.$marker;
  $node->set($check['field'],$changed);$node->setNewRevision(TRUE);$node->setRevisionLogMessage('Temporary native presentation verification.');$node->save();
  $visible=str_contains($get($check['path']),$marker);
  if(!$visible)throw new RuntimeException('Saved edit did not appear: '.$check['label']);
 } finally {
  \Drupal::service('cache_tags.invalidator.checksum')->reset();
  $node->set($check['field'],$original);$node->setNewRevision(TRUE);$node->setRevisionLogMessage('Restore original value after presentation verification.');$node->save();
 }
 $restored=!str_contains($get($check['path']),$marker);
 if(!$restored)throw new RuntimeException('Restoration not visible: '.$check['label']);
 $report[]=['label'=>$check['label'],'nid'=>$node->id(),'field'=>$check['field'],'route'=>$check['path'],'edit_visible_without_flush'=>$visible,'restored_without_flush'=>$restored];
}
file_put_contents(dirname(DRUPAL_ROOT).'/output/document-6-review/native-presentation-verification.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
