<?php

/**
 *
 *
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 *
 * Plugin Name:       Map Plugin using MapQuest Maps, with directions
 * Plugin URI:        http://fullworks.net/products/get-directions/
 * Description:       Map display using MapQuest
 * Version:           2.15.7
 * Author:            Fullworks
 * Author URI:        http://fullworks.net/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       get-directions
 * Domain Path:       /languages
 *
 * @package get-directions
 */

namespace Get_Directions;

use \Get_Directions\Includes\Core;
use \Get_Directions\Includes\Freemius_Config;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if (!function_exists('Get_Directions\run_Get_Directions')) {
	define( 'GET_DIRECTIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'GET_DIRECTIONS_PLUGIN_VERSION', '2.15.6' );

// Include the autoloader so we can dynamically include the classes.
	require_once GET_DIRECTIONS_PLUGIN_DIR . 'includes/autoloader.php';
	require_once GET_DIRECTIONS_PLUGIN_DIR . 'includes/vendor/autoload.php';



	/**
	 * Begins execution of the plugin.
	 */
	function run_Get_Directions() {
		/**
		 *  Load freemius SDK
		 */
		$freemius = new Freemius_Config();
		$freemius = $freemius->init();
		// Signal that SDK was initiated.
		do_action( 'get_directions_fs_loaded' );


		/**
		 * The code that runs during plugin uninstall.
		 * This action is documented in includes/class-uninstall.php
		 * * use freemius hook
		 *
		 * @var \Freemius $freemius freemius SDK.
		 */
		$freemius->add_action( 'after_uninstall', array( '\Get_Directions\Includes\Uninstall', 'uninstall' ) );
		/**
		 * The core plugin class that is used to define internationalization,
		 * admin-specific hooks, and public-facing site hooks.
		 */
		$plugin = new Core( $freemius );
		$plugin->run();
	}

	run_Get_Directions();
} else {
	die( __( 'Cannot execute as the plugin already exists, if you have a another version installed deactivate that and try again', 'get-directions' ) );
}
