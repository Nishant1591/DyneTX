<?php

/**
 * @file
 * create-pages.php — creates ALL dyne_page nodes (22 pages) 1:1 from live WP site
 * https://www.dyne-tx.com/ (content captured 9/10/2026).
 * Idempotent: upserts by path alias, rebuilds paragraph components each run.

 * Run:
 *   ddev drush php:script web/modules/custom/dyne_migrate/scripts/create-pages.php
 */

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\path_alias\Entity\PathAlias;

$ns = \Drupal::entityTypeManager()->getStorage('node');
$alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
$alias_repo = \Drupal::service('path_alias.repository');

/** Create + save a paragraph component; auto-encode JSON item fields.
 */
$mk = function (string $type, array $fields): Paragraph {
  foreach (['field_items_json', 'field_rows_json', 'field_sections_json'] as $f) {
    if (isset($fields[$f]) && is_array($fields[$f])) {
      $fields[$f] = json_encode($fields[$f], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
  }
  $p = Paragraph::create(['type' => $type] + $fields);
  $p->save();
  return $p;
};

/** Delete all paragraph components currently referenced by a node.
 */
$clear_components = function (Node $node) {
  if (!$node->hasField('field_components') || $node->get('field_components')->isEmpty()) { return; }
  foreach ($node->get('field_components')->referencedEntities() as $old) {
    if ($old instanceof Paragraph) { $old->delete(); }
  }
  $node->set('field_components', []);
};

/** Find a node by its path alias (e.g. /about-us → /node/N.
 */
$node_by_alias = function (string $alias) use ($ns, $alias_storage) {
  $alias_entities = $alias_storage->loadByProperties(['alias' => $alias]);
  if ($alias_entities) {
    $ae = reset($alias_entities);
    if ($ae && preg_match('#^/node/(\d+)$#', (string) $ae->get('path')->value, $m)) {
      return $ns->load($m[1]);
    }
  }
  return NULL;
};

/** Upsert a dyne_page node que components + path alias (WP URL  Ả1:1.
 */
$upsert = function (string $title, string $alias, array $components) use ($ns, $alias_storage, $mk, $clear_components, $node_by_alias): Node {
  $node = $node_by_alias($alias);
  if ($node) {
    $clear_components($node);
    $node->set('title', $title);
  } else {
    $node = $ns->create(['type' => 'dyne_page', 'title' => $title, 'uid' => 1]);
  }
  $refs = [];
  foreach ($components as $c) {
    $p = $mk($c['type'], $c['fields']);
    $refs[] = ['target_id' => $p->id(), 'target_revision_id' => $p->getRevisionId()];
  }
  $node->set('field_components', $refs);
  $node->save();

  // Path alias.

  $alias_entities = $alias_storage->loadByProperties(['alias' => $alias]);
  if ($alias_entities) {
    $ae = reset($alias_entities);
    $ae->set('path', '/node/' . $node->id())->save();
  } else {
    PathAlias::create(['path' => '/node/' . $node->id(), 'alias' => $alias])->save();
  }
  print '  + ' . $title . ' -> ' . $alias . ' (node ' . $node->id() . ")\n";
  return $node;
};

$pages = [
'home' => [
    'title' => 'Home',
    'alias' => '/home',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'The muscle to',
        'field_title_line2' => 'keep life moving',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Dyne Therapeutics is trailblazing the development of therapies to deliver functional improvement for people living with genetically driven neuromuscular diseases',
        'field_style' => 'h1-lede',
        'field_body_text' => <<<'HTML'
<p>We are on a mission to deliver functional improvement for people living with genetically driven neuromuscular diseases through targeted therapies enabled by our FORCE platform. We have assembled an exceptional team with deep experience in neuromuscular disease, rare disorders, drug development, and manufacturing and commercialization of novel therapeutics. We have also established a scientific advisory board comprised of leading experts in neuromuscular disease drug discovery and development.</p>
HTML,
      ]],
      ['type' => 'dyne_icon_cards', 'fields' => [
        'field_heading' => 'What we do',
        'field_items_json' => [
          ['icon' => '/themes/custom/dynetx/images/icons/ico-cells.svg', 'title' => 'Pioneering approach', 'text' => 'Utilizing our FORCE platform to develop therapeutics that address the root cause of disease.', 'link' => '/our-forcetm-platform', 'style' => 'red-one'],
          ['icon' => '/themes/custom/dynetx/images/icons/ico-muscle.svg', 'title' => 'Pipeline', 'text' => 'Developing a broad portfolio of therapeutics for genetically driven neuromuscular diseases.', 'link' => '/pipeline', 'style' => 'blue-one'],
          ['icon' => '/themes/custom/dynetx/images/icons/ico-dna-vert.svg', 'title' => 'Find a Dyne clinical trial', 'text' => 'Conducting clinical trials for neuromuscular diseases.', 'link' => '/clinical-trials', 'style' => 'mid-blue-one'],
          ['icon' => '/themes/custom/dynetx/images/icons/ico-commitment.svg', 'title' => 'Commitment', 'text' => 'Working to deliver functional improvement for people living with neuromuscular diseases.', 'link' => '/our-commitment', 'style' => 'dark-gray-one'],
        ],
      ]],
      ['type' => 'dyne_cta', 'fields' => [
        'field_heading' => 'Culture and careers',
        'field_body_text' => <<<'HTML'
<p>Our commitment to people living with neuromuscular diseases is our greatest strength. Hear our Dynamos share their perspective on what makes Dyne a special place to work.</p>
HTML,
        'field_link' => ['uri' => 'internal:/careers-culture', 'title' => 'Learn more'],
      ]],
      ['type' => 'dyne_news_list', 'fields' => [
        'field_heading' => 'Latest news',
        'field_items_json' => [
          ['date' => 'September 29, 2026', 'title' => 'Dyne Therapeutics Announces Additional One-Year Clinical Data from Phase 1/2 ACHIEVE Trial of Z-Basivarsen (DYNE-101) for Myotonic Dystrophy Type 1 (DM1)', 'url' => 'https://investors.dyne-tx.com/news-releases/news-release-details/dyne-therapeutics-announces-additional-one-year-clinical-data-0'],
          ['date' => 'September 17, 2026', 'title' => 'Dyne Therapeutics Reports Inducement Grants Under Nasdaq Listing Rule 5635(c)(4)', 'url' => 'https://investors.dyne-tx.com/news-releases/news-release-details/dyne-therapeutics-reports-inducement-grants-under-nasdaq-6'],
        ],
      ]],
    ],
  ],

'about-us' => [
    'title' => 'About Us',
    'alias' => '/about-us',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Trailblazing the development of targeted therapies to deliver',
        'field_title_line2' => 'functional improvement',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'We are on a mission to deliver functional improvement for people living with genetically driven neuromuscular diseases through targeted therapies enabled by our FORCE™ platform.',
        'field_style' => 'h1-lede',
        'field_body_text' => <<<'HTML'
<p>We have assembled an exceptional team with deep experience in neuromuscular disease, rare disorders, drug development, and manufacturing and commercialization of novel therapeutics.</p>
<p>We have also established a scientific advisory board comprised of leading experts in neuromuscular disease drug discovery and development.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Our leadership team',
        'field_body_text' => <<<'HTML'
<p>Our leadership team brings deep knowledge and extensive experience in the discovery, clinical development and commercialization of treatments for rare genetic diseases and novel therapeutic modalities.</p>
HTML,
      ]],
      ['type' => 'dyne_team_grid', 'fields' => [
        'field_heading' => 'Our leadership team',
        'field_anchor' => 'leadership',
        'field_items_json' => [
          ['name' => 'John Cox, MBA', 'role' => 'Presidentand Chief Executive Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Doug Kerr, M.D., Ph.D., MBA', 'role' => 'Chief Medical Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Erick J. Lucera', 'role' => 'Chief Financial Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Oxana Beskrovnaya, Ph.D.', 'role' => 'Chief Innovation Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Ranjan (Ron) Batra, Ph.D.', 'role' => 'Chief Scientific Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Johanna Friedl-Naderer', 'role' => 'Chief Commercial Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Monica Fay', 'role' => 'Chief Medical Affairs Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Debra Feldman', 'role' => 'Chief Regulatory Affairs Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Vikram (Vik) Ranade', 'role' => 'Chief Business Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Raj Manchanda', 'role' => 'Chief Technical Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'James P. Bilotta, MBA', 'role' => 'Chief Digital Information Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Lucia Celona', 'role' => 'Chief Human Resources Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Alessandro Martuscelli', 'role' => 'Chief Legal Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'José Juves', 'role' => 'Chief Corporate & Patient Affairs Officer', 'photo' => '', 'bio' => ''],
          ['name' => 'Axel Wiest', 'role' => 'Senior Vice President, Head of Portfolio Strategy & Operations', 'photo' => '', 'bio' => ''],
        ],
      ]],

['type' => 'dyne_team_grid', 'fields' => [
        'field_heading' => 'Board of directors',
        'field_anchor' => 'bod',
        'field_items_json' => [
          ['name' => 'Jason Rhodes', 'role' => 'Chairman, Board of Directors', 'photo' => '', 'bio' => ''],
          ['name' => 'John Cox', 'role' => 'Member, Board of Directors', 'photo' => '', 'bio' => ''],
          ['name' => 'Barry Greene', 'role' => 'Member, Board of Directors', 'photo' => '', 'bio' => ''],
          ['name' => 'Ed Hurwitz', 'role' => 'Member, Board of Directors', 'photo' => '', 'bio' => ''],
          ['name' => 'Carlo Incerti, M.D.', 'role' => 'Member, Board of Directors', 'photo' => '', 'bio' => ''],
          ['name' => 'Vikram Karnani', 'role' => 'Member, Board of Directors', 'photo' => '', 'bio' => ''],
          ['name' => 'Dirk Kersten', 'role' => 'Member, Board of Directors', 'photo' => '', 'bio' => ''],
          ['name' => 'David Lubner', 'role' => 'Member, Board of Directors', 'photo' => '', 'bio' => ''],
          ['name' => 'Brian Posner', 'role' => 'Member, Board of Directors', 'photo' => '', 'bio' => ''],
          ['name' => 'Catherine Stehman-Breen', 'role' => 'Member, Board of Directors', 'photo' => '', 'bio' => ''],
        ],
      ]],
      ['type' => 'dyne_team_grid', 'fields' => [
        'field_heading' => 'Scientific advisory board',
        'field_anchor' => 'scientific',
        'field_items_json' => [
          ['name' => 'Sudhir Agrawal, D.Phil.', 'role' => 'Scientific Advisory Board', 'photo' => '', 'bio' => ''],
          ['name' => 'Nancy Andrews, M.D., Ph.D.', 'role' => 'Scientific Advisory Board', 'photo' => '', 'bio' => ''],
          ['name' => 'Louis Kunkel, Ph.D.', 'role' => 'Scientific Advisory Board', 'photo' => '', 'bio' => ''],
          ['name' => 'Francesco Muntoni, FRCPCH, FMedSci', 'role' => 'Scientific Advisory Board', 'photo' => '', 'bio' => ''],
          ['name' => 'Jeffrey Statland, M.D.', 'role' => 'Scientific Advisory Board', 'photo' => '', 'bio' => ''],
          ['name' => 'Charles Thornton, M.D.', 'role' => 'Scientific Advisory Board', 'photo' => '', 'bio' => ''],
        ],
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Contact us',
        'field_anchor' => 'contact',
        'field_body_text' => <<<'HTML'
<p>For general inquiries: <a href="mailto:info@dyne-tx.com">info@dyne-tx.com</a> · 781.786.8230</p>
<p>To connect with our clinical trials team: <a href="mailto:clinicaltrials@dyne-tx.com">clinicaltrials@dyne-tx.com</a></p>
<p>To connect with our Patient Advocacy team: <a href="mailto:patientadvocacy@dyne-tx.com">patientadvocacy@dyne-tx.com</a></p>
HTML,
      ]],
    ],
  ],

  'our-forcetm-platform' => [
    'title' => 'Our FORCE Platform',
    'alias' => '/our-forcetm-platform',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Advancing targeted therapeutics to address the',
        'field_title_line2' => 'root cause of disease',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Dyne created the FORCE™ platform to develop therapeutics that address the root cause of diseaseand deliver functional improvement for people living with neuromuscular disorders.',
        'field_body_text' => <<<'HTML'
<p>The FORCE platform consists of an antigen-binding fragment (Fab), a linker and a payload that is rationally designed to target the genetic basis of the disease we seek to treat.</p>
<p>We have a broad pipeline with clinical programs for Duchenne muscular dystrophy (DMD) and myotonic dystrophy type 1 (DM1) as well as preclinical programs for facioscapulohumeral muscular dystrophy (FSHD), Pompe diseaseand multiple DMD mutations.</p>
<p><em>Note: Figure depicts oligonucleotide payload; ASO = antisense oligonucleotide; PMO = phosphorodiamidate morpholino oligomer; siRNA = small interfering RNA;CNS = central nervous system.</em></p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Our proprietary Fab binds to transferrin receptor 1 (TfR1) to enable delivery to muscleand the central nervous system (CNS) with the goal of providing functional improvement for people living with neuromuscular diseases.',
        'field_body_text' => <<<'HTML'
<p>We believe a Fab offers significant advantages over a full antibody, including enhanced tissue penetration, increased tolerabilityand reduced risk of immune system activation.</p>
<p>Our Fab is linked to a therapeutic payload, such as an oligonucleotide or biologic, and the linker is chosen based on payload chemistry. The payload is carefully selected to directly address the root cause of the disease with the goal of delivering functional improvement.</p>
<p>We believe our FORCE platform provides several potential advantages, including delivery to muscleand CNS, extended time between dosesand ability to re-dose.</p>
HTML,
      ]],
    ],
  ],

'pipeline' => [
    'title' => 'Pipeline',
    'alias' => '/pipeline',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'A pipeline built to deliver functional improvement',
        'field_title_line2' => 'for neuromuscular diseases',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'We are advancing therapeutics to provide functional improvement for people living with genetically driven neuromuscular diseases.',
        'field_body_text' => <<<'HTML'
<p>By utilizing our FORCE™ platform, we believe we will be able to deliver targeted therapies to muscle and the central nervous system (CNS) to address the root cause of disease.</p>
<p>We have a broad pipeline, including clinical programs for Duchenne muscular dystrophy (DMD) and myotonic dystrophy type 1 (DM1) as well as preclinical programs for facioscapulohumeral muscular dystrophy (FSHD), Pompe disease and multiple DMD mutations. Each of these neuromuscular diseases has a profound impact on affected communities around the world.</p>
HTML,
      ]],
      ['type' => 'dyne_pipeline_table', 'fields' => [
        'field_heading' => 'Neuromuscular Pipeline',
        'field_rows_json' => [
          ['disease' => 'Duchenne muscular dystrophy (DMD)', 'prevalence' => 'U.S.: ~12,000 · Europe: ~16,000', 'target' => 'Exon 51', 'phase' => 'Registrational', 'status' => 'PDUFA: Jan 21, 2027', 'url' => '/dyne-251-for-dmd'],
          ['disease' => 'Myotonic dystrophy type 1 (DM1)', 'prevalence' => 'U.S.: ~40,000 · Europe: ~55,000', 'target' => 'DMPK', 'phase' => 'Phase 3', 'status' => 'HARMONIA trial enrolling', 'url' => '/dyne-101-for-dm1'],
          ['disease' => 'Facioscapulohumeral muscular dystrophy (FSHD)', 'prevalence' => 'U.S.: ~15,000-40,000 / Europe: ~20,000-50,000', 'target' => 'DUX4', 'phase' => 'Preclinical', 'status' => '', 'url' => '/dyne-302-for-fshd'],
          ['disease' => 'Pompe disease', 'prevalence' => '', 'target' => '—', 'phase' => 'Preclinical', 'status' => '', 'url' => ''],
          ['disease' => 'DMD (additional exons)', 'prevalence' => '', 'target' => 'Exons 53, 45, 44', 'phase' => 'Preclinical', 'status' => '', 'url' => ''],
        ],
      ]],
    ],
  ],

'clinical-trials' => [
    'title' => 'Clinical Trials',
    'alias' => '/clinical-trials',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Clinical trials to advance our',
        'field_title_line2' => 'lead programs',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Dyne Therapeutics is focused on delivering functional improvement for people living with genetically driven neuromuscular diseases.',
        'field_body_text' => <<<'HTML'
<p>Clinical trials, a type of research that studies the safety and efficacy of potential therapeutics in people, must be conducted before bringing new medicines to patients. The U.S. Food and Drug Administration (FDA) and other global healthcare regulatory agencies review preclinical data on investigational medicines before clinical trials may start.</p>
<p>Clinical trials would not be possible without the collaboration of the people living with the diseases we aim to treat, healthcare professionals and advocacy groups. If you or someone you know is interested in participating in one of our clinical trials, please see our active studies below:</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'HARMONIA — Phase 3 trial in DM1',
        'field_body_text' => <<<'HTML'
<p>HARMONIA is a global, randomized, placebo-controlled, double-blind, confirmatory Phase 3 clinical trial evaluating the efficacy, safety and tolerability of zeleciment basivarsen (z-basivarsen, also known as DYNE-101) in people living with myotonic dystrophy type 1 (DM1). The trial will enroll approximately 150 participants age 16 and older who will receive 6.8 mg/kg of z-basivarsen or placebo once every eight weeks for 48 weeks.</p>
<p>The primary endpoint of HARMONIA is the change from baseline in the five times sit to stand (5xSTS) test at week 49. Secondary endpoints include video hand opening time, quantitative muscle testing, the 10-meter walk/run test, the Myotonic Dystrophy Health Index, and additional patient- and clinician-reported outcomes.</p>
<p>For more information on the HARMONIA trial, visit <a href="https://www.clinicaltrials.gov">www.clinicaltrials.gov</a>.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'DELIVER — Phase 1/2 trial in DMD',
        'field_body_text' => <<<'HTML'
<p>Zeleciment rostudirsen (z-rostudirsen, also known as DYNE-251) is being evaluated in the global Phase 1/2 DELIVER clinical trial in people living with DMD amenable to exon 51 skipping.</p>
<p>The multiple ascending dose (MAD) portion of the study resulted in the selection of a registrational dose and regimen of 20 mg/kg of z-rostudirsen administered every four weeks. The placebo-controlled portion of the registrational expansion cohort (REC) to support a potential regulatory submission for U.S. Accelerated Approval has been completed. The primary endpoint for this cohort was the change from baseline in dystrophin protein levels as measured by Western blot at 6 months.</p>
<p>For more information on the DELIVER trial, visit <a href="https://www.clinicaltrials.gov">www.clinicaltrials.gov</a> and <a href="https://euclinicaltrials.eu">euclinicaltrials.eu</a>.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Patient access',
        'field_body_text' => <<<'HTML'
<p>Expanded access (sometimes referred to as “managed access” or “early access”) programs (EAP) refer to the use of an investigational medicine when the primary purpose is to diagnose, monitor, or treat apatient’s disease rather than to obtain the kind of information about the drug that is generally derived from clinical trials. Currently, Dyne does not have investigational medicines available through an expanded access program. We will continue to assess this as our clinical programs progress.</p>
<p>Dyne believes that participating in clinical trials is the best way for patients to access investigational medicines. These carefully controlled, rigorous scientific studies are needed to obtain regulatory approval,which will be critical for enabling the broadest access to treatment for aspecific condition. Dyne will strive to design trials that are inclusive of as many patients as possible,but we recognize that not every patient will be eligible for these trials.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Contact',
        'field_body_text' => <<<'HTML'
<p>For questions about Dyne Therapeutics’ clinical trials, please contact us at <a href="mailto:clinicaltrials@dyne-tx.com">clinicaltrials@dyne-tx.com</a>.</p>
<p>To connect with our Patient Advocacy team, please contact us at <a href="mailto:patientadvocacy@dyne-tx.com">patientadvocacy@dyne-tx.com</a>.</p>
HTML,
      ]],
    ],
  ],

'our-commitment' => [
    'title' => 'Our Commitment',
    'alias' => '/our-commitment',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Committed to the communities',
        'field_title_line2' => 'we serve',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => '',
        'field_body_text' => <<<'HTML'
      <p>At Dyne, we are committed to listening to and learning from the communities that we aim to serve, including individuals and families living with Duchenne muscular dystrophy (DMD), myotonic dystrophy type 1 (DM1) and facioscapulohumeral muscular dystrophy (FSHD). Their stories inspire us. Equally important, these insights guide our development of targeted therapies designed to deliver what patients value most: functional improvement.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Letters to Our Communities',
        'field_body_text' => <<<'HTML'
<p>Below are links to our correspondence with patient advocacy organizations representing individuals and families affected by the diseases we focus on. These materials address key events and topics.</p>
<ul>
<li><a href="https://www.dyne-tx.com/new-one-year-efficacy-data-on-z-basivarsen-dyne-101-for-dm1/">New One-Year Efficacy Data on Z-Basivarsen (DYNE-101) for DM1</a> (September 29, 2026)</li>
<li><a href="https://www.dyne-tx.com/message-to-the-global-myotonic-dystrophy-community">A Message from Dyne to the Global Myotonic Dystrophy Community</a> (September 21, 2026)</li>
<li><a href="https://www.dyne-tx.com/early-stage-fshd-clinical-trial">Dyne Receives FDA Clearance to Start Early-Stage FSHD Clinical Trial</a> (July 28, 2026)</li>
<li><a href="https://www.dyne-tx.com/fda-accepts-bla-for-z-rostudirsen">Together in Progress: FDA Accepts BLA for Z-Rostudirsen and Grants Priority Review</a> (July 20, 2026)</li>
<li><a href="https://www.dyne-tx.com/forzetto-trial-initiated">Zeleciment Rostudirsen Application Submitted for U.S. Accelerated Approval; Phase 3 Confirmatory FORZETTO Trial Initiated</a> (May 26, 2026)</li>
<li><a href="https://www.dyne-tx.com/our-commitment/new-clinical-trial-opportunity-in-dm1">New Clinical Trial Opportunity in DM1: HARMONIA Phase 3 Study Evaluating Zeleciment Basivarsen</a> (April 16, 2026)</li>
<li><a href="https://www.dyne-tx.com/community-letter-duchenne-added-to-newborn-screening">Historic Step Forward: Duchenne Added to Newborn Screening</a> (December 16, 2025)</li>
<li><a href="https://www.dyne-tx.com/community-letter-deliver-clinical-trial">Dyne Therapeutics announces positive topline results from Phase 1/2 DELIVER trial of z-rostudirsen in Duchenne muscular dystrophy (DMD)</a> (December 8, 2025)</li>
<li><a href="https://www.dyne-tx.com/community-letter-duchenne-muscular-dystrophy-community">Dyne receives FDA Breakthrough Therapy Designation for DYNE-251 for the treatment of patients with DMD</a> (August 4, 2025)</li>
</ul>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Rare Disease Day',
        'field_body_text' => <<<'HTML'
<p>Rare Disease Day aims to raise awareness of the impact that rare diseases have on more than 300 million people around the world. In honor of Rare Disease Day, Dyne hosted members of the neuromuscular disease community to reflect on the 2026 theme: “More Than You Can Imagine.” This theme captures the essential, often unseen perseverance of those living with rare conditions.</p>
<p>In prior years, Dyne hosted members of the neuromuscular disease community, Dynamos and their families, and welcomed guest speakers including Rick Guidotti of Positive Exposures. Community members living with rare diseases shared their experiences and insights with our Dynamos.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'World FSHD Day',
        'field_body_text' => <<<'HTML'
<p>Every year on June 20th, we join the FSHD community in recognizing World FSHD Day. In recognition of World FSHD Day, Dynamos share orange slice selfies to support the FSHD Society’s annual campaign to build awareness of facial weakness experienced by some people living with FSHD that can make smiling difficult.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'World Duchenne Awareness Day',
        'field_body_text' => <<<'HTML'
<p>Every year on September 7th, we join the Duchenne community in recognizing World Duchenne Awareness Day.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'International Myotonic Dystrophy Awareness Day',
        'field_body_text' => <<<'HTML'
<p>Every year on September 15th, we mark International Myotonic Dystrophy Awareness Day, joining the Myotonic Dystrophy community and focusing on therapies that are designed to deliver functional improvement for individuals living with DM1.</p>
HTML,
      ]],
    ],
  ],

  'resources' => [
    'title' => 'Resources',
    'alias' => '/resources',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Resources for people living with genetically driven',
        'field_title_line2' => 'neuromuscular diseases',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'For more information about DM1, DMD, and FSHD, see the resources below*:',
        'field_body_text' => <<<'HTML'
<h3>DM1 — US</h3>
<ul><li>Myotonic Dystrophy Foundation</li><li>Muscular Dystrophy Association</li><li>National Organization for Rare Disorders</li><li>National Institutes of Health – Myotonic Dystrophy</li></ul>
<h3>DM1 — Ex-US</h3>
<ul><li>Cure Myotonic Dystrophy</li><li>UK Myotonic Dystrophy Support Group – UK</li><li>Treat-NMD – UK</li><li>Euro-Dyma – Europe</li><li>Muscular Dystrophy UK</li><li>Myotonic Dystrophy Patients’ Group of Japan</li></ul>
<h3>DMD — US</h3>
<ul><li>Charley’s Fund</li><li>CureDuchenne</li><li>Jett Foundation</li><li>Parent Project Muscular Dystrophy</li><li>Muscular Dystrophy Association</li><li>National Institutes of Health-Duchenne Muscular Dystrophy</li><li>National Organization for Rare Disorders</li></ul>
<h3>DMD — Ex-US</h3>
<ul><li>Defeat Duchenne Canada</li><li>Action Duchenne – UK</li><li>French Muscular Dystrophy Association (AFM)</li><li>Duchenne UK</li><li>Muscular Dystrophy UK</li><li>Treat-NMD – UK</li><li>Parent Project Italy</li><li>Progena</li><li>Swiss Duchenne Foundation</li></ul>
<h3>FSHD — US</h3>
<ul><li>FSHD Society</li><li>Friends of FSH Research</li><li>Chris Carrino Foundation for FSHD</li><li>Muscular Dystrophy Association</li><li>National Organization for Rare Disorders</li></ul>
<h3>FSHD — Ex-US</h3>
<ul><li>Amis FSH – France</li><li>Treat-NMD – UK</li><li>French Muscular Dystrophy Association (AFM)</li><li>Muscular Dystrophy UK</li><li>FSHD Europe</li><li>FSHD Canada Foundation</li></ul>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => '',
        'field_body_text' => <<<'HTML'
<p><em>*Viewing these links requires you to leave Dyne’s website. Dyne assumes no responsibility for the information, statements or other content you may encounter outside of Dyne’s website.</em></p>
HTML,
      ]],
    ],
  ],

  'dmd' => [
    'title' => 'DMD',
    'alias' => '/dmd',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'DMD Stories',
        'field_title_line2' => '',
        'field_banner_text' => 'Each person living with DMD has a unique and powerful story to tell',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => '',
        'field_body_text' => <<<'HTML'
<p>Living with DMD has profound impacts on every part of people’s lives, including work, social interactions and family relationships. Community members have shared their moving perspectives of living with Duchenne with our team and have told us what it would mean to them to have a potentially transformative therapeutic for their disease. These insights are guiding our efforts to develop therapies that deliver meaningful functional improvement for people living with DMD.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Meet our community',
        'field_body_text' => <<<'HTML'
<ul>
<li><strong>Meet Catherine and Alan</strong> — Catherine and Alan share their journey living with DMD and their hopes for the future (<a href="https://www.youtube.com/embed/1jIRAJkiXrY">Watch video</a>)</li>
<li><strong>Meet Diana and Jordan</strong> — Diana shares her family’s journey with Duchenne (<a href="https://www.youtube.com/embed/apwkz3L7DVg">Watch video</a>)</li>
<li><strong>Meet Ravi</strong> — “I want to be someone:” Ravi’s journey with DMD (<a href="https://www.dyne-tx.com/dmd-story-ravi">Read story</a>)</li>
</ul>
HTML,
      ]],
    ],
  ],

'dm1' => [
    'title' => 'DM1',
    'alias' => '/dm1',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'DM1 Stories',
        'field_title_line2' => '',
        'field_banner_text' => 'Each person living with DM1 has a unique and powerful story to tell',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => '',
        'field_body_text' => <<<'HTML'
<p>Living with DM1 has profound impacts on every part of people’s lives, including work, social interactions and family relationships. Community members have shared their moving perspectives of living with DM1 with our team, including what it would mean to them to have a potentially transformative therapeutic for their disease. These insights are guiding our efforts to develop therapies that deliver meaningful functional improvement for people living with DM1.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Meet our community',
        'field_body_text' => <<<'HTML'
<ul>
<li><strong>Emily</strong> — I’m Emily, a broadcast journalist turned advocate living with myotonic dystrophy type 1 (DM1). (<a href="https://www.dyne-tx.com/dm1-story-emily/">Read story</a>)</li>
<li><strong>Sarah</strong> — Sarah shares the impact DM1 has on her and her family and why a potentially transformative therapy is urgently needed (<a href="https://youtu.be/5ednXRKHnIc">Watch video</a>)</li>
<li><strong>BillyDean and Suzette</strong> — Through their resilience and Suzette’s incredible advocacy efforts for her son, BillyDean, they succeeded in getting a DM1 diagnosis and have been learning how to live and thrive ever since (<a href="https://www.dyne-tx.com/dm1-story-billydean">Read story</a>)</li>
<li><strong>Joachim and Tim</strong> — Joachim and Tim share their personal stories on living with DM1 (<a href="https://www.youtube.com/embed/Oov_breRaxs">Watch video</a>)</li>
<li><strong>Joachim</strong> — “I’m not alone in this:” One person’s journey with DM1 (<a href="https://www.dyne-tx.com/dm1-story-joachim">Read story</a>)</li>
<li><strong>Loraine</strong> — A courageous family battle and devotion in the face of generational adversity (<a href="https://www.dyne-tx.com/dm1-story-loraine">Read story</a>)</li>
</ul>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Sign up for more information about DM1',
        'field_body_text' => <<<'HTML'
<p>We’ll use the information you provide to share updates from Dyne, including disease and product information, education, resources and other relevant updates.</p>
HTML,
      ]],
    ],
  ],

  'fshd' => [
    'title' => 'FSHD',
    'alias' => '/fshd',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'FSHD Stories',
        'field_title_line2' => '',
        'field_banner_text' => 'Each person living with FSHD has a unique and powerful story to tell',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => '',
        'field_body_text' => <<<'HTML'
<p>Living with FSHD has profound impacts on every part of peoples’ lives, including work, social interactions and family relationships. Community members have shared their moving perspectives of living with FSHD with our team and have told us what it would mean to them to have a potentially transformative therapeutic for their disease. These insights are guiding our efforts to develop therapies that deliver meaningful functional improvement for people living with FSHD.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Meet our community',
        'field_body_text' => <<<'HTML'
<ul>
<li><strong>Laura and Chelsea</strong> — Joint perspective of a mother and daughter’s journey with FSHD (<a href="https://www.youtube.com/embed/3ipgGAeYmMg">Watch video</a> · <a href="https://www.dyne-tx.com/fshd-story-chelsea">Read story</a>)</li>
<li><strong>Rachel</strong> — “If there was a treatment for FSHD, it would be life-changing for me.” (<a href="https://www.youtube.com/embed/ETAkHmDghXs">Watch video</a>)</li>
<li><strong>Ian</strong> — “So many therapies in development right now. I’m very, very excited.” (<a href="https://youtube.com/embed/KgDQLs-2Oas">Watch video</a>)</li>
<li><strong>Sam</strong> — “I would love to see some type of therapy that would regrow muscle tissue.” (<a href="https://www.youtube.com/embed/k6fS0ZqSr_4">Watch video</a>)</li>
</ul>
HTML,
      ]],
    ],
  ],

'dyne-251-for-dmd' => [
    'title' => 'DYNE-251 for DMD',
    'alias' => '/dyne-251-for-dmd',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Advancing a robust portfolio focused on',
        'field_title_line2' => 'muscle diseases',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'DYNE-251, an investigational therapeutic for people living with Duchenne muscular dystrophy (DMD)',
        'field_body_text' => <<<'HTML'
<p>DYNE-251 is an investigational therapeutic being evaluated in the Phase 1/2 global DELIVER clinical trial for people living with DMD who are amenable to exon 51 skipping.</p>
<p>DYNE-251 consists of a phosphorodiamidate morpholino oligomer (PMO) conjugated to a fragment antibody (Fab) that binds to the transferrin receptor 1 (TfR1) which is highly expressed on muscle. It is designed to enable targeted muscle tissue delivery and promote exon skipping in the nucleus, allowing muscle cells to create a truncated, functional dystrophin protein, with the goal of stopping or reversing disease progression.</p>
<p>DYNE-251 has been granted fast track, orphan drug and rare pediatric disease designations for the treatment of DMD mutations amenable to exon 51 skipping by the US Food and Drug Administration. In addition to DYNE-251, Dyne is building a global DMD franchise and has preclinical programs targeting other exons, including 53, 45 and 44.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Learn more about DELIVER',
        'field_body_text' => <<<'HTML'
<p>The Phase 1/2 global DELIVER clinical trial is evaluating DYNE-251 in people living with DMD amenable to exon 51 skipping.</p>
HTML,
        'field_link' => ['uri' => 'internal:/clinical-trials', 'title' => 'Learn more about DELIVER'],
      ]],
      ['type' => 'dyne_drug_detail', 'fields' => [
        'field_heading' => 'About DMD',
        'field_body_text' => <<<'HTML'
<p>DMD is a rare genetic muscle disease. It occurs primarily in males, although females can be affected and they also pass the genetic mutation onto their children. DMD affects an estimated 12,000 to 15,000 people in the US and 25,000 in Europe.</p>
HTML,
        'field_sections_json' => [
          ['title' => 'Onset', 'body' => 'DMD is the most common childhood-onset form of muscular dystrophy. Loss of strength and function typically first appear between 3 and 5 years of age.'],
          ['title' => 'Cause', 'body' => 'DMD results from a genetic mutation in the DMD gene on the X chromosome. This gene regulates the production of dystrophin, a protein essential to healthy muscle development and function. In people with DMD, dystrophin levels are absent or nearly absent, which causes permanent damage to muscle cells.'],
          ['title' => 'Symptoms', 'body' => 'People living with DMD experience a range of symptoms, which may include: progressive muscle weakness that worsens over time, usually beginning in the upper arms, upper legs and pelvic area and progressing to affect the lower legs, forearms, neck and trunk; enlarged calf muscles; delays in developing motor skills such as sitting, standing or walking; toe walking or waddling gait; breathing problems; cardiomyopathy, or inflammation of the muscles in the heart; and varying degrees of cognitive impairment affecting approximately one third of patients.'],
          ['title' => 'Treatment', 'body' => 'There are no transformative therapies and no cure for DMD. Thus, there is a significant need for new treatment options for the disease.'],
        ],
      ]],
    ],
  ],

'dyne-101-for-dm1' => [
    'title' => 'DYNE-101 for DM1',
    'alias' => '/dyne-101-for-dm1',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Advancing a robust portfolio focused on',
        'field_title_line2' => 'muscle diseases',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'DYNE-101, an investigational therapeutic for people living with myotonic dystrophy type 1 (DM1)',
        'field_body_text' => <<<'HTML'
<p>DYNE-101 is an investigational therapeutic being evaluated in the Phase 1/2 global ACHIEVE clinical trial for people living with DM1.</p>
<p>DYNE-101 consists of an antisense oligonucleotide (ASO) conjugated to a fragment antibody (Fab) that binds to the transferrin receptor 1 (TfR1) which is highly expressed on muscle. DYNE-101 is designed to address the genetic basis of DM1 by reducing the levels of mutant DMPK RNA in the nucleus, releasing splicing proteins, allowing normal mRNA processing and translation of normal proteins and potentially stopping or reversing disease.</p>
<p>DYNE-101 has been granted orphan drug designation for the treatment of DM1 by the European Medicines Agency and the US Food and Drug Administration.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Learn more about ACHIEVE',
        'field_body_text' => <<<'HTML'
<p>The Phase 1/2 global ACHIEVE clinical trial is evaluating DYNE-101 in people living with DM1.</p>
HTML,
        'field_link' => ['uri' => 'internal:/clinical-trials', 'title' => 'Learn more about ACHIEVE'],
      ]],
      ['type' => 'dyne_drug_detail', 'fields' => [
        'field_heading' => 'About DM1',
        'field_body_text' => <<<'HTML'
<p>DM1 is a rare, genetic, progressive muscle disease estimated to affect >40,000 people in the US and >74,000 people in Europe. The severity of symptoms and pace of progression varies.</p>
HTML,
        'field_sections_json' => [
          ['title' => 'Onset', 'body' => 'Symptoms can begin at any point in an affected person’s life, depending on the DM1 subtype. Adult-onset DM1 symptoms typically appear in early teens to 50 years of age.'],
          ['title' => 'Cause', 'body' => 'The underlying cause of DM1 is a defective DMPK gene that encodes a mutated RNA. DM1 is known as a triplet repeat disease because of the expanded number of CTG repeats in the DMPK gene. These CTG repeats cause toxic RNA to cluster in the nucleus. The toxic RNA binds to RNA splicing proteins, causing altered splicing events in many other genes. This altered splicing results in a wide range of disease symptoms and contributes to the significant disease burden.'],
          ['title' => 'Symptoms', 'body' => 'People experience a broad spectrum of symptoms, including: muscle weakness throughout the body, from difficulties with facial and swallowing muscles to hand and grip weakness to lower leg and foot weakness, foot drop and resulting mobility issues; myotonia, or difficulty relaxing muscles in the jaw, tongue, throat, lower arm and hand; respiratory issues; gastrointestinal dysfunction throughout the GI tract; cognitive deficits, excessive daytime sleepiness and dysregulated sleep; and early cataracts.'],
          ['title' => 'Treatment', 'body' => 'There are currently no disease-modifying treatments for DM1.'],
        ],
      ]],
    ],
  ],

'dyne-302-for-fshd' => [
    'title' => 'DYNE-302 for FSHD',
    'alias' => '/dyne-302-for-fshd',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Advancing a robust portfolio focused on',
        'field_title_line2' => 'muscle diseases',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'DYNE-302, a product candidate for people living with facioscapulohumeral muscular dystrophy (FSHD)',
        'field_body_text' => <<<'HTML'
<p>DYNE-302 is Dyne’s product candidate being developed for people living with FSHD. DYNE-302 consists of a fragment antibody (Fab) that binds to the transferrin receptor 1 (TfR1) which is highly expressed on muscle, conjugated to an siRNA designed against DUX4 mRNA.</p>
<p>Dyne has generated comprehensive preclinical data supporting its FSHD program demonstrating robust and durable DUX4 suppression and functional benefit in both in vitro and an innovative in vivo model developed by Dyne.</p>
HTML,
      ]],
      ['type' => 'dyne_drug_detail', 'fields' => [
        'field_heading' => 'About FSHD',
        'field_body_text' => <<<'HTML'
<p>FSHD is a rare disease characterized by progressive loss of skeletal muscle, which causes profound weakness. It is estimated to affect approximately 16,000 to 38,000 people in the US and 35,000 people in Europe.</p>
HTML,
        'field_sections_json' => [
          ['title' => 'Onset', 'body' => 'There is wide variability in age of onset with FSHD. Symptoms typically begin to appear in the teen years. In some cases, individuals do not begin to experience the muscle weakness characteristic of FSHD until well into adulthood. A very rare form of FSHD, often called infantile FSHD or IFSHD, is particularly debilitating, with symptoms beginning in infancy or early childhood.'],
          ['title' => 'Cause', 'body' => 'FSHD is caused by aberrant expression of a gene called DUX4. In a healthy individual, DUX4 is active for only a short time in early embryonic development. In individuals with FSHD, the DUX4 gene remains “on” long after it is supposed to be silenced. This activation leads to surplus production of the DUX4 protein, which causes the gradual destruction of muscle cells throughout the body.'],
          ['title' => 'Symptoms', 'body' => 'The symptoms of FSHD often emerge first with a loss of facial muscle strength, making it difficult to smile or use a straw. Weakness typically progresses to all major muscle groups including the arms, torso, legs, and abdomen and can lead to limited mobility. The severity of symptoms varies widely. People living with FSHD can also experience joint and spinal abnormalities, including protrusion of the shoulder blades.'],
          ['title' => 'Treatment', 'body' => 'Although the genetic cause of FSHD is well understood,there are currently no approved disease-modifying treatments for FSHD.'],
        ],
      ]],
    ],
  ],

'our-forcetm-publications' => [
    'title' => 'Scientific Publications & Presentations',
    'alias' => '/our-forcetm-publications',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'The power to target muscle and the central nervous system',
        'field_title_line2' => 'to address the root cause of disease',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Scientific Publications & Presentations',
        'field_body_text' => <<<'HTML'
<h3>September 29 – October 2, 2026 — American Association of Neuromuscular &amp; Electrodiagnostic Medicine</h3>
<ul><li>Long-Term Functional Improvement with Zeleciment Basivarsen in the Phase 1/2 ACHIEVE Trial <a href="https://www.dyne-tx.com/wp-content/uploads/Andresson__2026_AANEM_ACHIEVE_Poster.pdf">Poster</a> · <a href="https://www.dyne-tx.com/wp-content/uploads/Andresson__2026_AANEM_ACHIEVE_Poster_Additional_Data.pdf">Additional data</a></li></ul>
<h3>September 29 – October 3, 2026 — 31st Annual International Congress of the World Muscle Society</h3>
<ul><li>Long-Term Functional Improvement With Zeleciment Basivarsen in the Phase 1/2 ACHIEVE Trial <a href="https://www.dyne-tx.com/wp-content/uploads/Mul_2026_WMS_ACHIEVE_ORAL.pdf">Oral presentation</a> · <a href="https://www.dyne-tx.com/wp-content/uploads/Mul_2026_WMS_ACHIEVE_ORAL_APPENDIX.pdf">Additional data</a></li></ul>
<h3>September 25, 2026 — RNA Leaders</h3>
<ul><li><a href="https://www.dyne-tx.com/wp-content/uploads/Natoli_2026_RNA-Leaders_DYNE-302_Presentation.pdf">DYNE-302 Leads to Functional Improvement and Resolves Muscle Transcriptomic Changes in Mouse Models of FSHD</a></li></ul>
<h3>September 25-27, 2026 — 2026 Annual Neuromuscular Study Group Scientific Meeting (NMSG)</h3>
<ul><li><a href="https://www.dyne-tx.com/wp-content/uploads/Desjardins_2026_NMSG_-DMD-Franchise_Poster.pdf">Delivering for DMD: Leveraging the FORCE Platform to Deliver Exon Skipping Oligonucleotides for a Broad Set of DMD Patients</a></li></ul>
<h3>July 7-11, 2026 — 19th International Congress on Neuromuscular Diseases (ICNMD)</h3>
<ul><li><a href="https://www.dyne-tx.com/wp-content/uploads/Naylor_2026_ICNMD_FORZETTO.pdf">FORZETTO, a Phase 3 Study of Z-Rostudirsen in Ambulatory Males with Exon 51 Amenable DMD</a></li></ul>
<h3>May 11-15, 2026 — American Society of Gene &amp; Cell Therapy (ASGCT) Annual Meeting</h3>
<ul><li><a href="https://www.dyne-tx.com/wp-content/uploads/Correia_2026_ASGCT_MAPT.pdf">Targeting Tauopathies: Robust and Widespread MAPT Silencing in CNS of Mice and NHP with TfR1-mediated Oligonucleotide Delivery</a></li></ul>
<h3>March 22-25, 2026 — Society of Toxicology</h3>
<ul><li><a href="https://www.dyne-tx.com/wp-content/uploads/Davis_SOT_March_2026.pdf">The FORCE™ Platform Does Not Cause Anemia in Repeat-Dose Preclinical Toxicity Studies</a></li></ul>
<h3>March 8-11, 2026 — Muscular Dystrophy Association (MDA) Clinical &amp; Scientific Conference</h3>
<ul>
<li>Zeleciment Rostudirsen Led to Trends in Long-Term Improvement in Clinical Outcomes Including Cardiopulmonary Function: Additional Data from DELIVER</li>
<li>Zeleciment Rostudirsen Significantly Increased Dystrophin Protein Levels and Led to Functional Improvement in Clinical Measures in the DELIVER Trial</li>
<li>A Global Phase 3 Trial Assessing the Efficacy and Safety of Z-Basivarsen in Myotonic Dystrophy Type 1</li>
<li>DYNE-401 Demonstrates Potential to Address Pompe Disease with Low and Infrequent Dosing</li>
<li>Zeleciment Basivarsen Targets the Underlying Cause of DM1 to Enable Functional Improvement in the Phase 1/2 ACHIEVE Trial</li>
<li>Dyne-Sponsored Symposium: Functional Improvement: Moving Beyond Dystrophin in DMD</li>
</ul>
<h3>February 28, 2026 — Journal of Medical Economics</h3>
<ul><li>Clinical and Healthcare Burden of Myotonic Dystrophy Type 1 (DM1) in the United States: A Claims-Based Study</li></ul>
<h3>February 23, 2026 — Nucleic Acid Insights</h3>
<ul><li>Reaching the CNS with Therapeutic Oligonucleotides via TfR1: Navigating Delivery and Distribution Challenges</li></ul>
<h3>October 19-21, 2025 — 2025 NORD Breakthrough Summit</h3>
<ul><li>Health Insurance Literacy in Myotonic Dystrophy Type 1 (DM1), an Interim Report</li></ul>
<h3>October 7-11, 2025 — 30th Annual International Congress of the World Muscle Society</h3>
<ul><li>Dyne Sponsored Symposium: Achieving Functional Improvement in Myotonic Dystrophy Type1 (DM1) – Defining Goals of Treatment</li></ul>
<h3>May 7-10, 2023 — ISPOR 2023</h3>
<ul><li>The Humanistic Burden of Myotonic Dystrophy Type1: A Literature Review</li></ul>
<h3>April 22-27, 2023 — American Academy of Neurology (AAN) Annual Meeting</h3>
<ul><li>ACHIEVE Trial, a Randomized, Placebo-Controlled, Multiple Ascending Dose Study of DYNE-101 in Individuals With Myotonic Dystrophy Type 1 (DM1)</li></ul>
<h3>October 11-15, 2022 — 27th International Hybrid Annual Congress of the World Muscle Society</h3>
<ul><li>Building a FORCE™ platform-based DMD franchise for the treatment of individuals with mutations amenable to exon skipping</li></ul>
<h3>August 10, 2022 — Nucleic Acids Research (NAR)</h3>
<ul><li>Enhanced exon skipping and prolonged dystrophin restoration achieved by TfR1-targeted delivery of antisense oligonucleotide using FORCE conjugation in mdx mice</li></ul>
<h3>May 14, 2021 — ASGCT Annual Meeting</h3>
<ul><li>Splice Correction and Reduction of Toxic DMPK RNA In Vitro and In Vivo Utilizing Novel Antibody Targeted Antisense Oligonucleotides</li></ul>
HTML,
      ]],
    ],
  ],

'careers-culture' => [
    'title' => 'Careers + Culture',
    'alias' => '/careers-culture',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Our commitment to people with neuromuscular diseases',
        'field_title_line2' => 'is our greatest strength',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'We are fearless innovators on a mission to deliver functional improvement for individuals, familiesand communities.',
        'field_body_text' => <<<'HTML'
<p>We pursue our goals with passion,determination,agilityand urgency,recognizing that countless lives depend on our efforts. We are here to forever change lives. We know we will deliver.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Our core values',
        'field_body_text' => <<<'HTML'
<ul>
<li><strong>Delivering for patients</strong> — Our purpose: Functional improvement for people living with neuromuscular diseases</li>
<li><strong>One Dyne community</strong> — Our commitment: Thrive through resiliency,transparency,respect,inclusionand honesty</li>
<li><strong>One mission,no ego</strong> — Our mindset: United in purpose,selfless in action</li>
<li><strong>Embrace accountability &amp; excellence</strong> — Our pledge: Own the enterprise outcome,deliver with excellence</li>
<li><strong>Fearless innovation</strong> — Our resolve: Think bigger,act bolder,aim higherand never fear failure</li>
</ul>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'We’re looking for colleagues who share this ethos',
        'field_body_text' => <<<'HTML'
<p>We’re looking for colleagues who share this ethos to join Dyneand help shape one of the most innovative and impactful biotech companies. If you’re driven to deliver for people living with neuromuscular diseases; if you put teamand mission first; if you embrace bold ideas; if you are committed to accountabilityand transparency; if you have adrive to make adifference,we’d love to connect.</p>
HTML,
        'field_link' => ['uri' => 'internal:/careers-culture#positions', 'title' => 'Connect'],
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Hear from our Dynamos',
        'field_anchor' => 'dynamic',
        'field_body_text' => <<<'HTML'
<p>We are on amission to deliver functional improvement for individuals,familiesand communities. The foundation of our efforts is driven by our exceptional team of Dynamosand their dedication to fostering an unparalleled culture. Hear Dynamos share their perspectives on their biotech careersand what makes Dyne aspecial place to work.</p>
<ul>
<li>Collaborative Culture — Watch video</li>
<li>Commitment to Innovation — Watch video</li>
<li>Passionate and Driven Team — Watch video</li>
</ul>
HTML,
      ]],
      ['type' => 'dyne_quote_carousel', 'fields' => [
        'field_heading' => 'Voices from our Dynamos',
        'field_items_json' => [
          ['quote' => 'What I find most rewarding about acareer in science is that it allows me to actively contribute to solving real-world problems. At Dyne,I’m part of agroundbreaking team finding solutions to rare neuromuscular diseases. I’m proud to utilize my skills here as I nurture my love for continuous learning and intellectual growth.', 'author' => 'Oreoluwa Ade-army'],
        ],
      ]],
['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Competitive benefits',
        'field_anchor' => 'benefits',
        'field_body_text' => <<<'HTML'
<p>We offer a comprehensive benefits package designed to support the health, wellbeing and financial security of our Dynamos and their families. These benefits are designed to help you thrive inside and outside of work.</p>
<ul>
<li>Competitive compensation and equity</li>
<li>Comprehensive medical, dental and vision insurance</li>
<li>401(k) retirement plan with company match</li>
<li>Generous paid time off and family leave</li>
<li>Professional development and learning opportunities</li>
</ul>
HTML,
      ]],
      ['type' => 'dyne_jobs_list', 'fields' => [
        'field_heading' => 'Open positions',
        'field_anchor' => 'positions',
        'field_items_json' => [
          ['title' => 'Associate Director, Talent Acquisition Operations &amp; Programs', 'department' => 'HR', 'location' => 'Waltham MA', 'url' => 'https://www.dyne-tx.com/jobs/associate-director-talent-acquisition-operations-programs/'],
          ['title' => 'Director, Clinical Scientist', 'department' => 'Clinical', 'location' => 'Remote', 'url' => 'https://www.dyne-tx.com/jobs/director-clinical-scientist/'],
          ['title' => 'Dyne Care Partner, Atlanta', 'department' => 'Market Access', 'location' => 'Atlanta GA', 'url' => 'https://www.dyne-tx.com/jobs/dyne-care-partner-atlanta/'],
          ['title' => 'Key Account Manager, Northeast Metro', 'department' => 'Commercial', 'location' => 'Northeast Metro', 'url' => 'https://www.dyne-tx.com/jobs/key-account-manager-northeast-metro/'],
          ['title' => 'Thought Leader Liaison, West', 'department' => 'Medical Affairs', 'location' => 'West', 'url' => 'https://www.dyne-tx.com/jobs/thought-leader-liaison-west/'],
          ['title' => 'Scientist, RNA Chemistry', 'department' => 'Platform', 'location' => 'Waltham MA', 'url' => 'https://www.dyne-tx.com/jobs/scientist-rna-chemistry/'],
          ['title' => 'Senior Director, Quality Control', 'department' => 'Quality', 'location' => 'Waltham MA', 'url' => 'https://www.dyne-tx.com/jobs/senior-director-quality-control/'],
          ['title' => 'VP, Head of Procurement', 'department' => 'Procurement', 'location' => 'Waltham MA', 'url' => 'https://www.dyne-tx.com/jobs/vp-head-of-procurement/'],
        ],
      ]],
    ],
  ],

'corporate-responsibility' => [
    'title' => 'Corporate Responsibility',
    'alias' => '/corporate-responsibility',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Corporate responsibility',
        'field_title_line2' => '',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => '',
        'field_body_text' => <<<'HTML'
<p>Dyne Therapeutics is a clinical-stage company focused on delivering functional improvement for people living with genetically driven neuromuscular diseases by addressing the root cause of disease through its FORCE™ platform. The platform uses an antigen-binding fragment (Fab) that leverages transferrin receptor1 (TfR1) to enable broad distribution to muscleand the central nervous system (CNS). We have assembled an exceptional team of employees focused on fostering adifferentiated cultureand working toward delivering functional improvement for individuals,familiesand communities.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Key corporate responsibility initiatives',
        'field_body_text' => <<<'HTML'
<ul>
<li>Exceptional Team: Fostering a Differentiated Culture</li>
<li>Delivering for Patients &amp; Community</li>
<li>Protecting the Environment</li>
<li>Corporate Governanceand Operating our Business with Integrity</li>
</ul>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Exceptional team fostering a differentiated culture',
        'field_body_text' => <<<'HTML'
<p>At Dyne we are committed to attracting,retaining,and developing our employees(Dynamos) through comprehensive initiatives centered on enhancing engagement in our business and culture. As of the end of2025,we employed approximately251 Dynamos. We leverage avariety of networks,including our own Dynamos,to recruit potential candidatesand promote equal opportunityin how we evaluate candidates for roles based on technical qualificationsas well as fit within our organization.</p>
<p>New employees benefit from arobust onboarding program that includes acomprehensive online platform accessible from the moment that acandidate accepts their offer to prepare new Dynamos for their first dayand beyond. Beyond onboarding,professional development takes on many forms at Dyne.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Corporate governance',
        'field_body_text' => <<<'HTML'
<ul>
<li>Separate role for CEOand Chairman of the Board</li>
<li>8 of9 Directors qualify as independent according to the applicable Nasdaq listing standardsand rulesand regulations of the U.S. Securitiesand Exchange Commission</li>
<li>In2025,each Director attended75% or more of the aggregate number of meetings of the Board and the committeeson which each director served</li>
<li>Directorsand employees are responsible for adhering to the company’s Code of Business Conductand Ethics</li>
</ul>
<p>Employeesand directors must report any concerns with compliance with corporate policies internally or with appropriate regulatory authoritiesand can do so openly or anonymously. This includes through the Dyne Therapeutics hotline at <a href="https://www.whistleblowerservices.com/DYN">https://www.whistleblowerservices.com/DYN</a> or utilizing atoll-free telephone number877-306-4941.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Our Continued Commitment',
        'field_body_text' => <<<'HTML'
<p>At Dyne,we are committed to delivering meaningful functional improvement for people living with genetically driven neuromuscular diseases. As amission-driven organization,we pursue this goal with ashared sense of purposeand aculture grounded in transparency,respectand honesty.</p>
HTML,
      ]],
    ],
  ],

'perspectives' => [
    'title' => 'Perspectives',
    'alias' => '/perspectives',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Perspectives',
        'field_title_line2' => '',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => '',
        'field_body_text' => <<<'HTML'
<p>We are passionate about using our voices to advance our mission of developing life-transforming therapies for people living with serious muscle diseases.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'From our Dynamos',
        'field_body_text' => <<<'HTML'
<ul>
<li><strong>April  Ả24,,  Ả2023</strong> — Cultivating a Dynamic Culture Centered Around Purpose-Driven Values, by Kate Mitchell, Vice President, Head of Human Resources</li>
<li><strong>Sep  Ả15,,  Ả2022</strong> — Lessons Learned on Preparing to Weather the Inevitable Challenges in Biotech, by Joshua Brumm, President &amp; CEO of Dyne</li>
<li><strong>Oct  Ả7,,  Ả2021</strong> — From the Trenches: A LifeSci VC blog, by Josh Brumm</li>
<li><strong>Sep  Ả15,,  Ả2021</strong> — Starting a New Tradition of Support, by Molly White, Dyne’s vice president, global head of patient advocacyand engagement</li>
<li><strong>Jul  Ả8,,  Ả2021</strong> — Playing To Win: Building a Position of Strength in Biotech, by Josh Brumm</li>
<li><strong>April  Ả28,,  Ả2021</strong> — Toward abrighter future: targeting the genetic basis of rare muscle diseases, by Romesh Subramanian,Ph.D.</li>
<li><strong>April  Ả8,,  Ả2021</strong> — Scaling culture with growth: how to maintain your startup’s “special sauce”, by Joshua Brumm</li>
<li><strong>Jan  Ả8,,  Ả2021</strong> — Preparing to pitchin avirtual world,, Life Science Leader panel featuring Joshua Brumm</li>
<li><strong>Dec  Ả16,,  Ả2020</strong> — Beyond the balance sheet: thinking broadly about dilutionin building atransformative biotech, by Joshua Brumm</li>
<li><strong>Dec  Ả14,,  Ả2020</strong> — Voice of the patient –the biopharma perspective, by Molly White</li>
</ul>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Stay connected',
        'field_body_text' => <<<'HTML'
<p>Stay connected with us on LinkedIn, X and Facebook.</p>
HTML,
      ]],
    ],
  ],

'community-letter' => [
    'title' => 'Community Letter',
    'alias' => '/community-letter',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Our Focus: Letters to Our Communities',
        'field_title_line2' => '',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'A Letter to the Myotonic and Duchenne Muscular Dystrophy Communities',
        'field_body_text' => <<<'HTML'
<p><strong>February  Ả2024</strong></p>
<p><strong>Our Commitment</strong> — Dyne has alongstanding commitment to engaging people living with rare muscle diseasesand including their expertiseand insightsin our therapy development work. It’s been acornerstone of the company’s approach since our foundingand is evidentin the myriad ways we have involved myotonic dystrophy type1 (DM1,, Duchenne muscular dystrophy (DMD) and facioscapulohumeral dystrophy(FSHD) community members, in clinical trial design advisory workshops,panel presentationsand webinars,and community conversationsand listening sessions,hosted both at Dyneand in the community.</p>
<p><strong>Your Experience Matters</strong> — We are committed to listening to and learning from the communities we serve,because your stories inspire usand because they provide insightsand information about disease burdenand meaningful benefit that we know is critical to developing truly transformative therapies. Our community engagement work has been particularly significant with respect to the development of our current global clinical trials,ACHIEVE(for DM1) and DELIVER(for DMD. Community feedback leads to more thoughtful and sensitive trial designsand drives more informativeand meaningful outcomes from clinical studies.</p>
<p><strong>We Captured and Implemented Your Expertise</strong> — For these reasons,we designed amulti-pronged process to capture input from individuals living with DM1 and Duchenne to inform the design of our ACHIEVEand DELIVER trialsand to decrease the difficulties that participantsand families might experience participatingin them. In response to workshop feedback,we refined our trial protocoland clinic visit designand developed atravel service program that takes into account the burden of clinical trial travel,the possibility of home visits when feasible,and allows for adequate rest before clinic visit initiationand between assessments.</p>
<p><strong>We have Published your Feedback</strong> — Few publications existthat provide comprehensive insights from DM1 and DMD community members about the impact,preferencesand burden associated with clinical trial participation. So Dyne invited patient advocacy leader Patricia Furlong,Founder and CEO of Parent Project Muscular Dystrophy(PPMD,,to join usin capturing and documenting the workshop discussionsand conclusionsin anewly published articlein Research Involvementand Engagement titled:“Patient Engagement in Clinical Trial Design for Rare Neuromuscular Disorders: Impact on the DELIVERand ACHIEVE Clinical Trials.”</p>
<p><strong>The Conversation Continues</strong> — We are committed to ongoing communication with the DM1,DMDand FSHD communities to stay current with disease community perspectivesand feedback. You can also reach out to us directly at communityinquiries@dyne-tx.com.</p>
<p><strong>Thank You</strong> — Our therapy development effortsand the other critical studies being conducted to better understand serious muscle diseases,progressionand symptom burdenwould not be possible without the generous participation of community members like you. We sincerely recognizeand acknowledge the hard workand feedback provided by community participants,and we are deeply grateful. Thank you.</p>
<p>Sincerely,<br />Dyne Therapeutics</p>
HTML,
      ]],
    ],
  ],

'privacy-policy' => [
    'title' => 'Privacy Policy',
    'alias' => '/privacy-policy',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Privacy Policy',
        'field_title_line2' => '',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Dyne Therapeutics, Inc. Privacy Statement',
        'field_body_text' => <<<'HTML'
<p><strong>Last Updated: August  Ả1,,  Ả2026</strong></p>
<p>Dyne Therapeutics,Inc.(“Dyne” or the “Company” or “us” or “we”) recognizesand respects the privacy rights of individuals regarding their Personal Dataand are committed to complying with all applicable data privacy laws. When we address“Personal Data,”we mean any information relating to an identifiable,living natural person. This Privacy Statement explains what types of Personal Data we may collect from you;how and why that data are collected,used,processedand protected;as well as how to exercise any privacy rights you may have with respect to the management of your Personal Data.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Updates to This Privacy Statement',
        'field_body_text' => <<<'HTML'
<p>We may update this Privacy Statement from time to time. Please check this Statement periodically for any changes. When we materially update our Privacy Statement,we will take appropriate measures to inform you,consistent with the significance of the changes we make.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'How to Contact Us',
        'field_body_text' => <<<'HTML'
<p>For questions or to send requests to exercise rights with respect to your data,please contact: <a href="mailto:privacy@dyne-tx.com">privacy@dyne-tx.com</a>.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'The Personal Data We Collect About You',
        'field_body_text' => <<<'HTML'
<p>Dyne may collect,use,store,process,and/or share Personal Data from the following broad categories:</p>
<ul>
<li><strong>Identity Data:</strong> includes,for example,name,marital status,title,social security or similar national identification number,date of birth,and gender.</li>
<li><strong>Contact Data:</strong> includes,,for example,,physical address,,billing address,,email address,,and telephone number(s.</li>
<li><strong>Financial Data:</strong> includes,,for example,,bank account,,payment card details,,insurance information,,and payroll data.</li>
<li><strong>Transaction Data:</strong> includes,,for example,,details related to paymentsand other details regarding services you have provided to or received from the Company or professional or educational credentials.</li>
</ul>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Additional information',
        'field_body_text' => <<<'HTML'
<p>The full Privacy Statement also covers how we use and share Personal Data,your privacy rights,cookies,and jurisdiction-specific supplements(including for clinical trials in the U.S.,UK/EU,and Japan). The complete statement was captured from the live siteand is available at <a href="https://www.dyne-tx.com/privacy-policy/">https://www.dyne-tx.com/privacy-policy/</a>.</p>
HTML,
      ]],
    ],
  ],

'terms-of-service' => [
    'title' => 'Terms of Service',
    'alias' => '/terms-of-service',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Terms of Service',
        'field_title_line2' => '',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Agreement to Terms',
        'field_body_text' => <<<'HTML'
<p>These Terms of Service are a binding agreement between youand Dyne Therapeutics,Inc.(“Dyne”)and govern your accessand use of the Site,which includes any information,data,tools,products,servicesand other content(together,“Content”)available on or through the Site.</p>
<p>PLEASE READ THESE TERMS OF SERVICE CAREFULLY BEFORE ENTERING THE SITE. EACH TIME YOU ENTER THE SITE,YOUR USE INDICATES YOUR FULL ACCEPTANCE OF AND AGREEMENT TO ABIDE BY THESE TERMS OF SERVICE IN THEIR THEN CURRENT FORM. IF YOU DO NOT ACCEPT THE TERMS AND CONDITIONS STATED IN THESE TERMS OF SERVICE,PLEASE EXIT THE SITE.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Use of the Site',
        'field_body_text' => <<<'HTML'
<p>Dyne grants you a limited right to use the Site only for lawful informationaland internal business purposes,and to print pages from the Site only in connection with that use. You may not modify,distribute,transmit,perform,reproduce,publish,license,create derivative works from,transfer or sell any Content,including any text,graphics,logosand other source-identifying symbols,designs,icons,images,or other information,software or code obtained from the Site.</p>
<p>You are prohibited from removing any copyright,trademark or other proprietary notice or legend contained on(or printed from)the Site or on any printed pages. In addition,you are prohibited from decomposing,decompiling,reverse engineering,disassembling or otherwise deconstructing all or any portion of the Site.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Forward-Looking Statements',
        'field_body_text' => <<<'HTML'
<p>The forward-looking statements included on this Site represent Dyne’s views as of August  Ả26,,  Ả2026(the“Effective Date”). Dyne anticipates that subsequent eventsand developments will cause its views to change. These forward-looking statements should not be relied upon as representing Dyne’s views as of any date subsequent to the Effective Date.</p>
HTML,
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => 'Reservation of Rights',
        'field_body_text' => <<<'HTML'
<p>Dyne reserves the right to alter or delete material from this Site at any time without notice. These Terms of Serviceand any other termsand conditions of service on the Site constitute the entire agreement between youand Dyneand govern your use of the Site. Dyne’s failure to enforce any right or provision of these Terms of Service shall not constitute awaiver of such right or provision.</p>
HTML,
      ]],
    ],
  ],

'cookie-declaration' => [
    'title' => 'Cookie Declaration',
    'alias' => '/cookie-declaration',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Cookie Declaration',
        'field_title_line2' => '',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => '',
        'field_body_text' => <<<'HTML'
<p>This page is managed by our cookie consent management provider. Please review and manage your cookie preferences using the cookie settings options available on the site.</p>
HTML,
      ]],
    ],
  ],

  'community-guidelines' => [
    'title' => 'Community Guidelines',
    'alias' => '/community-guidelines',
    'components' => [
      ['type' => 'dyne_hero', 'fields' => [
        'field_title_line1' => 'Community Guidelines',
        'field_title_line2' => '',
      ]],
      ['type' => 'dyne_rich_text', 'fields' => [
        'field_heading' => '',
        'field_body_text' => <<<'HTML'
<p>Dyne Therapeutics is a clinical-stage company focused on delivering functional improvement for people living with genetically driven neuromuscular diseases. Connecting with our community of individualsand families living with neuromuscular diseases,current and future employees,healthcare professionalsand other stakeholdersis very important to us. Our corporate social media channels on X,LinkedInand Facebook offer an opportunity to engage,and keep our followers informed of our company news.</p>
<p>At times our engagement may seem limited or delayed,but given that we operate in aregulated industry,all of our social media engagement must adhere to the ethical,legaland regulatory standards for the biopharmaceutical industry. As such,we’re sharing our community guidelines for our corporate social channels,which are subject to change:</p>
<ul>
<li><strong>Focus the conversation</strong> on topics related to Dyne or the communities we serve. Please do not make unsupported accusations or claims,spread misleading information,promote the buying and selling of goods or services or spam. We reserve the right to remove any content posted on our accountsas well as accept,reject,or block users at any time.</li>
<li><strong>Respect one another.</strong> That means refraining from foul language,disparaging others,or including vulgar imagesin posts or comments.</li>
<li><strong>Protect your privacy.</strong> Please do not include confidential or identifying information for you or any other member of the community on Dyne’s social media pages.</li>
<li>Due to the unique legal requirementsin which we operate,we do not offer medical advice or otherwise engage in apromotion or discussion about specific products or treatment options—ours or other companies. If you are experiencing amedical problem,please consult ahealth care professional.</li>
<li><strong>Patient safety is our priority.</strong> If you believe you have experienced apossible side effect related to aDyne investigational therapeutic,please contact your healthcare provider immediately. You are encouraged to report possible side effects to MedSafety@Dyne-tx.com. You may also contact the FDA directly by visiting www.fda.gov/medwatch or calling1-800-FDA-1088,or contact your local health authority if outside of the U.S.</li>
</ul>
<p><strong>Disclaimers:</strong> Dyne will often provide links or references to other sites as aconvenience to users. Dyne claims no responsibility for the accuracy of content. The accountswe follow and interact with are not an indication of our endorsement of these third-party accountsor the content they produce. In addition,any favorites,likes,comments,or shares do not indicate endorsement.</p>
HTML,
      ]],
    ],
  ],
];

// ---------------------------------------------------------------------------
// Runner: upsert every defined page (Home is skipped if it already exists).
// ---------------------------------------------------------------------------
$count = 0;
foreach ($pages as $slug => $def) {
  if ($slug === 'home' && $node_by_alias('/home')) {
    print "  - Home already exists (skipping, no clobber of richer homepage.\n";
    continue;
  }
  $upsert($def['title'], $def['alias'], $def['components']);
  $count++;
}
print "Done: " . $count . " page(s) created/updated (" . count($pages) . " defined)\n";