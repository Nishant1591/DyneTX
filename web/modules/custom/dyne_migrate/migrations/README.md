# Complete archive migrations

The five pipelines (`dyne_pages`, `dyne_jobs`, `dyne_leaders`, `dyne_perspectives`, and `dyne_news`) consume the 458 records in the retained source snapshot through the `dyne_archive` source plugin. They use the existing bundle names (`dyne_page`, `job`, `leader`, `perspective`, and `news`). The source combines the module's `data/legacy-content.json` with project-root `content-archive/wordpress-supplement.json`, which must have matching archive hashes. These source catalogs remain required for imports. Private, draft, and unrelated source entries retain their unpublished status.

Run after the existing architecture/native-field setup and managed-media import:

```sh
ddev drush migrate:status --tag='Dyne archive'
ddev drush migrate:import dyne_pages,dyne_jobs,dyne_leaders,dyne_perspectives,dyne_news
# Required after creating missing archive nodes on a fresh database:
ddev drush php:script scripts/upgrade-native-content.php
ddev drush php:script scripts/upgrade-native-components.php
ddev drush php:script scripts/upgrade-native-layouts.php
ddev drush cr
```

The destination adopts existing nodes through source-ID ledgers and verified aliases and records real native Migrate map entries. It does not overwrite their current fields, revisions, status, aliases or native components. Missing archive rows can be created with the archived components; run the documented native content/component upgrades after restoring a fresh database. The complete private `content-archive/wordpress-supplement.json` must accompany a deployment outside the public `web/` directory.

`--update` is safe for unchanged sources. A changed archive fingerprint fails with a reconciliation message, so editorial changes cannot be silently overwritten by an altered fixture. Source keys combine WordPress type and integer ID. Bundle/alias collisions fail rather than creating duplicates.

Rollback is intentionally **non-destructive**: map entries may be reset, but all migrated/adopted nodes and their revisions/aliases are preserved (`ROLLBACK_PRESERVE`). Restore a reviewed database backup when a full content rollback is required. Re-import after a map rollback re-adopts the same node IDs.

The URL ledger and numeric WordPress URL redirects are separate: `scripts/build-wordpress-redirects.py ARCHIVE`, then preview/apply `scripts/import-verified-redirects.php`. Unchanged aliases return 200 and do not receive self-redirects. Private archive aliases remain restricted. Disabled WordPress rules and conflicting historical slugs are recorded as exclusions.

## Verified historical URL behavior

The reviewed URL plan preserves 400 public numeric WordPress URLs and two unoccupied `_wp_old_slug` paths. The request subscriber also redirects all 2,539 verified legacy uploads (`/wp-content/uploads/...`) to their existing Drupal public-file paths, using an explicit manifest map. Unknown upload paths and the 12 unavailable PDF originals remain 404; no redirect points at a missing file. All 112 private archive nodes remain inaccessible through numeric WordPress URLs. `/home` retains Drupal's canonical redirect to `/`.

The archived Workbox News plugin contains dormant archive-generation methods, but registers its news post type with `has_archive => false`. Its `/newsyear/` rewrite also requires a published page containing `[news]`; the supplied SQL has none. Consequently `/news/newsyear/*` and `/press-releases/newsyear/*` are not verified active legacy routes and no invented destination is installed. Drupal's native news Paragraph offers year filtering if a new archive page is intentionally created later.

Validation: `ddev drush php:script scripts/test-archive-migrations.php` verifies complete maps, source/alias collisions, idempotency, fresh draft creation and rollback protection in a transaction. `python3 scripts/verify-wordpress-url-ledger.py` checks page aliases, numeric/historical redirects, upload redirects, missing-file 404s and private IDs against the local site.
