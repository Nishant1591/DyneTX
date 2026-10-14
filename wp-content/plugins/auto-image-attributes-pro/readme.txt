=== Auto Image Attributes Pro ===
Contributors: arunbasillal
Donate link: https://millionclues.com/donate/
Tags: image title, image caption, image description, alt text, bulk edit images, bulk rename images, auto image attributes, auto image alt text, remove underscores, image seo
Requires at least: 6.3
Tested up to: 6.9
Stable tag: trunk
Requires PHP: 7.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Pro add-on of the popular Auto Image Attributes From Filename With Bulk Updater WordPress plugin.

== Description ==

Automatically add Image attributes such as Image Title, Image Caption, Description And Alt Text from Image Filename. 

The plugin can update image attributes for both new images and existing images in the media library. 

The pro add-on needs the basic plugin to function. You can download the basic version here: https://wordpress.org/plugins/auto-image-attributes-from-filename-with-bulk-updater/

With this plugin you can:

* Set the image filename as the image Title.
* Set the image filename as the image Caption.
* Set the image filename as the image Description.
* Set the image filename as the image Alt Text. This was a default feature in WordPress before 4.7. The plugin restores this essential feature which is great for SEO.
* Insert Image Title into post HTML. WordPress stopped adding Image Titles to images since WordPress 3.5. The plugin restores it.
* Remove hyphens from the image filename.
* Remove underscores from the image filename.
* Remove full stops from filename.
* Remove commas from filename.
* Remove all numbers from filename.
* Remove apostrophe ( ' ) from filename
* Remove tilde ( ~ ) from filename
* Remove plus ( + ) from filename
* Remove pound ( # ) from filename
* Remove ampersand ( & ) from filename
* Remove round brackets ( ( ) ) from filename
* Remove square brackets ( [ ] ) from filename
* Remove curly brackets ( { } ) from filename
* Filter words or characters from filename
* Filter filename with regex
* convert image attributes to lowercase
* CONVERT IMAGE ATTRIBUTES TO UPPERCASE
* Use title casing for image attributes. First Letter Of Each Word Will Be Capitalized.
* Use sentence casing for image attributes. First letter of a sentence will be capitalized.
* Clean the actual image filename after upload.
* Use post title as title text. If image is not attached to a post, image filename will be used instead.
* Use post title as alt text. If image is not attached to a post, image filename will be used instead.
* Use post title as caption. If image is not attached to a post, image filename will be used instead.
* Use post title as description. If image is not attached to a post, image filename will be used instead.
* Build your own attributes using [custom image attribute tags](https://imageattributespro.com/custom-image-attribute-tags/?utm_source=iap&utm_medium=readme) like `%filename%`, `%posttitle%`, `%sitetitle%`, `%category%`, `%tag%`, `%wc_variation%`, `%wc_variation_terms%`, `%yoastfocuskw%`, `%yoastseotitle%`, `%rankmathfocuskw%`, `%seopresstargetkw%`. Each custom tag will be replaced  with it's value. 
* Use Yoast Focus Keyword, Rank Math Focus Keyword or SEOPress Target Keyword as image attributes.
* Clear any image attribute by setting it as blank / empty. 
* Exclude images from Bulk Updater. A meta box and a checkbox is added to the `Media Library` > `Edit Media` sidebar. When checked, the bulk updater will not update the attributes of that image in the media library or in posts / products where the image is used. 
* Choose to turn off any of the above mentioned features.

With the Image Attributes Pro bulk updater you can:

* Set the image filename as image Title, Caption, Description and Alt Text after removing hyphens and underscores from the filename.
* Update any number of images in your Media Library in one click.
* Update image title and alt text for images inserted into posts and custom post types.
* Fine tune all settings. Choose what to update.
* Update image titles / alt text in media library and / or existing posts.
* Update image titles / alt text in media library and / or existing posts only if no title / alt text is set. Existing image titles / alt text will be preserved.
* Update image caption and description in the media library. Existing image captions and descriptions can be preserved.
* Build your own attributes using custom tags like `%filename%`, `%posttitle%`, `%sitetitle%`, `%category%`, `%tag%`, `%yoastfocuskw%`, `%yoastseotitle%`, `%rankmathfocuskw%`, `%seopresstargetkw%` and [many more](https://imageattributespro.com/custom-image-attribute-tags/?utm_source=iap&utm_medium=readme). Each custom tag will be replaced  with it's value. 
* Choose to turn off any of the above mentioned features.
* Bulk update image attributes in [ACF's WYSIWYG Editor](https://imageattributespro.com/acf-compatibility/?utm_source=iap&utm_medium=readme) and [Divi theme](https://imageattributespro.com/divi-compatibility/?utm_source=iap&utm_medium=readme).
* Modify auto generated image attributes using the [iaffpro_image_attributes filter](https://imageattributespro.com/codex/iaffpro_image_attributes/?utm_source=iap&utm_medium=readme).
* Choose specific post types to bulk update using the [iaffpro_included_post_types filter](https://imageattributespro.com/codex/iaffpro_included_post_types/?utm_source=iap&utm_medium=readme).
* Disable updating of attributes in media library completely and just updated attributes in Post HTML or vice versa.
* Add or remove custom image attributes using the [iaffpro_html_image_markup_post_update filter](https://imageattributespro.com/codex/iaffpro_html_image_markup_post_update/?utm_source=iap&utm_medium=readme)

Other Image Attributes Pro features:

* Bulk edit image attributes from the Media Library quickly and easily. [Read more.](https://imageattributespro.com/media-library-bulk-editing/?utm_source=iap&utm_medium=readme)
* Bulk Update image attributes from WordPress Media Library. Select images and choose "Update image attributes" Bulk action in Media Library (list view). [Read more.](https://imageattributespro.com/bulk-actions/?utm_source=iap&utm_medium=readme)
* Bulk Update image attributes from WordPress admin page for Posts, Pages and WooCommerce Products. Select the posts, pages or WooCommerce products in bulk and choose "Update image attributes" Bulk action. [Read more.](https://imageattributespro.com/bulk-actions/?utm_source=iap&utm_medium=readme)
* Copy image attributes to post HTML while updating in Media Library. Any changes made to image attributes in the media library will be automatically synced to the corresponding post HTML. [Read more.](https://imageattributespro.com/auto-copy-image-attributes-to-post-html-from-media-library/?utm_source=iap&utm_medium=readme)
* Update image attributes on post publish or update. Automatically updates image attributes when a post is published or updated. Ensures image attributes are always consistent with settings. [Read more.](https://imageattributespro.com/update-image-attributes-on-save-post/?utm_source=iap&utm_medium=readme)

== Installation ==

To install this plugin:

1. Install the plugin through the WordPress admin interface > Plugins > Add New > Upload the plugin.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Enter the license info. 
4. Go to WordPress Admin > Settings > Image Attributes Pro.

== Changelog ==

= 4.9 =
* Date: 17 December 2025
* Tested with WordPress 6.9
* New Feature: Added a selector in the UI (Bulk Updater Settings tab) to choose which post types to update while running the Bulk Updater. 
* New Filter: [iaffpro_safe_words_in_filename](https://imageattributespro.com/codex/iaffpro_safe_words_in_filename/?utm_source=iap&utm_medium=readme) to preserve specified words from being modified during filename-based attribute generation.
* Enhancement: Enable editing attributes of non image media in the Media Library using the [bulk editor](https://imageattributespro.com/media-library-bulk-editing/?utm_source=iap&utm_medium=readme).
* UX Enhancement: Event Log now updates automatically while the Bulk Updater is running.
* Bug fix: Prevent duplicate display of 'License saved' admin notice.

= 4.8 =
* Date: 08 November 2025
* New Custom Attribute: `%wc_variation%` to use WooCommerce product variation name in image attributes. The name will include the variation attribute and the term. Example: Color: Red, Size: Large. [Read more.](https://imageattributespro.com/custom-image-attribute-tags/?utm_source=iap&utm_medium=readme)
* New Custom Attribute: `%wc_variation_terms%` to use WooCommerce product variation terms in image attributes. This will only use the variation terms, separated by commas. Example: Red, Large. [Read more.](https://imageattributespro.com/custom-image-attribute-tags/?utm_source=iap&utm_medium=readme)
* New Filter: [iaffpro_get_posts_by_attachment_id](https://imageattributespro.com/codex/iaffpro_get_posts_by_attachment_id/?utm_source=iap&utm_medium=readme) to extend the results of Image Attributes Pro's image search.
* New Filter: [iaffpro_image_used_in_text](https://imageattributespro.com/codex/iaffpro_image_used_in_text/?utm_source=iap&utm_medium=readme) to customize the `Image Used In` text shown in the [Media Library meta box](https://imageattributespro.com/media-library-meta-box/?utm_source=iap&utm_medium=readme).
* New Filter: [iaffpro_image_name_from_filename_pre](https://imageattributespro.com/codex/iaffpro_image_name_from_filename_pre/?utm_source=iap&utm_medium=readme) to modify the filename of the image before Image Attributes Pro starts processing it.
* Enhancement: Added compatibility with Divi's Blurb module. 
* Enhancement: Improved search criteria to find WooCommerce product variations where the image is used. The post to which the image is uploaded to is also now considered as a fallback option.
* Enhancement: Efficiency improvements.
* UX Enhancement: Automatically install the required base plugin during Image Attributes Pro activation.

= 4.7 =
* Date: 20.October.2025
* New Filter: [iaffpro_disable_event_logging](https://imageattributespro.com/codex/iaffpro_disable_event_logging/?utm_source=iap&utm_medium=readme). Use this filter to disable event log file generation.
* Enhancement: Updated event log messaging for cases where image attributes are not updated.
* Enhancement: Preserve hyphens separated by space while using the `Remove hyphens ( - ) from filename` filter. This is useful to include hyphens in the generated image attribute while removing unwanted hyphens.
* Enhancement: Added a confirmation dialog box before event log file deletion.

= 4.6 =
* Date: 07.October.2025
* Enhancement: Apply title casing to words separated by hyphen and words in brackets.
* Enhancement: Improve performance of Media Library Meta Box.
* Enhancement: Added compatibility with webp images for certain functions.
* Tested with WordPress 6.8.3
* Minimum required WordPress version is now 6.3
* Tested with PHP 8.4.10
* Minimum required PHP version is now 7.1
* Updated Action Scheduler to 3.9.3
* Updated plugin update checker to version 5.6

= 4.5 =
* Date: 15.March.2024.
* Tested with WordPress 6.5.
* Tested with PHP 8.3.0.
* Enhancement: Added checks to use the correct filename for image attributes when an image file is renamed.
* Enhancement: Added checks to prevent fatal error if the Action Scheduler loaded is prior to version 3.3.0.

= 4.4 =
* Date: 09.November.2023.
* Tested with WordPress 6.4.1.
* New Feature: Display and [edit image attributes directly in Media Library](https://imageattributespro.com/media-library-bulk-editing/?utm_source=iap&utm_medium=readme) columns (list view).
* Enhancement: Limit the display of [Event Log](https://imageattributespro.com/event-log/?utm_source=iap&utm_medium=readme) to the most recent 10,000 entries from the log file. This prevents abnormal UI load time on websites with huge Event Log files.
* Enhancement: Remove trailing hyphens (if any) after cleaning image filenames using the Advanced and custom filter during a new image upload.
* UX Enhancement: Improved messaging when base plugin is not installed and activated.
* Bug fix: Handle edge cases were Image Attributes Pro is not loaded when a post is saved.

= 4.3.1 =
* Date: 11.October.2023.
* Tested with WordPress 6.3.1.
* Tested with PHP 8.2.10.
* Updated action scheduler to 3.6.3.
* Updated plugin update checker to version 5.2.

= 4.3 =
* Date: 25.April.2023.
* New Feature: Choose where to update image title and alt text while running the Bulk Updater. Image attributes can be updated in Media Library, post HTML, or both. What's the difference? Read: [Understanding How WordPress Store Image Attributes](https://imageattributespro.com/how-wordpress-store-image-attributes/?utm_source=iap&utm_medium=readme).
* New Feature: Added option to automatically copy image attributes from Media Library to post HTML while updating in Media Library. When enabled, any changes made to image attributes in the media library will be automatically synced to the corresponding post HTML. [Read more.](https://imageattributespro.com/auto-copy-image-attributes-to-post-html-from-media-library/?utm_source=iap&utm_medium=readme)
* New Feature: Added option to automatically update image attributes on post publish or update. Ensures that the image attributes are always consistent with settings. [Read more.](https://imageattributespro.com/update-image-attributes-on-save-post/?utm_source=iap&utm_medium=readme)
* New Feature: Save image caption added in the Gutenberg editor to the Media Library for new image uploads.
* New Custom Attribute: `%copymedialibrary%` to copy image attributes from Media Library to the post / product HTML in bulk.
* New Custom Attribute: `%imagetitle%`, `%imagealttext%`, `%imagecaption%` and `%imagedescription%`, for image title, alt text, caption and description respectively. This can be used to copy one attribute to other. For example, use `%imagedescription%` as image alt text to copy image description to alt text in bulk. [Read more.](https://imageattributespro.com/copy-description-to-alt-text-in-bulk/?utm_source=iap&utm_medium=readme)
* Enhancement: Remove orphan separators while generating custom attributes. If your attribute ends with a hyphen (-) or pipe symbol (|), it will be trimmed off. Can be filtered with [iaffpro_custom_attribute_tag_trim_list](https://imageattributespro.com/codex/iaffpro_custom_attribute_tag_trim_list/?utm_source=iap&utm_medium=readme).
* Enhancement: `!important` Developers please note: The third argument of `iaffpro_get_custom_attribute_tag_{%tagname%}()` is changed to an array. Please [refer documentation](https://imageattributespro.com/codex/iaffpro_get_custom_attribute_tag_tagname/?utm_source=iap&utm_medium=readme) if you have used this function to define your own custom attribute tags.
* UI Enhancement: Simplified UI by removing `Filter Settings` and `Custom Filter` from `Bulk Updater Settings`. The bulk updater will use the same filters from the `Advanced` settings from here on.
* UI Enhancement: Removed `Capitalization Settings` from `Bulk Updater Settings`. The bulk updater will use the capitalization settings from the `Advanced` settings from here on.
* UI Enhancement: Simplified UI for image attributes by removing the image attribute configuration in `Bulk Updater Settings`. The image attributes you set in the `Advanced` settings will be used by the bulk updater from here on.
* UI Enhancement: Hide attribute settings that are not selected in `General Settings` of `Bulk Updater Settings`. Makes the UI cleaner and easier to use.
* UI Enhancement: Copy custom attributes to all settings in one click using the new `Copy to all attributes` link in settings.
* Bug Fix: Fixed an issue where special characters were being replaced by their unicode values.

= 4.2 =
* Date: 28.March.2023.
* Tested with WordPress 6.2.
* New Feature: Compatibility with Advanced Custom Fields plugin to update image attributes in ACF's WYSIWYG editor. [Learn more.](https://imageattributespro.com/acf-compatibility/?utm_source=iap&utm_medium=readme)
* New Feature: Compatibility with Divi theme to update image attributes in Divi modules. [Learn more.](https://imageattributespro.com/divi-compatibility/?utm_source=iap&utm_medium=readme)
* New Custom Attribute: `%yoastseotitle%` to use Yoast SEO Title as image attributes. [Learn more.](https://imageattributespro.com/yoast-seo-title-as-image-attributes/?utm_source=iap&utm_medium=readme)
* Enhancement: The bulk updater is now faster by intelligently varying the batch size as per server performance and available resources.
* Enhancement: Updated default batch size of bulk updater to 20.
* UI Enhancement: Automatically redirect to license information page on plugin activation.

= 4.1 =
* Date: 01.February.2023.
* New Feature: Set image attributes automatically after importing WooCommerce products from a CSV file using the [WooCommerce product import feature](https://imageattributespro.com/bulk-update-alt-text-image-title-woocommerce/?utm_source=iap&utm_medium=readme#csv-product-imports).
* New Feature: Update image attributes of a single Image, Post, Page or WooCommerce Product using the "Update image attributes" row action in the Media Library, Post, Page and WooCommerce Product list. [Read more.](https://imageattributespro.com/row-actions/?utm_source=iap&utm_medium=readme)
* New Feature: Update image attributes from Post, Page or Product Editor directly using the new [Image Attributes Pro meta box](https://imageattributespro.com/posts-pages-products-meta-box/?utm_source=iap&utm_medium=readme).
* New Filter: [iaffpro_bu_batch_size](https://imageattributespro.com/codex/iaffpro_bu_batch_size/?utm_source=iap&utm_medium=readme). Use this filter to modify the batch size of the bulk updater of Image Attributes Pro. This filter can be used to increase or decrease the default batch size.
* Enhancement: Added a button to delete [Bulk Updater Event Log](https://imageattributespro.com/event-log/?utm_source=iap&utm_medium=readme) file.
* Enhancement: Add alt text even when `alt=""` placeholder is missing from image markup.
* Bug Fix: Fixed an issue where updating attributes using the Media Library Meta Box was not reflecting in the post HTML in certain cases.

= 4.0 =
* Date: 13.December.2022.
* New Feature: Bulk Updater will now run in the background and is much faster than before.
* New Feature: Event Log is now saved into a log file. [Learn More.](https://imageattributespro.com/event-log/?utm_source=iap&utm_medium=readme)
* UI Enhancement: Install and activate [Auto Image Attributes From Filename With Bulk Updater](https://wordpress.org/plugins/auto-image-attributes-from-filename-with-bulk-updater/) in one click if plugin is not installed and / or activated.
* Bug Fix: Fixed an issue where Image Attributes Pro bulk actions were executed along with other bulk actions.
* Bug Fix: Fixed an issue where image attributes of images excluded via the [Media Library meta box](https://imageattributespro.com/media-library-meta-box/?utm_source=iap&utm_medium=readme) were updated in certain cases.

= 3.2 =
* Date: 10.November.2022.
* Tested with WordPress 6.1.
* New Feature: Added support for external images. Image Attributes Pro can now update image attributes of external images used within posts / products.
* New Custom Attribute: `%excerpt%` to add post excerpt or WooCommerce product short description as image attribute.
* Enhancement: Added space after comma for Rank Math focus keywords. Previously multiple focus keywords were separated only by a comma.
* Enhancement: Display [Image Attributes Pro meta box](https://imageattributespro.com/media-library-meta-box/?utm_source=iap&utm_medium=readme) in Media Library only for images.
* Bug Fix: Fixed an issue where backslashes were stripped out of post content while updating image attributes.
* Bug Fix: Fixed a PHP error that occurred while loading [Image Attributes Pro meta box](https://imageattributespro.com/media-library-meta-box/?utm_source=iap&utm_medium=readme) for non-images in the media library.

= 3.1 =
* Date: 27.April.2022.
* Tested with WordPress 5.9.3.
* New Feature: Display a list of posts or products where an image is used. This list is available in the [Image Attributes Pro meta box](https://imageattributespro.com/media-library-meta-box/?utm_source=iap&utm_medium=readme) in `Media Library` > `Edit Media`.
* New Feature: Added a button to update image attributes in the [Image Attributes Pro meta box](https://imageattributespro.com/media-library-meta-box/?utm_source=iap&utm_medium=readme) in `Media Library` > `Edit Media`.
* Enhancement: Update attributes of all images including featured image and WooCommerce Product Gallery images while updating attributes of a post or product.
* Enhancement: Improved the messaging for Bulk Actions by making sure the success notice is displayed even when action takes longer than usual.
* Bug Fix: Fixed an edge case where searching for images matched partial ID's. Thanks [Farrel Coetzee](https://www.linkedin.com/in/farrel-coetzee-softdevman/) for his collaboration to debug.

= 3.0 =
* Date: 24.March.2022.
* Tested with WordPress 5.9.2.
* This version requires WordPress version 4.7 or above.
* This version requires `Auto Image Attributes From Filename With Bulk Updater` version 3.1 or above.
* New Feature: Bulk Update image attributes from WordPress Media Library. Select images and choose "Update image attributes" Bulk action in Media Library (list view). [Read more.](https://imageattributespro.com/bulk-actions/?utm_source=iap&utm_medium=readme)
* New Feature: Bulk Update image attributes from WordPress admin page for Posts, Pages and WooCommerce Products. Select the posts, pages or WooCommerce products in bulk and choose "Update image attributes" Bulk action. [Read more.](https://imageattributespro.com/bulk-actions/?utm_source=iap&utm_medium=readme)
* New Custom Attribute: `%category%` to add Category name for Post / WooCommerce product as image attribute.
* New Custom Attribute: `%tag%` to add Tag name for Post / WooCommerce product as image attribute.
* New Custom Attribute: `%seopresstargetkw%` to add SEOPress Target Keyword as image attribute. [Read more.](https://imageattributespro.com/seopress-target-keyword-as-image-attributes/?utm_source=iap&utm_medium=readme)
* New Filter: [iaffpro_custom_attribute_tag_category_taxonomy](https://imageattributespro.com/codex/iaffpro_custom_attribute_tag_category_taxonomy/?utm_source=iap&utm_medium=readme). Use this filter to extend `%category%` Custom Attribute Tag to other post types.
* New Filter: [iaffpro_custom_attribute_tag_category_names](https://imageattributespro.com/codex/iaffpro_custom_attribute_tag_category_names/?utm_source=iap&utm_medium=readme). Use this filter to modify the output of `%category%` Custom Attribute Tag.
* New Filter: [iaffpro_custom_attribute_tag_tag_taxonomy](https://imageattributespro.com/codex/iaffpro_custom_attribute_tag_tag_taxonomy/?utm_source=iap&utm_medium=readme). Use this filter to extend `%tag%` Custom Attribute Tag to other post types.
* New Filter: [iaffpro_custom_attribute_tag_tag_names](https://imageattributespro.com/codex/iaffpro_custom_attribute_tag_tag_names/?utm_source=iap&utm_medium=readme). Use this filter to modify the output of `%tag%` Custom Attribute Tag.
* Enhancement: Improved image discovery. The Bulk Updater will search in featured images and WooCommerce product gallery images to determine where the image is used. This changes the behaviour of Image Attributes Pro when an image is used on multiple posts / products. [Read More.](https://imageattributespro.com/image-attributes-pro-behavior-same-image-on-multiple-posts/?utm_source=iap&utm_medium=readme)
* Enhancement: Display %yoastfocuskw% custom attribute tag only when Yoast is active.
* Enhancement: Display %rankmathfocuskw% custom attribute tag only when Rank Math plugin is active.
* Bug Fix: Fixed an issue where settings were overwritten with default values during plugin activation if license information was not added to database.

= 2.0 =
* Date: 02.July.2021.
* New Feature: Modular Custom Attributes! Build your own attributes using custom tags. !IMPORTANT! Update to version 2.1 of `Auto Image Attributes From Filename With Bulk Updater` to use this feature. 
* New Feature: Exclude images from Bulk Updater. A new meta box and a checkbox is added to the `Media Library` > `Edit Media` sidebar. When checked, the bulk updater will not update the attributes of that image in the media library or in posts / products where the image is used.
* New Feature: Added `Bulk Updater Behaviour` choices for Image Captions and Image Descriptions. Bulk updater can be configured to preserve existing image captions and descriptions. 
* Enhancement: !IMPORTANT! Plugin structure was changed including the name of the main plugin file to meet WordPress standards. This will deactivate the plugin on update. Simply reactivate the plugin to fix the issue. 
* Enhancement: Changed text domain from abl_iaffpro_td to auto-image-attributes-pro to meet WordPress standards. 
* Enhancement: Converted License Key text box into a password field so that the license key is not easily copied. 
* Enhancement: Code improvements. Few key areas are more reliable and much easier to maintain. 
* Enhancement: Improved empty title detection on Gutenberg image block editor. 

= 1.4.1 =
* Date: 20.June.2021.
* Bug Fix: Image Titles were set to blank in a specific edge case. 
* Enhancement: Code improvements. 

= 1.4 =
* Date: 18.June.2021.
* New Feature: Added option to preserve existing image title and image alt text in the media library. You will find this in `Bulk Updater Settings` > `Bulk Updater Behaviour` for `Image Title Settings` and `Image Alt Text Settings`. Namely: `Update image titles in media library and posts only if no title is set. Existing image titles will not be changed.` and `Update alt text in media library and posts only if no alt text is set. Existing alt text will not be changed.`
* New Filter: [iaffpro_html_image_markup_post_update](https://imageattributespro.com/codex/iaffpro_html_image_markup_post_update/?utm_source=iap&utm_medium=readme). You can use this filter to modify image HTML markup and add or remove custom image attributes. The image HTML markup (<img alt="" title="" ...) without the closing '>' after it is updated by Image Attributes Pro is passed as argument to the filter. 
* Enhancement: Added UI link to account dashboard so that users can easily find their license key. 
* Enhancement: Removed mandatory lower case image filename conversion when `Advanced tab` > `Miscellaneous Settings` > `Clean actual image filename after upload` is enabled. 
* Enhancement: Updated plugin update checker library to v4.11.
* Enhancement: Code improvements. 

= 1.3 =
* Date: 07.January.2019.
* Enhancement: Added filter [iaffpro_image_attributes](https://imageattributespro.com/codex/iaffpro_image_attributes/?utm_source=iap&utm_medium=readme). Now you can modify the image attributes generated by the bulk updater and customize it before its inserted into the database. 
* Enhancement: Added filter [iaffpro_update_media_library](https://imageattributespro.com/codex/iaffpro_update_media_library/?utm_source=iap&utm_medium=readme) to disable updating of attributes in media library completely. 
* Enhancement: Added filter [iaffpro_included_post_types](https://imageattributespro.com/codex/iaffpro_included_post_types/?utm_source=iap&utm_medium=readme) to choose specific WordPress post types to bulk update. Want to update WooCommerce products only? This is the solution. 

= 1.02 =
* Date: 14.April.2018.
* Enhancement: Improved the plugin update checker with user friendly notices.
* Bug Fix: Fixed an edge case where the Bulk updater ignored the Bulk Updater General Settings. This was found and fixed during internal testing before any users reported it. 

= 1.01 =
* Date: 23.November.2017.
* New Feature: Added support for custom post types.
* Enhancement: Better search criteria for updating images within posts. The bulk updater is more comprehensive than before.
* Enhancement: Better handling of default options when upgrading from ver 1.3 IAFF Basic plugin.

= 1.0 =
* Date: 22.November.2017.
* First release of the plugin.

== Upgrade Notice ==

= 4.9 =
* Date: 17 December 2025 
* Tested with WordPress 6.9 
* New Feature: Added a selector in the UI (Bulk Updater Settings tab) to choose which post types to update while running the Bulk Updater. 
* New Filter: [iaffpro_safe_words_in_filename](https://imageattributespro.com/codex/iaffpro_safe_words_in_filename/?utm_source=iap&utm_medium=readme) to preserve specified words from being modified during filename-based attribute generation. 
* Enhancement: Enable editing attributes of non image media in the Media Library using the [bulk editor](https://imageattributespro.com/media-library-bulk-editing/?utm_source=iap&utm_medium=readme). 
* UX Enhancement: Event Log now updates automatically while the Bulk Updater is running. 
* Bug fix: Prevent duplicate display of 'License saved' admin notice. 

= 4.8 =
* Date: 08 November 2025
* New Custom Attribute: `%wc_variation%` to use WooCommerce product variation name in image attributes. The name will include the variation attribute and the term. Example: Color: Red, Size: Large. [Read more.](https://imageattributespro.com/custom-image-attribute-tags/?utm_source=iap&utm_medium=readme)
* New Custom Attribute: `%wc_variation_terms%` to use WooCommerce product variation terms in image attributes. This will only use the variation terms, separated by commas. Example: Red, Large. [Read more.](https://imageattributespro.com/custom-image-attribute-tags/?utm_source=iap&utm_medium=readme)
* New Filter: [iaffpro_get_posts_by_attachment_id](https://imageattributespro.com/codex/iaffpro_get_posts_by_attachment_id/?utm_source=iap&utm_medium=readme) to extend the results of Image Attributes Pro's image search.
* New Filter: [iaffpro_image_used_in_text](https://imageattributespro.com/codex/iaffpro_image_used_in_text/?utm_source=iap&utm_medium=readme) to customize the `Image Used In` text shown in the [Media Library meta box](https://imageattributespro.com/media-library-meta-box/?utm_source=iap&utm_medium=readme).
* New Filter: [iaffpro_image_name_from_filename_pre](https://imageattributespro.com/codex/iaffpro_image_name_from_filename_pre/?utm_source=iap&utm_medium=readme) to modify the filename of the image before Image Attributes Pro starts processing it.
* Enhancement: Added compatibility with Divi's Blurb module. 
* Enhancement: Improved search criteria to find WooCommerce product variations where the image is used. The post to which the image is uploaded to is also now considered as a fallback option.
* Enhancement: Efficiency improvements.
* UX Enhancement: Automatically install the required base plugin during Image Attributes Pro activation.

= 4.7 =
* Date: 20.October.2025 
* New Filter: [iaffpro_disable_event_logging](https://imageattributespro.com/codex/iaffpro_disable_event_logging/?utm_source=iap&utm_medium=readme). Use this filter to disable event log file generation. 
* Enhancement: Updated event log messaging for cases where image attributes are not updated. 
* Enhancement: Preserve hyphens separated by space while using the `Remove hyphens ( - ) from filename` filter. This is useful to include hyphens in the generated image attribute while removing unwanted hyphens. 
* Enhancement: Added a confirmation dialog box before event log file deletion.

= 4.6 =
* Date: 07.October.2025
* Enhancement: Apply title casing to words separated by hyphen and words in brackets.
* Enhancement: Improve performance of Media Library Meta Box.
* Enhancement: Added compatibility with webp images for certain functions.
* Tested with WordPress 6.8.3
* Minimum required WordPress version is now 6.3
* Tested with PHP 8.4.10
* Minimum required PHP version is now 7.1
* Updated Action Scheduler to 3.9.3
* Updated plugin update checker to version 5.6

= 4.5 =
* Date: 15.March.2024.
* Tested with WordPress 6.5.
* Tested with PHP 8.3.0.
* Enhancement: Added checks to use the correct filename for image attributes when an image file is renamed.
* Enhancement: Added checks to prevent fatal error if the Action Scheduler loaded is prior to version 3.3.0.

= 4.4 =
* Date: 09.November.2023.
* Tested with WordPress 6.4.1.
* New Feature: Display and edit image attributes directly in Media Library columns (list view).
* Enhancement: Limit the display of [Event Log](https://imageattributespro.com/event-log/?utm_source=iap&utm_medium=readme) to the most recent 10,000 entries from the log file. This prevents abnormal UI load time on websites with huge Event Log files.
* Enhancement: Remove trailing hyphens (if any) after cleaning image filenames using the Advanced and custom filter during a new image upload.
* UX Enhancement: Improved messaging when base plugin is not installed and activated.
* Bug fix: Handle edge cases were Image Attributes Pro is not loaded when a post is saved.

= 4.3.1 =
* Date: 11.October.2023.
* Tested with WordPress 6.3.1.
* Tested with PHP 8.2.10.
* Updated action scheduler to 3.6.3.
* Updated plugin update checker to version 5.2.

= 4.3 =
* Date: 25.April.2023.
* New Feature: Choose where to update image title and alt text while running the Bulk Updater. Image attributes can be updated in Media Library, post HTML, or both. Don't know the difference? Read: [Understanding How WordPress Store Image Attributes](https://imageattributespro.com/how-wordpress-store-image-attributes/?utm_source=iap&utm_medium=readme).
* New Feature: Added option to automatically copy image attributes from Media Library to post HTML while updating in Media Library. When enabled, any changes made to image attributes in the media library will be automatically synced to the corresponding post HTML. [Read more.](https://imageattributespro.com/auto-copy-image-attributes-to-post-html-from-media-library/?utm_source=iap&utm_medium=readme)
* New Feature: Added option to automatically update image attributes on post publish or update. Ensures that the image attributes are always consistent with settings. [Read more.](https://imageattributespro.com/update-image-attributes-on-save-post/?utm_source=iap&utm_medium=readme)
* New Feature: Save image caption added in the Gutenberg editor to the Media Library for new image uploads.
* New Custom Attribute: `%copymedialibrary%` to copy image attributes from Media Library to the post / product HTML in bulk.
* New Custom Attribute: `%imagetitle%`, `%imagealttext%`, `%imagecaption%` and `%imagedescription%`, for image title, alt text, caption and description respectively. This can be used to copy one attribute to other. For example, use `%imagedescription%` as image alt text to copy image description to alt text in bulk. [Read more.](https://imageattributespro.com/copy-description-to-alt-text-in-bulk/?utm_source=iap&utm_medium=readme)
* Enhancement: Remove orphan separators while generating custom attributes. If your attribute ends with a hyphen (-) or pipe symbol (|), it will be trimmed off. Can be filtered with [iaffpro_custom_attribute_tag_trim_list](https://imageattributespro.com/codex/iaffpro_custom_attribute_tag_trim_list/?utm_source=iap&utm_medium=readme).
* Enhancement: `!important` Developers please note: The third argument of `iaffpro_get_custom_attribute_tag_{%tagname%}()` is changed to an array. Please [refer documentation](https://imageattributespro.com/codex/iaffpro_get_custom_attribute_tag_tagname/?utm_source=iap&utm_medium=readme) if you have used this function to define your own custom attribute tags.
* UI Enhancement: Simplified UI by removing `Filter Settings` and `Custom Filter` from `Bulk Updater Settings`. The bulk updater will use the same filters from the `Advanced` settings from here on.
* UI Enhancement: Removed `Capitalization Settings` from `Bulk Updater Settings`. The bulk updater will use the capitalization settings from the `Advanced` settings from here on.
* UI Enhancement: Simplified UI for image attributes by removing the image attribute configuration in `Bulk Updater Settings`. The image attributes you set in the `Advanced` settings will be used by the bulk updater from here on.
* UI Enhancement: Hide attribute settings that are not selected in `General Settings` of `Bulk Updater Settings`. Makes the UI cleaner and easier to use.
* UI Enhancement: Copy custom attributes to all settings in one click using the new `Copy to all attributes` link in settings.
* Bug Fix: Fixed an issue where special characters were being replaced by their unicode values.

= 4.2 =
* Date: 28.March.2023.
* Tested with WordPress 6.2.
* New Feature: Compatibility with Advanced Custom Fields plugin to update image attributes in ACF's WYSIWYG editor. [Learn more.](https://imageattributespro.com/acf-compatibility/?utm_source=iap&utm_medium=readme)
* New Feature: Compatibility with Divi theme to update image attributes in Divi modules. [Learn more.](https://imageattributespro.com/divi-compatibility/?utm_source=iap&utm_medium=readme)
* New Custom Attribute: `%yoastseotitle%` to use Yoast SEO Title as image attributes. [Learn more.](https://imageattributespro.com/yoast-seo-title-as-image-attributes/?utm_source=iap&utm_medium=readme)
* Enhancement: The bulk updater is now faster by intelligently varying the batch size as per server performance and available resources.
* Enhancement: Updated default batch size of bulk updater to 20.
* UI Enhancement: Automatically redirect to license information page on plugin activation.

= 4.1 =
* Date: 01.February.2023.
* New Feature: Set image attributes automatically after importing WooCommerce products from a CSV file using the [WooCommerce product import feature](https://imageattributespro.com/bulk-update-alt-text-image-title-woocommerce/?utm_source=iap&utm_medium=readme#csv-product-imports).
* New Feature: Update image attributes of a single Image, Post, Page or WooCommerce Product using the "Update image attributes" row action in the Media Library, Post, Page and WooCommerce Product list. [Read more.](https://imageattributespro.com/row-actions/?utm_source=iap&utm_medium=readme)
* New Feature: Update image attributes from Post, Page or Product Editor directly using the new [Image Attributes Pro meta box](https://imageattributespro.com/posts-pages-products-meta-box/?utm_source=iap&utm_medium=readme).
* New Filter: [iaffpro_bu_batch_size](https://imageattributespro.com/codex/iaffpro_bu_batch_size/?utm_source=iap&utm_medium=readme). Use this filter to modify the batch size of the bulk updater of Image Attributes Pro. This filter can be used to increase or decrease the default batch size.
* Enhancement: Added a button to delete [Bulk Updater Event Log](https://imageattributespro.com/event-log/?utm_source=iap&utm_medium=readme) file.
* Enhancement: Add alt text even when `alt=""` placeholder is missing from image markup.
* Bug Fix: Fixed an issue where updating attributes using the Media Library Meta Box was not reflecting in the post HTML in certain cases.

= 4.0 =
* Date: 13.December.2022.
* New Feature: Bulk Updater will now run in the background and is much faster than before.
* New Feature: Event Log is now saved into a log file. (Learn More.)[https://imageattributespro.com/event-log/?utm_source=iap&utm_medium=readme]
* UI Enhancement: Install and activate (Auto Image Attributes From Filename With Bulk Updater)[https://wordpress.org/plugins/auto-image-attributes-from-filename-with-bulk-updater/] in one click if plugin is not installed and / or activated.
* Bug Fix: Fixed an issue where Image Attributes Pro bulk actions were executed along with other bulk actions.
* Bug Fix: Fixed an issue where image attributes of images excluded via the (Media Library meta box)[https://imageattributespro.com/media-library-meta-box/?utm_source=iap&utm_medium=readme] were updated in certain cases.

= 3.2 =
* Date: 10.November.2022.
* Tested with WordPress 6.1.
* New Feature: Added support for external images. Image Attributes Pro can now update image attributes of external images used within posts / products.
* New Custom Attribute: `%excerpt%` to add post excerpt or WooCommerce product short description as image attribute.
* Enhancement: Added space after comma for Rank Math focus keywords. Previously multiple focus keywords were separated only by a comma.
* Enhancement: Display [Image Attributes Pro meta box](https://imageattributespro.com/media-library-meta-box/) in Media Library only for images.
* Bug Fix: Fixed an issue where backslashes were stripped out of post content while updating image attributes.
* Bug Fix: Fixed a PHP error that occurred while loading [Image Attributes Pro meta box](https://imageattributespro.com/media-library-meta-box/) for non-images in the media library.

= 3.1 =
* Date: 27.April.2022. 
* Tested with WordPress 5.9.3. 
* New Feature: Display a list of posts or products where an image is used. This list is available in the [Image Attributes Pro meta box](https://imageattributespro.com/media-library-meta-box/?utm_source=iap&utm_medium=readme) in `Media Library` > `Edit Media`. 
* New Feature: Added a button to update image attributes in the [Image Attributes Pro meta box](https://imageattributespro.com/media-library-meta-box/?utm_source=iap&utm_medium=readme) in `Media Library` > `Edit Media`. 
* Enhancement: Update attributes of all images including featured image and WooCommerce Product Gallery images while updating attributes of a post or product. 
* Enhancement: Improved the messaging for Bulk Actions by making sure the success notice is displayed even when action takes longer than usual. 
* Bug Fix: Fixed an edge case where searching for images matched partial ID's. Thanks [Farrel Coetzee](https://www.linkedin.com/in/farrel-coetzee-softdevman/) for his collaboration to debug. 

= 3.0 =
* Date: 24.March.2022.
* Tested with WordPress 5.9.2.
* This version requires WordPress version 4.7 or above.
* This version requires `Auto Image Attributes From Filename With Bulk Updater` version 3.1 or above.
* New Feature: Bulk Update image attributes from WordPress Media Library. Select images and choose "Update image attributes" Bulk action in Media Library (list view). [Read more.](https://imageattributespro.com/bulk-actions/?utm_source=iap&utm_medium=readme)
* New Feature: Bulk Update image attributes from WordPress admin page for Posts, Pages and WooCommerce Products. Select the posts, pages or WooCommerce products in bulk and choose "Update image attributes" Bulk action. [Read more.](https://imageattributespro.com/bulk-actions/?utm_source=iap&utm_medium=readme)
* New Custom Attribute: `%category%` to add Category name for Post / WooCommerce product as image attribute.
* New Custom Attribute: `%tag%` to add Tag name for Post / WooCommerce product as image attribute.
* New Custom Attribute: `%seopresstargetkw%` to add SEOPress Target Keyword as image attribute. [Read more.](https://imageattributespro.com/seopress-target-keyword-as-image-attributes/?utm_source=iap&utm_medium=readme)
* New Filter: [iaffpro_custom_attribute_tag_category_taxonomy](https://imageattributespro.com/codex/iaffpro_custom_attribute_tag_category_taxonomy/?utm_source=iap&utm_medium=readme). Use this filter to extend `%category%` Custom Attribute Tag to other post types.
* New Filter: [iaffpro_custom_attribute_tag_category_names](https://imageattributespro.com/codex/iaffpro_custom_attribute_tag_category_names/?utm_source=iap&utm_medium=readme). Use this filter to modify the output of `%category%` Custom Attribute Tag.
* New Filter: [iaffpro_custom_attribute_tag_tag_taxonomy](https://imageattributespro.com/codex/iaffpro_custom_attribute_tag_tag_taxonomy/?utm_source=iap&utm_medium=readme). Use this filter to extend `%tag%` Custom Attribute Tag to other post types.
* New Filter: [iaffpro_custom_attribute_tag_tag_names](https://imageattributespro.com/codex/iaffpro_custom_attribute_tag_tag_names/?utm_source=iap&utm_medium=readme). Use this filter to modify the output of `%tag%` Custom Attribute Tag.
* Enhancement: Improved image discovery. The Bulk Updater will search in featured images and WooCommerce product gallery images to determine where the image is used. This changes the behaviour of Image Attributes Pro when an image is used on multiple posts / products. [Read More.](https://imageattributespro.com/image-attributes-pro-behavior-same-image-on-multiple-posts/?utm_source=iap&utm_medium=readme)
* Enhancement: Display %yoastfocuskw% custom attribute tag only when Yoast is active.
* Enhancement: Display %rankmathfocuskw% custom attribute tag only when Rank Math plugin is active.
* Bug Fix: Fixed an issue where settings were overwritten with default values during plugin activation if license information was not added to database.

= 2.0 =
* Date: 02.July.2021.
* Enhancement: !IMPORTANT! Plugin structure was changed including the name of the main plugin file to meet WordPress standards. This will deactivate the plugin on update. Simply reactivate the plugin to fix the issue. 
* New Feature: Modular Custom Attributes! Build your own attributes using custom tags. !IMPORTANT! Update to version 2.1 of `Auto Image Attributes From Filename With Bulk Updater` to use this feature. 
* New Feature: Exclude images from Bulk Updater. A new meta box and a checkbox is added to the `Media Library` > `Edit Media` sidebar. When checked, the bulk updater will not update the attributes of that image in the media library or in posts / products where the image is used.
* New Feature: Added `Bulk Updater Behaviour` choices for Image Captions and Image Descriptions. Bulk updater can be configured to preserve existing image captions and descriptions. 
* Enhancement: Changed text domain from abl_iaffpro_td to auto-image-attributes-pro to meet WordPress standards. 
* Enhancement: Converted License Key text box into a password field so that the license key is not easily copied. 
* Enhancement: Code improvements. Few key areas are more reliable and much easier to maintain. 
* Enhancement: Improved empty title detection on Gutenberg image block editor. 

= 1.4.1 =
* Date: 20.June.2021.
* Bug Fix: Image Titles were set to blank in a specific edge case. 
* Enhancement: Code improvements. 

= 1.4 =
* Date: 18.June.2021.
* New Feature: Added option to preserve existing image title and image alt text in the media library. You will find this in `Bulk Updater Settings` > `Bulk Updater Behaviour` for `Image Title Settings` and `Image Alt Text Settings`. Namely: `Update image titles in media library and posts only if no title is set. Existing image titles will not be changed.` and `Update alt text in media library and posts only if no alt text is set. Existing alt text will not be changed.`
* New Filter: [iaffpro_html_image_markup_post_update](https://imageattributespro.com/codex/iaffpro_html_image_markup_post_update/?utm_source=iap&utm_medium=readme). You can use this filter to modify image HTML markup and add or remove custom image attributes. The image HTML markup (<img alt="" title="" ...) without the closing '>' after it is updated by Image Attributes Pro is passed as argument to the filter. 
* Enhancement: Added UI link to account dashboard so that users can easily find their license key. 
* Enhancement: Removed mandatory lower case image filename conversion when `Advanced tab` > `Miscellaneous Settings` > `Clean actual image filename after upload` is enabled. 
* Enhancement: Updated plugin update checker library to v4.11.
* Enhancement: Code improvements. 

= 1.3 =
* Enhancement: Added filter iaffpro_image_attributes. Now you can modify the image attributes generated by the bulk updater and customize it before its inserted into the database. 
* Enhancement: Added filter iaffpro_update_media_library to disable updating of attributes in media library completely. 
* Enhancement: Added filter iaffpro_included_post_types to choose specific WordPress post types to bulk update. Want to update WooCommerce products only? This is the solution. 

= 1.02 =
* Enhancement: Improved the plugin update checker with user friendly notices.
* Bug Fix: Fixed an edge case where the Bulk updater ignored the Bulk Updater General Settings. This was found and fixed during internal testing before any users reported it. 

= 1.01 =
* New Feature: Options to choose individual image attributes for NEW uploads. 

= 1.0 =
* First release of the plugin.