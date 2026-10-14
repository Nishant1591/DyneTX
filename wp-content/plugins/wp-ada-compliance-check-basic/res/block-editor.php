<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Add support for guttenberg editor
 */
add_action(
	'admin_footer',
	function () {

		// don't load on block editor widgets screen.
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$requesturi = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			if ( ! strstr( $requesturi, 'post.php' ) ) {
				return;
			}
		} else {
			return;
		}

		global $post;
		// need to add check for beaver builder and return if not.
		$current_screen = get_current_screen();
		if ( ! method_exists( $current_screen, 'is_block_editor' )
		|| ! $current_screen->is_block_editor()
		) {
			return;
		}
		?>
<div id="GutenbergADAError" class="adaError"><button type="button"
	aria-label="<?php esc_html_e( 'Dismiss error', 'wp-ada-compliance-basic' ); ?>"
	class="adadismiss">X</button></div>
</div>

<script>
	jQuery(function($) {
	$(document).on("click", '.adadismiss', function() {
		$('#GutenbergADAError').hide();
	});

	// Gutenberg editor status updates.
	jQuery(document).ready(function($) {

		const isSavingPost = () => wp.data.select('core/editor').isSavingPost();
		const isAutosavingPost = () => wp.data.select('core/editor').isAutosavingPost();
		wp.data.subscribe(() => {
		const savestatus = isSavingPost();
		if (savestatus && !isAutosavingPost()) {

			var seperator = '&';
			var nonce =
			'<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>';
			var resturl =
			'<?php echo esc_url_raw( get_rest_url() ); ?>';
			if (resturl.search('/wp-json/') > 0) seperator = '?';

			var url = resturl +
			'wp_ada_compliance_basic/v1/errorstatus/<?php echo (int) $post->ID; ?>' +
			seperator + '_wpnonce=' + nonce;
			$.ajax({
			url: url,
			success: function(data) {

				$('.ada_compliance_report_link').html(data);
			}
			});
			url = resturl +
			'wp_ada_compliance_basic/v1/displaynotice/<?php echo (int) $post->ID; ?>' +
			seperator + '_wpnonce=' + nonce;
			$.ajax({
			url: url,
			success: function(data) {
				if (data !== '' && data != '-1') {
				$('#GutenbergADAError').html(
					'<button type="button" aria-label="<?php esc_html_e( 'Dismiss error', 'wp-ada-compliance-basic' ); ?>" class="adadismiss">X</button>' +
					data);

				$('#GutenbergADAError').show();

				} else {
				$('#GutenbergADAError').hide();
				}
			}

			});

		}
		});
	});
	});
</script>
		<?php
	}
);
?>
