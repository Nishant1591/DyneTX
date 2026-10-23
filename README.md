# Title-based URL aliases (October 23, 2026)

Saving a changed title now updates the URL when **Generate automatic URL alias**
is checked. Pathauto patterns cover all eight content types, using `jobs/`,
`team/`, `news/`, `perspectives/`, `pipeline/`, `policies/` and `quotes/` where
appropriate. Pages use the title directly. Uncheck the option to retain or enter
a manual URL.

Existing URLs are retained during setup and on unrelated content edits. A title
change creates an automatic 301 redirect from the previous URL to the same
content. Source imports retain their initial source URLs and restore the editor's
automatic/manual policy afterward. The home page keeps its internal `/node/3`
configuration, so changing its alias does not break the front page.

Apply with `ddev drush updatedb -y`. Update 8113 creates patterns and enrolls
existing records without saving content or rewriting existing aliases.
Configuration is exported in `config/sync`.

```sh
ddev drush php:script scripts/tests/title-aliases.php
```

The read-only check uses unsaved clones. Real save/redirect tests use
`scripts/tests/title-aliases-isolated.php`, which refuses to run without an
explicit disposable database named `dyne_alias_verify_*`. Reports are in
`output/aliases/`; the pre-update backup is
`output/dyne-before-title-aliases-2026-10-23.sql.gz`.
The completed database is `output/dyne-drupal-title-aliases-2026-10-23.sql.gz`.
Job 334's previously saved title change was repaired without resaving its content;
the earlier URL redirects to the corrected title-based alias.

# CKEditor 5 authoring (October 23, 2026)

CKEditor 5 is enabled on both Dyne HTML text formats, covering formatted text
areas across Jobs, Leaders, News, Perspectives, Policies and paragraph sections.
Cards, text sections and alert banners also have native formatted text controls;
their earlier plain source fields remain available for historical revisions.
The trusted legacy format retains arbitrary HTML attributes, classes and styles;
its toolbar includes headings, links, lists, tables and Source editing. The
restricted job format offers the controls supported by its allowed HTML.
Plain metadata such as SEO descriptions, image alt text, source JSON and revision
messages remains plain text.

The editor configuration and core module dependencies are exported in
`config/sync`. Existing databases apply the update with
`ddev drush updatedb -y`, followed by `ddev drush cr`. New module installations
configure the same editors. Existing administrator editor customizations are
preserved when the setup helper is rerun.

Read-only verification:

```sh
ddev drush php:script scripts/tests/rich-text-editors.php
ddev exec php scripts/tests/structured-inline-rendering.php
ddev exec php scripts/tests/rich-text-component-fields.php
```

The local preview is **http://dyne-tx-workspace.ddev.site/**. The pre-editor
database backup is `output/dyne-before-ckeditor-2026-10-23.sql.gz`.
The completed database is `output/dyne-drupal-ckeditor-2026-10-23.sql.gz`.
Verification reports and a Job editor preview are in `output/ckeditor/`.

# Structured editorial fields (October 22, 2026)

The current workspace preview is **http://dyne-tx-workspace.ddev.site/**.
Its database is separate from the earlier Downloads checkout.

Jobs now expose company overview, company/social URLs, workplace arrangement,
role summary, responsibilities, education/experience and skills requirements,
typed salary endpoints/currency, compensation information, job disclaimer and
equal-opportunity statement. The department, location and application controls
continue to use their existing native fields. Fields are grouped and ordered
around those editing tasks; the combined archived description is retained but
hidden from normal authoring.

News separates the article, company information, forward-looking statements and
contacts. Perspectives expose article heading, introduction, byline, portrait
and ordered sections. Policies and marketing text components use sections with
separate heading, formatted text, Media image and link controls. Existing leader
profile and quote fields remain the appropriate model for a single biography
or quotation. The unused Pipeline program bundle now exposes its existing
disease, prevalence, target, phase and status controls.

The public renderer consumes the native fields, including new optional fields
and new/reordered sections. Original bodies, node IDs, aliases, publication
states and historical revisions are preserved. Imported layout scaffolding is
hidden from editors; it preserves presentation rather than supplying the copy.
Source updates populate the same native fields and refuse to replace structured
content that has been edited since its last source baseline.

Back up an existing database before applying the repeatable migration:

```sh
ddev drush updatedb -y
ddev drush cr
```

For a local audit/report run, use
`ddev drush php:script scripts/upgrade-structured-content.php`.
Evidence is in `output/structured-model/`. The before-migration backup is
`output/dyne-before-structured-model-2026-10-22.sql.gz`.
The completed local database is
`output/dyne-drupal-structured-content-2026-10-22.sql.gz`; configuration is
exported in `config/sync` (924 YAML files).
`workplace-review.json` records the source conflict for job 4088: its explicit
Remote location takes precedence over its inconsistent Onsite LinkedIn tag.
Unspecified workplace arrangements remain empty.

Regression checks:

```sh
ddev exec php scripts/tests/structured-job-parser.php
ddev exec php scripts/tests/structured-article-parser.php
ddev drush php:script scripts/tests/structured-content-forms.php
ddev drush php:script scripts/tests/structured-content-source-guard.php
```

The form and source-guard checks do not save content. The separate transactional
save test is disabled by default and belongs on a disposable verification copy.

# Native Drupal editorial upgrade (October 16, 2026)

The response to **Document 6.docx** is recorded finding by finding in
[`output/document-6-changes.html`](output/document-6-changes.html). The preview
remains **http://dyne-tx-master.ddev.site/**.

Current database: **469 nodes** (357 published, 112 unpublished), **422 Media
entities** (417 available source attachments plus five employee portraits), and
424 managed files. The earlier 458 nodes, their body/title/status values, all
64 menu records and 466 existing aliases are preserved. Nine reusable quote
nodes and two policy nodes replace duplicated content on their landing pages.

Hero/SEO/body-class fields, Media, taxonomy, publication dates and external links
are native editor controls. Team cards reference leader nodes; news, jobs and
perspectives read current node records. Cached pages respond to normal saves.
21 ACF layout authoring types and nested card/row/section fields are provided.
Older ACF copy is not substituted for the newer page markup; custom source HTML
remains formatted content. Historical JSON fields and old revisions are retained
for preservation and hidden from normal editing. The theme no longer reads
presentation JSON files during requests.

The complete archive migrations now use native Migrate maps for all 458 archive
records. See [`migrations/README.md`](web/modules/custom/dyne_migrate/migrations/README.md)
for import order, existing-node adoption and non-destructive rollback semantics.
The new [`dyne_content_sync` module](web/modules/custom/dyne_content_sync/README.md)
provides Greenhouse/GCS Drush, cron and queue integration. Both providers remain
disabled. The local container requires its trusted issuer CA for outbound TLS;
GCS also requires an approved client identifier. No live feed changes were
published or queued. Twelve original newsletter PDFs remain unavailable.

Configuration is exported to **`config/sync`** (822 YAML files). Settings point
outside the public web root. `drush config:status` reports zero differences and
there are no pending database updates. Old sample-era updates explicitly defer
to the native model instead of reintroducing sample menus or JSON controls.

For an existing pre-upgrade database, back it up before applying these ordered,
idempotent upgrades. Keep `content-archive/` outside `web/` and preserve all local
media files. Do not run `site:install` or sample page-seeding scripts.

```sh
ddev drush php:script scripts/upgrade-native-content.php
ddev drush php:script scripts/upgrade-native-components.php
ddev drush php:script scripts/upgrade-native-layouts.php
ddev drush php:script scripts/finalize-native-editor-config.php
ddev drush en dyne_content_sync -y
ddev drush migrate:import dyne_pages,dyne_jobs,dyne_leaders,dyne_perspectives,dyne_news
ddev drush php:script scripts/import-verified-redirects.php
ddev drush updatedb -y
ddev drush cr
```

Verification evidence lives in `output/document-6-review/`: 3,415 URL checks,
936 migration assertions, 29 feed assertions, all 31 biographies at desktop and
mobile widths, 18 page/viewport checks, all 21 layout render/widget checks, and
six save/restore checks demonstrating cache invalidation. Verification scripts
that temporarily edit content restore it in `finally`; migration/feed tests use
transactions. Use these on a local verification copy.

Portable current database: **`output/dyne-drupal-document-6-updated-2026-10-16.sql.gz`**.
Pre-upgrade backup: `output/dyne-before-document-6-changes-2026-10-16.sql.gz`.
Import a backup only into an empty or intentionally replaceable database.

## Earlier content-completion checkpoint (October 14, 2026)


The complete source inventory is **`output/wordpress-content-inventory.html`**.
Open this standalone HTML file locally; it contains search, filters, source IDs,
publication states, Drupal destinations, media availability, and export/print
controls. It accounts for all **1,747 WordPress post-table records** without
treating plugin configuration, logs, attachments, or revisions as website pages.
An additional archive section inventories 77 records outside the WordPress
posts table: 63 cached social posts and 14 historical map, marker, QR,
widget/footer and social-link records.

The original 344 imported nodes are preserved. The additive completion adds
114 records using the existing content types: two published source pages and
112 unpublished private/draft/test/duplicate/foreign-feed records. At this checkpoint, Drupal
had **458 nodes** (346 published, 112 unpublished). Six additional source privacy
and quotation records remain embedded in the existing pages. WordPress's 464
editorial records therefore map to 458 nodes plus those six component records.
Drupal's Content screen is paginated at 50 rows; that is not the total count.

The private Community Involvement page stays unpublished. FSHD DAY was restored
from its archived ACF title and Vimeo embed; Vimeo currently requests sign-in
for playback. Our Focus retains its original title and empty body. Historical
ACF copy and 175 WordPress revisions are preserved as source archive data rather
than replacing current public copy. The seven unused mobile navigation items
are retained in a separate disabled, unplaced Drupal menu.

All 2,528 eligible source-upload files are copied and hash-verified. All 417
available attachment originals are registered with Drupal's existing File
module. Twelve newsletter PDFs are missing from the supplied archive and their
original URLs do not return PDFs; exact filenames and available preview images
are listed in the HTML inventory. The additional FORCE Platform video was
recovered from its original public URL. There are now 2,539 local media files,
including eleven recovered videos, totaling approximately 2.47 GB.

`content-archive/` contains the complete content-safe source catalog, attachment
metadata, historical revisions, static theme snippets, and additive import
fixture. **Keep this directory outside the public `web/` document root**: it
includes private and unpublished editorial content. Credentials, submitted
forms, operational log bodies and executable WordPress plugin settings are not
imported. Do not publish the local inventory report as a public website page.

Reproduce the completion after the original public import, with a database
backup and the same source archive:

```sh
python3 scripts/export-wordpress-records.py /path/to/DyneTX
python3 scripts/export-wordpress-additional-content.py /path/to/DyneTX
python3 scripts/complete-wordpress-media.py /path/to/DyneTX/wp-content --apply
# Add --recover to attempt recovery of missing originals at their source URLs.
python3 scripts/build-wordpress-supplement.py
ddev exec env DYNE_COMPLETION_DRY_RUN=1 drush php:script scripts/import-wordpress-supplement.php
ddev drush php:script scripts/import-wordpress-supplement.php
ddev drush cr
python3 scripts/build-wordpress-inventory.py
```

The supplement import is additive and idempotent. It refuses alias collisions
and changed source records instead of overwriting editorial changes. The
original migration ledger, nodes, Paragraphs, files, menus and aliases were
compared field-by-field before and after completion. Evidence and known missing
source assets are in `output/content-audit/`. Restore the final database export
`output/dyne-drupal-content-complete-2026-10-14.sql.gz` only into an empty or
intentionally replaceable database; include theme/module code, `content-archive/`
outside the document root, and the entire public legacy media directory.

## Earlier public archive reconciliation (October 13, 2026)

This checkout remains Drupal 11 with the existing `dynetx` Bootstrap5 theme,
`dyne_migrate` module, node bundles and Paragraph component field. The public
WordPress archive is extracted into portable JSON; no WordPress runtime,
plugins, users, passwords or settings database is installed in Drupal.

The isolated local preview uses `http://dyne-tx-master.ddev.site` via
`.ddev/config.local.yaml`. That override avoids reusing another local checkout's
`dyne-tx` database. The supplied Drupal backup was loaded only into this new
instance. Do not use `site:install` on an existing site.

```sh
ddev start
ddev composer install
# For a NEW, EMPTY local database only:
ddev import-db --file=db_backup_local_dyne_tx_12.10.26.sql.gz
# Preview changes before reconciling content:
ddev exec env DYNE_IMPORT_DRY_RUN=1 drush php:script web/modules/custom/dyne_migrate/scripts/import-legacy.php
ddev drush php:script web/modules/custom/dyne_migrate/scripts/import-legacy.php
ddev drush en ot_webform -y
ddev drush php:script scripts/repair-site-shell.php
ddev drush php:script scripts/repair-cookie-banner.php
ddev drush cr
python3 scripts/verify-legacy-runtime.py
```

The importer matches existing aliases, retains node IDs, creates new node
revisions, and retains previous Paragraphs. Its source-hash ledger makes an
unchanged rerun a no-op, preserving subsequent editorial changes. Back up the
database before applying it elsewhere. Do not run the old sample CSV imports or
page-building scripts after reconciliation; they contain incomplete content.

Source fixtures and presentation metadata live in
`web/modules/custom/dyne_migrate/data/legacy-*.json`. Referenced uploaded assets
are in `web/sites/default/files/legacy/`; original static theme assets are in
`web/themes/custom/dynetx/`. Include both locations in any deployment. The
builder scripts under `web/modules/custom/dyne_migrate/scripts/legacy/` regenerate
the fixtures from the supplied archive without executing its SQL or PHP.
`legacy-verified-redirects.json` stores verified first-party tracking destinations;
`legacy-recovered-media.json` records the recovered local video mappings. Keep
these alongside the fixtures when rebuilding or deploying the project.

OneTrust is available at `/admin/config/services/ot-webform`. It defaults to
disabled in the test environment. Configure and validate the provider's test
collection points before enabling it; DM1-specific IDs were not present in the
archive. No live form submission is part of local verification.

Runtime audit results are written to `output/legacy-runtime-verification.json`.
The fixture explicitly lists source pages with no content. The ten videos absent
from the supplied archive were recovered from the public original server;
`output/media-recovery.json` records their URLs, sizes and SHA-256 hashes. This
work does not deploy or change the production website.

## Verified local result

On October 13, 2026, all 345 recovered page URLs and 412 additional local
links/assets passed HTTP checks, with zero missing internal anchors. Desktop
and mobile rendering, navigation, video playback, biography dialogs and job
filters were checked in the browser. Nine source-extraction/link tests and eight
form-behavior tests passed. The repeat import reported all 344 records unchanged,
with no new Paragraphs or menu changes.

The verified database is saved at
`output/dyne-drupal-reconciled-2026-10-13.sql.gz`. Restore it only into an empty or
intentionally replaceable local/staging database; it includes the current local
Drupal users and configuration. Keep the supplied source backups for rollback.
OneTrust remains disabled/staging in this backup; its test contact form was
rendered without any submission. No production deployment was performed.

## Visual and navigation correction

The original brand fonts and source CSS now control the public presentation.
The theme overrides Bootstrap5's base CSS with the archived MDB base CSS while
retaining Drupal and Bootstrap5's interaction runtime. Earlier approximate
global styles are no longer loaded. Small integration styles handle Drupal
wrappers, responsive navigation, team dialogs, jobs, carousels and consent.
Header/footer dimensions, responsive image sizing, the homepage news background,
pipeline symbols and biography dialog layouts were corrected against the live site.

`output/visual-parity.json` compares seven pages at 390, 768, 1024 and 1440 pixels:
home, FORCE platform, pipeline, our commitment, clinical trials, about and careers.
The 28 comparisons have no horizontal overflow and no typography differences
among 1,016 matched text elements. Screenshots and separate image/popup checks
supplement these measurements; text measurements alone do not establish visual
identity. Evidence is in `output/playwright/`.
All 13 primary pages also passed overflow, error-page and broken-image checks
at 320, 820 and 1920 pixels (39 further cases in `output/responsive-extremes.json`).

Internal HTML navigation stays in the same Drupal tab. Archived corporate and
old staging links are localized while preserving query strings and fragments.
Intentional external services and document downloads retain their source behavior.
The Physicians link uses the source exit notice; Investors navigates directly
in the same tab. A general interceptor no longer forces all external links into
new tabs. Biography controls, mobile navigation, job filters and media player
cleanup are handled by Drupal behaviors.

The About page uses one persistent `#modalTeam` dialog. Previous/Next replace
the biography in place, retaining the backdrop, arrow focus and scroll position;
navigation wraps independently within the 15 leaders, ten directors and six
scientific advisers. Close, Escape and backdrop dismissal restore focus to the
card that opened it. Biography data remains in the existing Paragraph fields.

The ten recovered MP4 files add approximately 1.8 GB to the local media folder;
include them in deployment assets. Failed native players show an unavailable
message instead of an empty player. OneTrust is still disabled pending provider
configuration. The native
Drupal cookie dialog keeps its configured copy and working consent controls,
so its mobile height differs from the live Cookiebot dialog. The archive has
49 jobs versus 44 on the live site at verification; existing archived jobs were
not deleted to force equal page lengths.

## Recovered content

| Existing Drupal type | Imported records |
| --- | ---: |
| `dyne_page` | 52 |
| `job` | 49 |
| `leader` | 31 |
| `perspective` | 13 |
| `news` | 199 |

The original 23 node IDs are preserved. The source primary navigation has 35
links. 188 archived uploaded files, ten recovered videos and the original theme
assets are included. The inventory in
`web/modules/custom/dyne_migrate/data/legacy-source-inventory.json` explains all
published source post types, including administrative/test data excluded from
public content.

## Editing and verification

Marketing pages remain `dyne_page` nodes with the existing `field_components`
Paragraph stack. Source layout sections use `dyne_rich_text`; team grids, jobs,
news use specialized Paragraphs. Hero headings and media are edited on the
page itself; legacy hero Paragraph controls are hidden. A restricted
editorial permission is required for the trusted `dyne_legacy_html` format;
it is not granted automatically to authenticated users. Use a trusted editor
or the existing site owner to edit preserved layout HTML.

Job listings query published `job` nodes with department and location filters.
New/edited jobs invalidate the listing cache. Current vacancies reflect the
October 12 archive until the new synchronization provider is configured and
enabled. The provider is disabled by default.

Source presentation metadata is retained as import evidence; rendering uses
native node and Media fields. Header/footer navigation remains editable through
Drupal menus. Normal content saves invalidate dependent pages automatically.
Rebuild caches after template or code changes, not after ordinary content edits.

```sh
PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s web/modules/custom/dyne_migrate/scripts/legacy/tests -v
ddev exec php web/modules/custom/dyne_migrate/scripts/legacy/tests/test-navigation.php
node --test web/modules/custom/ot_webform/tests/behavior.test.cjs
python3 scripts/verify-legacy-runtime.py
python3 scripts/verify-link-behavior.py
```

The runtime verifier checks every imported alias, local resource/link, responsive
image source, CSS font/background reference and named internal anchor. It rejects
unexpected external HTTP redirects. It does not submit forms or inspect external
account contents. `scripts/verify-link-behavior.py` separately inventories link
destinations and new-tab targets.
Desktop/mobile visual evidence is saved in `output/playwright/`. The missing archive videos were recovered from their original URLs; FSHD Day
and Our Focus were restored during the later content-completion pass.

The original database backups are unchanged. There are no pending database updates in the final checkpoint.
The native-model guard prevents old update hooks from reintroducing sample
menus and editing controls. Continue to avoid legacy page-building seed scripts.
