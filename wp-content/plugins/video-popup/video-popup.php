<?php
/**
 * Plugin Name: Video Popup – Video Lightbox for YouTube, Vimeo & MP4
 * Version: 2.0.4
 * Description: Video Popup plugin lets you create unlimited, responsive Video Lightbox and Popup for YouTube, Vimeo, MP4 & WebM. Smart, flexible, and made for easy control. Supports on click and On-Page Load.
 * Author: Alobaidi
 * Author URI: https://wp-time.com/video-popup-plugin-for-wordpress/
 * Plugin URI: https://wp-time.com/video-popup-plugin-for-wordpress/
 * Text Domain: video-popup
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

if ( !defined('ABSPATH') ) {
    exit; // Exit if accessed directly
}


// Define plugin constants
define('VIDEO_POPUP_PLUGIN_ID', 'video_popup');
define('VIDEO_POPUP_PLUGIN_VERSION', '2.0.4');
define('VIDEO_POPUP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VIDEO_POPUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VIDEO_POPUP_PLUGIN_BASENAME', plugin_basename(__FILE__));


// Plugin initialization
if ( !class_exists('Video_Popup_Core') ) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-core.php';
    $Video_Popup_Core = Video_Popup_Core::get_instance();
    $Video_Popup_Core->initialize();
}