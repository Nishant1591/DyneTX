<?php
/*
 Plugin Name: Page Modification Date Shortcode
 Plugin URI: https://www.workbox.com
 Author: Workbox Inc
 Author URI: https://www.workbox.com
 Version: 1.0
 Description: Adds the [modification_date] shortcode that prints the modification date of the current page. The date is either the real modification date of the page or a custom one, chosen in the "Modification Date" box of the page editor.
*/

class PageModificationDateShortcode {

	/**
	 * Meta key that stores the custom modification date of a page (Y-m-d).
	 */
	const META_DATE = '_wb_modification_date';

	/**
	 * Meta key that stores how the modification date is taken:
	 * self::MODE_AUTO or self::MODE_MANUAL.
	 */
	const META_MODE = '_wb_modification_date_mode';

	/**
	 * Mode: the real modification date of the page is used.
	 */
	const MODE_AUTO = 'auto';

	/**
	 * Mode: the date entered by the user is used and never changes automatically.
	 */
	const MODE_MANUAL = 'manual';

	/**
	 * Nonce name used to protect the "Modification Date" meta box.
	 */
	const META_NONCE = 'wb_modification_date_nonce';

	/**
	 * Default date format used by the shortcode (example: August 1, 2026).
	 */
	const DATE_FORMAT = 'F j, Y';

	/**
	 * Constructor. Registers all hooks and the shortcode of the plugin.
	 *
	 * @return void
	 */
	public function __construct() {
		add_shortcode( 'modification_date', array( $this, 'doShortCodeModificationDate' ) );

		add_action( 'add_meta_boxes', array( $this, 'addModificationDateMetaBox' ) );
		add_action( 'save_post', array( $this, 'saveModificationDate' ), 10, 3 );
	}

	/**
	 * Returns the list of post types the "Modification Date" meta box is added to.
	 *
	 * @return array Post type names.
	 */
	private function getSupportedPostTypes() {
		return apply_filters( 'wb_modification_date_post_types', array( 'page', 'post' ) );
	}

	/**
	 * Registers the "Modification Date" meta box in the sidebar of the editor
	 * for every supported post type.
	 *
	 * @param string $post_type Post type of the entry being edited.
	 *
	 * @return void
	 */
	public function addModificationDateMetaBox( $post_type ) {
		if ( ! in_array( $post_type, $this->getSupportedPostTypes(), true ) ) {
			return;
		}

		add_meta_box(
			'wb_modification_date',
			'Modification Date',
			array( $this, 'showModificationDateMetaBox' ),
			$post_type,
			'side',
			'high'
		);
	}

	/**
	 * Prints the content of the "Modification Date" meta box: the mode selector
	 * (real date / custom date) and the date field used in the manual mode.
	 *
	 * @param WP_Post $post Page being edited.
	 *
	 * @return void
	 */
	public function showModificationDateMetaBox( $post ) {
		$mode = $this->getModificationDateMode( $post->ID );
		$date = $this->getRawModificationDate( $post );

		wp_nonce_field( self::META_NONCE, self::META_NONCE );

		?>
		<p>
			<label>
				<input type="radio" name="wb_modification_date_mode" value="<?php echo esc_attr( self::MODE_AUTO ); ?>" <?php checked( $mode, self::MODE_AUTO ); ?>>
				Use the real modification date
			</label>
			<br>
			<label>
				<input type="radio" name="wb_modification_date_mode" value="<?php echo esc_attr( self::MODE_MANUAL ); ?>" <?php checked( $mode, self::MODE_MANUAL ); ?>>
				Set manually
			</label>
		</p>
		<p>
			<input type="date" id="wb_modification_date_date" name="wb_modification_date_date"
				   value="<?php echo esc_attr( $date ); ?>"
				   <?php disabled( $mode, self::MODE_AUTO ); ?>>
		</p>
		<p class="description">
			In the manual mode the date stays untouched when the page is saved again.
			The <code>[modification_date]</code> shortcode outputs
			<strong><?php echo esc_html( $this->getModificationDate( $post, self::DATE_FORMAT ) ); ?></strong>.
		</p>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				// Enables the date field in the manual mode only.
				$('input[name="wb_modification_date_mode"]').on('change', function () {
					$('#wb_modification_date_date').prop('disabled', $(this).val() !== '<?php echo esc_js( self::MODE_MANUAL ); ?>');
				});
			});
		</script>
		<?php
	}

	/**
	 * Saves the modification date mode and the custom date when a page is saved.
	 * The real modification date is not stored: in the automatic mode it is read
	 * from the page itself every time the shortcode is rendered.
	 *
	 * @param int     $post_id ID of the saved page.
	 * @param WP_Post $post    Saved page object.
	 * @param bool    $update  True when an existing page is updated.
	 *
	 * @return void
	 */
	public function saveModificationDate( $post_id, $post = false, $update = false ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// The meta box is not on the screen: nothing was submitted, nothing to save.
		if ( ! isset( $_POST[ self::META_NONCE ] ) || ! wp_verify_nonce( $_POST[ self::META_NONCE ], self::META_NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$mode = ( isset( $_POST['wb_modification_date_mode'] ) && $_POST['wb_modification_date_mode'] === self::MODE_MANUAL )
			? self::MODE_MANUAL
			: self::MODE_AUTO;

		update_post_meta( $post_id, self::META_MODE, $mode );

		if ( $mode !== self::MODE_MANUAL ) {
			return;
		}

		$date = isset( $_POST['wb_modification_date_date'] ) ? $this->sanitizeDate( $_POST['wb_modification_date_date'] ) : '';

		// An empty or invalid date falls back to the real modification date of the page.
		if ( ! $date ) {
			$date = mysql2date( 'Y-m-d', get_post_field( 'post_modified', $post_id ) );
		}

		update_post_meta( $post_id, self::META_DATE, $date );
	}

	/**
	 * Shortcode [modification_date]. Prints the modification date of the current page.
	 *
	 * Attributes:
	 *   id      - ID of the page to take the date from (default: the current page).
	 *   format  - PHP date format of the output (default: F j, Y).
	 *   allowbr - 1 to allow the date to be wrapped over several lines. By default
	 *             the spaces of the date are non-breaking, so it always stays on one line.
	 *
	 * @param array|string $atts Shortcode attributes.
	 *
	 * @return string Formatted date or an empty string when the page is unknown.
	 */
	public function doShortCodeModificationDate( $atts = '' ) {
		$atts = shortcode_atts(
			array(
				'id'      => 0,
				'format'  => self::DATE_FORMAT,
				'allowbr' => 0,
			),
			$atts,
			'modification_date'
		);

		$post_id = intval( $atts['id'] );
		$post    = $post_id > 0 ? get_post( $post_id ) : get_post();

		if ( ! $post ) {
			return '';
		}

		$html = esc_html( $this->getModificationDate( $post, $atts['format'] ) );

		// Keep the date on a single line unless line breaks are explicitly allowed.
		if ( ! $this->isTrue( $atts['allowbr'] ) ) {
			$html = str_replace( ' ', '&nbsp;', $html );
		}

		return $html;
	}

	/**
	 * Checks whether a shortcode attribute holds a positive value.
	 *
	 * @param mixed $value Attribute value, for example "1", "true", "yes" or "on".
	 *
	 * @return bool True when the attribute is enabled.
	 */
	private function isTrue( $value ) {
		return in_array( strtolower( trim( (string) $value ) ), array( '1', 'true', 'yes', 'on' ), true );
	}

	/**
	 * Returns the modification date of a page formatted for the front-end: either
	 * the custom date set in the meta box or the real modification date of the page.
	 *
	 * @param int|WP_Post $post   Page object or its ID.
	 * @param string      $format PHP date format of the output.
	 *
	 * @return string Formatted date or an empty string when the page is unknown.
	 */
	private function getModificationDate( $post, $format = self::DATE_FORMAT ) {
		$date = $this->getRawModificationDate( $post );
		if ( ! $date ) {
			return '';
		}

		return mysql2date( $format, $date . ' 00:00:00' );
	}

	/**
	 * Returns the modification date of a page in the Y-m-d format, respecting the
	 * mode chosen in the meta box.
	 *
	 * @param int|WP_Post $post Page object or its ID.
	 *
	 * @return string Date in the Y-m-d format or an empty string when the page is unknown.
	 */
	private function getRawModificationDate( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return '';
		}

		if ( $this->getModificationDateMode( $post->ID ) === self::MODE_MANUAL ) {
			$date = $this->sanitizeDate( get_post_meta( $post->ID, self::META_DATE, true ) );
			if ( $date ) {
				return $date;
			}
		}

		// post_modified is already the local site time, no conversion needed.
		return mysql2date( 'Y-m-d', $post->post_modified );
	}

	/**
	 * Returns the modification date mode of a page. Pages without the meta field
	 * use the real modification date.
	 *
	 * @param int $post_id ID of the page.
	 *
	 * @return string self::MODE_AUTO or self::MODE_MANUAL.
	 */
	private function getModificationDateMode( $post_id ) {
		$mode = get_post_meta( $post_id, self::META_MODE, true );

		return ( $mode === self::MODE_MANUAL ) ? self::MODE_MANUAL : self::MODE_AUTO;
	}

	/**
	 * Validates a date coming from the meta box or from the database.
	 *
	 * @param string $date Date to check, expected in the Y-m-d format.
	 *
	 * @return string The date in the Y-m-d format or an empty string when it is invalid.
	 */
	private function sanitizeDate( $date ) {
		$date = trim( (string) $date );
		if ( $date === '' ) {
			return '';
		}

		$parts = explode( '-', substr( $date, 0, 10 ) );
		if ( count( $parts ) !== 3 || ! checkdate( intval( $parts[1] ), intval( $parts[2] ), intval( $parts[0] ) ) ) {
			return '';
		}

		return sprintf( '%04d-%02d-%02d', intval( $parts[0] ), intval( $parts[1] ), intval( $parts[2] ) );
	}
}

new PageModificationDateShortcode();
