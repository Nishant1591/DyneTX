# XML sitemap

The public XML sitemap is `/sitemap.xml`. Manage it at
`/admin/config/search/xmlsitemap`. Previously XML Sitemap was enabled, but all
content bundles were excluded, generated XML was absent, and the saved
generation origin belonged to an older DDEV site.

Update 8117 includes public Pages, Jobs, Leaders, Perspectives, Pipeline
programs and HTML News. Drupal's anonymous-access check excludes unpublished
content; the existing `noindex` exclusion remains enabled. Additional filtering
excludes `/legacy-archive/*`, reusable Quote/Policy records and News whose public
listing destination is an external URL or document. The composed Privacy Policy
page remains included. The front-page node is excluded in favor of the module's
dedicated root entry, so the homepage appears once, at `/`.

RobotsTxt 1.6 supplies `/robots.txt` dynamically. Existing Drupal crawl rules
are retained; XML Sitemap's standard robots hook adds the absolute sitemap URL.
Original rules remain as an install fallback in
`web/sites/default/default.robots.txt`. Composer's scaffold mapping prevents a
static `web/robots.txt` from hiding this route again.

XML Sitemap regenerates changed URLs during normal Drupal cron. Existing cron
scheduling and feed sync settings are preserved. Production must schedule
`drush cron` through its normal scheduler; this change does not enable all of
Drupal's unrelated background maintenance on local page requests.

## Deployment

Back up the target database and install the locked dependencies. Use the target
site's actual origin in the normal update/configuration deployment process:

```sh
vendor/bin/drush --uri=https://your-site.example updatedb -y
vendor/bin/drush --uri=https://your-site.example config:import -y
vendor/bin/drush --uri=https://your-site.example state:set xmlsitemap_base_url https://your-site.example
vendor/bin/drush --uri=https://your-site.example cache:rebuild
vendor/bin/drush --uri=https://your-site.example xmlsitemap:rebuild
```

The existing `$settings['xmlsitemap_base_url']` override, if present, takes
precedence over state and must match that deployment. Base URL is environment
state, not exported configuration. The portable label is `Default sitemap`.
Run the rebuild after updates finish: a nested sitemap batch inside an update
batch can skip generation.

The update changes configuration, scheduler setup and sitemap index/cache data.
It does not save content entities, create revisions, alter aliases or submit
anything to external search engines. The pre-update database backup is the
rollback boundary; generated XML can be rebuilt from content.

## Verification

```sh
ddev drush --uri=https://dyne-tx-workspace.ddev.site php:script scripts/tests/sitemap.php
```

The read-only verifier reconciles the sitemap with anonymously accessible
content, parses generated XML, checks aliases/origin/exclusions and exercises
link policies on unsaved clones. Report: `output/sitemap/verification.json`.
HTTP checks also confirm `/sitemap.xml` returns XML and `/robots.txt` advertises
the same environment's sitemap.

References checked October 26, 2026:
[XML Sitemap 2.x](https://www.drupal.org/project/xmlsitemap), including installed
link storage, generator, Drush rebuild command and robots hook;
[RobotsTxt 1.6](https://www.drupal.org/project/robotstxt), including its installed
controller, install fallback and Composer scaffold instructions. Both installed
releases support this site's Drupal 11 version.
