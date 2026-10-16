# Dyne content synchronization

Cron fetches the public **dynetherapeutics Greenhouse board** and the GCS News JSON contract used by the legacy Workbox plugin. Normal page requests never fetch either provider. Both providers are **disabled by default**; existing migrated content continues to render.

## Operator workflow

1. Apply the native-field upgrade and enable this module (`ddev drush en dyne_content_sync -y`). The upgrade backfills Greenhouse public IDs and GCS IDs from the original WordPress metadata, so existing nodes are adopted instead of duplicated.
2. Preview jobs: `ddev drush dyne:sync greenhouse --dry-run`.
3. For GCS, supply the approved client identifier through a local `settings.php` configuration override, for example:
   ```php
   $config['dyne_content_sync.settings']['gcs_client_id'] = getenv('DYNE_GCS_CLIENT_ID') ?: '';
   ```
   Do not export credentials or copy the historical WordPress API key into version control. Preview using `ddev drush dyne:sync gcs --dry-run`.
4. After reviewing the preview, enable only the desired provider: `ddev drush config:set dyne_content_sync.settings greenhouse_enabled true -y` (or `gcs_enabled`).
5. Queue changes using `ddev drush dyne:sync greenhouse --enqueue`; process them with `ddev drush queue:run dyne_content_sync`. Drupal cron subsequently refreshes enabled providers at the configured interval (default one hour) and processes the queue.

The default CLI action is read-only even when `--dry-run` is omitted. Providers must be enabled before writes can be queued or processed. Disabling a provider also stops its already queued writes.

## Safeguards and operational behavior

* The complete response is validated before any rows are queued. Empty, malformed, duplicate-ID, wrong-employer, unrelated Jazz, missing-body and invalid-date responses fail without unpublishing or deleting any nodes.
* HTTPS and exact provider host allowlists apply to requests and application URLs. HTTP redirects are not followed. API exception messages omit sensitive URLs.
* Stable remote IDs identify nodes; aliases and IDs of existing nodes remain unchanged. New nodes receive an alias ending in their remote ID to avoid collisions.
* Updates create Drupal revisions. Later manual edits to feed-owned fields are detected against the last synchronized version and block overwrite until reviewed. Unpublished existing nodes remain unpublished.
* Missing jobs are **not deleted or unpublished automatically**. A missing item can be a temporary partial upstream response; closure is an editorial decision until an explicit provider completeness policy is approved.
* Locks and queue fingerprints suppress overlapping refreshes. Failed workers retain their queue item for retry. Content edits that conflict with the feed require editorial review; do not repeatedly run the queue until the conflict is resolved.
* GCS bodies are fetched only from `clientapi.gcs-web.com`, sanitized, and linked using the provider's hosted URL. Greenhouse content is sanitized; first-party Dyne marketing links stay local.

## Verification

`ddev exec php web/modules/custom/dyne_content_sync/tests/test-feed-payload.php` exercises validation and content-safety regressions. The database integration test is `ddev drush php:script web/modules/custom/dyne_content_sync/tests/test-feed-integration.php`; all temporary node/alias/state writes are rolled back.

Live GCS verification requires a deployment-approved client identifier; no live GCS call has been made without that configuration. Neither this module nor its tests submit job applications or forms.
