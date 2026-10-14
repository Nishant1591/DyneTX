# Dyne Therapeutics
## Live Website Updates and Drupal Change-Impact Report

**Prepared for:** Client and project stakeholders  
**Investigation date:** October 7, 2026  
**Recent-change window investigated:** October 5-7, 2026  
**Live reference:** https://www.dyne-tx.com/  
**Drupal comparison:** https://dyne-tx.ddev.site/  
**Assessment type:** Read-only content, route, navigation, asset, and selected visual comparison

## 1. Executive Summary

The live website has differences that require updates to the Drupal implementation. The strongest evidence of activity within the recent-change window is:

- **Nine community-letter pages** have sitemap modification timestamps on **October 6, 2026, between 19:10 and 19:11 UTC**. All nine live routes return HTTP 200; all nine corresponding Drupal routes return HTTP 404.
- **The FORCE platform page** has a sitemap modification timestamp of **October 7, 2026, at 01:53:04 UTC**. Its current headline, diagram/video presentation, and visual treatment differ from Drupal.
- **The jobs sitemap contains 59 URLs**, with modification timestamps on October 7. Our repository contains eight sample job rows, not a complete equivalent inventory. The timestamps do not establish that 59 jobs were newly added.

The homepage also has a substantial content and presentation difference: live uses a photographic hero and the headline **"expanding what's possible for people living with genetically driven neuromuscular diseases"**, while Drupal retains **"The muscle to keep life moving"** and the earlier blue hero treatment. However, the homepage sitemap reports an older modification date. This is a confirmed current difference, **not proof of an October 5-7 homepage publication date**.

Additional existing gaps include missing ACHIEVE and FORZETTO sections on the clinical-trials page, missing recent conference material on the publications page, and incomplete footer/consent presentation. These should be addressed, but must not all be described as changes made in the last two days.

**Items already matching:** the homepage's September 29 and September 17 news items, the Physicians external destination, the investor-domain links, and most current About Us/careers navigation anchors. These do not need to be rebuilt from outdated menu or seed-script assumptions.

**Recommendation:** first migrate the missing community letters and repair their index links; update the homepage and FORCE platform experience through the existing components; reconcile clinical-trial and publication content with client-approved scientific copy; then complete careers, footer, consent, and responsive validation.

## 2. What Can and Cannot Be Proven

### 2.1 Evidence Sources

- Current live HTML and browser DOM, including headings, navigation destinations, canonical links, asset URLs, and letter datelines.
- Live Yoast page and jobs XML sitemaps.
- Current Drupal browser DOM and read-only HTTP checks for the affected routes.
- Current rendered Drupal content, with repository CSVs/page-builder scripts as supplementary baseline evidence.
- Selected live/Drupal desktop homepage screenshots at a requested 1440 x 1000 browser viewport.
- The earlier project architecture report for context, not as a substitute for current observations.

### 2.2 Timeline Qualification

There is no archived live-site capture from immediately before October 5 in the inspected project evidence. Consequently, we can identify **recently modified URLs and current live-versus-Drupal differences**, but cannot provide a verified before/after WordPress revision history.

Sitemap `lastmod` is a modification indicator. Theme/template changes, bulk edits, indexing changes, or metadata edits can produce a timestamp without proving new editorial publication. Conversely, a template-driven homepage update may not change the homepage's sitemap timestamp.

The nine letters contain editorial dates from **August 4, 2025 through September 29, 2026**. They must not be described as nine newly written October letters. Their availability at recently modified URLs is confirmed; their original publication history is not.

### 2.3 Confidence Labels

| Label | Meaning |
| --- | --- |
| Confirmed current difference | Directly observed on live and Drupal or confirmed through route checks |
| Recent modification evidence | Sitemap timestamp falls within October 5-7; exact content change date remains uncertain |
| Existing migration gap | Source content or capability is missing locally, but recent introduction is not proven |
| Review required | Text extraction or incomplete visual/interaction evidence prevents a definitive conclusion |

This report is not an exhaustive pixel-by-pixel review of all 54 page-sitemap entries, every job detail, every publication document, or every browser/device. No site content, migration, menu, or configuration was changed during the investigation.

## 3. Recent Activity Inventory

| Live area | Observed sitemap evidence | Current Drupal impact | Classification |
| --- | --- | --- | --- |
| Nine community-letter routes | October 6, 19:10:14-19:11:26 UTC | All nine corresponding routes return 404 | Recent modification evidence and confirmed missing content |
| FORCE platform | October 7, 01:53:04 UTC | Different headline; figure/video experience incomplete | Recent modification evidence and confirmed current difference |
| Jobs | 59 sitemap URLs with October 7 timestamps | Eight source sample rows; full job-detail/listing parity not established | Recent refresh evidence and existing scope gap |
| Homepage | Sitemap reports March 5, 2026 | Different hero, messaging, cards, community section, imagery | Confirmed current difference; recent date unproven |
| Clinical trials | October 2, 2026, 15:09 UTC | Two of four trial sections missing | Existing gap outside the requested two-day window |
| Publications | September 30, 2026, 13:20 UTC | September conference material missing | Existing gap outside the requested two-day window |
| Commitment | September 30, 2026, 13:26 UTC | Live letters index and missing detail destinations need reconciliation | Existing hub gap connected to recently modified letters |
| Corporate responsibility | October 1, 2026, 19:58 UTC | Current live reporting/content needs final field-level reconciliation | Earlier modification; do not classify as October 5-7 |

The live page sitemap contains **54 URLs**, compared with the repository's **22 marketing-page rows**. These are different inventories: legacy pages, stories, letter pages, and other source URLs are included in the 54. The difference must not be reported as "32 new pages in the last two days."

## 4. Nine Missing Community-Letter Pages

All pages below were directly checked: **live HTTP 200 / Drupal HTTP 404**. All nine have self-referencing canonical URLs on live and use a shared **"Letters to our communities"** H1, with the individual letter title and dated content below it. No PDF download links were found in the inspected letter HTML; these are HTML letter pages, not simply PDF viewers.

| ID | Source route | Letter subject | Visible editorial date | Audience | Sitemap lastmod, UTC |
| --- | --- | --- | --- | --- | --- |
| L01 | `/new-one-year-efficacy-data-on-z-basivarsen-dyne-101-for-dm1/` | New one-year efficacy data on z-basivarsen / DYNE-101 | September 29, 2026 | DM1 | Oct 6, 19:10:14 |
| L02 | `/message-to-the-global-myotonic-dystrophy-community/` | Message to the global myotonic dystrophy community | September 21, 2026 | DM1 | Oct 6, 19:11:26 |
| L03 | `/early-stage-fshd-clinical-trial/` | FDA clearance to start an early-stage FSHD trial | July 28, 2026 | FSHD | Oct 6, 19:10:20 |
| L04 | `/fda-accepts-bla-for-z-rostudirsen/` | FDA accepts BLA and grants priority review | July 20, 2026 | DMD | Oct 6, 19:10:27 |
| L05 | `/forzetto-trial-initiated/` | Z-rostudirsen application submission and FORZETTO initiation | May 26, 2026 | DMD | Oct 6, 19:10:33 |
| L06 | `/new-clinical-trial-opportunity-in-dm1/` | New DM1 clinical-trial opportunity / HARMONIA | April 16, 2026 | DM1 | Oct 6, 19:10:39 |
| L07 | `/community-letter-duchenne-added-to-newborn-screening/` | Duchenne added to newborn screening | December 16, 2025 | DMD | Oct 6, 19:10:46 |
| L08 | `/community-letter-deliver-clinical-trial/` | Positive topline DELIVER trial results | December 8, 2025 | DMD | Oct 6, 19:10:51 |
| L09 | `/community-letter-duchenne-muscular-dystrophy-community/` | Duchenne community letter / breakthrough therapy designation | August 4, 2025 | DMD | Oct 6, 19:10:56 |

### 4.1 Required Drupal Work

1. Add all nine letters with the exact approved source paths, titles, original visible datelines, full body content, signature, disclaimers, and relevant outbound links.
2. Preserve the shared letter-page presentation while exposing meaningful individual page titles and metadata.
3. Update `/our-commitment` to reproduce the live **"Letters to Our Communities"** index, its ordering, dates, and links.
4. Verify every letter-to-press-release/trial/email link and remove accidental production-domain links for content hosted locally.
5. Preserve canonical paths and trailing-slash handling consistently. A redirect to the generic commitment page is not equivalent to migrating the letter content.
6. Include these pages in the agreed migration inventory, sitemap, and future update workflow.

### 4.2 Content-Model Recommendation

For the smallest near-term change, use existing `dyne_page` nodes with appropriate hero/rich-text Paragraphs and preserved datelines. For repeatable editorial maintenance, evaluate an existing structured content type with a community-letter category, editorial date, disease/audience, body, and source identifier. Create a new dedicated type only if the existing model cannot cleanly represent that workflow.

Do not place dated community letters into patient-story content merely because both concern diseases. Do not run the current pages migration unchanged: it targets an absent standard `page` bundle and writes the title as body content. Adding CSV rows alone will not create complete letter pages.

## 5. Homepage: Substantial Current Difference

**Live:** https://www.dyne-tx.com/  
**Drupal:** https://dyne-tx.ddev.site/

| Element | Current live | Current Drupal | Required action |
| --- | --- | --- | --- |
| Hero | Photographic treatment with large "more"/"within reach" creative | Earlier blue hero and "The muscle to keep life moving" | Replace approved desktop/mobile hero assets and layout treatment |
| Main headline | Expanding what is possible for people living with genetically driven neuromuscular diseases | Older muscle/functional-improvement introduction | Replace headline hierarchy and introduction with current approved copy |
| Introduction | Meaningful everyday activities and purpose-led mission narrative | Older corporate team/mission summary | Update body copy and spacing |
| Feature grid | Three items: Pioneering approach, Clinical-stage pipeline, clinical-trial participation | Four items including Commitment | Recompose grid to match live; retain appropriate destination links |
| CTA labels | Discover our science; Explore our pipeline; See if you may qualify | Generic Learn more links | Match current source labels |
| Community feature | Dedicated "Committed to the community" image/text section | Commitment appears primarily as a grid item | Add separate section and link to `/our-commitment` |
| Culture and careers | Revised copy and employee collage; link includes `#HfD` | Older copy and single culture image; general careers link | Update copy/imagery; verify whether the live anchor resolves before adopting it |
| Latest news | September 29 and September 17 items | Same dates, headlines, and investor destinations | **Already aligned; do not duplicate or replace blindly** |
| News visual | Office-building imagery accompanies the section | Text-oriented news section | Match agreed visual layout and asset treatment |

### 5.1 Source Asset Manifest

The current live DOM identifies these theme asset paths. Source ownership/licensing and originals should be confirmed with the client before copying them into a deployment.

| Purpose | Live asset path beneath `/wp-content/themes/dynetxdev2020/images/` |
| --- | --- |
| Desktop hero fallback frame | `hero/hero-home-desktop-lastframe.webp` |
| Mobile hero fallback frame | `hero/hero-home-mobile-lastframe.webp` |
| Pioneering icon | `icons/intro/ico-intro-pioneering.svg` |
| Pipeline icon | `icons/intro/ico-intro-pipeline.svg` |
| Trial icon | `icons/intro/ico-intro-trial.svg` |
| Community photo | `committed/committed-desktop.webp` |
| Culture collage | `culture/culture-photos-desktop.webp` |
| News/office photo | `news/news-desktop.webp` |

The `lastframe` images indicate fallback imagery; they do not establish that a static image alone reproduces the full hero experience. Verify video/animation sources, autoplay/muted behavior, fallback, reduced-motion handling, and mobile framing during implementation.

**Classification:** confirmed current content/visual gap. The exact homepage release date is not established by the sitemap.

## 6. FORCE Platform: Recently Modified and Out of Alignment

**Live:** https://www.dyne-tx.com/our-forcetm-platform/  
**Recent sitemap evidence:** October 7, 01:53:04 UTC.

| Element | Live | Drupal evidence | Action |
| --- | --- | --- | --- |
| H1 | "built to get therapies precisely where they need to go" | "Advancing targeted therapeutics to address the root cause of disease" | Replace hero messaging and match brand treatment |
| Hero | New source artwork; "more / intent" creative | Older generic treatment | Use approved matching desktop/mobile assets |
| Payload explanation | Fab/linker/payload text accompanied by a targeted-delivery diagram | Much of explanatory text exists; figure experience not equivalent | Preserve correct existing scientific text; add/reconcile figure and caption |
| Video section | "The FORCE Platform", poster, Watch video link | No corresponding video link in the inspected local content | Add video component/behavior and poster |

Verified live video destination: https://youtu.be/yRcW0eOl5ms  
Verified live poster: https://www.dyne-tx.com/wp-content/themes/dynetxdev2020/images/cover-force-platform-25.webp  
Verified live diagram: https://www.dyne-tx.com/wp-content/themes/dynetxdev2020/images/payloads.webp  
Verified live desktop hero source: https://www.dyne-tx.com/wp-content/uploads/hero-force-bg-base-desktop@2x-scaled.webp

Use existing hero, rich-text, and video components where suitable. Test poster rendering, video launch/close, keyboard focus, responsive layout, and consent-dependent provider loading. Do not rewrite scientific mechanisms based on an inferred recent change; retain client-approved source wording.

## 7. Clinical Trials: Four Live Sections, Two Local Sections

**Live:** https://www.dyne-tx.com/clinical-trials/  
**Sitemap lastmod:** October 2, 2026, outside the recent-change window.

| Trial | Current live presence | Current Drupal | Required work |
| --- | --- | --- | --- |
| HARMONIA | Detailed Phase 3 section, logo, specific registry link, information PDF | Section exists but shortened | Reconcile full approved copy and specific links |
| ACHIEVE | Detailed Phase 1/2 section, logo, ClinicalTrials.gov and EU registry links | Missing from inspected trial-page content | Add complete section in source order |
| FORZETTO | Detailed Phase 3 section, logo, study flyer | Missing from inspected trial-page content | Add complete section in source order |
| DELIVER | Updated study/cohort/extension description and registry links | Section exists but shortened | Reconcile current copy and specific links |
| Patient access | Expanded-access policy and explanatory paragraphs | Not equivalent in the inspected local excerpt | Reconcile full section before sign-off |

Verified source destinations:

- HARMONIA registry: https://clinicaltrials.gov/study/NCT07486934
- ACHIEVE registry: https://clinicaltrials.gov/study/NCT05481879
- ACHIEVE EU registry: https://euclinicaltrials.eu/search-for-clinical-trials/?lang=en&EUCT=2023-510353-42-00
- DELIVER registry: https://clinicaltrials.gov/study/NCT05524883
- HARMONIA information: https://www.dyne-tx.com/wp-content/uploads/About-the-HARMONIA-Trial.pdf
- FORZETTO flyer: https://www.dyne-tx.com/wp-content/uploads/DYNE251-DMD-301_Flyer.pdf

**Important scientific distinction:** the live HARMONIA primary endpoint is the five-times-sit-to-stand test; the live ACHIEVE registrational-cohort primary endpoint is video hand opening time. These must not be interchanged during migration. All dosing, eligibility, enrollment status, endpoints, and regulatory wording require content-owner review against the current source; this report does not provide independent medical validation.

**Classification:** confirmed current content gap, not proven to originate in the last two days.

## 8. Publications: Recent Conference Material Missing

**Live:** https://www.dyne-tx.com/our-forcetm-publications/  
**Sitemap lastmod:** September 30, 2026.

The live archive begins with September 2026 conference entries. The local page starts with the July 7-11 ICNMD entry. At minimum, the following recent groups need reconciliation:

| Conference/date | Source material | Required Drupal action |
| --- | --- | --- |
| AANEM, September 29-October 2, 2026 | ACHIEVE long-term functional improvement poster and additional data | Add title/date/event and both document links |
| World Muscle Society, September 29-October 3, 2026 | ACHIEVE oral presentation and additional data | Add title/date/event and both document links |
| RNA Leaders, September 25, 2026 | DYNE-302 functional improvement / muscle transcriptomic changes presentation | Add entry and document link |
| NMSG, September 25-27, 2026 | FORCE platform / DMD exon-skipping franchise poster | Add entry and document link |

Verified document URLs:

- https://www.dyne-tx.com/wp-content/uploads/Andresson__2026_AANEM_ACHIEVE_Poster.pdf
- https://www.dyne-tx.com/wp-content/uploads/Andresson__2026_AANEM_ACHIEVE_Poster_Additional_Data.pdf
- https://www.dyne-tx.com/wp-content/uploads/Mul_2026_WMS_ACHIEVE_ORAL.pdf
- https://www.dyne-tx.com/wp-content/uploads/Mul_2026_WMS_ACHIEVE_ORAL_APPENDIX.pdf
- https://www.dyne-tx.com/wp-content/uploads/Natoli_2026_RNA-Leaders_DYNE-302_Presentation.pdf
- https://www.dyne-tx.com/wp-content/uploads/Desjardins_2026_NMSG_-DMD-Franchise_Poster.pdf

Preserve exact filenames: apparent spelling or doubled underscores are part of the observed source URLs, not reasons to invent replacement links. Download availability and file integrity remain implementation acceptance checks.

The broader live archive contains material through 2020 and appears more extensive than the local representation. A full document-by-document manifest should be reconciled before calling the archive complete; no percentage of archive completeness is asserted here. Existing local text also contains spacing/encoding defects that need cleanup.

**Classification:** current archive gap with September source dates; not a proven October 5-7 addition.

## 9. Pipeline and Program Pages

### 9.1 Pipeline

The current live pipeline has a graphical presentation, named program anchors, and purpose-led hero messaging. Drupal has a simplified table and older headline/copy treatment.

Required work:

- Match the live hero headline: "a pipeline driven by purpose with the potential to effect real change."
- Reconcile the graphical progression, disease/prevalence rows, program names, milestone annotations, and mobile presentation rather than treating the table as a text-only replacement.
- Preserve the PDUFA date shown in the current source, January 21, 2027, with client-approved regulatory wording.
- Provide `#DMD`, `#DM1`, and `#FSHD` anchors if adopting the current live pipeline dropdown destinations.
- Correct spacing/encoding and missing program link behavior.

**Do not interpret "Commercial" in extracted pipeline text as proof of marketing approval.** It can be a progression/axis label in a graphical pipeline. Exact stage placement and regulatory claims require visual and scientific review, not a blind replacement of Drupal's "Registrational" text.

### 9.2 Individual Program Pages

The inspected DYNE-251 and DYNE-101 pages share substantial scientific material with their live counterparts. They were not proven to have new October 5-7 content. The DYNE-302 local opening contains a product-candidate wording/spacing defect identified during comparison; reconcile it with approved source copy.

Retain existing program routes until the client agrees their role. Live navigation currently targets pipeline anchors rather than those detail routes; changing navigation does not automatically require deleting the program pages.

### 9.3 Baseline-Wide Coverage Check

A final HTTP/headline/key-heading check covered all 22 baseline marketing pages on both sites. Every checked baseline route returned HTTP 200 on live and Drupal. This verifies route availability, not complete text or visual parity. The table below records the current gap category; none of these differences alone establishes an October 5-7 change.

| Baseline route | Current comparison finding | Follow-up |
| --- | --- | --- |
| `/` | Hero, H1, feature grid, community/culture/news presentation differ | Section 5 |
| `/about-us` | Different hero headline; duplicate local leadership headings | Headline, section, portrait/biography review |
| `/pipeline` | Different H1 and graphical/table presentation | Section 9.1 |
| `/our-forcetm-platform` | Different H1; figure/video gap | Section 6 |
| `/our-forcetm-publications` | Different hero/H1; September entries absent | Section 8 |
| `/dyne-251-for-dmd` | Scientific text substantially overlaps; local generic hero differs | Header hierarchy and full copy review |
| `/dyne-101-for-dm1` | Scientific text substantially overlaps; local generic hero and truncated heading differ | Header and punctuation review |
| `/dyne-302-for-fshd` | Generic local hero and product-candidate typo | Header and copy repair |
| `/dmd` | Live story-led H1; local "DMD Stories" | Hero/copy and story-detail inventory |
| `/dm1` | Live story-led H1; local "DM1 Stories" | Hero/story inventory; signup interaction |
| `/fshd` | Live resilience-led H1; local "FSHD Stories" | Hero/copy and story-detail inventory |
| `/clinical-trials` | Different H1; ACHIEVE/FORZETTO sections missing | Section 7 |
| `/our-commitment` | Different H1 and awareness-event heading structure | Letters index and community sections |
| `/resources` | Different headline/heading hierarchy; local region-based resource grouping | Source resource/link reconciliation |
| `/careers-culture` | Different purpose-led H1 and value-section structure | Careers visuals/copy and jobs |
| `/corporate-responsibility` | H1 substantially aligns; internal section structure differs | Current reporting data and section review |
| `/perspectives` | Live article-title list; local general sections | Full article/index migration reconciliation |
| `/community-letter` | Live workshop-letter sections; local simplified general letter structure | Preserve the original letter separately from nine newer URL entries |
| `/privacy-policy` | Live detailed collection/contact sections; local abbreviated heading set | Complete approved privacy and consumer-health policy review |
| `/terms-of-service` | Live medical disclaimer/liability/privacy/copyright sections; local generic terms headings | Legal-owner review of complete source-equivalent text |
| `/cookie-declaration` | HTTP route exists on both; extracted headings do not establish functioning consent declaration | Test live/approved provider declaration and settings |
| `/community-guidelines` | Live guidelines/disclaimers subheadings; local H1 only in extracted headings | Full copy and disclaimers review |

Legal and scientific pages should never be filled with generic substitute copy to achieve layout parity. Have the responsible client owners approve the full content. The evidence here confirms heading/structure differences; full clause-by-clause legal equivalence was not audited.

## 10. Navigation, Footer, Consent, and Careers

### 10.1 Navigation: Corrections Already Present

Current Drupal DOM confirms the following already match the live destination strategy:

- Physicians -> https://dynemedicalcentral.com/
- Investors and Media -> https://investors.dyne-tx.com/ and the expected external investor subpages.
- Patients -> `/our-commitment`, rather than an absent `/patients` page.
- About Us leadership, board, SAB, and contact -> page anchors.
- Careers benefits/open positions -> page anchors.

Remaining differences include labels, typography, menu sizing, and pipeline program links: live points to `/pipeline/#DMD`, `#DM1`, and `#FSHD`; Drupal currently points to separate program pages. Confirm and implement the intended source behavior, and verify target IDs actually exist. A URL containing an anchor does not prove a usable destination.

This current evidence supersedes earlier assessments based on absent menu blocks or original install-hook URLs. Do not reintroduce outdated internal Physicians/investors routes by rerunning seed code.

### 10.2 Footer and Stay-in-Touch

Live exposes legal links, named social links/icons, and a stay-in-touch interaction. The inspected Drupal footer exposes contact and outbound social links but does not expose equivalent legal-link content or a working subscription interaction.

Required work: correct legal-menu placement, social icon presentation/accessible names, consumer-health policy anchor, stay-in-touch service/form behavior, and submission/privacy handling. These are current gaps, not demonstrated two-day changes.

### 10.3 Consent and Accessibility Tools

Live uses Cookiebot by Usercentrics and includes accessibility tooling. Drupal uses a different consent banner; earlier inspection showed literal HTML in its displayed copy. Confirm the client-approved consent platform, categories, analytics/marketing blocking, cookie declaration, and settings controls before replacing any integration.

Do not copy WordPress plugins into Drupal or treat an accessibility widget as a substitute for WCAG testing. No complete tracking/consent or accessibility audit was performed here.

### 10.4 Careers and Jobs

The live jobs sitemap contains **59 job-detail URLs**. Repository `jobs-sample.csv` has **eight rows**, and that sample is not evidence of an operational equivalent job feed. All/most observed job sitemap timestamps cluster on October 7, suggesting a refresh or batch activity; they do not prove 59 new vacancies or 51 recent additions.

Required work:

1. Obtain the authoritative current vacancies and determine the source/owner of application links.
2. Reconcile live job URLs, actual open status, departments, locations, descriptions, and apply destinations.
3. Decide whether Drupal should host job-detail records or link to the approved external recruiting service.
4. Replace sample-only content with the agreed complete representation and retirement process for closed roles.
5. Test careers anchors, filters/grouping, mobile listing usability, application links, and external-link notices.

An automated ATS/feed sync may be appropriate, but its vendor/API has not been verified. Do not assume Workday, Greenhouse, or Lever, or commit to an integration without source-system confirmation. Detailed leadership roster parity also requires a separate field/portrait/biography reconciliation; a matching name list does not certify the whole page.

## 11. Prioritized Drupal Backlog

| ID | Priority | Work item | Existing implementation surface | Acceptance result |
| --- | --- | --- | --- | --- |
| U01 | P1 | Create nine community-letter pages | `dyne_page` / hero / rich-text or approved structured records | All nine exact routes return 200 with correct content, dates, disclaimers, canonical metadata |
| U02 | P1 | Update commitment letters index | Existing commitment page sections | Nine source-equivalent links/dates/order; no missing local destinations |
| U03 | P1 | Refresh homepage | Hero, icon cards, rich text, CTA, news section, theme assets/CSS | Current source section sequence, three cards, community/culture imagery; news retained correctly |
| U04 | P1 | Refresh FORCE platform | Hero, rich text, figure, video component | Current headline, diagram/caption, video/poster, desktop/mobile layout |
| U05 | P1 | Complete clinical-trials page | Existing trial rich-text sections and asset/link fields | HARMONIA, ACHIEVE, FORZETTO, DELIVER, patient-access copy and precise links approved |
| U06 | P1 | Add recent publications and reconcile archive | Publication content and link-update workflow | Four September conference groups and six listed documents; approved complete archive manifest |
| U07 | P2 | Reconcile pipeline experience | Pipeline table/template, hero, navigation links | Correct labels/visual progression, anchors, milestones; no inferred approval claims |
| U08 | P2 | Replace jobs sample scope | Job records/listing or approved outbound recruiting integration | All agreed active vacancies and valid application destinations represented |
| U09 | P2 | Complete footer/stay-in-touch | Footer regions, legal menu, social assets, approved form/service | Legal/social/subscription flows usable and accessible |
| U10 | P2 | Confirm consent/accessibility behavior | Approved consent integration and theme interactions | Verified blocking/settings, keyboard/focus, mobile behavior |
| U11 | P2 | Clean copy/encoding and link details | Existing fields/templates | No known malformed spacing, product-candidate typo, or broken source-equivalent anchors |
| U12 | P2 | Stabilize synchronization/rebuild process | Source manifests, migrations/configuration, targeted update scripts | Approved edits survive rebuild; current data and configuration reproducible |
| U13 | P1 for legal; P2 otherwise | Reconcile remaining baseline pages | Legal, disease-story, About Us, resources, perspectives and careers content | Full approved legal copy; source-equivalent headings/sections and complete linked-content inventory |

P1/P2 labels are delivery priorities, not estimates of security severity. Timeline and cost require client agreement on scope, assets, source access, and content approval.

## 12. Implementation Approach

### 12.1 Recommended Order

1. Freeze an approved live reference capture and confirm which recently modified material belongs in the migration scope.
2. Import the nine letters and their metadata, then update the commitment index.
3. Obtain approved hero/figure/photo/video source assets and refresh homepage/FORCE components.
4. Reconcile scientific copy, clinical-trial sections, PDFs, and the publication archive with the content owner.
5. Reconcile pipeline anchors and presentation; complete careers, footer, subscription, and consent decisions.
6. Validate content, routes, documents, desktop/mobile views, forms, consent, and editorial workflow.
7. Export configuration/source manifests and retain evidence for the next source update comparison.

### 12.2 Protect Existing Work

Use targeted field/component updates or controlled imports. The existing whole-site page-builder script deletes and recreates referenced Paragraphs; rerunning it indiscriminately can overwrite editorial work and revert newly corrected navigation/content. Take a database/files backup, test in a staging copy, and preserve revisions before applying changes.

Current migrations are not a complete synchronization mechanism. Correct schema/destination issues, introduce stable source identifiers, and capture complete bodies/assets before relying on them for new letters or scientific content.

### 12.3 Client Inputs Needed

- Confirm whether the October update was a theme release, content batch, or both, and provide the approved release/change list if available.
- Provide pre-update screenshots/export or WordPress revision history if exact two-day changes must be proven.
- Supply original desktop/mobile hero artwork, video sources, diagram assets, and related brand guidance.
- Approve scientific/regulatory copy and confirm current trial/publication document versions.
- Confirm recruitment, newsletter/CRM, consent-platform, and accessibility-tool ownership.
- Confirm full source URL inventory and any intentional exclusions from Drupal.

## 13. Acceptance Checklist

1. Nine letter routes return 200 with original datelines and full approved content; the commitment index reaches each one.
2. Homepage hero, introduction, three-card grid, community/culture/news sections match the approved live baseline on desktop and mobile.
3. FORCE headline, figure/caption, poster, and video behavior match the approved reference.
4. All four trial sections and patient-access content are present with correct source-approved details and specific document/registry links.
5. Recent conference entries and their six document URLs resolve; the wider publication manifest is reconciled.
6. Pipeline links reach real disease anchors or approved detail pages; stage/regulatory language is reviewed.
7. Physicians/investor destinations stay correct; menu/footer/social links and anchors are usable.
8. Approved active vacancies and application journeys are represented, with a process for removals and updates.
9. Subscription, contact, consent/settings, keyboard/focus, and responsive interactions pass agreed tests.
10. Targeted updates and a rebuild do not overwrite approved editor changes; source/configuration evidence is retained.

## 14. Evidence and Scope Appendix

### 14.1 Live Sources

- [Page sitemap](https://www.dyne-tx.com/page-sitemap.xml): 54 URLs; ten page entries modified within the investigated window.
- [Sitemap index](https://www.dyne-tx.com/sitemap_index.xml): page/jobs and other content-family sitemap timestamps.
- [Jobs sitemap](https://www.dyne-tx.com/jobs-sitemap.xml): 59 job URLs with October 7 modification evidence.
- [Homepage](https://www.dyne-tx.com/): current content, hero assets, navigation and footer.
- [FORCE platform](https://www.dyne-tx.com/our-forcetm-platform/): current headline, scientific copy, diagram and video.
- [Commitment](https://www.dyne-tx.com/our-commitment/): community-letter index and source context.
- [Clinical trials](https://www.dyne-tx.com/clinical-trials/): four trial descriptions and patient-access content.
- [Publications](https://www.dyne-tx.com/our-forcetm-publications/): recent conference documents and wider archive.
- [Pipeline](https://www.dyne-tx.com/pipeline/): graphical program presentation and anchor behavior.
- [Careers](https://www.dyne-tx.com/careers-culture/), [About Us](https://www.dyne-tx.com/about-us/), [Resources](https://www.dyne-tx.com/resources/), [Community letter](https://www.dyne-tx.com/community-letter/), and [Corporate responsibility](https://www.dyne-tx.com/corporate-responsibility/): supplementary scope checks.

The nine exact letter source paths are recorded in Section 4. Current page metadata confirms self-referencing canonical links and a shared letter-page H1. Editorial dates and lastmod timestamps are different fields and have been kept separate.

### 14.2 Local Implementation Sources

| Repository artifact | Relevance |
| --- | --- |
| [Page source inventory](../web/modules/custom/dyne_migrate/data/pages.csv) | 22 baseline marketing-page rows; nine letter routes absent |
| [Jobs sample](../web/modules/custom/dyne_migrate/data/jobs-sample.csv) | Eight sample rows, not a full source feed |
| [Page builder](../web/modules/custom/dyne_migrate/scripts/create-pages.php) | Earlier scripted content; destructive component replacement behavior |
| [Homepage news updater](../web/modules/custom/dyne_migrate/scripts/update-homepage-news.php) | September 29/17 news already represented |
| [Publication link updater](../web/modules/custom/dyne_migrate/scripts/update-publication-links.php) | Existing limited document-link repair workflow |
| [Story index updater](../web/modules/custom/dyne_migrate/scripts/update-story-index-content.php) | Disease-story work is distinct from community-letter migration |
| [Module install logic](../web/modules/custom/dyne_migrate/dyne_migrate.install) | Component catalogue and original navigation/schema intent |
| [Pages migration](../web/modules/custom/dyne_migrate/migrations/dyne_pages.yml) | Destination/body mapping needs correction before extending import scope |
| [Theme page shell](../web/themes/custom/dynetx/templates/page.html.twig) | Header, footer, regions and interaction structure |
| [Theme stylesheet](../web/themes/custom/dynetx/css/style.css) | Current presentation rules requiring reference-driven updates |
| [Existing architecture report](Dyne-Project-Architecture-and-Approach-2026-10-07.md) | Earlier same-day context; current menu DOM supersedes earlier menu observations |

### 14.3 Direct Verification Performed

- Parsed the live page XML sitemap: 54 entries, nine October 6 letter entries, one October 7 FORCE entry.
- Fetched all nine live letters: HTTP 200, canonical metadata present, common H1, original visible datelines, no detected PDF anchors.
- Fetched the same nine local routes: HTTP 404.
- Compared current live/local homepage headings, navigation URLs, feature sections, news links, and asset URLs.
- Captured selected live/local desktop homepage screenshots; no full mobile visual acceptance test is claimed.
- Compared selected current local scientific/community pages and live trial/publication/platform content.
- Reviewed supplementary public About Us/careers/community/resource/program pages and the jobs sitemap.
- Checked HTTP status, H1 and key headings for all 22 baseline marketing routes on both sites: 44 HTTP 200 responses. Section 9.3 records the observed gap categories; full page-body/visual parity is not implied.

**Client conclusion:** recent activity is confirmed for the community-letter URLs, FORCE platform, and job sitemap. The Drupal backlog includes both these recently modified areas and older unresolved migration differences. Homepage/news/navigation evidence must be evaluated against the current runtime, not assumed from the original seed scripts or prior reports.