<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Everything in uninstall.php will be executed when user decides to delete the plugin. 
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

// If uninstall not called from WordPress, then die.
if ( ! defined('WP_UNINSTALL_PLUGIN') ) die;

// Delete database entries
delete_option('abl_iaffpro_version');
delete_option('iaffpro_updated_posts');
delete_option('iaffpro_settings');
delete_transient( 'iaffpro_event_log_updated_time' );