<?php


/**
 * Fired during plugin uninstall.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 */

namespace Get_Directions\Includes;

class Uninstall {

	/**
	 * Uninstall specific code
	 */
	public static function uninstall() {
		delete_option( 'get-directions-settings' );
	}

}
