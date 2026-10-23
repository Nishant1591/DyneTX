<?php
/** Read-only reconstruction regression: ddev exec php scripts/tests/structured-article-parser.php */
chdir(dirname(__DIR__, 2));
require 'web/modules/custom/dyne_migrate/includes/structured-articles.inc';
function expand_plan($p) {
  return preg_replace_callback('/<!--dyne:([a-z_]+)(?::([0-9]+|url|text))?-->/', function($m) use($p) {
    if($m[1]==='field_article_sections') return expand_plan($p['sections'][(int)$m[2]]??['template'=>'','fields'=>[],'sections'=>[]]);
    if(($m[2]??'')==='url') return htmlspecialchars($m[1]==='field_section_image'?$p['image_url']:($p['variant_urls'][$m[1]]??$p['fields'][$m[1]]['uri']??''), ENT_QUOTES|ENT_HTML5, 'UTF-8');
    if(($m[2]??'')==='text') return ($p['fields'][$m[1]]['title']??'')!=='' ? htmlspecialchars($p['fields'][$m[1]]['title'], ENT_QUOTES|ENT_HTML5, 'UTF-8') : ($p['fields']['field_section_body']??'');
    return $m[1]==='field_section_image_alt' ? htmlspecialchars($p['fields'][$m[1]]??'', ENT_QUOTES|ENT_HTML5, 'UTF-8') : ($p['fields'][$m[1]]??'');
  }, $p['template']);
}
function norm_text($s) { return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($s), ENT_QUOTES|ENT_HTML5, 'UTF-8'))); }
function count_sections($p) { return count($p['sections'])+array_sum(array_map('count_sections', $p['sections'])); }
function urls($s) {
  preg_match_all('~(?:href|src|srcset)\s*=\s*(?:"([^"]*)"|\x27([^\x27]*)\x27)~i', $s, $a, PREG_SET_ORDER);
  return array_map(fn($m)=>html_entity_decode($m[1]?:($m[2]??''), ENT_QUOTES|ENT_HTML5), $a);
}
function background_urls($s) {
  preg_match_all('~background-image\s*:\s*url\([\x27"]?([^\x27")]+)[\x27"]?\)~i', html_entity_decode($s, ENT_QUOTES|ENT_HTML5), $matches);
  return $matches[1];
}
function image_alts($s) {
  preg_match_all('~<img\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>~i', $s, $images);
  return array_map(static fn($tag) => preg_match('/(?<![-:\w])alt\s*=/i', $tag) ? dyne_structured_html_attribute($tag, 'alt') : NULL, $images[0]);
}
$rows=json_decode(file_get_contents('web/modules/custom/dyne_migrate/data/legacy-content.json'), TRUE)['records'];
$counts=[]; $fail=[]; $total=0;
$placement_fixture = '<picture><source srcset="/mobile.webp" media="(max-width: 575.99px)"><source srcset="/retina.webp" media="(min-resolution: 1.5dppx)"><img class="cover" src="<!--dyne:field_section_image:url-->" alt="Family &amp; friends"></picture>';
$placement = dyne_structured_image_template_plan($placement_fixture);
if (($placement['fields']['field_section_image_alt'] ?? NULL) !== 'Family & friends' || ($placement['variant_urls']['field_section_mobile_image'] ?? '') !== '/mobile.webp' || ($placement['variant_urls']['field_section_retina_image'] ?? '') !== '/retina.webp') $fail[]=['error'=>'Image placement extraction'];
$second_placement = dyne_structured_image_template_plan($placement['template']);
if ($second_placement['template'] !== $placement['template'] || $second_placement['fields'] || $second_placement['variant_urls']) $fail[]=['error'=>'Image placement idempotence'];
$no_alt = '<img src="<!--dyne:field_section_image:url-->">';
if (dyne_structured_image_template_plan($no_alt)['template'] !== $no_alt) $fail[]=['error'=>'Missing image alt must remain absent'];
$complex_srcset = '<picture><source srcset="/small.webp 1x, /large.webp 2x" media="(max-width: 600px)">' . $no_alt . '</picture>';
if (dyne_structured_image_template_plan($complex_srcset)['template'] !== $complex_srcset) $fail[]=['error'=>'Unknown responsive candidate list must remain intact'];
$nested_picture = '<picture><source srcset="/mobile.webp" media="(max-width: 575.99px)"><!--dyne:field_article_sections:0--></picture>';
if ((dyne_structured_image_template_plan($nested_picture)['variant_urls']['field_section_mobile_image'] ?? '') !== '/mobile.webp') $fail[]=['error'=>'Existing picture with nested native image must expose source variant'];
$background_fixture = '<img style="background-image: url(&#x27;/sites/default/files/legacy/person.jpg&#x27;); background-position: center;" src="<!--dyne:field_section_image:url-->" alt="Person">';
$background = dyne_structured_image_template_plan($background_fixture);
if (($background['variant_urls']['field_section_bg_image'] ?? '') !== '/sites/default/files/legacy/person.jpg' || !str_contains($background['template'], 'background-position: center;')) $fail[]=['error'=>'Background image must become native while preserving styles'];
$ambiguous_backgrounds = '<div style="background-image: url(/sites/default/files/legacy/one.jpg)"></div><div style="background-image: url(/sites/default/files/legacy/two.jpg)"></div>';
$ambiguous = dyne_structured_image_template_plan($ambiguous_backgrounds);
if ($ambiguous['template'] !== $ambiguous_backgrounds || count($ambiguous['ambiguous_backgrounds']) !== 2 || isset($ambiguous['variant_urls']['field_section_bg_image'])) $fail[]=['error'=>'Ambiguous backgrounds must remain unchanged'];
$uri_cases = [
  '/careers-culture/positions' => 'internal:/careers-culture/positions',
  '#leadership' => 'internal:#leadership',
  '?page=2&category=all' => 'internal:?page=2&category=all',
  '/sites/default/files/legacy/trial-overview.pdf' => 'internal:/sites/default/files/legacy/trial-overview.pdf',
  '//example.org/overview.pdf' => 'https://example.org/overview.pdf',
  'https://example.org/story' => 'https://example.org/story',
  'mailto:privacy@example.org' => 'mailto:privacy@example.org',
  'internal:/careers' => 'internal:/careers',
  '' => '',
];
foreach ($uri_cases as $input => $expected) {
  if (dyne_structured_article_uri($input) !== $expected || dyne_structured_article_uri($expected) !== $expected) $fail[] = ['error' => 'URI normalization', 'input' => $input, 'expected' => $expected];
}
foreach (['/careers-culture/positions', '#leadership', '/sites/default/files/legacy/trial-overview.pdf'] as $uri) {
  $html = '<a class="btn" href="' . htmlspecialchars($uri, ENT_QUOTES | ENT_HTML5) . '">View details</a>';
  $plan = dyne_structured_article_plan('dyne_rich_text', $html);
  if (norm_text(expand_plan($plan)) !== 'View details' || urls(expand_plan($plan)) !== [$uri]) $fail[] = ['error' => 'Native link fixture reconstruction', 'uri' => $uri];
}
foreach($rows as $r) {
  $samples=[];
  if(in_array($r['bundle'], ['news','perspective'], TRUE)) $samples[]=[$r['bundle'],$r['body']??''];
  foreach($r['components']??[] as $c) if($c['type']==='dyne_rich_text') $samples[]=['dyne_rich_text',$c['fields']['field_body_text']];
  foreach($samples as [$bundle,$body]) {
    $p=dyne_structured_article_plan($bundle,$body); $expanded=expand_plan($p);
    $counts[$bundle]=($counts[$bundle]??0)+1; $total+=count_sections($p);
    if(norm_text($body)!==norm_text($expanded)) $fail[]=['id'=>$r['source_id'],'bundle'=>$bundle,'before'=>strlen($body),'after'=>strlen($expanded),'error'=>'text'];
    if(urls($body)!==urls($expanded)) $fail[]=['id'=>$r['source_id'],'bundle'=>$bundle,'error'=>'links'];
    if(background_urls($body)!==background_urls($expanded)) $fail[]=['id'=>$r['source_id'],'bundle'=>$bundle,'error'=>'background image URLs'];
    if(image_alts($body)!==image_alts($expanded)) $fail[]=['id'=>$r['source_id'],'bundle'=>$bundle,'error'=>'placement alternative text'];
  }
}
foreach(json_decode(file_get_contents('content-archive/wordpress-records.json'), TRUE)['records'] as $r) if($r['type']==='wb_privacy_policy') {
  $p=dyne_structured_article_plan('privacy_policy',$r['content']); $total+=count_sections($p);
  if(norm_text($r['content'])!==norm_text(expand_plan($p)) || urls($r['content'])!==urls(expand_plan($p))) $fail[]=['id'=>$r['id'],'bundle'=>'privacy_policy','error'=>'policy reconstruction'];
  $counts['privacy_policy']=($counts['privacy_policy']??0)+1;
}
echo json_encode(['counts'=>$counts,'sections'=>$total,'uri_cases'=>count($uri_cases),'failures'=>$fail], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
exit($fail ? 1 : 0);
