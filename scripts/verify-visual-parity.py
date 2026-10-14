#!/usr/bin/env python3
"""Compare rendered text and hero geometry using existing Playwright CLI sessions.

Run after starting visual-ref and visual-local browser sessions. Reloading at each
viewport is intentional: the source homepage fixes its video height on load.
No form submissions or persistent browser configuration changes are performed.
"""
import argparse
from concurrent.futures import ThreadPoolExecutor
import json
from pathlib import Path
import subprocess

METRICS = r'''async () => {
  await document.fonts.ready;
  // Measure the settled brand heading, after its font-loading transition.
  await Promise.all(document.getAnimations().filter(animation =>
    animation.effect?.target?.closest('.hero') &&
    animation.effect.getTiming().iterations !== Infinity
  ).map(animation => animation.finished.catch(() => {})));
  await Promise.all([...document.querySelectorAll('.wrapper img')].map(img =>
    img.complete ? Promise.resolve() : new Promise(resolve => {
      img.addEventListener('load', resolve, {once:true});
      img.addEventListener('error', resolve, {once:true});
      setTimeout(resolve, 3000);
    })));
  const visible = el => {
    const rect = el.getBoundingClientRect(), s = getComputedStyle(el);
    return rect.width > 2 && rect.height > 2 && s.visibility === 'visible'
      && !el.closest('.modal,.offcanvas');
  };
  const measure = el => {
    const rect = el.getBoundingClientRect(), s = getComputedStyle(el);
    return {text:el.textContent.replace(/\s+/g,' ').trim(), tag:el.tagName,
      box:[rect.x,rect.y+scrollY,rect.width,rect.height].map(n=>+n.toFixed(3)),
      font:s.font, color:s.color, letterSpacing:s.letterSpacing};
  };
  return {url:location.href, viewport:[innerWidth,innerHeight],
    overflow:document.documentElement.scrollWidth-innerWidth,
    shell:Object.fromEntries(['.header','.header > .logo','.header .navbar-nav',
      '.header .button-collapse','.site-footer','.footer-info','.footer-legal',
      '.footer-moto','.footer-mark'].map(selector => {
        const el=document.querySelector(selector);
        return [selector,el && visible(el) ? measure(el) : null];
      })),
    fonts:[...document.fonts].filter(f=>f.status==='loaded').map(f=>f.family),
    hero:[...document.querySelectorAll('.wrapper > .hero,.wrapper .hero-media,.wrapper .hero-secondary')]
      .filter(visible).map(el=>({...measure(el),text:''})).slice(0,1),
    text:[...document.querySelectorAll('.wrapper h1,.wrapper h2,.wrapper h3,.wrapper h4,.wrapper h5,.wrapper p')]
      .filter(visible).map(measure).filter(el=>el.text)};
}'''

def cli(binary, session, *args):
    result = subprocess.run([binary, '-s='+session, *args], capture_output=True, text=True, check=True)
    output = result.stdout
    if '### Result\n' in output:
        return json.loads(output.split('### Result\n',1)[1].split('\n###',1)[0])

def collect(binary, session, origin, path, width, height):
    cli(binary, session, 'resize', str(width), str(height))
    cli(binary, session, 'goto', origin+path)
    return cli(binary, session, 'eval', METRICS)

def compare(reference, local):
    source = {(el['tag'],el['text']):el for el in reference['text']}
    matches, differences = [], []
    for el in local['text']:
        expected = source.get((el['tag'],el['text']))
        if not expected:
            continue
        delta = max(abs(a-b) for a,b in zip(el['box'],expected['box']))
        typography = {k:{'source':expected[k],'drupal':el[k]} for k in ['font','color','letterSpacing'] if el[k]!=expected[k]}
        match = {'text':el['text'][:100], 'max_geometry_delta_px':round(delta,3)}
        matches.append(match)
        if delta>1 or typography:
            differences.append({**match,'source_box':expected['box'],'drupal_box':el['box'],'typography':typography})
    shell = {}
    for selector, actual in local['shell'].items():
        expected=reference['shell'].get(selector)
        if actual and expected:
            # Footer position depends on editorial content / external embeds;
            # compare its presentation relative to its own top edge.
            actual_box=actual['box'][:]
            expected_box=expected['box'][:]
            if 'footer' in selector:
                actual_box[1]-=local['shell']['.site-footer']['box'][1]
                expected_box[1]-=reference['shell']['.site-footer']['box'][1]
            shell[selector]={'max_geometry_delta_px':round(max(abs(a-b) for a,b in zip(actual_box,expected_box)),3),
                'typography_matches':all(actual[k]==expected[k] for k in ['font','color','letterSpacing'])}
    return {'matched_text_elements':len(matches), 'differences':differences, 'shell':shell,
        'max_geometry_delta_px':max((x['max_geometry_delta_px'] for x in matches),default=0),
        'source_hero':reference['hero'], 'drupal_hero':local['hero'],
        'drupal_overflow_px':local['overflow']}

def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--cli',required=True)
    parser.add_argument('--widths',default='390,768,1024,1440')
    parser.add_argument('--paths',default='/,/our-forcetm-platform/,/pipeline/,/about-us/')
    parser.add_argument('--output',default='output/visual-parity.json')
    args=parser.parse_args()
    report=[]
    with ThreadPoolExecutor(max_workers=2) as pool:
        for width in map(int,args.widths.split(',')):
            height=844 if width<600 else 1000
            for path in args.paths.split(','):
                futures=[pool.submit(collect,args.cli,session,origin,path,width,height) for session,origin in [
                    ('visual-ref','https://www.dyne-tx.com'),('visual-local','http://dyne-tx-master.ddev.site')]]
                reference,local=[f.result() for f in futures]
                comparison=compare(reference,local)
                report.append({'width':width,'path':path,'comparison':comparison,'reference':reference,'drupal':local})
                print(json.dumps({'width':width,'path':path,'matched':comparison['matched_text_elements'],
                    'differences':len(comparison['differences']),'max_delta':comparison['max_geometry_delta_px'],
                    'overflow':comparison['drupal_overflow_px']}),flush=True)
                Path(args.output).write_text(json.dumps(report,indent=2)+'\n')

if __name__=='__main__':
    main()
