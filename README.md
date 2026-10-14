# Legacy archive reconciliation (October 13, 2026)

## GitHub checkout and deployment assets

Git tracks the application code and assets, with application MP4 videos stored
using Git LFS. Install Git LFS before cloning. For an existing checkout, run:

```sh
git lfs install --local
git lfs pull
```

Database backups (`*.sql.gz`), the original `dyne-tx-2026-10-01.zip` archive, and
environment-specific Drupal settings are kept locally and excluded from Git.
Existing local copies are preserved.

For a new checkout, provision Drupal settings for the target environment and
obtain the appropriate database backup separately before following the restore
steps below. Ensure Git LFS has downloaded the actual video files before deploying;
`output/media-recovery.json` records their source URLs, local paths, sizes and
SHA-256 checksums. The database backup and local settings must be provided
separately from the Git clone.

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
news and hero headings use the existing specialized Paragraphs. A restricted
editorial permission is required for the trusted `dyne_legacy_html` format;
it is not granted automatically to authenticated users. Use a trusted editor
or the existing site owner to edit preserved layout HTML.

Job listings query published `job` nodes with department and location filters.
New/edited jobs invalidate the listing cache. Current vacancies reflect the
October 12 archive; this is not a live Greenhouse synchronization service.

Source hero images and SEO metadata are recorded in the presentation metadata
JSON; hero headings are taken from the editable hero Paragraph. Header/footer
navigation remains editable through Drupal menus. Body-copy and metadata
updates should be followed by `ddev drush cr` when fixtures or templates change.

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
Desktop/mobile visual evidence is saved in `output/playwright/`. The ten absent
archive videos were recovered from their original URLs; the two empty source
pages remain explicitly documented rather than filled with invented content.

The original database backups are unchanged. Pending Drupal update hooks may be
applied using your normal release process after a backup; the source-menu guard
prevents old menu seeding from replacing reconciled navigation. For this
checkout, the importer applies only its required schema and avoids unrelated
legacy page-building scripts.
