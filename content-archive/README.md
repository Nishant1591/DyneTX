# Private migration source catalog

This folder is outside Drupal's public `web/` document root intentionally.
It contains original editorial HTML, unpublished/private text and revision
history. It is local migration data, not a public website endpoint.

- `wordpress-records.json`: every WordPress post-table ID and its disposition;
  editorial text, historical ACF fields and 175 revision bodies are retained.
  Operational log and plugin configuration bodies are suppressed; users,
  passwords and form submissions are not included.
- `wordpress-supplement.json`: 114 additive Drupal node records. Only FSHD DAY
  and Our Focus are published; the other 112 remain unpublished.
- `wordpress-media.json`: all 429 source attachments, metadata, local file
  hashes, derivative paths, recovery evidence and 12 unavailable originals.
- `theme-content.json`: source theme text, including dormant historical banners
  preserved without activating them on the current site.
- `wordpress-additional-content.json`: allowlisted cached social posts and
  historical map, QR and widget/footer copy stored outside `wp_posts`. These are
  dated source records, not a live social feed or active site configuration.

The historical generated HTML inventory and one-time audit reports were removed
during repository cleanup. This source catalog remains because the maintained
migration scripts and parser tests read these records. Preserve the original
archive as the source of truth; this catalog is deliberately not a copy of
WordPress users, credentials or plugin runtime configuration.
