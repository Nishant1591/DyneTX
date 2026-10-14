# Dyne Therapeutics Drupal Migration
## Jira Task Progress Assessment

**Assessment date:** October 8, 2026  
**Source plan:** [jira_task.md](../jira_task.md)  
**Assessment scope:** Read-only comparison of the Jira task plan with repository artifacts and the active local Drupal runtime  
**Environment checked:** DDEV local site, `https://dyne-tx.ddev.site/`

## Executive Summary

The Jira Markdown describes six planned work packages and their expected deliverables. It does not contain task owners, progress states, completion dates, or evidence links. Check marks next to the word “Deliverable” identify the intended outputs; they should not be read as proof that those outputs have been completed.

The local Drupal foundation, core content model, and custom theme are implemented and running. However, the discovery inventory, complete SEO/redirect strategy, and hosting/CI/CD work are not evidenced as complete. The custom CSV migrations are registered but have not imported any rows. The site currently contains 22 published marketing pages populated through page-building scripts, not through completed CSV imports.

| Jira task | Assessment |
| --- | --- |
| 1. Sitemap and media inventory | **Partial; completion blocked by missing complete, approved inventory** |
| 2. SEO audit and redirect planning | **Partial; sample URL map only, no redirects imported** |
| 3. Acquia platform and DevOps | **Not evidenced; only local DDEV is established** |
| 4. Drupal 11 foundation | **Implemented and runtime-verified locally; deployment readiness not established** |
| 5. Content architecture and data model | **Schema implemented and installed; content migration incomplete** |
| 6. Global theme, navigation, and design system | **Partially implemented; Bootstrap5 used instead of Jira-specified Tailwind** |

No overall completion percentage is assigned because the task plan lacks approved scope, weights, owners, and acceptance results.

## Assessment Method

Statuses below are based on repository and current local-runtime evidence, not on Jira itself. “Implemented” means the code or configuration is present; it does not necessarily mean client acceptance, production deployment, or complete validation.

The live sitemap counts below came from the October 7, 2026 live-site review and are evidence of the inventory gap at that review date. Sitemap entries are not a complete or approved migration scope by themselves.

## Task 1: Sitemap and Media Inventory

**Jira deliverable:** Master Sitemap & Content Inventory CSV  
**Assessment:** **Partial / incomplete**

### Evidence of work completed

- [pages.csv](../web/modules/custom/dyne_migrate/data/pages.csv) maps 22 marketing-page slugs, WordPress URLs, and proposed Drupal paths.
- [jobs-sample.csv](../web/modules/custom/dyne_migrate/data/jobs-sample.csv) contains eight job records.
- The project also has [leaders.csv](../web/modules/custom/dyne_migrate/data/leaders.csv) with 11 rows and [perspectives.csv](../web/modules/custom/dyne_migrate/data/perspectives.csv) with seven rows.
- These four source CSVs contain 48 rows in total. The jobs file is explicitly a sample.
- An earlier live review found 54 URLs in the page sitemap and 59 URLs in the jobs sitemap. Nine community-letter pages were absent from the Drupal routes checked during that review.

### Not yet evidenced

- A consolidated, approved master inventory covering all relevant page, job, story, publication, and community-letter sources.
- A dedicated image, PDF, video, and other media inventory.
- Orphaned/duplicate URL analysis and a documented inclusion/exclusion decision for each source item.
- Client approval of source counts and scope.

**Next action:** Produce a source inventory that records each URL/content item, content family, last-modified evidence, media references, proposed destination, migration status, and client-approved inclusion or exclusion.

## Task 2: SEO Audit and Redirect Planning

**Jira deliverable:** 301 Redirect Mapping Matrix  
**Assessment:** **Partial / incomplete**

### Evidence of work completed

- [url-map.csv](../web/modules/custom/dyne_migrate/data/url-map.csv) contains six entries: two explicit 301 suggestions, two routes marked to keep, and two wildcard path patterns.
- The project declares Drupal Redirect, Metatag, Pathauto, and XML Sitemap dependencies in [composer.json](../composer.json).
- Redirect is enabled in the current local runtime.

### Not yet evidenced

- A complete mapping of legacy URLs to approved canonical destinations.
- A migration/import definition that turns `url-map.csv` entries into redirect entities.
- Any redirect records in the current local database; the latest read-only check found zero.
- A completed SEO metadata, canonical URL, indexing, or generated-sitemap comparison against WordPress.

**Next action:** Complete and approve the URL matrix, distinguish preserved URLs from redirects, implement the import/update method, and test status codes and canonical metadata in staging.

## Task 3: Acquia Platform and DevOps

**Jira deliverable:** Acquia Staging Environment Ready  
**Assessment:** **Not evidenced in this workspace**

### Evidence of work completed

- Local development runs with DDEV 1.25.2, Docker/WSL2, Apache-FPM, PHP 8.3, Node.js 20, and MariaDB 11.4, as declared in [.ddev/config.yaml](../.ddev/config.yaml).
- The local web and database services were running during the assessment.

### Not evidenced

- Acquia-specific configuration or an Acquia staging environment.
- BLT configuration or BLT framework dependencies.
- CI/CD workflows, automated deployment steps, environment-specific release configuration, or a rollback runbook.
- Production hosting, monitoring, backup, or restore verification.

This report establishes only that those artifacts were not evidenced in the reviewed workspace. It does not establish whether an Acquia account or external environment exists outside it.

**Next action:** Confirm the intended hosting provider, then document the development/staging/production topology, deployment pipeline, release permissions, rollback, and backup/restore approach.

## Task 4: Drupal 11 Foundation

**Jira deliverable:** Base Drupal Repository Build  
**Assessment:** **Implemented and verified locally; operational promotion remains incomplete**

### Evidence of work completed

- [composer.json](../composer.json) defines a Drupal 11 Composer project with `drupal/core-recommended` and PHP 8.3 or newer.
- The active local runtime reports Drupal 11.4.6, PHP 8.3.30, successful Drupal bootstrap, and an active database connection.
- DDEV starts the local Drupal web and MariaDB services.
- Contributed dependencies for Paragraphs, migration, SEO, redirects, Webform, and other site capabilities are declared and relevant modules are enabled locally.

### Remaining work

- A complete configuration export/promotion workflow is not established. Drush configuration status reports extensive configuration as present only in the database.
- A clean rebuild from repository-controlled configuration has not been demonstrated.
- Staging/production deployment and release automation remain part of the unverified platform task.

**Next action:** Define and validate configuration synchronization, clean-install/rebuild steps, dependency checks, and an automated baseline test before describing the foundation as deployment-ready.

## Task 5: Content Architecture and Data Model

**Jira deliverable:** Content Entity Schema Documentation  
**Assessment:** **Architecture implemented and installed; migration and governance incomplete**

### Evidence of work completed

- The custom module [dyne_migrate.install](../web/modules/custom/dyne_migrate/dyne_migrate.install) defines six node bundles, twelve Paragraph bundles, five taxonomy vocabularies, shared fields, and menu setup.
- The current database contains 22 published `dyne_page` nodes and 100 Paragraph entities. The five vocabularies contain 24 terms.
- Four CSV migration definitions are registered for pages, jobs, leaders, and perspectives. Current status is `Idle` for each migration, with **0 imported and 48 unprocessed rows**.
- The active theme and local site render the scripted marketing pages.
- A backend editing fix was also implemented: Field UI is enabled, default Paragraph form/view displays are present, and the repair check confirmed editable widgets for all 12 Paragraph bundles.

### Key gaps and risks

- The reusable `job`, `leader`, `perspective`, `news`, and `pipeline_program` bundles are not populated in the current database.
- The page migration targets the standard `page` bundle, while the current marketing pages use `dyne_page`; its mapping also does not build the page’s Paragraph stack.
- The job CSV contains sample data, and its field mapping does not match the active job schema.
- Some page content is embedded in scripts or JSON Paragraph fields instead of being managed as structured reusable records.
- Configuration is not yet demonstrably reproducible from version-controlled exports.

**Next action:** Reconcile the source inventory and active schema, approve complete source extracts, fix migration mappings, execute a staged migration, and reconcile imported records and relationships before production use.

## Task 6: Global Theme, Navigation, and Design System

**Jira deliverable:** Global Theme & Navigation Framework  
**Assessment:** **Partially implemented; framework differs from Jira plan**

### Evidence of work completed

- The custom `dynetx` theme is active locally. It extends the Bootstrap5 base theme and declares global CSS/JavaScript libraries in [dynetx.info.yml](../web/themes/custom/dynetx/dynetx.info.yml) and [dynetx.libraries.yml](../web/themes/custom/dynetx/dynetx.libraries.yml).
- The theme includes a page shell, menu templates, and twelve Paragraph templates.
- Navigation markup includes a desktop menu and Bootstrap off-canvas mobile drawer.
- Custom CSS includes responsive rules and project-specific presentation tokens.
- Current local pages render in the browser.

### Open decision and validation gaps

- Jira specifically calls for Tailwind. The repository uses Bootstrap5 and custom CSS, with no Tailwind build configuration. The client/project owner should confirm whether Bootstrap5 is an approved deviation or Tailwind remains a requirement.
- Responsive markup exists, but full desktop/mobile, cross-browser, accessibility, interaction, and page-by-page visual acceptance are not established by code presence alone.
- Navigation definitions do not prove all destinations and anchors resolve or match the current live site.

**Next action:** Record the CSS framework decision, complete a route/navigation inventory, and execute a documented responsive and visual acceptance pass.

## Priority Order for Pending Work

1. **Agree scope and source of truth:** complete and approve the page/content/media inventory, including jobs and community letters.
2. **Fix the migration foundation:** align active Drupal bundles and fields with source mappings; replace samples with approved source extracts.
3. **Complete URL continuity:** approve the legacy URL matrix, implement redirect import, and verify SEO metadata.
4. **Decide hosting and deployment:** confirm Acquia or an alternative; define CI/CD, configuration promotion, backup, and rollback.
5. **Resolve frontend framework direction:** confirm Bootstrap5 versus Tailwind, then perform responsive and visual acceptance.
6. **Run staged migration and client acceptance:** reconcile counts, media, routes, content, forms, and editorial workflows before production cutover.

## Evidence and Limitations

- The Jira task file is a proposed work breakdown, not a Jira export or completion log.
- Runtime facts were read from the local DDEV/Drush database and configuration; they do not describe an unobserved production system.
- The live sitemap/job counts and nine missing community-letter findings were established in the October 7 live-site review. Sitemap counts are not themselves an approved migration inventory.
- No migration, configuration import, deployment, code edit, or database update was performed for this assessment.
- The previous October 1 and October 7 reports in [reports/](.) provide additional context, but this assessment uses the current local runtime where available.

**Conclusion:** The local Drupal foundation, data model, and theme provide a working implementation base. Tasks 1-3 remain incomplete or unevidenced; Task 4 is established locally but not deployment-ready; Task 5 requires schema/mapping alignment and actual migration; Task 6 is implemented with Bootstrap5 rather than the Tailwind framework named in the Jira plan and still requires acceptance testing.