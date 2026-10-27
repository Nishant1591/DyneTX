# Dyne Drupal content model and archive migration

This module provides the native Drupal content model, source-archive migration
plugins, and update hooks used by the Dyne site. Marketing pages use the
`dyne_page` bundle and ordered Paragraph sections. Jobs, leaders, news,
perspectives, pipeline programs, policies, and quotations are reusable nodes.
Current editorial values are stored in Drupal fields; source catalogs preserve
import provenance and support repeatable migration.

## Archive sources and migrations

The five pipelines in `migrations/` all use the `dyne_archive` source plugin and
`dyne_archive_node` destination:

| Pipeline | Drupal bundle |
| --- | --- |
| `dyne_pages` | `dyne_page` |
| `dyne_jobs` | `job` |
| `dyne_leaders` | `leader` |
| `dyne_perspectives` | `perspective` |
| `dyne_news` | `news` |

The source combines `data/legacy-content.json` with
`content-archive/wordpress-supplement.json` at the project root. Both must come
from the same archive snapshot. Existing records are adopted through source-ID
ledgers and verified aliases; source collisions and changed archive fingerprints
require reconciliation. Unpublished source records remain unpublished, and
rollback preserves content and revisions. See [migration details](migrations/README.md).

The retained catalogs have distinct purposes:

- `data/legacy-content.json` contains sanitized public source records.
- `data/legacy-page-metadata.json` records source hero, page-class, and SEO data.
- `data/legacy-source-inventory.json` records included and excluded source types.
- `data/legacy-asset-manifest.json` and `data/legacy-recovered-media.json` record
  source uploads, recovered videos, local paths, and verification hashes.
- `data/legacy-navigation-update.json` and
  `data/legacy-verified-redirects.json` support targeted navigation repairs.
- Project-root `content-archive/` contains the complete record, media, redirect,
  theme-content, and supplemental catalogs required by the migration tooling.
  Keep this directory outside the public `web/` directory.

Retain the managed media under `web/sites/default/files/legacy/` and the static
brand assets in `web/themes/custom/dynetx/`. Catalogs describe those files; they
are not replacements for the uploaded assets.

Back up an existing database before applying pending updates or importing an
archive. The normal deployment update command is:

```sh
ddev drush updatedb -y
ddev drush cr
```

For archive migration order, existing-node adoption, native-field upgrade
commands, and rollback behavior, use [migrations/README.md](migrations/README.md).
For an intentional public-source reconciliation, the retained legacy importer
supports previewing changes before applying them:

```sh
ddev exec env DYNE_IMPORT_DRY_RUN=1 drush php:script web/modules/custom/dyne_migrate/scripts/import-legacy.php
ddev drush php:script web/modules/custom/dyne_migrate/scripts/import-legacy.php
ddev drush cr
```

Its source-hash ledger makes unchanged source records no-ops and protects later
editorial changes. The original main menu is retained in migration state;
superseded links are disabled rather than deleted. Long source titles are
preserved in `field_legacy_title` when they exceed Drupal's node-title limit.

To regenerate the portable public fixtures from an approved source archive:

```sh
python3 web/modules/custom/dyne_migrate/scripts/legacy/build-fixture.py /path/to/source-archive --web-root web
python3 -m unittest discover -s web/modules/custom/dyne_migrate/scripts/legacy/tests -v
ddev exec php web/modules/custom/dyne_migrate/scripts/legacy/tests/test-navigation.php
```

The extractor reads allowlisted content tables and public metadata without
executing WordPress or loading its database into Drupal. User credentials,
private operational data, form submissions, and plugin secrets are excluded
from public fixtures. Imported HTML omits scripts, inline event handlers,
unsafe URL schemes, and feed tracking pixels.

## Editorial model

The active native model exposes these editing tasks:

- `dyne_page`: page-level hero and SEO controls, with ordered Paragraph sections.
- `job`: department/location taxonomy references, application links, structured
  company and role descriptions, responsibilities, qualifications, workplace
  arrangement, and compensation fields.
- `leader`: role, biography, Media portrait, category, and display ordering.
- `news`: publication date, article sections, company information, contacts, and
  HTML/external-link/document dispatch controls.
- `perspective`: publication date, introduction, byline, Media image, and article
  sections.
- `pipeline_program`: program code, prevalence, target, phase/status, disease
  details, trial links, and program media.
- `privacy_policy` and `quote`: reusable policy sections and attributed quotations.

Team, careers, news, perspective, policy, quote, and pipeline components use the
current referenced records. The careers list queries published job nodes and
uses native department/location filters; publishing or editing a job invalidates
the dependent listing cache. Archived merged bodies, presentation metadata, and
historical revisions are retained for preservation and hidden where native
fields are the current editorial authority.

Images use core Media and Media Library. See the project documentation for
[image selection](../../../../docs/image-library.md) and
[pipeline authoring](../../../../docs/pipeline-programs.md). Source imports
preserve the editorial automatic/manual alias policy; normal title changes use
Pathauto and redirects according to the current site configuration.

## Field administration and verification

The `field_ui` dependency supplies Manage fields, Manage form display, and
Manage display. Grant the corresponding node/Paragraph administration
permissions only to trusted accounts. An anonymous account should receive
Access denied on schema administration routes.

New installations receive Paragraph displays through the module's install
hook. For an existing site's specific Paragraph display repair, use:

```sh
ddev drush php:script web/modules/custom/dyne_migrate/scripts/repair-paragraph-displays.php
ddev drush cr
```

Custom Twig templates render native entity values and structured sections, so
formatter changes in Manage display do not necessarily change every custom
component's output. Template changes should preserve field access and cache
dependencies.

Current read-only/editorial regression checks include:

```sh
ddev drush php:script scripts/tests/rich-text-editors.php
ddev drush php:script scripts/tests/structured-content-forms.php
ddev drush php:script scripts/tests/title-aliases.php
ddev exec php scripts/tests/structured-job-parser.php
ddev exec php scripts/tests/structured-article-parser.php
ddev exec php scripts/tests/structured-inline-rendering.php
ddev exec php scripts/tests/rich-text-component-fields.php
```

The separate tests ending in `-isolated.php` require their documented disposable
databases. Live Greenhouse/GCS synchronization belongs to `dyne_content_sync`;
OneTrust provider configuration belongs to `ot_webform`. Archive imports do not
configure or authorize those external services.
