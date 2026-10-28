# DyneTX Drupal application

Dyne Therapeutics site built on Drupal **11.4.11**, PHP **8.5.12**, the custom `dynetx`
Bootstrap5 theme, and native Drupal content, Paragraphs and Media. Webform is
locked to **6.3.1**. The local preview is <https://dyne-tx-workspace.ddev.site/>.

## Run the existing site

```sh
ddev start
ddev composer install
ddev drush status
```

DDEV 1.25.5 or newer is required and selects the PHP 8.5 series. Its official
web image includes PHP 8.5.12; the build rejects older runtimes. Update DDEV to
its current stable release and run `ddev utility rebuild` for newer bundled
security patches. Composer requires 8.5.12 or a newer 8.5 patch.

This corporate-network workstation also has a local DDEV pre-build file that
imports the public corporate CA already trusted by macOS. That file and
certificate are gitignored. Other workstations behind the proxy need their
approved corporate CA configured before HTTPS package downloads can succeed.
The corporate filter currently blocks DDEV's optional MariaDB client package
refresh. The bundled client remains available, and Drupal's database connection
has been verified; the PHP upgrade does not require that download.

The existing local database already contains the site. Do not run `site:install`
or restore a database over it to start the application. Keep public media files
under `web/sites/default/files/` with the database when moving environments.

For a deployment or dependency upgrade, back up the target database first, then
apply the committed Composer lockfile, pending updates, and a cache rebuild:

```sh
ddev export-db --file=output/dyne-before-deployment.sql.gz
ddev composer install
ddev drush updatedb -y
ddev drush cr
```

See [Drupal 11.4.11 upgrade notes](docs/drupal-11.4.11-upgrade.md) for the verified
release changes, rollback revision and container certificate-trust limitation.
No production deployment is implied by this local setup.

## Configuration synchronization

`config/sync/` contains the versioned YAML configuration. It is intentionally
outside the public `web/` root. `web/sites/default/settings.php` selects it with
`$settings['config_sync_directory'] = dirname($app_root) . '/config/sync';`.

```sh
ddev drush config:status  # Compare YAML with active database configuration.
ddev drush cex            # Export reviewed database configuration to YAML.
ddev drush cim            # Import reviewed YAML into the database.
```

Configuration synchronization covers schema and settings. Editorial content,
users, database state and uploaded media require separate preservation.

## Repository contents

- `web/modules/custom/`: active content-model, migration, feed and form code.
- `web/themes/custom/dynetx/`: public rendering, styles, interactions and assets.
- `config/sync/`: exported Drupal configuration.
- `content-archive/`: private source catalogs used by imports and parser checks;
  keep this outside `web/` and include it when rebuilding the site.
- `scripts/`: maintained imports, upgrades, source extraction and verification.
- `scripts/tests/`: regression checks; scripts named `*-isolated.php` require
  the disposable databases documented in their headers.
- `docs/`: current authoring and deployment guidance.
- `output/`: ignored generated reports and local database backups. Four small
  source/baseline inputs remain versioned because regression checks consume
  them; `.gitkeep` files retain directories needed by existing tools.
- `vendor/`, `web/core/`, and contributed packages: installed Composer-managed
  dependencies, retained in this repository's existing layout.

Historical assessment reports, screenshots, before/after HTML captures, obsolete
sample seeders, duplicate diagnostics, unused sample CSVs and AI-validator
metadata have been removed. Standard Drupal/DDEV scaffolding, original source
archives and database backups are retained. Generated caches and Python bytecode
are ignored.

## Editorial features and migration

Pages, jobs, news, leaders, perspectives, policies, quotes and pipeline programs
use native Drupal entities and fields. Formatted fields use CKEditor 5, images
use Media Library, and title-based aliases use Pathauto and Redirect. Existing
content and revisions are preserved by the source-hash guards in the migration
code. Do not replace the archive imports with placeholder/sample content.

- [Archive import and content model](web/modules/custom/dyne_migrate/README.md)
- [Native Migrate pipelines](web/modules/custom/dyne_migrate/migrations/README.md)
- [Pipeline program authoring](docs/pipeline-programs.md)
- [Image selection and reuse](docs/image-library.md)
- [Sitemap deployment and verification](docs/sitemap.md)
- [OneTrust configuration](web/modules/custom/ot_webform/README.md)
- [External content feeds](web/modules/custom/dyne_content_sync/README.md)

OneTrust and external feed providers require explicit provider configuration.
Their live submissions/synchronization are separate from local regression tests.

The UserWay accessibility menu loads asynchronously through the theme's global
library using the original site's public account identifier. Its button is
positioned at the bottom right. Use the HTTPS preview above: the provider SDK
logs a browser-crypto error on HTTP, where its menu did not finish loading during
verification. The loader and account/position attributes are in
`web/themes/custom/dynetx/dynetx.libraries.yml`.

## Read-only regression checks

```sh
ddev drush php:script scripts/tests/rich-text-editors.php
ddev drush php:script scripts/tests/title-aliases.php
ddev drush php:script scripts/tests/structured-content-forms.php
ddev exec php scripts/tests/structured-inline-rendering.php
ddev exec php scripts/tests/rich-text-component-fields.php
ddev exec php scripts/tests/pipeline-program-parser.php
ddev exec php scripts/tests/structured-job-parser.php
ddev exec php scripts/tests/structured-article-parser.php
PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s web/modules/custom/dyne_migrate/scripts/legacy/tests -v
node --test web/modules/custom/ot_webform/tests/behavior.test.cjs
python3 scripts/verify-legacy-runtime.py --base http://dyne-tx-workspace.ddev.site
python3 scripts/verify-link-behavior.py --base http://dyne-tx-workspace.ddev.site
```

Generated verification reports are written under `output/`. The public verifiers
check local pages/assets and do not submit forms or follow external destinations.
Review other scripts before running them: migration, repair, transactional and
disposable-database checks have different execution requirements.
