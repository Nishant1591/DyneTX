<?php

register_deactivation_hook( 'Meow_WR2X_Admin', 'wr2x_deactivate' );
register_activation_hook( 'Meow_WR2X_Admin', 'wr2x_activate' );

class Meow_WR2X_Admin extends MeowKit_WR2X_Admin {

	public $core = null;

	public function __construct( $core ) {
		$this->core = $core;
		parent::__construct( WR2X_PREFIX, WR2X_ENTRY, WR2X_DOMAIN, class_exists( 'MeowPro_WR2X_Core' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'app_menu' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			// Late priority: the media modal check in needs_scripts() relies on the screen having
			// enqueued the media scripts already.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 100 );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		}
	}

	function enqueue_scripts( $hook_suffix = '' ) {

		if ( !$this->needs_scripts( $hook_suffix ) ) {
			return;
		}

		$physical_file = WR2X_PATH . '/app/vendor.js';
		$cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : WR2X_VERSION;
		wp_register_script( 'wr2x_perfect_images-vendor', WR2X_URL . 'app/vendor.js',
			['wp-element', 'wp-i18n'], $cache_buster
		);

		// Load the "admin" scripts
		$physical_file = WR2X_PATH . '/app/index.js';
		$cache_buster = file_exists( $physical_file ) ? filemtime( $physical_file ) : WR2X_VERSION;
		wp_register_script( 'wr2x_perfect_images-index', WR2X_URL . 'app/index.js', 
			['wr2x_perfect_images-vendor'], $cache_buster 
		);

		// Localize and options
		global $wplr;
		wp_localize_script( 'wr2x_perfect_images-index', 'wr2x_retina', array_merge( [
			//'api_nonce' => wp_create_nonce( 'mfrh_media_file_renamer' ),
			'home_url' => get_home_url(),
			'site_url' => get_site_url(),
			'api_url' => get_rest_url( null, '/wp-retina-2x/v1/' ),
			'upload_url' => $this->core->get_upload_root_url(),
			'rest_url' => get_rest_url(),
			'plugin_url' => WR2X_URL,
			'prefix' => WR2X_PREFIX,
			'domain' => WR2X_DOMAIN,
			'is_pro' => class_exists( 'MeowPro_WR2X_Core' ),
			'is_registered' => !!$this->is_registered(),
			'rest_nonce' => wp_create_nonce( 'wp_rest' ),
			//'image_sizes' => $this->core->get_image_sizes( ARRAY_A ),

			'options' => $this->core->get_options(),
			'image_size_names' => get_intermediate_image_sizes(),
		] ) );

		wp_enqueue_script( 'wr2x_perfect_images-index' );
	}

	// The admin app only mounts on a few screens (see app/js/index.js), so there is no reason
	// to load it on every admin page.
	private function needs_scripts( $hook_suffix ) {

		// Perfect Images settings, and the Meow Apps dashboard (which hosts the common dashboard).
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( $page === 'wr2x_settings' || $page === 'meowapps-main-menu' ) {
			return true;
		}

		// Media Library list (Retina column, Replace Image row action) and the Add Media File
		// screen (the full-size uploader of the Pro version).
		if ( in_array( $hook_suffix, [ 'upload.php', 'media-new.php' ], true ) ) {
			return true;
		}

		return false;
	}

	function admin_notices() {


		if ( current_user_can( 'activate_plugins' ) ) {
			if ( delete_transient( 'wr2x_flush_rules' ) ) {
				global $wp_rewrite;
				Meow_WR2X_Admin::generate_rewrite_rules( $wp_rewrite, true );
			}
		}

		$hide_message = $this->core->get_option( 'hide_admin_messages', false );
		if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) && !$hide_message ) {
			echo "<div class='error' style='margin-top: 20px;'><p>";
			_e( "JetPack's <b>Photon</b> module breaks features built in WP Retina 2x (as Photos moves the files away). A common and better alternative to Photon is to use <a href='http://tracking.maxcdn.com/c/97349/3982/378'>MaxCDN</a> (very popular), CloudFlare or Fastly.", 'wp-retina-2x' );
			echo "</p></div>";
		}
	}

	function add_meta_boxes() {
		add_meta_box( 'meta-meow-perfect-images', 'Perfect Images', array( $this, 'metabox_meow_perfect_images' ), 
			'attachment', 'side', 'low' );
	}

	function metabox_meow_perfect_images( $post ) {
		$attachment_id = $post->ID;
		$nonce = wp_create_nonce( 'wp_rest' );
		?>
		<div id="wr2x-metabox-content">
			<p>
				<a href="#" id="wr2x-regenerate-btn" class="button button-secondary"data-id="<?php echo esc_attr( $attachment_id ); ?>">
					<?php _e( 'Regenerate Thumbnails', WR2X_DOMAIN ); ?>
				</a>
				<div id="wr2x-loading" style="display:none; text-align: center;">
					<p style="margin-top: 10px;">
						Regenerating...
					</p>
					<span class="spinner is-active" style="float:none;"></span>
				</div>
				<span id="wr2x-regenerate-result" style="display:none; margin-top: 10px;"></span>
			</p>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('#wr2x-regenerate-btn').on('click', function(e) {
				e.preventDefault();
				var $btn = $(this);
				var $loading = $('#wr2x-loading');
				var mediaId = $btn.data('id');
				
				$btn.hide();
				$loading.show();
				
				$.ajax({
					url: '<?php echo esc_js( get_rest_url( null, '/wp-retina-2x/v1/regenerate' ) ); ?>',
					method: 'POST',
					contentType: 'application/json',
					headers: { 'X-WP-Nonce': '<?php echo esc_js( $nonce ); ?>' },
					data: JSON.stringify({ mediaId: mediaId, optimized: true }),
					success: function(response) {
						$loading.hide();
						$btn.show();
						if (response.success) {
							$('#wr2x-regenerate-result').text('Thumbnails regenerated successfully!').css('color', 'green').show();
						} else {
							$('#wr2x-regenerate-result').text('Error: ' + (response.message || 'Unknown error')).css('color', 'red').show();
						}
					},
					error: function() {
						$loading.hide();
						$btn.show();
						alert('Request failed');
					}
				});
			});
		});
		</script>
		<?php
	}

	static function activate() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	static function deactivate() {
		remove_filter( 'generate_rewrite_rules', array( 'Meow_WR2X_Admin', 'generate_rewrite_rules' ) );
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	static function generate_rewrite_rules( $wp_rewrite, $flush = false ) {
		global $wp_rewrite;
		$options = get_option( Meow_WR2X_Core::get_plugin_option_name(), null );
		$method = $options["method"] ?? false;
		if ( $method == "Retina-Images" ) {

			// MODIFICATION: docwhat
			// get_home_url() -> trailingslashit(site_url())
			// REFERENCE: http://wordpress.org/support/topic/plugin-wp-retina-2x-htaccess-generated-with-incorrect-rewriterule

			// MODIFICATION BY h4ir9
			// .*\.(jpg|jpeg|gif|png|bmp) -> (.+.(?:jpe?g|gif|png))
			// REFERENCE: http://wordpress.org/support/topic/great-but-needs-a-little-update

			$handlerurl = str_replace( trailingslashit( site_url()), '', plugins_url( 'wr2x_image.php', __FILE__ ) );
			add_rewrite_rule( '(.+.(?:jpe?g|gif|png))', $handlerurl, 'top' );
		}
		if ( $flush == true ) {
			$wp_rewrite->flush_rules();
		}
	}

	// function common_url( $file ) {
	// 	return trailingslashit( plugin_dir_url( __FILE__ ) ) . 'common/' . $file;
	// }

	public function wr2x_settings() {
		echo '<div id="wr2x-admin-settings"></div>';
	}

	function app_menu() {
		add_submenu_page( 'meowapps-main-menu', __( 'Perfect Images', WR2X_DOMAIN ), __( 'Perfect Images', WR2X_DOMAIN ), 
			'manage_options', 'wr2x_settings', array( $this, 'wr2x_settings' )
		);
	}
}

?>
