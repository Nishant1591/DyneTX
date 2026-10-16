<?php
/** Preserve audit fields while exposing only the current editorial authority. */
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
foreach (['dyne_page', 'news', 'leader', 'job', 'perspective', 'quote', 'privacy_policy'] as $bundle) {
 if ($field=FieldConfig::loadByName('node',$bundle,'field_native_presentation')) $field->setDefaultValue(1)->save();
 $form=EntityFormDisplay::load('node.'.$bundle.'.default');
 if($form){foreach(['field_wp_source_data','field_wp_source_status','field_wp_source_type','field_wp_source_id','field_legacy_title'] as $name)$form->removeComponent($name);$form->save();}
}
if($field=FieldConfig::loadByName('node','news','field_news_type'))$field->setDefaultValue('html')->save();
// The old hero Paragraph is preserved for revisions; node hero fields own output.
if ($form=EntityFormDisplay::load('paragraph.dyne_hero.default')) {
 foreach(['field_title_line1','field_title_line2','field_banner_text','field_bg','field_image'] as $name)$form->removeComponent($name);
 $form->save();
}
print "Native editorial defaults configured; provenance fields retained outside normal editing forms.\n";
