# Pipeline program authoring and migration

The earlier content type existed without records. The active pipeline chart and
details were stored in a marketing page component. Update 8114 adopts the current
composed editorial content into reusable native records and replaces that single
component in a new page revision. It does not use the older three-row chart or
the older standalone program pages as its source.

This uses Drupal's existing server-rendered architecture. Program records,
component references, rendering, and cache invalidation remain in the existing
custom module and theme.

## Authoring

Edit programs through **Content → Pipeline program**. Each program has separate
controls for its name, stable program code, alternative names, disease, U.S. and
European prevalence, target, chart label, milestone and chart bar width. Program
and disease headings, program description, disease overview, onset, cause,
symptoms and unmet therapeutic need use CKEditor 5 and the generic **Full HTML**
text format. Clinical trial links, managed images, video URL, video cover and
placement alt text have dedicated controls.

Update 8115 also keeps Restricted HTML ahead of Plain text in the format order.
Drupal uses label order to break weight ties; the earlier generic rename had
caused some empty text areas to default to Plain text without CKEditor. Stored
content formats and role permissions are unchanged.

The **Program pipeline** component on the Pipeline page owns its introduction,
chart heading, expansion copy and ordered program references. Drag these
references to reorder the chart. Add another Pipeline program record, then
reference it here to include it. Publication and node/field access are respected;
an unpublished reference is excluded from the public chart. Missing references
are skipped without falling back to archived copy.

Eight source-supported records are adopted: DYNE-251, DYNE-101, DYNE-302,
DYNE-253, DYNE-245, DYNE-244, DYNE-255 and DYNE-401. Only the first three have
detailed narratives in the current source. The others retain empty optional
detail fields until approved copy is supplied. Mobile and desktop details now
share one set of fields, including the complete FSHD description.

Chart labels are copied literally. For example, the source chart's “Commercial”
label and the same source's “investigational” narrative remain separate values.
The migration makes no clinical interpretation or external medical updates.

## Applying the upgrade

Back up the correct local project first. Existing sites apply the additive
configuration and adoption through the normal update path:

```sh
ddev export-db --file=output/dyne-before-pipeline-programs.sql.gz
ddev drush updatedb -y
ddev drush cr
```

For a fresh installation, import the existing archive/native page content first,
then use the script. Its default mode only writes a local dry-run report:

```sh
ddev drush php:script scripts/upgrade-pipeline-programs.php
ddev exec env DYNE_PIPELINE_APPLY=1 vendor/bin/drush php:script scripts/upgrade-pipeline-programs.php
```

The script generates `output/pipeline-programs/dry-run.json` or `migration.json`
when run. Historical generated reports were removed during repository cleanup.
The state key `dyne_migrate.pipeline_programs` stores exact source revision IDs,
source SHA-256 and resulting program/component IDs. Rerunning the adoption
preserves existing records and editorial edits; it does not create duplicates
or extra page revisions. Duplicate program codes, unknown sections and source
changes after planning cause a fail-fast error rather than replacing content.

Existing node IDs, aliases and earlier standalone pages remain intact. New
program aliases follow `/pipeline/[node:title]`; title changes use the existing
Pathauto/Redirect behavior. The previous Pipeline page and HTML component
revisions remain available. Reverting that page revision restores the earlier
component; it does not delete the newly created program records. A full database
backup is the exact restore boundary for configuration and content together.

## Verification

Read-only extraction tests:

```sh
ddev exec php scripts/tests/pipeline-program-parser.php
```

The parser test uses `output/pipeline-programs/live-components.json` as a
preserved source fixture. Keep this file when clearing generated test reports.

Real-save migration, rendering and editing tests require a separately created
database clone. The runner refuses to boot without an explicitly named
`dyne_pipeline_verify_*` database and switches the connection before Drupal
boots. Never run save tests transactionally in the working database.

```sh
ddev exec env DYNE_PIPELINE_TEST_DATABASE=dyne_pipeline_verify_example php scripts/tests/pipeline-programs-isolated.php
```

The isolated runner generates `output/pipeline-programs/isolated-verification.json`
with reconciliation, revision preservation, form controls, rendered edits and
publication checks.
