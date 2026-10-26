# Image selection and reuse

Content image controls use Drupal's core Media Library. Click **Add media**,
search or browse the thumbnail grid, select an image, then click **Insert
selected**. The same dialog offers **Add file** to upload a local image. The
grid is paginated, so it can expose the full library without loading every
image at once. The initial workspace library contains 479 managed Image Media
records with valid source files and thumbnails.

Update 8116 replaces the remaining upload-only controls for alert-banner logos,
CTA images and video posters with image-only Media references. Banner logos
also retain per-placement alternative text; selecting or reusing a logo does
not change the shared image's alternative text in other placements. The unused
generic Page image and Hero logo controls are hidden, matching their absence
from the page renderer. The existing hero, portrait, article, card, Quote and
Pipeline image controls already use Media Library and retain that behavior.

The Media image source form still uses a normal file upload. It is the upload
area inside the library, rather than another editorial selection control.
Core view configuration and role permissions are unchanged. Accounts need the
existing content-editing and image-creation permissions to upload; this upgrade
does not grant those permissions to all authenticated visitors.

## Preservation and rendering

Original File-image field storage and historical revisions remain intact.
New image fields are additive. The upgrade changes field/form configuration
without resaving existing nodes, paragraphs, Files or Media. Every currently
rendered legacy image control is empty, so no current content needs backfill.
Historical values remain renderable through a compatibility fallback. A normal
save adopts a legacy image into a reusable Media reference when appropriate;
already registered files are reused rather than duplicated.

Once the new field owns an image, clearing it stays empty. An old File value
cannot silently restore a removed image. The CTA's original default culture
image is preserved when it is first adopted. Media/file changes and access
results are included in render-cache metadata, and unpublished Media cannot
appear through the new component image bindings.

Only managed editorial images belong in the picker. Drupal's generic/video
system icons and the archive's many image-size derivatives are not registered
as additional editorial images by this update.

## Applying and checking

Use the normal deployment path after taking a database backup:

```sh
ddev export-db --file=output/dyne-before-image-library.sql.gz
ddev drush updatedb -y
ddev drush cr
```

The implementation uses the installed Drupal 11.4.6 Media Library field widget,
field-widget opener and Media APIs. Their installed core implementations were
checked on October 26, 2026. No new package, external asset service, credential
or browser integration is required.

Real-save tests must run against a separately created disposable clone:

```sh
ddev exec env DYNE_IMAGE_TEST_DATABASE=dyne_image_verify_example php scripts/tests/image-library-isolated.php
```

The runner refuses a working database name and switches connections before
Drupal boots. It verifies library configuration, field widgets, selection and
rendering, clearing, historical fallback and preservation. Test saves never use
the workspace's working database. Review the report under `output/image-library`.
The pre-update SQL backup is the exact restore boundary for configuration and
content; no original fields or files are removed by the upgrade.
