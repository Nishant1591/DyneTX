<?php
/**
 * Admin UI setup and render
 *
 * @since 1.0
 * @function	iaffpro_license_section_callback()		Callback function for License Settings section
 * @function	iaffpro_license_email_field_callback()	Callback function for Registered email field
 * @function	iaffpro_license_key_field_callback()	Callback function for License Key field
 * @function	iaffpro_license_page_render()				Admin interface renderer
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

/**
 * Callback function for License Settings section
 *
 * @since 1.0
 * @since 4.8 Added placeholders to display base plugin install status.
 */
function iaffpro_license_section_callback() {

	// Display info only if base plugin is not installed and active.
	if ( ! defined( 'IAFF_VERSION_NUM' ) ) {
	?>

	<div class="error inline" id="iap-base-plugin-check" style="border-left-color: #dba617;">
		<p style="margin-bottom: 0px;"><?php
			printf( 
				__( '<strong>Required Plugin:</strong> Image Attributes Pro needs the base plugin, <a href="%s" target="_blank">Auto Image Attributes From Filename With Bulk Updater</a> to operate.', 'auto-image-attributes-pro' ), 
				'https://wordpress.org/plugins/auto-image-attributes-from-filename-with-bulk-updater/'
			);
		?></p>
		<span class="spinner is-active" id="iaffpro-spinner" style="float: left; margin: 2px 8px 0px 0px;"></span>
		<p id="iaffpro-base-plugin-status" style="margin-top: 0px;"><?php _e( 'Checking for and installing the required base plugin...', 'auto-image-attributes-pro' ); ?></p>
	</div>
	
<?php
	}

	printf( __( '<p>Please enter your license information to activate the plugin and receive automatic updates.<br>You will find the details in the welcome email or your <a href="%s" target="_blank">account dashboard</a>. Please do not hesitate to <a href="%s" target="_blank">contact support</a> if you need any help.</p>', 'auto-image-attributes-pro' ), 
	'https://imageattributespro.com/my-account/?utm_source=iap&utm_medium=license-page',
	'https://imageattributespro.com/contact/?utm_source=iap&utm_medium=license-page' );
}

/**
 * Callback function for Registered email field
 *
 * @since 1.0
 */
function iaffpro_license_email_field_callback() {	

	// Get Settings
	$settings = iaffpro_get_settings();
	?>
	
	<!-- Registered Email -->
	<input type="email" name="iaffpro_settings[registered_email]" placeholder="registered@email.com" class="all-options" value="<?php if ( isset( $settings['registered_email'] ) && ( ! empty($settings['registered_email']) ) ) esc_attr_e($settings['registered_email']); ?>"/><br>

	<?php
}

/**
 * Callback function for License Key field
 *
 * @since 1.0
 */
function iaffpro_license_key_field_callback() {	

	// Get Settings
	$settings = iaffpro_get_settings();
	?>
	
	<!-- License Key -->
	<input type="password" name="iaffpro_settings[license_key]" placeholder="####-####-####-####-####" class="all-options" value="<?php if ( isset( $settings['license_key'] ) && ( ! empty($settings['license_key']) ) ) esc_attr_e($settings['license_key']); ?>"/><br>

	<?php
}
 
/**
 * License key settings page renderer
 *
 * @since 1.0
 */ 
function iaffpro_license_page_render() {
	
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?> 
	
	<div class="wrap">	
		<h1><?php echo 'Image Attributes Pro <sup>'. IAFFPRO_VERSION_NUM .'</sup>'; ?></h1>
		
		<form action="options.php" method="post">		
			<?php
			// Output nonce, action, and option_page fields for a settings page.
			settings_fields( 'iaffpro_settings_group' );
			
			// Prints out all settings sections added to a particular settings page. 
			do_settings_sections( 'image-attributes-pro-activation' );	// Page slug
			
			// Output save settings button
			submit_button( __( 'Save License Information ', 'auto-image-attributes-pro' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Add Image Attributes Pro HTML and JS to the settings page of basic plugin.
 * 
 * @since 4.7
 */
function iaffpro_add_html_js_to_basic_plugin_settings_page( $screen ) {
    if ( $screen->id === 'settings_page_image-attributes-from-filename' ) {
        add_action( 'in_admin_header', 'iaffpro_settings_page_html_and_js' );
    }
}
add_action( 'current_screen', 'iaffpro_add_html_js_to_basic_plugin_settings_page' );

function iaffpro_settings_page_html_and_js() {
    ?>
	<div class="hidden-dialogs" style="display:none;">

		<!-- Delete Event Log Dialog -->
		<div id="iaffpro-delete-event-log-dialog" title="<?php esc_attr_e( 'Delete Event Log', 'auto-image-attributes-pro' ); ?>">
			<p><?php _e( 'You are about to delete the event log. Press Ok to confirm.', 'auto-image-attributes-pro' ); ?></p>
		</div>

	</div>

	<script type="text/javascript" >
	jQuery(document).ready(function($) {

		// Delete Event Log
		$('#bulk-updater-delete-log-button').click( function() {

			// Reset Counter Dialog
			$('#iaffpro-delete-event-log-dialog').dialog({
				autoOpen: false,
				width: 600,
				modal: true,
				buttons: {
					"Ok": function() {
						
						data = {
							action: 'iaffpro_bulk_updater_delete_log',
							security: '<?php echo wp_create_nonce( "iaffpro_bulk_updater_delete_log_nonce" ); ?>'
						};

						$.post(ajaxurl, data, function (response) {

							// Delete log entries from Event Log
							$('#bulk-updater-log').find('p.iaff-event-log-line').remove();

							$('#bulk-updater-log').append('<p class="iaff-red"><span class="dashicons dashicons-trash"></span> ' + response.message + '</p>');
							$("#bulk-updater-log").animate({scrollTop:$("#bulk-updater-log")[0].scrollHeight - $("#bulk-updater-log").height()},200);
						});
						$(this).dialog("close");
					},
					"Cancel": function() {
						$(this).dialog("close");
					}
				}
			});
			$('#iaffpro-delete-event-log-dialog').dialog('open');

		});
	});	
	</script>
	<?php
}