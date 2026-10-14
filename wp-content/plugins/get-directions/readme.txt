=== Map Plugin alternative to Google Maps using MapQuest, with directions  ===
Contributors: Fullworks
Tags: MapQuest, Maps, Responsive Map, Directions, Get-Directions, Google Maps, Map
Requires at least: 3.4
Tested up to: 5.7
Stable tag: 2.15.7
Requires PHP: 5.6
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

This Map Plugin using MapQuest is a viable alternative for Google Maps based on OpenStreetMaps.

== Description ==

Google Maps has been changing to a paid for model and now many website owners are looking for alternative plugins that do the same job.

The pricing model for Google Maps requires an active billing account, which gets a credit per month of $200 ( at the time of writing ), which in theory gives you 28,500
dynamic maps, but beware the charge is per API call not per map transaction and that can easily be used up with substantial over runs and unexpected charges.

Many are looking for OpenStreetMap solutions, OpenStreetMaps doesn't have a directions module. MapQuest is one service that provides a directions / routiong module on top of an OpenStreetMap solution.

The MapQuest service is free for up to 15,000 map transactions per month, which is sufficient for many websites, visit the MapQuest developer website for pricing plans.
( this plugin has no commercial association with Mapquest). A couple of advantages MapQuest has over Google, you don't have to lodge a credit card so you won't accidentally
get a massive bill for overage,
map transactions are generally less numerous than API calls, so whilst 15,000 may seem less than 28,0000 you should find the realities different,
and finally, if you do need decide to pay for mapquest, it works out at closer to $3 per thousand compared to Google's $7 per thousand.

This is a map display and routing plugin that uses MapQuest.

A fantastic way of showing your business location and for your users to get travel directions to you.

See a [demo site here](https://demo.fullworks.net/get-directions/free-plugin-features/)


= Features Include =
* Show or hide route direction ability
* Set initial zoom level
* Decide on full map controls or minimal
* Set the map height
* Developer friendly template layouts for shortcode / widget allowing overriding

= PHP 8.0 support =

Tested on PHP 8.0

= Go PRO =

The pro version has many additional features that help you get the best out of your MapQuest map.
One of the features allow you to easily create a multi map pin Geo-Directory style site.

* Ability to store lat/long on posts and custom post types
* Post-aware widget, using post location meta fields
* Multi map pins shortcode for post with locations, create a directory style map - [see an example here](https://demo.fullworks.net/get-directions/2018/07/17/demo-world-map-with-custom-pins/)
* Different colours by taxonomy on multi pin markers
* select styles default map, dark, light, hybrid, satellite

see [pro features demo here](https://demo.fullworks.net/get-directions/pro-plugin-features/)

== Screenshots ==
1. Display routes to your business

== Installation ==

**Through Dashboard**

1. Log in to your WordPress admin panel and go to Plugins -> Add New
1. Type widget for get-directions in the search box and click on search button.
1. Find Get Directions.
1. Then click on Install Now after that activate the plugin.

**Installing Via FTP**

1. Download the plugin to your hardisk.
2. Unzip.
3. Upload the **get-directions** folder into your plugins directory.
4. Log in to your WordPress admin panel and click the Plugins menu.
5. Then activate the plugin.

== Frequently asked questions ==

= how do I use the shortcode? =
to display a map on the page use the short code
[get-directions] with the appropriate parameters

A destination is mandatory and is set as a latitude / longitude pair
e.g.    `[get-directions latlng="51.36887,-0.408999"]`


*Optional*

A route selection dialogue overlay will be shown, unless you turn it off with
*    `showroute="false"`

Specify the map image height ( default = 500px)
*    `height="400px"`

Specify the map width ( default full width - 100%)
*    `width="66%"`  you can use px too e.g. 320px but bear in mind fixed sizes on responsive sites

Specify the initial zoom level (1-16) (default 12)
 *   `zoom="10"`   :note the map will auto zoom if a route is displayed so this setting is only really relevant when hideroute='yes'

Specify the type of map controls
 *   `controls="true"` shows multi controls
 *  `controls="false"` shows only zoom controls

Specify the language for the directions ( note mapquest only translates the route directions, some text remains in English )
 *   `locale="en_US"` shows in Amercican English ( default )
 *   `locale="fr_FR"` shows in French
 *   `locale="hu_HU"` shows in Hungarian
 *   `locale="es_ES"` shows in Spanish
    many locale codes are supported, try them out, if it doesn't work you just get "Sorry e could not calculate directions ..." when you test directions. If you need further info on loacles contact mapquest.

Specify the units used in the directions
 *   `unit="m"` shows distances in miles ( default )
 *   `unit="k"` shows distances in kilometers ( default )

*Examples*

Map with directions overlay with icon at latitude 40.74802 longtitude -73.98512, minimal control
*    `[get-directions latlng="40.748021,-73.98512" controls="false"]`


= Can I display multiple maps (shortcodes or widgets) on one page? =
You can display one shortcode and as many widgets as you like on one page. Multiple shortcodes on one page is a Pro feature.
= Can I display multiple map markers? =
Multiple map marker is only available in the Pro version



== Changelog ==
= 2.15.7 =
* Library updates

= 2.15.6 =
* Library updates

= 2.15.5 =
* Disable scroll wheel zoom to map it more like Google Maps

= 2.15.4 =
* Premium only - multi pin - unlimited

= 2.15.3 =
* Changed settings to point to new knowledgebase

= 2.15.2 =
* Update Freemius Library to 2.3.0
* Make default shortcode ids unique based on post id

= 2.15.1 =
* Minor improvement

= 2.15 =
* Minor improvement

= 2.14 =
* Fixed missing dependency

= 2.13 =
* Minor change for 5.0 plus fixed missing arrow fonts in css

= 2.12 =
* minor change to settings

= 2.11 =
* minor change to settings

= 2.10 =
* remove spaces from height / width css

= 2.9 =
* load mapquest js and css locally to improve reliability

= 2.8 =
* template tweak - minor

= 2.7 =
* added internationalisation features of locale and distance units

= 2.6 =
* fix pin not showing

= 2.5 =
* bug fix

= 2.4 =
* fix set of post type ..


= 2.3 =
* Fix bug that stops route showing

= 2.2 =
* Minor fixes

= 2.1 =
* Bug fix to 2.0 and add legacy shortcode latlong as alias for latlng

= 2.0 =
* Complete rebuild using new Map Quest API - note some features from earlier versions may not be available, if this were working for you you can remain at v1.24 but this version has no ongoing support.

= 1.24 =
* Removed hard coded http:// to stop issues with insecure content on https://pages
= 1.23 =
* Important, geocoding by postcode is no longer supported in the shortcode, only use lat / long. Change your shortcodes if using postcode before upgrading. Changed calls to MapQuest Open rather than MapQuest Community as MapQuest is removing this as of 31 Dec 2014, so earlier versions of this plugin may not work after 1 Jan 2015
= 1.22 =
* Remove credit link to llocally, cause no one likes those
= 1.21 =
* minor fix to remove warning message
= 1.2 =
* removed the need for new users to get an API key from mapquest, you can still use your own Mapquest API key if you like add
define( 'MAPQUEST_API_KEY', 'your long api key here' ); to your wp-config.php file
= 1.1 =
* fixed bug where browser asking for location when no route required
* added feature to optionally show or hide map on mobile devices - leaving just the button
* added new feature to display a radius area to the short code
= 1.002 =
* allow settings page =
= 1.001b =
* readme changes
= 1.001a =
* added images to readme
= 1.0 =
*  added readme.txt

== Upgrade notice ==

= 2.0 =
Complete rebuild using new Map Quest API - note some features from earlier versions may not be available or have different settings, if this were working for you you can remain at v1.24 but this version has no ongoing support.
