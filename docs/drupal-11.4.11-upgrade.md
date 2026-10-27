# Drupal 11.4.11 upgrade

Verified locally on October 27, 2026 at
<http://dyne-tx-workspace.ddev.site/>. This was an in-place patch upgrade of the
existing Drupal site. PHP remains 8.3.32; the DDEV configuration is unchanged.

## Dependency changes

| Package | Before | After |
| --- | --- | --- |
| drupal/core | 11.4.6 | 11.4.11 |
| drupal/core-recommended | 11.4.6 | 11.4.11 |
| drupal/core-composer-scaffold | 11.4.6 | 11.4.11 |
| drupal/core-project-message | 11.4.6 | 11.4.11 |
| drupal/core-dev | 11.4.6 | 11.4.11 |
| drupal/webform | 6.3.0 | 6.3.1 |
| symfony/http-foundation | v7.4.18 | v7.4.21 |
| symfony/yaml | v7.4.18 | v7.4.21 |

The core requirements in `composer.json` are exact pins, including `core-dev`.
Webform's minimum is `^6.3.1`; its locked version is 6.3.1. The Webform security
patch was explicitly approved during this upgrade. No other package versions
changed. Installed dependency files are included because this repository tracks
`web/core`, contributed modules, and `vendor`.

The [official Drupal release notes](https://www.drupal.org/project/drupal/releases/11.4.11)
identify the `.htaccess` change from `assert.active` to `zend.assertions`.
Composer generated the updated file. It remains scaffold-managed and ignored by
`web/.gitignore`. The existing robots.txt scaffold exclusion remains in place.
A new `.gitattributes` scaffold exclusion protects this repository's existing
Git LFS video rules, which were restored after Composer initially replaced them.

## Verification

Evidence is in `output/drupal-11.4.11-upgrade/`. All five existing regression
commands passed before and after:

```sh
ddev drush php:script scripts/tests/rich-text-editors.php
ddev drush php:script scripts/tests/title-aliases.php
ddev drush php:script scripts/tests/structured-content-forms.php
ddev exec php scripts/tests/structured-inline-rendering.php
ddev exec php scripts/tests/rich-text-component-fields.php
```

Results: 166 CKEditor checks, 1,528 alias checks, 66 content-form checks, and 37
rendering/component cases. All four generated report JSONs match the baseline.
The captured fingerprints for configuration, nodes, node data, node revisions,
aliases, Pathauto states, and redirects are unchanged. Historical report files
outside the upgrade evidence folder were restored after testing.

The public verifier passed all 345 routes and 411 additional local assets/links
with zero failures or missing anchors in both runs. Route results are unchanged.
The anonymous Webform contact route returns HTTP 200 with expected fields;
anonymous edit and submission-results routes return HTTP 403. No forms were
submitted. Drupal's browser status report confirms version 11.4.11.

`ddev composer validate --no-check-publish` and `check-platform-reqs` passed.
Validation emits expected warnings about the user-requested exact core pins.
The final `composer audit --locked --format=json` reports no advisories and no
abandoned packages, compared with 25 advisories before the upgrade. This is a
dependency advisory check, not a complete application security audit.

`drush updatedb -y` reported no pending updates, `drush cr` completed, and
`drush config:status` reported no differences. No content or configuration import
was performed. Custom application modules, theme source, and exported
configuration were not changed.

## Installation and rollback

Apply the committed manifest and lockfile together in another environment,
back up its database and files, then use its normal maintenance/deployment
procedure with these commands:

```sh
ddev composer install --no-interaction
ddev drush updatedb -y
ddev drush cr
ddev drush status
ddev composer audit --locked
```

Use `composer install` to reproduce this lockfile; an unrestricted `composer
update` can select different dependencies. Re-run relevant checks in the target
environment. No production deployment was performed in this session.

The pre-upgrade local database backup is
`output/dyne-before-drupal-11.4.11-2026-10-27.sql.gz`; gzip integrity was checked.
`baseline/` contains the original Composer files, `.htaccess`, and Git revision.
For a rollback, restore the previous code and dependency lockfile together and
rebuild caches. Restore the database backup only if necessary, into the intended
local environment, after preserving any newer editorial work. The upgrade had no
pending schema updates, so a database restore was not needed or tested.

## Local environment limitations

The container could resolve package metadata but failed certificate validation
for GitHub archive downloads. The exact locked archives were downloaded with the
host's trusted TLS configuration, checked for ZIP integrity, and placed in
Composer's cache. `package-downloads.json` records URLs, versions, and SHA-256
hashes. TLS verification was never disabled. A fresh container may still need
the organization's trusted issuer configured before uncached downloads work.

The Drupal status page also reports a missing Colorbox library and other local
setup warnings, including unavailable core update-feed data, optional frontend
libraries, and development settings. These are not represented as passing by
the Composer audit. They were not changed as part of this patch upgrade. Live
external integrations and form delivery were not exercised.
