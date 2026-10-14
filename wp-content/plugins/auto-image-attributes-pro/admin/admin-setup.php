<?php
/**
 * Admin setup for the plugin
 *
 * @since 1.0
 * @function	iaffpro_add_menu_links()			Add admin page for license key
 * @function	iaffpro_register_settings			Register Settings
 * @function	iaffpro_validater_and_sanitizer()	Validate And Sanitize User Input Before Its Saved To Database
 * @function	iaffpro_get_settings()				Get settings from database
 * @function	iaffpro_admin_notices() 			All the admin notices
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit; 
 
/**
 * Add admin page for license key
 *
 * Page is not shown in the admin menu and can only be accessed via the link in the plugins list (near deactivate plugin).
 * @since 	1.0
 */
function iaffpro_add_menu_links() {
	add_submenu_page ( 'options.php', __('Image Attributes Pro','auto-image-attributes-pro'), __('Image Attributes Pro','auto-image-attributes-pro'), 'manage_options', 'image-attributes-pro-activation','iaffpro_license_page_render'  );
}
add_action( 'admin_menu', 'iaffpro_add_menu_links' );

/**
 * Register Settings
 *
 * @since 	1.0
 */
function iaffpro_register_settings() {

	// Register Setting
	register_setting( 
		'iaffpro_settings_group', 			// Group name
		'iaffpro_settings', 				// Setting name = html form <input> name on settings form
		'iaffpro_validater_and_sanitizer'	// Input sanitizer
	);
	
	// License Section
    add_settings_section(
        'iaffpro_license_section',								// ID
        __('Image Attribute Pro Activation', 'auto-image-attributes-pro'),	// Title
        'iaffpro_license_section_callback',						// Callback Function
        'image-attributes-pro-activation'						// Page slug
    );
	
	// Registered Email
    add_settings_field(
        'iaffpro_license_email_field',				// ID
        __('Registered Email', 'auto-image-attributes-pro'),	// Title
        'iaffpro_license_email_field_callback',		// Callback function
        'image-attributes-pro-activation',			// Page slug
        'iaffpro_license_section'					// Settings Section ID
    );
	
	// License Key
    add_settings_field(
        'iaffpro_license_key_field',				// ID
        __('License Key', 'auto-image-attributes-pro'),		// Title
        'iaffpro_license_key_field_callback',		// Callback function
        'image-attributes-pro-activation',			// Page slug
        'iaffpro_license_section'					// Settings Section ID
    );
	
}
add_action( 'admin_init', 'iaffpro_register_settings' );

/**
 * Validate and sanitize user input before its saved to database
 *
 * @since 1.0
 * @since 4.4 Check if base plugin is available before displaying link to settings page.
 * @since 4.9 Switched to transient based notice instead of add_settings_error().
 */
function iaffpro_validater_and_sanitizer ( $settings ) {
	
	$settings['registered_email']	= sanitize_email($settings['registered_email']);
	$settings['license_key']		= sanitize_text_field($settings['license_key']);

	/**
   * Display link to Image Attributes Pro Settings page in the admin notice after settings are saved. Provided base plugin is available.
   * Also refer function iaffpro_license_key_saved_notice()
   */
	if ( function_exists( 'iaff_auto_image_attributes' ) ) {
		$message = sprintf( __( 'License information is saved. Configure <a href="%s">Image Attributes Pro &rarr;</a>', 'auto-image-attributes-pro' ), admin_url( 'options-general.php?
		page=image-attributes-from-filename' ) );
	} else {
		$message = sprintf( __( 'License information is saved. Please install and activate <a href="%s" target="_blank">Auto Image Attributes From Filename With Bulk Updater</a> to use Image Attributes Pro.' ), 'https://wordpress.org/plugins/auto-image-attributes-from-filename-with-bulk-updater/' );
	}

	// Using transients instead of add_settings_error() to avoid duplicate notice on first save.
	set_transient( 'iaffpro_license_saved_admin_notice', $message, 300 );
	
	return $settings;
}

/**
 * Display success message when license key is saved.
 * 
 * After moving License key page from options-general.php to (https://github.com/arunbasillal/auto-image-attributes-pro/commit/e9c1e7b720845521126b7adaac5ba3c1f3ef509a)
 * the settings error after saving license key was not displayed. This function fixes that. 
 * 
 * @since 4.3.1
 * @since 4.9 Switched out from settings_errors() to avoid duplicate notice.
 */
function iaffpro_license_key_saved_notice() {

	// License saved notice.
	if ( get_transient( 'iaffpro_license_saved_admin_notice' ) ) {

		$message = get_transient( 'iaffpro_license_saved_admin_notice' );

		if ( ! empty( $message ) ) {

			echo '<div class="notice notice-success is-dismissible">
				<p><strong>'. wp_kses_post( $message ) .' </strong></p>
			</div>';
		}

		// Delete transient
		delete_transient( 'iaffpro_license_saved_admin_notice' );
	}
}
add_action( 'admin_notices', 'iaffpro_license_key_saved_notice' );

/**
 * Get settings from database
 *
 * @since 	1.0
 * @return	Array	A merged array of default and settings saved in database. 
 */
function iaffpro_get_settings() {

	$defaults = array(); // Empty for now. Might be useful in the future.
	$settings = get_option('iaffpro_settings', $defaults);
	
	return $settings;
}

/**
 * All the admin notices
 *
 * @since 1.0
 * @since 4.8 Do no display admin notices on license activation page.
 */
function iaffpro_admin_notices() {

	// Do no display notices on license activation page.
	$screen = get_current_screen();
	if ( $screen->id === "admin_page_image-attributes-pro-activation" ) {
		return;
	}

	// Admin notice if the base plugin isn't installed 
	if ( ! defined( 'IAFF_VERSION_NUM' ) && current_user_can( 'install_plugins' ) ) { ?>
		<div class="notice notice-warning is-dismissible"><p>
				
			<?php
			if ( file_exists( WP_PLUGIN_DIR . '/auto-image-attributes-from-filename-with-bulk-updater/iaff_image-attributes-from-filename.php' ) ) {

				// Display link to activate plugin if plugin is installed but not activated.
				printf( 
					__( '<strong>Image Attributes Pro:</strong> <a href="%s" target="_blank">Auto Image Attributes From Filename With Bulk Updater</a> is installed but not activated. To use Image Attributes Pro, <a href="%s">click here and activate the plugin &rarr;</a>', 'auto-image-attributes-pro' ),
					'https://wordpress.org/plugins/auto-image-attributes-from-filename-with-bulk-updater/',
					wp_nonce_url( admin_url( 'admin-post.php?action=iaffpro_manually_activate_base_plugin' ), 'iaffpro_manually_activate_base_plugin_action', 'iaffpro_manually_activate_base_plugin_nonce_name' )
				);
			} 
			else {

				// Display link to install plugin.
				printf( 
					__( '<strong>Image Attributes Pro:</strong> Please install and activate <a href="%s" target="_blank">Auto Image Attributes From Filename With Bulk Updater</a> to use Image Attributes Pro. <a href="%s">Click here to install plugin &rarr;</a>', 'auto-image-attributes-pro' ),
					'https://wordpress.org/plugins/auto-image-attributes-from-filename-with-bulk-updater/',
					wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=auto-image-attributes-from-filename-with-bulk-updater' ), 'install-plugin_auto-image-attributes-from-filename-with-bulk-updater' )
				);
			}
			?>
			
		</p></div>
	<?php }
	
	// Admin notice if license information is not given
	$settings = iaffpro_get_settings();
	if ( empty( $settings['registered_email'] ) || empty( $settings['license_key'] ) ) { ?>
		<div class="notice notice-success is-dismissible">
			<p><?php printf( __( '<strong>Thank you for choosing Image Attributes Pro.</strong> <a href="%s">Please enter license info</a> to activate the plugin and download plugin updates.', 'auto-image-attributes-pro' ),
			admin_url( 'options.php?page=image-attributes-pro-activation' ) ); ?></p>
		</div>
	<?php }
}
add_action( 'admin_notices', 'iaffpro_admin_notices' );

/**
 * Load JS and CSS in the admin.
 *
 * @since 4.4
 * @since 4.8 Added js/iap-activation.js
 */
function iaffpro_enqueue_js_and_css() {
	
	$screen = get_current_screen();

	// Load only in Media Library.
	if ( $screen->id === "upload" ) {
		wp_enqueue_script( 
			'iaffpro-media-library-js', 
			plugins_url( '/js/media-library.js', __FILE__ ), 
			array( 'jquery' ), 
			IAFFPRO_VERSION_NUM, 
			true 
		);
		
		// Refer: https://developer.wordpress.org/plugins/javascript/enqueuing/#localize
		wp_localize_script(
			'iaffpro-media-library-js',
			'iaffpro_ml_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'iaffpro_media_library_js_nonce' ),
			)
		);	
	}

	/**
	 * Load only on license activation page
	 * i.e. /wp-admin/options.php?page=image-attributes-pro-activation
	 * 
	 * and if base plugin is inactive.
	 */
	if ( $screen->id === "admin_page_image-attributes-pro-activation"  && ! defined( 'IAFF_VERSION_NUM' ) ) {

		wp_enqueue_script(
			'iaffpro-activation-js',
			plugins_url( 'js/iap-activation.js', __FILE__ ),
			[ 'jquery' ],
			IAFFPRO_VERSION_NUM,
			true
		);

		wp_localize_script( 
			'iaffpro-activation-js', 
			'iaffpro_activation', 
			[
				'ajax_url'			=> admin_url( 'admin-ajax.php' ),
				'nonce'				=> wp_create_nonce( 'iaffpro_install_base_plugin_nonce' ),
				'status_error' 		=> [
					'message' => __( 'An unexpected error occurred during installation. Please install the base plugin manually.' , 'auto-image-attributes-pro' ),
					'border_color'	=> '#d63638',
				],
			]
		);
	}

	// Load only in Image Attributes Pro Settings page (for Bulk Updater)
	if ( $screen->id === "settings_page_image-attributes-from-filename" ) {
		wp_enqueue_script(
			'iaffpro-event-log-js',
			plugins_url( 'js/event-log.js', __FILE__ ),
			array( 'jquery' ),
			IAFFPRO_VERSION_NUM,
			true
		);

		wp_localize_script(
			'iaffpro-event-log-js',
			'iaffpro_event_log',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'iaffpro_event_log_update_nonce' ),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'iaffpro_enqueue_js_and_css' );

/**
 * AJAX handler to install and activate the base plugin.
 * 
 * @since 4.8
 */
function iaffpro_maybe_install_base_plugin() {

	// Check nonce.
	check_ajax_referer( 'iaffpro_install_base_plugin_nonce', 'security' );

	// If already active, just exit early.
	if ( defined( 'IAFF_VERSION_NUM' ) ) {
		wp_send_json_success( 
			[ 
				'message' 		=> __( 'Base plugin is installed and activated.', 'auto-image-attributes-pro' ),
				'border_color'	=> '#00a32a',
			]);
	}

	// Check permission.
	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_send_json_error( 
			[ 
				'message' 		=> __( 'You do not have permission to install plugins. Please request your admin to install the base plugin.', 'auto-image-attributes-pro' ),
				'border_color'	=> '#d63638',
			]);
	}

	$plugin_slug = 'auto-image-attributes-from-filename-with-bulk-updater';
	$plugin_file = $plugin_slug . '/iaff_image-attributes-from-filename.php';

	// Check if plugin is already installed but not activated.
	if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {

		wp_send_json_success( [ 
			'message' 		=> sprintf ( __( 'Base plugin is installed but not activated. To use Image Attributes Pro, <a href="%s">click here and activate the plugin &rarr;</a>', 'auto-image-attributes-pro' ), wp_nonce_url( admin_url( 'admin-post.php?action=iaffpro_manually_activate_base_plugin' ), 'iaffpro_manually_activate_base_plugin_action', 'iaffpro_manually_activate_base_plugin_nonce_name' )), 
			'border_color'	=> '#dba617',
		]);
	}

	// Include required WordPress files.
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';

	WP_Filesystem();

	// Fetch plugin info.
	$api = plugins_api( 'plugin_information', [
		'slug'   => $plugin_slug,
		'fields' => [ 'sections' => false ],
	] );

	if ( is_wp_error( $api ) ) {
		wp_send_json_error( 
			[ 
				'message' 		=> __( 'Failed to fetch plugin info from WordPress.org. Please install the base plugin manually.', 'auto-image-attributes-pro' ),
				'border_color'	=> '#d63638',
			]);
	}

	// Download and install plugin.
	$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
	$result   = $upgrader->install( $api->download_link );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( 
			[ 
				'message' 		=> sprintf( __( 'Installation failed: %s. Please install the base plugin manually.', 'auto-image-attributes-pro' ), $result->get_error_message() ),
				'border_color'	=> '#d63638',
			]);
	}

	// Activate the plugin.
	$activate = activate_plugin( $plugin_file );

	if ( is_wp_error( $activate ) ) {
		wp_send_json_error( 
			[ 
				'message' 		=> sprintf( __( 'Activation failed: %s. Please install the base plugin manually.', 'auto-image-attributes-pro' ), $activate->get_error_message() ),
				'border_color'	=> '#d63638',
			]);
	}

	wp_send_json_success( 
		[ 
			'message' 		=> __( 'Base plugin installed and activated successfully.', 'auto-image-attributes-pro' ),
			'border_color'	=> '#00a32a',
		]);
}
add_action( 'wp_ajax_iaffpro_maybe_install_base_plugin', 'iaffpro_maybe_install_base_plugin' );

/**
 * Activate Auto Image Attributes From Filename With Bulk Updater base plugin from activation notice.
 * 
 * Using WordPress native activation (using plugins.php?action=activate... full link below) redirects to the Plugins list after activation.
 * There is no evident way to control the redirection after plugin activation without using activate_plugin().
 * 
 * To generate WordPress native activation link: 
 * wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . urlencode( 'plugin-folder/main-plugin-file.php') ), 'activate-plugin_' . 'plugin-folder/main-plugin-file.php' )
 * 
 * @since 4.8
 */
function iaffpro_manually_activate_base_plugin() {
	
	// Add a fallback if wp_get_referer() returns false.
	$redirect_url = wp_get_referer() === false ? admin_url( 'options-general.php?page=image-attributes-from-filename' ) : wp_get_referer();

	// Authentication
	if ( 
		! current_user_can( 'manage_options' ) 
		|| ! ( isset( $_GET['iaffpro_manually_activate_base_plugin_nonce_name'] ) && wp_verify_nonce( $_GET['iaffpro_manually_activate_base_plugin_nonce_name'], 'iaffpro_manually_activate_base_plugin_action' ) ) 
		|| is_plugin_active( 'auto-image-attributes-from-filename-with-bulk-updater/iaff_image-attributes-from-filename.php' )
	) {
		
		// Return to referer if authentication fails or if plugin is already active.
		wp_redirect( $redirect_url );
		exit;
	}

	activate_plugin( 'auto-image-attributes-from-filename-with-bulk-updater/iaff_image-attributes-from-filename.php', $redirect_url );
}
add_action( 'admin_post_iaffpro_manually_activate_base_plugin', 'iaffpro_manually_activate_base_plugin' );