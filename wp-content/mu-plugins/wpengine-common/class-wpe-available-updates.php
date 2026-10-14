<?php
/**
 * WP Engine Available Updates
 *
 * @package wpengine/common-mu-plugin
 * @owner wpengine/golden
 */

namespace wpe\plugin;

/**
 * A class that persists WordPress update transients to wp_options to ensure they survive
 * object cache flushes and are available for metrics collection in a context
 * without loaded plugins or themes.
 */
class Wpe_Available_Updates {

	/**
	 * The instance of the class.
	 *
	 * @var Wpe_Available_Updates
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 */
	private function __construct() {
		// High priority value to ensure this runs after all plugins/themes have run their hooks.
		add_filter( 'set_site_transient_update_plugins', array( $this, 'store_update_plugins_in_db' ), 9999, 1 );
		add_filter( 'set_site_transient_update_themes', array( $this, 'store_update_themes_in_db' ), 9999, 1 );
		add_filter( 'set_site_transient_update_core', array( $this, 'store_update_core_in_db' ), 9999, 1 );

		add_action( 'delete_site_transient_update_plugins', array( $this, 'delete_update_plugins_from_db' ) );
		add_action( 'delete_site_transient_update_themes', array( $this, 'delete_update_themes_from_db' ) );
		add_action( 'delete_site_transient_update_core', array( $this, 'delete_update_core_from_db' ) );
	}

	/**
	 * Get the instance of the class.
	 *
	 * @return Wpe_Available_Updates
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Store the available plugins updates in the database.
	 *
	 * Defers the actual persistence to shutdown so that read-side filters
	 * (e.g. site_transient_update_plugins) have a chance to include their
	 * data in the snapshot.
	 *
	 * @param object $transient The transient object.
	 * @return object The transient object.
	 */
	public function store_update_plugins_in_db( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		if ( ! has_action( 'shutdown', array( $this, 'persist_update_plugins_at_shutdown' ) ) ) {
			add_action( 'shutdown', array( $this, 'persist_update_plugins_at_shutdown' ) );
		}

		return $transient;
	}

	/**
	 * Persist the fully read-filtered update_plugins transient at shutdown.
	 */
	public function persist_update_plugins_at_shutdown() {
		$transient = get_site_transient( 'update_plugins' );
		if ( is_object( $transient ) ) {
			update_option( 'wpe_site_transient_update_plugins', $transient, false );
		}
	}

	/**
	 * Store the available themes updates in the database.
	 *
	 * Defers the actual persistence to shutdown so that read-side filters
	 * (e.g. site_transient_update_themes) have a chance to include their
	 * data in the snapshot.
	 *
	 * @param object $transient The transient object.
	 * @return object The transient object.
	 */
	public function store_update_themes_in_db( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		if ( ! has_action( 'shutdown', array( $this, 'persist_update_themes_at_shutdown' ) ) ) {
			add_action( 'shutdown', array( $this, 'persist_update_themes_at_shutdown' ) );
		}

		return $transient;
	}

	/**
	 * Persist the fully read-filtered update_themes transient at shutdown.
	 */
	public function persist_update_themes_at_shutdown() {
		$transient = get_site_transient( 'update_themes' );
		if ( is_object( $transient ) ) {
			update_option( 'wpe_site_transient_update_themes', $transient, false );
		}
	}

	/**
	 * Store the available WP core update in the database.
	 *
	 * Defers the actual persistence to shutdown so that read-side filters
	 * (e.g. site_transient_update_core) have a chance to include their
	 * data in the snapshot.
	 *
	 * @param object $transient The transient object.
	 * @return object The transient object.
	 */
	public function store_update_core_in_db( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		if ( ! has_action( 'shutdown', array( $this, 'persist_update_core_at_shutdown' ) ) ) {
			add_action( 'shutdown', array( $this, 'persist_update_core_at_shutdown' ) );
		}

		return $transient;
	}

	/**
	 * Persist the fully read-filtered update_core transient at shutdown.
	 */
	public function persist_update_core_at_shutdown() {
		$transient = get_site_transient( 'update_core' );
		if ( is_object( $transient ) ) {
			update_option( 'wpe_site_transient_update_core', $transient, false );
		}
	}

	/**
	 * Delete the available plugins updates from the database.
	 */
	public function delete_update_plugins_from_db() {
		remove_action( 'shutdown', array( $this, 'persist_update_plugins_at_shutdown' ) );
		delete_option( 'wpe_site_transient_update_plugins' );
	}

	/**
	 * Delete the available themes updates from the database.
	 */
	public function delete_update_themes_from_db() {
		remove_action( 'shutdown', array( $this, 'persist_update_themes_at_shutdown' ) );
		delete_option( 'wpe_site_transient_update_themes' );
	}

	/**
	 * Delete the available WP core update from the database.
	 */
	public function delete_update_core_from_db() {
		remove_action( 'shutdown', array( $this, 'persist_update_core_at_shutdown' ) );
		delete_option( 'wpe_site_transient_update_core' );
	}
}
