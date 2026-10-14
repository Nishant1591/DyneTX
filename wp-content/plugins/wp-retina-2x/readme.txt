=== Perfect Images: Regenerate Thumbnails, Image Sizes, WebP & AVIF ===
Contributors: TigrouMeow
Tags: retina, webp, avif, thumbnails, regenerate
Donate link: https://www.patreon.com/meowapps
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 7.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Optimize image sizes, regenerate thumbnails, enable retina, convert to WebP/AVIF, or use cloud optimization. An essential image toolkit.

== Description ==

**Perfect Images handles the basics of WordPress image management that should have been built-in from the start.** Manage image sizes, disable the ones you don't need, add custom sizes, and regenerate thumbnails in bulk. It's the essential foundation every WordPress install needs for clean, efficient image handling.

Think of it as your image management base layer. WordPress creates too many sizes? Disable them. Need a custom thumbnail size? Add it. Want retina support or modern formats like WebP/AVIF? Enable those modules. Perfect Images gives you complete control without bloat.

Explore [our official site](https://meowapps.com/wp-retina-2x) and check out [the tutorial](https://meowapps.com/wp-retina-2x/tutorial/) to get started.

== Core Features ==

⚙️ **Image Size Management**
The foundation of everything. View all registered image sizes (WordPress defaults, theme sizes, plugin sizes), disable the ones you don't need, and add custom sizes. Finally, control over those pesky WordPress-generated sizes like `medium_large`, `1536x1536`, and `2048x2048`.

🔄 **Regenerate Thumbnails**
Bulk regenerate thumbnails after changing sizes or switching themes. Progress tracking, smart processing, and automatic cleanup of old unused sizes.

🖼️ **Retina Images (Module)**
Optional module for high-DPI displays. Automatically generate and serve crisp @2x versions of your thumbnails. Works seamlessly with WordPress responsive images.

🎨 **Modern Formats (Module)**
Optional WebP/AVIF conversion. Convert images to modern formats for smaller file sizes and faster loading—without replacing your originals.

🚀 **Easy IO (Module)**
Optional CDN-based image optimization via EWWW. Automatically converts and delivers your images in WebP/AVIF, resizes on the fly, and serves them from a global CDN—zero server configuration required.

🤖 **AI Features (Module)**
Optional AI-powered tools. Upscale images via Claid.ai when your source is too small, or use the AI Assistant to analyze your sizes and recommend which to enable or disable based on your theme's srcset needs.

== 🖼️ Retina Images ==

Your visitors expect sharp, crisp images. Perfect Images makes retina support effortless.

**Smart Generation:**

* Automatic retina creation for all thumbnail sizes
* Multiple delivery methods (Responsive Images, PictureFill, Retina.js)
* Works with WordPress srcset natively
* Full-size retina support

**Delivery Options:**

* Responsive Images: Modern, standard method
* PictureFill: Hybrid approach with fallbacks
* Retina.js: Client-side detection
* Choose what fits your theme best

== 🎨 Modern Formats ==

WebP and AVIF offer dramatically better compression than JPEG/PNG—up to 50% smaller files with the same visual quality.

**What You Get:**

* Generate WebP or AVIF for any image size
* Automatic browser detection and fallbacks
* Works alongside your original images
* Enable for thumbnails or full-size
* Responsive Images delivery built-in

== 🤖 AI Features ==

Perfect Images integrates with AI services to help you manage and optimize your images smarter.

**AI Assistant:**

* Analyzes your image sizes and recommends which to enable or disable
* Detects redundant retina sizes already covered by other thumbnails
* Understands srcset behavior—sizes don't need to be pixel-perfect
* Requires [AI Engine](https://wordpress.org/plugins/ai-engine/) plugin

**Upscaling (via Claid.ai):**

* Generate retina images without 2x source files
* Create thumbnails larger than the original image
* Multiple upscaling modes (Smart Enhance, Digital Art, Photo, etc.)
* Automatic or manual processing

== ⚙️ Image Tools ==

The Image Tools dashboard lets you manage all your media in one place—regenerate thumbnails, build retina images, generate WebP/AVIF, and more, individually or in bulk.

**Regenerate Thumbnails:**

* Bulk regenerate all thumbnails
* Progress tracking for large libraries
* Preserves custom crops
* Updates retina and WebP versions automatically

**Replace Images:**

* Swap images directly from Media Library
* Drag & drop replacement in dashboard
* Maintains all metadata and links
* Updates all thumbnails instantly

**Manage Sizes:**

* View all registered image sizes
* Disable unwanted sizes (medium_large, 1536x1536, etc.)
* Create custom sizes
* Track which sizes are enabled per image

**Disable Image Threshold:**

* Stop WordPress from creating "-scaled" versions
* Keep your original full-size images intact

== Pro Features ==

* Full-size retina images
* Full-size WebP/AVIF conversion
* Priority support

== Why Perfect Images? ==

**Essential Foundation**
Every WordPress site needs proper image size management and thumbnail control. Perfect Images makes it simple, giving you the baseline image handling WordPress should have included.

**Modular & Lightweight**
Start with just image size management and thumbnail regeneration. Enable retina, WebP, Easy IO, or AI modules only when you need them. No bloat, no unnecessary features.

**Works with Everything**
Compatible with WooCommerce, page builders, galleries, and any theme. It uses WordPress's native image handling, so there are no conflicts.

**No Database Bloat**
Everything works through WordPress's existing metadata structure. No custom tables, no performance overhead.

**Built for Real Workflows**
Developed by someone who manages dozens of WordPress sites. Every feature solves a real problem we've encountered over years of WordPress development.

== Installation ==

1. Upload `wp-retina-2x-pro` to `/wp-content/plugins/`
2. Activate through the 'Plugins' menu
3. Visit Perfect Images in your admin menu
4. Follow the **Setup Assistant** on the Overview tab—it walks you through configuring image sizes, retina, and optimization step by step
5. Enable additional modules as needed

For AI upscaling, sign up for [Claid.ai](https://claid.ai/pricing?via=meow) and add your API key. For CDN delivery, enable Easy IO and connect your site.

== Frequently Asked Questions ==

= Does Perfect Images slow down my site? =

No. Perfect Images processes images during upload or when you manually regenerate. There's no performance impact on the frontend—just optimized images served efficiently.

= Can I use WebP and Retina together? =

Absolutely! Perfect Images can generate both retina and WebP/AVIF versions of your images. Enable both modules and they work seamlessly together.

= Do I need AI for retina images? =

No. Perfect Images generates retina images from your uploaded originals by default. AI upscaling is optional—use it when you need to create retina versions larger than your source images.

= Which format should I use: WebP or AVIF? =

* **WebP**: Excellent browser support, great compression, safe choice for most sites
* **AVIF**: Better compression than WebP, but newer—check your visitor's browsers first

You can switch between them anytime in settings.

= Will this work with my page builder? =

Yes! Perfect Images works with WordPress's native image handling, so it's compatible with Elementor, Divi, Gutenberg, WooCommerce, and virtually any plugin or theme.

= Can I regenerate thumbnails for thousands of images? =

Yes! Perfect Images includes bulk regeneration with progress tracking. It handles large libraries efficiently.

= What happens to my existing images? =

Nothing, unless you choose to regenerate them. Perfect Images never modifies your original uploads—it creates additional optimized versions alongside them.

= Is this compatible with CDNs? =

Absolutely. Use the built-in Easy IO integration, or enter your own CDN domain in settings.

= Can I disable specific WordPress image sizes? =

Yes! Perfect Images lets you disable any registered size—WordPress defaults, theme sizes, plugin sizes—complete control.

== Changelog ==

= 7.2.2 (2026/09/14) =
* Update: Documented the options cache rules covering recursion and frozen image sizes.

= 7.2.1 (2026/09/05) =
* Fix: Custom image sizes were compared by value, so only the first one applied and the crop setting was ignored.
* Fix: Infinite recursion when saving or repairing options that exhausted memory.
* Update: Replacing a media file now requires permission to edit media, not just to upload files.
* Update: Reworked how options are read and written to avoid loops.

= 7.2.0 (2026/09/04) =
* Add: Option to serve all image URLs through Easy IO or the CDN, not only srcset.
* Update: CDN URL rewriting now applies to images only, so PDFs and other files keep their original URL.
* Add: Action fired when CDN settings change, allowing Meow Lightbox to clear its cached image URLs.
* Update: The plugins list now shows the actual license issue instead of a generic label.
* Fix: Error when media status data was empty.
* Update: Admin script loading priority and conditions.
* 🎵 Discuss with others about Wp Retina 2x on [the Discord](https://discord.gg/bHDGh38).
* 🌴 Keep us motivated with [a little review here](https://wordpress.org/support/plugin/wp-retina-2x/reviews/). Thank you!
* 🥰 If you want to help us, check our [Patreon](https://www.patreon.com/meowapps). Thank you!

= 7.1.9 (2026/08/20) =
* Add: Plugins board and AI analysis in the dashboard.
* Update: Minimum PHP version is now 8.1.
* Update: Native browser dialogs replaced with a NekoUI notice modal.
* Fix: Disabled image sizes were ignored during WordPress 7.1 client-side uploads.
* Fix: AVIF files were duplicated as avif.avif on existing AVIF images.

= 7.1.8 (2026/06/29) =
* Fix: Ensure correct file permissions are set for generated WebP and AVIF images.
* Fix: Preserve original dimensions during retina image upload when they already match.
* 🎵 Discuss with others about Wp Retina 2x on [the Discord](https://discord.gg/bHDGh38).
* 🌴 Keep us motivated with [a little review here](https://wordpress.org/support/plugin/wp-retina-2x/reviews/). Thank you!
* 🥰 If you want to help us, check our [Patreon](https://www.patreon.com/meowapps). Thank you!

= 7.1.7 (2026/05/30) =
* Fix: Fixed dashboard styling for NekoUI 2026.
* Fix: Fixed missing historyReader reference.
* Fix: Fixed rendering loop with tooltip.
* Fix: Ensure correct file permissions for generated WebP/AVIF images.

= 7.1.6 (2026/04/25) =
* Update: Reworked the ignore workflow for image entries in the dashboard.
* Fix: Engine functions now correctly check whether an image is set to ignored before processing.
* Update: Better UI/UX.
* 🎵 Discuss with others about Wp Retina 2x on [the Discord](https://discord.gg/bHDGh38).
* 🌴 Keep us motivated with [a little review here](https://wordpress.org/support/plugin/wp-retina-2x/reviews/). Thank you!
* 🥰 If you want to help us, check our [Patreon](https://www.patreon.com/meowapps). Thank you!

= 7.1.5 (2026/04/15) =
* Add: Meta box in the attachment editor for regenerating thumbnails directly from the media edit screen.

= 7.1.4 (2026/03/10) =
* Add: New options to disable AVIF and WebP thumbnail generation.
* Add: Ignore list for modern image formats so specific images or paths can be excluded from AVIF and WebP processing.

= 7.1.3 (2026/02/23) =
* Fix: Hotfix to prevent Easy IO and Modern Formats from being enabled at the same time.
* Fix: Corrected disabled image sizes that were incorrectly shown as pending.
* Update: Refreshed the Image Tools inspector.
* Fix: Improved custom size handling by correcting the error message, switching the input to a numeric field, fixing the AI modal height, and updating the default custom size settings.

= 7.1.2 (2026/02/22) =
* Add: Introduced an AI Assistant to Image Sizes and Retina Images to help recommend optimal sizes.
* Update: Overhauled the Dashboard and Overview tabs with redesigned size badges, clearer tooltips, a Setup Assistant, convenient module toggles, and a reordered tab flow.
* Update: Reorganized settings with a new Dev Tools tab, a cleaner Settings layout, polished Easy IO feature cards, GIF Thumbnails now enabled by default under General, and automatic disabling of CDN Domain when Easy IO is active to avoid conflicts.
* Fix: Improved Regenerate Thumbnails to properly skip disabled sizes, remove leftover ghost thumbnails, label untitled images, wrap size labels more neatly, and unify button text for consistency.
* Fix: Resolved a compatibility issue where cdn_this() could fail with Polylang domain-per-language setups.
* Fix: Prevented bulk operations from unnecessarily reloading the media list and stats.
* 🎵 Discuss with others about Wp Retina 2x on [the Discord](https://discord.gg/bHDGh38).
* 🌴 Keep us motivated with [a little review here](https://wordpress.org/support/plugin/wp-retina-2x/reviews/). Thank you!
* 🥰 If you want to help us, check our [Patreon](https://www.patreon.com/meowapps). Thank you!

= 7.0.9 (2025/12/16) =
* Update: Removed legacy features.
* Update: Enhanced UI.

= 7.0.8 (2025/12/03) =
* Update: Cleaned up duplicated code.
* Fix: Hotfix resolved the busy/loading state.
* Fix: Corrected tooltip positioning.

= 7.0.7 (2025/11/15) =
- Update: Moved Dashboard into Settings as the Images tab.    
- Update: Made various UI/UX tweaks.  
- Update: Upgraded to MeowKit.

= 7.0.6 (2025/10/01) =
* Fix: PRO version header display issue resolved.

= 7.0.5 (2025/09/29) =
* Update: Renamed "Regenerate" to "Build" with clearer icons.
* Fix: Disabled thumbnail size generation issues resolved.
* Fix: Non-registered sizes now handled correctly.
* Fix: Thumbnails regeneration optimized to delete unused sizes.
* Fix: Display issues corrected.

= 7.0.4 (2025/08/27) =
* Update: Version synchronization with WordPress.org repository.

= 7.0.3 (2025/08/27) =
* Fix: Ensure constants are only defined if not already set in simple_html_dom.php.
* Fix: Use temporary file for palette image conversion to avoid freezing GIF or animated original sources.
* Add: Debug and Logs features are now separated for better control.
* Add: Click on thumbnail and title for original size view and edit link.
* Fix: Preserve WebP sizes when module is disabled for potential restoration.
* Fix: Properly handle Retina sizes when module is disabled.
* Update: Sanitize options on load to ensure data integrity.
* Update: Refactored WebP options handling and ensured defaults are reset when module is disabled.

= 7.0.2 (2025/03/12) =
* Update: Added a check for WebP and AVIF support before using GD and Imagick.
* Fix: Corrected typo for better accuracy.
* Add: Implemented an Imagick handler for WebP and AVIF conversion.

= 7.0.1 (2025/02/17) =
* Update: Adjusted rendering to align with React 18 deprecation changes.
* Fix: Corrected display conditions for Retina Full Size uploads.
* Fix: Ensured AI options reset properly when AI features are disabled.

= 7.0.0 (2024/12/22) =
* Add: Upscaling feature with Claid.ai for high-quality image enhancement.
* Add: AI-generated thumbnails with history tracking for generated sizes.
* Add: New settings for restoration types, upscale methods, and locale/remote uploads.
* Update: Dashboard UI redesigned for a more intuitive and efficient experience.
* Update: Tooltips, clickable thumbnails, and streamlined bulk actions for improved usability.
* Fix: Retina sizes now handled correctly when the module is disabled.
* Fix: WebP sizes preserved when the module is disabled, with restoration options.
* Fix: Various bugs and optimizations for enhanced performance and data integrity.

= 6.6.6 (2024/11/04) =
* Fix: Meta Viewer tooltips.

= 6.6.5 (2024/10/17) =
* Fix: Handle more errors coming from server.
* Fix: Count was wrong in the stats.
* Fix: Links to docs.

= 6.6.4 (2024/09/18) =
* Fix: Make sure size names is an array.

= 6.6.3 (2024/08/01) =
* Fix: Make sure image is created prior to palette conversion.
* Fix: Disable WebP when Optimized Images is disabled.

= 6.6.2 (2024/07/07) =
* Fix: Issue with Responsive Image.

= 6.6.1 (2024/06/29) =
* Update: Enhanced the way WebP and AVIF images are handled.
* Update: Code was cleaned up and optimized for better performance.

= 6.6.0 (2024/06/23) =
* Fix: Minor issues and code cleanup.
* Update: Enabled WebP delivery for PNG images.
* Fix: Converted Palette images before WebP creation to prevent errors.
* Fix: Corrected sort functionality for Post Title.

= 6.5.7 (2024/05/24) =
* Fix: Clear logs functionality.
* Fix: Addressed warnings and meta sizes.
* Add: AVIF support and Optimizer check for AVIF.
* Remove: Lato font.

= 6.5.6 (2024/05/13) =
* Add: Import / Export Settings functionality.
* Update: Enhanced button readability in actions by reducing text.
* Update: User Interface improvements for Full-Size image handling.
* Add: Automatic building of Full-Size Webp images.
* Optimization: Optimized image handling by fetching only the mime type.

= 6.5.5 (2024/04/27) =
* Fix: Corrected an issue where "crop" was always set to true.
* Add: Introduced log reader, log cleaner, and secure anti-traversal for log paths to enhance security and functionality.

= 6.5.4 (2024/03/23) =
* Fix: Corrected actions for selected items and addressed missing status on entries.
* Update: Deprecated string variables for improved code quality.

= 6.5.3 (2024/03/16) =
* Fix: Issue in Regenerate Entries.
* Update: Make sure sizes are array to avoid issues.

= 6.5.2 (2024/02/02) =
* Add: Visual clue for media items lacking information, enhancing user awareness.
* Update: Improved logging for wp_get_image_editor errors for better troubleshooting.
* Update: Made MetadataViewer's status code more user-friendly, improving usability.
* Add: Optimized regenerate feature for thumbnails, alongside restoration of the previous version.
* Fix: Corrected issues with additional image sizes, ensuring comprehensive size coverage.

= 6.5.1 (2023/12/25) =
* Update: Enhanced thumbnail check before regeneration to process only when necessary.

= 6.5.0 (2023/12/04) =
* Update: Improved thumbnail viewing and metadata display for a more user-friendly experience.
* Add: Added and refined features for handling WebP images, including creation, deletion, and status updates.
* Update: Updates to ESLint, error handling, and API connections for improved performance and stability.

= 6.4.7 (2023/10/19) =
* Add: Custom Image Sizes.
* Fix: Logs will be only enabled if the option is checked.
* Update: Many enhancements in the code in preparation for new features.
* Update: For better confidentiality, the logs file is now randomly generated.

= 6.4.5 (2023/08/17) =
* Fix: The Replace Media link was breaking the layout, and showing even though this feature was not enabled.

= 6.4.4 (2023/07/21) =
* Update: Refreshed the UI, better checkboxes and so on.
* Update: Latest packages.
* Fix: There were many wrong links.

= 6.4.3 (2023/06/18) =
* Fix: Avoid issues related to ResizeObserver.

= 6.4.2 (2023/04/11) =
* Update: Better UI.
* Add: Optimizers (check if the binaries are installed in your server).

= 6.4.1 (2023/02/16) =
* Update: Common libs updated, should be less issues with updates.

= 6.4.0 (2023/02/03) =
* Fix: Replace wasn't working fine.

= 6.3.9 (2023/01/06) =
* Fix: Better handling of the image replacement.

= 6.3.8 (2022/12/09) =
* Update: Still going towards a better UI organization.

= 6.3.2 (2022/11/01) =
* Update: Better organization of the UI. This is just the first step, this plugin is going to improve a lot, with a cleaner UI, and everything will be modular (so you can disable what you don't need completely).

= 6.3.1 (2022/10/19) =
* Fix: There was an issue when the options are re-initialized and the sizes were not refreshed.

= 6.3.0 (2022/10/12) =
* Update: Enhanced way to handle options.

= 6.2.9 (2022/08/11) =
* Fix: Escape more HTML.
* Fix: Ignored entries were reset by the issues calculation.

= 6.2.8 (2022/06/16) =
* Fix: Security fix.
* Update: Remove all the notifications as they probably don't needed anymore.

= 6.2.4 (2022/04/14) =
* Fix: The Refresh Stats button should not reset the list of ignored entries.

= 6.2.3 (2022/03/19) =
* Update: Latest version of the framework and admin.

= 6.2.2 (2022/01/28) =
* Update: Better compatibility with latest version of WP.
* Fix: There was an useless error message about a modal.

= 6.2.1 (2021/12/07) =
* Fix: Avoid displaying the PHP Info logo in the Meow Apps Dashboard.
* Update: Composer version.

= 6.2.0 (2021/11/10) =
* Fix: Hide the Dashboard button in the header if the hide dashboard option is checked.

= 6.1.9 (2021/10/12) =
* Fix: Removed a JS issue which was showing an alert for no reason.

= 6.1.8 (2021/09/23) =
* Update: Common libs 3.6.

= 6.1.7 (2021/09/17) =
* Fix: Was trying to add a Retina image in the srcset even if it was non-existent (when used with a CDN).
* Update: Better sanitization in the common library.

= 6.1.6 (2021/08/31) =
* Update: Enhanced security.

= 6.1.5 (2021/08/31) =
* Update: New common library.
* Update: Better security (but we will add even enhanced it more in the next update).
* Update: Tiny UI enhancements.

= 6.1.4 (2021/07/06) =
* Update: Lot of enhancements in the UI.

= 6.1.3 (2021/04/29) =
* Fix: Little issue with some network sites.
* Fix: Now use the default jpeg_quality set in WP.
* Fix: The "Build Automatically" feature is now available even if no Retina Method is used.

= 6.1.2 =
* Fix: Avoid double slashes in the URLs of the scripts.
* Fix: Updated admin, which works better with PHP Error Logs.
* Add: Better paging.

= 6.1.1 =
* Annoucement: Partnership with Easy IO! Probably the best deal on the market to optimize your images :)
* Fix: Some variables should be initialized as arrays instead of booleans.
* Fix: The CDN domain could not be modifed.

= 6.1.0 =
* Fix: PictureFill was not being ran, the Responsive Images method was instead.

= 6.0.8 =
* Fix: Avoid crashing the Retina Dashboard when there are no Retina images at all.

= 6.0.7 =
* Update: Much better dashboard.
* Fix: Upload New Retina Image.
* Add: Dashboard search.
* Add: Ignore button.
* Update: Upload in directly in the dashboard.

= 6.0.5 =
* Add: Implementation of Easy IO (CDN + Image Optimization).
* Add: Versioning for images, when they are replaced (that helps CDNs to refresh themselves).

= 6.0.4 =
* Fix: The dashboard was crashing when a non-image was being shown.
* Update: Removed the unused code from the plugin.
* Update: Optimized the way data is loaded in the dashboard. 

= 6.0.3 =
* Fix: The API wasn't accessible anymore.
* Fix: Lazysizes was only working with PictureFill.
* Fix: Avoid the JS of common admin to load more than once.

= 6.0.2 =
* Update: A lot of new features: Image Sizes Management, Disable Image Threshold, Regenerate Thumbnails, Replace Images.
* Update: Completely new UI for the Dashboard and the Settings.

= 5.6.1 =
* Update: Lazysize from 5.1.1 to 5.2.2.
* Update: PHP Simple Dom updated to 1.9.1.

= 5.6.0 =
* Add: Option to remove the image size threshold (which is set to 2560 since WordPress 5.3). 

= 5.5.7 =
* Fix: Background CSS wasn't working properly in a few cases.
* Update: Lazysizes updated to 5.1.1 (from 5.0.0).
* Update: Parser optimized.

= 5.5.6 =
* Update: Lazysizes updated to 5.1.0 (from 4.0.4).

= 5.5.5 =
* Fix: Display Full-Size Retina uploader only if the option is active.

= 5.5.4 =
* Add: Filter for cropping plugins.

= 5.5.3 =
* Fix: Usage of Composer.
* Update: If available, will use the Full-Size Retina for generating Retina thumbnails.
* Fix: New version of HtmlDomParser.
* Update: New dashboard.

= 5.5.1 =
* Fix: Uploading a PNG as a Retina was turning its transparency into black.
* Fix: Now LazyLoad used with Keep SRC only loads one image, the right one (instead of two before). Thanks to Shane Bishop, the creator of EWWW (https://wordpress.org/plugins/ewww-image-optimizer/).

= 5.4.3 =
* Add: New hooks: wr2x_before_regenerate, wr2x_before_generate_thumbnails, wr2x_generate_thumbnails, wr2x_regenerate and wr2x_upload_retina.
* Fix: Issues where happening with a few themes (actually the pagebuilder they use) after the last update.
* Update: Lazysizes 4.0.4.

= 5.4.1 =
* Fix: Issues where happening with a few themes (actually the pagebuilder they use) after the last update.
* Update: Lazysizes 4.0.4.

= 5.4.0 =
* Update: Removed annoying message that could appear by mistake in the admin.
* Add: Direct upload of Retina for Full-Size (for Pro).

= 5.2.9 =
* Add: New option to Regenerate Thumbnails.
* Fix: Tiny CSS fix, and update fix.
* Important: A few options will be removed in the near future. Have a look at this: https://wordpress.org/support/topic/simplifying-wp-retina-2x-by-removing-options/.

= 5.2.8 =
* Fix: Security update.
* Update: Lazysizes 4.0.3.

= 5.2.6 =
* Fix: Avoid re-generating non-retina thumbnails when Generate is used.
* Fix: Use ___DIR___ to include plugin's files.
* Fix: Better explanation.

= 5.2.3 =
* Fix: Sanitization to avoid cross-site scripting.
* Fix: Additional security fixes.

= 5.2.0 =
* Fix: When metadata is broken, displays a message.
* Fix: A few icons weren't displayed nicely.
* Fix: When metadata is broken, displays a message.
* Update: From Lazysizes 3.0 to 4.0.1.
* Add: Option for forcing SSL Verify.

= 5.1.4 =
* Add: wr2x_retina_extension, wr2x_delete_attachment, wr2x_get_pathinfo_from_image_src, wr2x_picture_rewrite in the API.

= 5.0.5 =
* Fix: There was a issue with the .htaccess rewriting (Class ‘Meow_Admin’ not found).
* Update: Core was totally re-organized and cleaned. Ready for nice updates.
* Update: LazyLoading from version 2.0 to 3.0.
* Info: There will be an important warning showing up during this update. It is an important annoucement.

= 4.8.0 =
* Add: Retina Image Quality for JPG (between 0 and 100). I know this little setting was really wanted :)
* Fix: Disabled sizes weren't really disabled in the UI.
* Fix: Notices about Ignore appearing in other screens.
* Add: Handles incompatibility with JetPack's Photon.

= 4.7.7 =
* Add: The Generate button (and the bulk Generate) will now also Re-Generate the thumbnails as well (like the Renerate Thumbnails plugin). If you are interested in a option to disable this behavior, please say so in the WP forums.

= 4.7.6 =
* Fix: Issue with Pro being non-Pro outside of WP Admin.
* Fix: Retina debugging file was not being created properly.

= 4.7.5 =
* Fix: Don't delete the full-size Retina if we re-generate.
* Fix: Little issue with Ignore.
* Update: Additional debugging.

= 4.7.4 =
* Update: Retina was moved into a new Meow Apps menu. The whole Meow Apps menu can be then hidden. For a nicer WP admin. The whole admin UI was updated.
* Add: New PictureFill option: inline CSS background can be now replaced by Retina images (excellent for sliders for example).
* Add: Over HTTP Check option: check for retina image remotely, for example if you are using images from a different website or server, it will check for the Retina version. Works with the PictureFill method.
* Change: Mobile detection was completely turned off as I don't think it should be used, but let's see if some of yours still need it. Ideally I would like to remove it from the code.
* Fix: Check if the CDN is already present before modifying/adding.

= 4.6.0 =
* Fix: Button Details was not working properly.
* Fix: Removed the beta Retina Uploader which is not working yet (was included by mistake).
* Update: Added the info screen available in the Retina Dashboard in the Media Library as well and improved the UI a tiny bit (it was a bit messy if you had a lot of image sizes.)

= 4.5.8 =
* Update: LazyLoad 2.0.3
* Fix: Don't display Retina information for a media that is not an image.
* Update: Retina.js 2.0.0
* Fix: Drag & Drop upload was a bit buggy, it now has been improved a lot!
* Add: Option to hide the ads, flatter and message about the Pro.
* Update: Options styles.

= 4.4.6 =
* Update: LazyLoad 1.5
* Update: Retina.js 1.4
* Update: PictureFill JS 3.0.2
* Fix: LazyLoad was not playing well when WordPress creates the src-set by itself.
* Fix: Get the right max-upload size when using HHVM.
* Fix: Displays an error in the dashboard when the server-side fails to process uploads.
* Update: During bulk, doesn't stop in case of errors anymore but display an errors counter.
* Update: Ignore Responsive Images support if the media ID is not existent (in case of broken HTML).

= 4.4.0 =
* Info: Please read my blog post about WP 4.4 + Retina on https://meowapps.com/wordpress-4-4-retina/.
* Add: New "Responsive Images" method.
* Add: Lot more information is available in the Retina settings, to help the newbies :)
* Update: Headers are compliant to WP 4.4.
* Update: Dashboard has been revamped for Pro users. Standard users can still use Bulk functions.
* Update: Support for WP 4.4.

= 3.5.2 =
* Fix: Search string not null but empty induces error.
* Change: User Agent used for Pro authentication.
* Fix: Issues with class containing trailing spaces. Fixed in in SimpleHTMLDOM.
* Fix: Used to show weird numbers when using 9999 as width or height.
* Add: Filter and default filter to avoid certain IMG SRC to be checked/parsed by the plugin while rendering.

= 3.4.2 =
* Fix: Full-Size Retina wasn't removed when the original file was deleted from WP.
* Fix: Images set up with a 0x0 size must be skipped.
* Fix: There was an issue if the class starts with a space (broken HTML), plugin automatically fix it on the fly.
* Fix: Full-Size image had the wrong path in the Details screen.
* Fix: Option Auto Generate was wrongly show unchecked even though it is active by default.
* Update: Moved the filters to allow developers to use files hosted on another server.
* Update: Translation strings. If you want to translate the plugin in your language, please contact me :)

= 3.3.6 =
* Fix: There was an issue with local path for a few installs.
* Add: Introduced $wr2x_extra_debug for extra developer debug (might be handy).
* Fix: Issues with retina images outside the uploads directory.
* Add: Custom CDN Domain support (check the "Custom CDN Domain" option).
* Fix: Removed a console.log that was forgotten ;)
* Change: different way of getting the temporary folder to write files (might help in a few cases).

= 3.1.0 =
* Add: Lazy-loading option for PictureFill.
* Fix: For the Pro users having the IXR_client error.
* Fix: Plugin now works even behind a proxy.
* Fix: Little UI bug while uploading a new image.
* Add: In the dashboard, added tooltips showing the sizes of the little squares on hover.
* Fix: The plugin was not compatible with Polylang, now it works.

= 3.0.0 =
* Add: Link to logs from the dashboard (if logs are available), and possibility to clear it directly.
* Add: Replace the Full-Size directly by drag & drop in the box.
* Add: Support for WPML Media.
* Change: Picturefill script to 'v2.2.0 - 2014-02-03'.
* Change: Enhanced logs (in debug mode), much easier to read.
* Change: Dashboard enhanced, more clear, possibility of having many image sizes on the screen.
* Fix: Better handing of non-image media and image detection.
* Fix: Rounding issues always been present, they are now fixed with an 2px error margin.
* Fix: Warnings and issues in case of broken metadata and images.
* Add: (PRO) New pop-up screen with detailed information.
* Add: (PRO) Added Retina for Full-Size with upload feature. Please note that Full-Size Retina also works with the normal version but you will have to manually resize and upload them.
* Add: (PRO) Option to avoid removing img's src when using PictureFill.
* Info: The serial for the Pro version can be bought at https://meowapps.com/wp-retina-2x. Thanks for all your support, the plugin is going to be 3 years old this year! :)

= 2.6.0 =
* Add: Support Manual Image Crop, resize the @2x as the user manually cropped them (that's cool!).
* Change: Name will change little by little to WP Retina X and menus simplified to simply "Retina".
* Change: Simplification of the dashboard (more is coming).
* Change: PictureFill updated to 'v2.2.0 - 2014-12-19'.
* Fix: Issue with the upload directory on some installs.
* Info: Way more is coming soon to the dashboard, thanks for your patience :)
* Info: Manual Image Crop received a Pull Request from me to support the Retina cropping but it is not part of their current version yet (1.07). For a version of Manual Image Crop that includes this change, you can use my forked version: https://github.com/tigroumeow/wp-manual-image-crop.

= 1.6.0 =
* Add: HTML srcset method.

= 1.0.0 =
* Change: enhancement of the Retina Dashboard.
* Change: better management of the 'issues'.
* Change: handle images with technical problems.
* Fix: random little fixes again.
* Change: upload is now HTML5, by drag and drop in the Retina Dashboard!

= 0.9.4 =
* Fix: esthetical issue related to the icons in the Retina dashboard.
* Fix: warnings when uploading/replacing an image file.
* Change: Media Replace is not used anymore, the code has been embedded in the plugin directly.
* Update: to the new version of Retina.js (client-method).
* Fix: updated rewrite-rule (server-method) that works with multi-site.
* Fix: support for Network install (multi-site). Thanks to Jeremy (Retina-Images).

= 0.3.0 =
* Fix: was not generating the images properly on multisite WordPress installs.
* Add: warning message if using the server-side method without the pretty permalinks.
* Add: warning message if using the server-side method on a multisite WordPress install.
* Change: the client-method (retina.js) is now used by default.
* Fix: simplified version of the .htaccess directive.
* Fix: new version of the client-side method (Retina.js), works 100x faster.
* Fix: SQL optimization & memory usage huge improvement.

= 0.2.2 =
* Fix: the recommended resolution shown wasn't the most adequate one.
* Fix: in a few cases, the .htaccess wasn't properly generated.
* Fix: files were renamed to avoid conflicts.
* Add: paging for the Retina Dashboard.
* Add: 'Generate for all files' handles and shows if there are errors.
* Add: the Retina Dashboard.
* Add: can now generate Retina files in bulk.
* Fix: the cropped images were not 'cropped'.
* Add: The Retina Dashboard and the Media Library's column can be disabled via the settings.
* Fix: resolved more PHP warning and notices.

= 0.1 =
* Very first release.
