=== Content Mask ===
Contributors: alexdemchak
Donate Link: https://www.paypal.me/xhynk/
Tags: Embed, Domain Mask, Mask, Redirect, Link
Requires at Least: 4.7
Tested Up To: 7.0
Stable tag: 1.8.5.6
Requires PHP: 5.4
Author URI: https://xhynk.com/
Plugin URL: https://xhynk.com/content-mask/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed any external content on a Page, Post, or Custom Post Type without the need to use complicated domain forwarding or domain masks.

== Description ==

[Read More & View Demos Here](https://xhynk.com/content-mask/)

= Embed Any Content†‡ Into Your WordPress Website =

Content Mask allows you to embed any external content onto your own WordPress Pages, Posts, and Custom Post Types. The end result is fairly similar to setting up a [Domain Mask](http://www.networksolutions.com/support/what-is-web-forwarding-and-masking/), but the content is embedded into the front end of your website and is fully contained inside your WordPress permalink ecosystem.

> *Example*: If you built a landing page on `landing-page-builder.com/your-landing-page/`, you can simply create a new Page on your website at `your-site.com/landing-page/` and paste in the URL of your landing page. The Content Mask plugin will then download and cache of copy of your landing page directly on your website, so any visitors that come to `your-site.com/landing-page/` will see the landing page you built. This allows you to keep all of your links integrated into your WordPress Website.

**† Do not use Content Mask to embed any content that you do not own or do not otherwise have license to share, embed, frame, or distribute.**

= Simple 2-Step UI =

With a simple 2-Step UI, you can embed any external content into your website without any complicated URL Forwarding, DNS Records, or `.htaccess` rules to mess with.

1. Just enable the Content Mask on any Page, Post, or Custom Post type by clicking on the check mark.

2. Then put in the URL that contains the content you want to embed.

It's that simple!

= Powerful Embedding and Redirect Options =

- Using the Download method (default) will fetch the content from the Content Mask URL, cache it on your website, and replace the current page request with that content. By default, this cache lasts 4 hours - but it can be changed anywhere from "Never Cache" all the way up to "Cache for 4 Weeks". Caching prevents the need for additional requests that slow down your site.

- Using the Iframe method will replace the current page request with a full width/height, frameless iframe containing the host URL. This method is ideal if the URL you want to embed won't serve scripts, styles, or images to other URLs or IP Addresses. If you use the Download Method, and links or images look broken, you can try the Iframe method instead.

- Using the Redirect (301) method will simply redirect the visitor to the host URL.

= Simple Integrated Vistor Tracking =

In the Content Mask admin panel, you can enable tracking for Content Masked pages. This will allow you to see how many visitors are viewing these links. This is ideal for when you need to track acquisition, such as on a Landing Page.

- [Views] shows how many times that Content Mask page has been viewed by anybody (even logged in users)
- [Non-User] shows how many times it's been viewed by visitors that are _not_ logged in to the website.
- [Unique] shows how many times it's been viewed by unique IP addresses. Note: IP addresses are one-way hashed and are not identifiable in any way.

= Creating a Content Masked Page =

https://www.youtube.com/watch?v=_H7IWFwmVfo?rel=0

= Using the Content Mask Admin Panel =

https://www.youtube.com/watch?v=5hEBMKSLHxI?rel=0

= Notes: =

 - Do *NOT* use Content Mask on any content you aren't explicitly authorized to share or use. Please confirm you're allowed to utilize and embed the content before embedding any particular URL.
 
 - Content embedded using the Download method is cached using the [WordPress Transients API](https://codex.wordpress.org/Transients_API) for 4 hours by default. If the content on the external URL is updated and you would like a fresh copy, you may just click the "Update" button on the Page, Post, or Custom Post Type to refresh the transient, or click the "Refresh" link in the Content Mask Admin panel. You may also change the cache expiration timer per page anywhere from "Never" to "4 weeks".

 - You may use the [Transients Manager](https://wordpress.org/plugins/transients-manager/) plugin to manage transients stored with the Download method. All Content Mask related transients contain the prefix "content_mask-" plus a stripped version of the Content Mask URL, such as "content_mask-httpxhynkcom".

 - ‡ Your site may be prevented from processing page requests for *any* reason; Reasons include, but are not limited to: masking unauthorized content, at the request of the masked URL site owner, masking hateful content, masking illegal content, circumventing IP bans, etc. A dual one-way encrypted hash of your masking URL may be used to check for infraction. No identifying information will be used for this check, and no information is saved other than as a transient to prevent unnecessary duplicate checks per site

[Read More About Content Mask](https://xhynk.com/content-mask/)


== Installation ==

1. Upload the `content-mask` folder to your `/wp-content/plugins/` directory.

2. Activate the "Content Mask" plugin.

**How to Use:**

1. Edit (or Add) a Page, Post, or Custom Post Type.

2. Underneath the page editor, find the "Content Mask Settings" metabox.

3. Click the Checkmark on the left to enable Content Mask.

4. Paste a URL in the Content Mask URL field.

5. Choose a method: Download, Iframe, or Redirect (301).

6. Update (or Publish) the Page, Post or Custom Post Type.

7. That's all! When a user visits that Page, Post or Custom Post Type, they will instead see the content from the URL you have put in the Content Mask URL field.


== Frequently Asked Questions ==
= I made changes to my Masked Page, and they're not showing up on my website =

Content Mask caches the masked page to your website. Click "Update" to resave the page, or click the "Refresh Transient" from the Content Mask admin page. If that doesn't work, you can add a query string to the Masked URL, such as `https://example.com?1` and Content Mask will fetch a new copy.

= Can I send custom headers with the Download Method =

*No*. If this is a feature you would like implemented, please contact me.

= Can You Show the Header/Footer on Content Masked Pages? =

*No*. This is because of how page requests are processed. Using Content Mask will override the _entire_ page content on the front end.

= Can I Embed Multiple URLs on One Page? =

*No*. There's not currently a way to embed multiple URLs onto a single page. You can embed one URL on one page.

= Will Content Mask Overwrite My Page Content? =

*No*. Content Mask does *not* permanently alter anything on your website. The embedded content is only shown on the front-end. When you turn off Content Mask, any page content you had in the editor will still be there.

= Something Isn't Loading With the Download Method =

Some websites "whitelist" IP addresses or domains for scripts, images, and files to be accessed from. If that's the case, try using the iframe method instead.

= Something Isn't Loading with the Iframe Method =

Some websites don't allow themselves to be iframed at all. Please reach out to the webmaster for the content you wish to iframe.

= Links Aren't Working with the Iframe Method =

If your website is secured (with https://), make sure any links on the iframed page are secure as well, as most modern browsers don't allow insecure content (http://) to be loaded into a secure page or iframe.

== Screenshots ==

1. Enable the Content Mask with the Checkmark - Put in the URL of the content you would like to embed. Done! Optionally, choose a different method (Download, Iframe, or Redirect). If using the download method, you may also change the cache duration from never up to 4 weeks (you may refresh the cache at any point manually).
2. The Content Mask Admin Panel shows a list of all Content Mask pages/posts and their current settings. Quickly enable or disable the Content Mask with a single click on the Method icon. The cache may also be refreshed from this page. You may also enabled/disabled Vistor Tracking that shows how many times each Content Masked page has been viewed. Only pages/posts that the current user can edit are displayed.
3. The regular WordPress page content, without Content Mask on.
4. The same WordPress page with Content Mask enabled and set to https://example.com/. You can see the URL has remained the same but the content has been entirely replaced (on the front end only) by the content from https://example.com/

== Changelog ==
= 1.8.5.6 =
* Security: fixed a Stored Cross-Site Scripting vulnerability in the per-post "Header Scripts & Styles" and "Footer Scripts" fields. A user who could edit posts, including Contributors, could store an event-handler payload that ran when an administrator previewed the pending post and, once published, for every visitor. Input from users without the unfiltered_html capability is now filtered with wp_kses while the markup is still live, and every affected render path filters the fully decoded value as its final step, which also neutralizes any payload already stored. Reported by Andrea Fiocchi via WPScan.
* Fixed: the per-role restriction in can_mask_content() was inverted and never took effect, so an administrator could not disable Content Mask for a role. The role check now applies, and its hook registration moved to the init hook so the current user is available when it runs.

= 1.8.5.5 =
* Security: require the target post type's publish capability before creating a Content Mask over AJAX. This fixes a privilege escalation where a Contributor could publish a live mask (including a redirect) without the publish_posts capability.
* Hardening: replaced the deprecated user_can_edit_post() permission checks with current_user_can( 'edit_post' ) on the edit, delete, toggle, and permission AJAX actions.
* Hardening: every request value is now unslashed and sanitized through a single helper, the metabox and admin inputs included, resolving all Plugin Check sanitization and nonce notices.
* Fixed: the Refresh Transient action referenced undefined variables and could fail on PHP 8; it now rebuilds and stores the cache correctly.
* Maintenance: replaced an obfuscated internal URL with its plain value, switched to wp_parse_url() and wp_strip_all_tags(), normalized line endings, corrected nonce-failure responses, removed a pass-by-reference notice, added the plugin text domain and license headers, and tested up to WordPress 7.0.

= 1.8.5.4 =
* Security: require edit-post capability before returning a post's role/condition permissions over AJAX, fixing an Insecure Direct Object Reference (IDOR). Reported by Nabil Irawan (CVE-2025-58012).
* Hardening: the "Content Mask enabled" admin notice now reads the current post from the trusted global rather than the raw request parameter.

= 1.8.5.3 =
* Revive abandoned notice, address SSRF

= 1.8.5.2 =
* Fix titles on iframe pages with some themes

= 1.8.5.1 =
* Fix null-coalescing operator for PHP 7.3 in admin panel

= 1.8.5 =
* Minor CSS Fixes for Columns
* Allow custom "Go Back

= 1.8.4.15 =
* fix for non-slashed relative urls in Download method

= 1.8.4.14 =
* adjust kses for SVGs and output

= 1.8.4.13 =
* Fix count in unique views

= 1.8.4.11 =
* allow more in kses

= 1.8.4.9 =
* Allow 'id' attribute in style and script tags for kses

= 1.8.4.9 =
* More pragmatic approach to wp_kses for scripts, styles, and HTML elements in single script areas and universal script areas
* Fixed Universal CSS in iframe and download

= 1.8.4.7 =
* Adjust wp_kses to allow script src

= 1.8.4.6 =
* Fixed individual footer scripts not being parsed and displayed

= 1.8.4.5 =
* Fixed missed/broken escapes and entity decoding for some header/footer sections

= 1.8.4.4 =
* Fixed missed SE/EL/AV mantra
* Added kses to some outputs

= 1.8.4.3 =
* Additional early sanitization
* additional late escaping in iframe output

= 1.8.4.2 =
* Additional Sanitization and Escaping
* Fixed accidental var_dump
* Adjusted script field display

= 1.8.4.1 =
* Added much more escaping and sanitization where required. Attributes, URLs, and bare HTML are escaped.
* Added individual function nonces for additional security, no longer reliant on a single nonce.
* Changed most concatenation to formatted strings for clarity and ease of escaping.

= 1.8.4 =
* Added WP Nonce Validation to all AJAX requests
* Checked user permissions/caps where necessary
* Patched security vulnerability where authenticated users could modify non-content mask options

= 1.8.3.2 =
* Add "Footer Scripts" section for Download and Iframe method
* Fix minor script access issue

= 1.8.3 =
* Fix PHP notice from smoke test for `post_type_XYZ_checked` dynamic variable

= 1.8.3 =
* Adjust single post type metabox to better illustrate Mask state
* Additional CSS to handle screen sizes better

= 1.8.2.9 =
* Prevent Content Mask save_meta function from running on post types that aren't considered "public", also hid meta box

= 1.8.2.7 =
* Fixed an issue with headers being sent and an error showing in the customizer

= 1.8.2.6 =
* Fixed an issue where Content Mask admin styles were being applied to other elements on the `edit.php` admin base screen.

= 1.8.2.4 =
* Modified IFL check
* Added Post Types to "Hide Content Mask From" list. Removes meta box and all active page processing for specificed post type(s).

= 1.8.2.3 =
* In some instances, roles aren't set when checked. Forced to array or skip in all cases.

= 1.8.2.2 =
* Replace deprecated functions

= 1.8.2 =
* Added "Content Mask Role" permissions. All roles enabled by default. Users with "manage_options" capability can disable Content Masking admin page/metabox for each role.
* Require "manage_options" capability to modify content mask options or scripts/styles settings

= 1.8.1.10 =
* Minor updates to IFL Request check, fix two further undefined index warnings on iframe specific requests

= 1.8.1.9 =
* Check infractions list, fix PHP string/array comparison warning in metabox.

= 1.8.1.8 =
* Fix in_array errors in metabox

= 1.8.1.7 =
* Fixed two inconsequential PHP notices

= 1.8.1.6 =
* Updated jQuery.load call to jQuery.on('load')

= 1.8.1.5 =
* Added an option "Return Link" to allow users to go back to their previous page.

= 1.8.1.4 =
* Accidentally left in diagnostic code in "condition permissions", this has been removed.

= 1.8.1.3 =
* Fixed scripts and styles saving in the admin
* Added condition permissions 

= 1.8.1.2 =
* Allow use of standard wp_head() in iframes

= 1.8.1.1 =
* Minor fixes from 1.8.1 

= 1.8.1 =
* Patched some minor security vulnerabilities exposed by PHP Grinder
* Allow Role-Based Content Mask Permission Locking.
* Added Support-based Iframe Query-Parameter Overrides

= 1.8.0.6 =
* Allow variants of 'localhost' in the Content Mask URL

= 1.8.0.5 =
* Allow passing of URL parameters to iframe
* update baked in version

= 1.8.0.4 =
* Updated Chrome User Agent String for HTTP Headers option

= 1.8.0.3 =
* Fixed displaying visitor tracking columns in admin panel

= 1.8.0.2 =
* Compatibility for WordPress 5.4 confirmed
* Fixed an issue where the Refresh Transient option wasn't working properly
* Made find/replace functions in the download method more reliable.
* Added content generator meta tag in the <head> tag on the download method.

= 1.8.0.1 =
* Fixed an issue where Error Reporting was turned on for the entire site after updating

= 1.8.0 =
* Introduced a method to easily create new content masks from the Content Mask admin panel.
* Content Mask has partnered with WhirLocal to embed Landing Pages and other content. A link to sign up for a FREE account has been added to the admin panel.