<?php
/**
 * Plugin - WP ADA Compliance Check
 * includes all content checks and validations for the scan process and content publishing
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * All content is sent to this function to be checked for issues.
 **/
function wp_ada_compliance_basic_validate_post( $postinfo ) {

	// skip check when importing records.
	if ( '1' == get_option( 'wp_ada_compliance_basic_import_inprocess' ) ) {
		return 1;
	}

	if ( ! isset( $postinfo['fullcontent'] ) ) {
		$postinfo['fullcontent'] = '';
	}

	if ( '' != $postinfo['fullcontent'] ) {
		$content = $postinfo['fullcontent'];
	} else {
		$content = wp_ada_compliance_basic_strip_shortcodes( $postinfo['content'] );

		// elementor content.
		if ( is_plugin_active( 'elementor/elementor.php' ) && class_exists( '\\Elementor\\Plugin' ) ) {
			$content = apply_filters( 'elementor/frontend/the_content', $content );
		}
		$content = apply_filters( 'the_content', $content );
	}

	/**
	 * Start validation
	 */

	// specialty validations.
	// duplicate title check.
	wp_ada_compliance_basic_validate_duplicate_title( $postinfo );

	// do content checks.
	if ( '' != $content ) {
		wp_ada_compliance_basic_complete_content_validations( $content, $postinfo );
	}
}

/**
 * Content validations
 **/
function wp_ada_compliance_basic_complete_content_validations( $content, $postinfo ) {

	if ( '' == $content ) {
		return;
	}

	// check for redundant alt text on images.
	if ( function_exists( 'wp_ada_compliance_basic_validate_redundant_alt_text' ) ) {
		wp_ada_compliance_basic_validate_redundant_alt_text( $content, $postinfo );
	}

	// check for redundant title attributes on links.
	if ( function_exists( 'wp_ada_compliance_basic_validate_redundant_title_tag' ) ) {
		wp_ada_compliance_basic_validate_redundant_title_tag( $content, $postinfo );
	}

	// check for absolute font sizes.
	if ( function_exists( 'wp_ada_compliance_basic_validate_absolute_fontsize' ) ) {
		wp_ada_compliance_basic_validate_absolute_fontsize( $content, $postinfo );
	}

	// check for ambiguous ancor text.
	if ( function_exists( 'wp_ada_compliance_basic_validate_ambiguous_anchor_tag' ) ) {
		wp_ada_compliance_basic_validate_ambiguous_anchor_tag( $content, $postinfo );
	}

	// check for audio or video tags with autoplay.
	if ( function_exists( 'wp_ada_compliance_basic_validate_av_tag_with_autoplay' ) ) {
		wp_ada_compliance_basic_validate_av_tag_with_autoplay( $content, $postinfo );
	}

	// check for audio, video or iframe video missing tracks.
	if ( function_exists( 'wp_ada_compliance_basic_validate_av_tags_missing_track' ) ) {
		wp_ada_compliance_basic_validate_av_tags_missing_track( $content, $postinfo );
	}

	// check for empty anchor tag.
	if ( function_exists( 'wp_ada_compliance_basic_validate_empty_anchor_tag' ) ) {
		wp_ada_compliance_basic_validate_empty_anchor_tag( $content, $postinfo );
	}

	// check for empty heading tag.
	if ( function_exists( 'wp_ada_compliance_basic_validate_empty_heading_tag' ) ) {
		wp_ada_compliance_basic_validate_empty_heading_tag( $content, $postinfo );
	}

	// validate title tag in iframe.
	if ( function_exists( 'wp_ada_compliance_basic_validate_iframe_missing_title' ) ) {
		wp_ada_compliance_basic_validate_iframe_missing_title( $content, $postinfo );
	}

	// validate alt text content.
	if ( function_exists( 'wp_ada_compliance_basic_validate_img_alt_invalid' ) ) {
		wp_ada_compliance_basic_validate_img_alt_invalid( $content, $postinfo );
	}

	// image missing alt text.
	if ( function_exists( 'wp_ada_compliance_basic_validate_img_missing_alt' ) ) {
		wp_ada_compliance_basic_validate_img_missing_alt( $content, $postinfo );
	}

	// image empty alt text.
	if ( function_exists( 'wp_ada_compliance_basic_validate_img_empty_alt' ) ) {
		wp_ada_compliance_basic_validate_img_empty_alt( $content, $postinfo );
	}

	// target new window.
	if ( function_exists( 'wp_ada_compliance_basic_validate_new_window_tag' ) ) {
		wp_ada_compliance_basic_validate_new_window_tag( $content, $postinfo );
	}

	// validate redundent link text.
	if ( function_exists( 'wp_ada_compliance_basic_validate_redundant_anchor_text' ) ) {
		wp_ada_compliance_basic_validate_redundant_anchor_text( $content, $postinfo );
	}

	// validate tables missing th.
	if ( function_exists( 'wp_ada_compliance_basic_validate_missing_th' ) ) {
		wp_ada_compliance_basic_validate_missing_th( $content, $postinfo );
	}

	// validate form fields for labels.
	if ( function_exists( 'wp_ada_compliance_basic_validate_missing_form_label' ) ) {
		wp_ada_compliance_basic_validate_missing_form_label( $content, $postinfo );
	}

	// image map hot spot missing alt text.
	if ( function_exists( 'wp_ada_compliance_basic_validate_imagemap_missing_alt_text' ) ) {
		wp_ada_compliance_basic_validate_imagemap_missing_alt_text( $content, $postinfo );
	}

	// validate forms that submit automatically or trigger an unexpected context change.
	if ( function_exists( 'wp_ada_compliance_basic_validate_context_change_form' ) ) {
		wp_ada_compliance_basic_validate_context_change_form( $content, $postinfo );
	}

	// validate elments with onclick but not onkeypress.
	if ( function_exists( 'wp_ada_compliance_basic_validate_missing_onkeypress' ) ) {
		wp_ada_compliance_basic_validate_missing_onkeypress( $content, $postinfo );
	}

	// look for links without a visual cue.
	if ( function_exists( 'wp_ada_compliance_basic_validate_missing_headings' ) ) {
		wp_ada_compliance_basic_validate_link_without_visual_cue( $content, $postinfo );
	}

	// validate page structure, look for missing headings.
	if ( function_exists( 'wp_ada_compliance_basic_validate_missing_headings' ) ) {
		wp_ada_compliance_basic_validate_missing_headings( $content, $postinfo );
	}

	// check for anchor tags linking images to them self.
	if ( function_exists( 'wp_ada_compliance_basic_validate_img_linked_to_self' ) ) {
		wp_ada_compliance_basic_validate_img_linked_to_self( $content, $postinfo );
	}

	// validate animated images.
	if ( function_exists( 'wp_ada_compliance_basic_validate_animated_image' ) ) {
		wp_ada_compliance_basic_validate_animated_image( $content, $postinfo );
	}

	// look for foreground and background colors without enough contrast.
	if ( function_exists( 'wp_ada_compliance_basic_validate_color_contrast_failure' ) ) {
		wp_ada_compliance_basic_validate_color_contrast_failure( $content, $postinfo );
	}

	// check missing page titles.
	if ( function_exists( 'wp_ada_compliance_basic_validate_missing_title' ) ) {
		wp_ada_compliance_basic_validate_missing_title( $content, $postinfo );
	}

	// image empty alt text with title or non empty aria label attribute.
	if ( function_exists( 'wp_ada_compliance_basic_validate_img_empty_alt_with_title' ) ) {
		wp_ada_compliance_basic_validate_img_empty_alt_with_title( $content, $postinfo );
	}

	// validate blinking text.
	if ( function_exists( 'wp_ada_compliance_basic_validate_blinking_text' ) ) {
		wp_ada_compliance_basic_validate_blinking_text( $content, $postinfo );
	}

	// look for links and focus styles where visual focus indication has been removed.
	if ( function_exists( 'wp_ada_compliance_basic_validate_visual_focus_removed' ) ) {
		wp_ada_compliance_basic_validate_visual_focus_removed( $content, $postinfo );
	}

	// look for tags with onclick used to emulate links.
	if ( function_exists( 'wp_ada_compliance_basic_validate_emulating_links' ) ) {
		wp_ada_compliance_basic_validate_emulating_links( $content, $postinfo );
	}

	// validate meta refresh attributes that reload the page or redirect to a new location after a timeout.
	if ( function_exists( 'wp_ada_compliance_basic_validate_meta_refresh_use' ) ) {
		wp_ada_compliance_basic_validate_meta_refresh_use( $content, $postinfo );
	}

	// validate page structure, look for headings that are not in order.
	if ( function_exists( 'wp_ada_compliance_basic_validate_incorrect_heading_order' ) ) {
		wp_ada_compliance_basic_validate_incorrect_heading_order( $content, $postinfo );
	}

	// check for tab order changed using tabindex.
	if ( function_exists( 'wp_ada_compliance_basic_validate_tab_order_modified' ) ) {
		wp_ada_compliance_basic_validate_tab_order_modified( $content, $postinfo );
	}

	// validate justified text.
	if ( function_exists( 'wp_ada_compliance_basic_validate_text_justified' ) ) {
		wp_ada_compliance_basic_validate_text_justified( $content, $postinfo );
	}

	// validate links without href but that include event handlers.
	if ( function_exists( 'wp_ada_compliance_basic_validate_missing_href' ) ) {
		wp_ada_compliance_basic_validate_missing_href( $content, $postinfo );
	}

	// check for empty button tag.
	if ( function_exists( 'wp_ada_compliance_basic_validate_empty_button_tag' ) ) {
		wp_ada_compliance_basic_validate_empty_button_tag( $content, $postinfo );
	}

	// check for empty anchor tag.
	if ( function_exists( 'wp_ada_compliance_basic_validate_empty_href' ) ) {
		wp_ada_compliance_basic_validate_empty_href( $content, $postinfo );
	}

	// links in content that are lot included in an anchor tag.
	if ( function_exists( 'wp_ada_compliance_basic_validate_unlinked_anchors' ) ) {
		wp_ada_compliance_basic_validate_unlinked_anchors( $content, $postinfo );
	}

	// look for elementor toc widgets.
	//if ( function_exists( 'wp_ada_compliance_basic_validate_elementor_toc' ) ) {
	//	wp_ada_compliance_basic_validate_elementor_toc( $content, $postinfo );
	//}

	// look for elementor toggle or accordion.
	if ( function_exists( 'wp_ada_compliance_basic_validate_elementor_toggles' ) ) {
		wp_ada_compliance_basic_validate_elementor_toggles( $content, $postinfo );
	}

	// validate adjacent identical links.
	if ( function_exists( 'wp_ada_compliance_basic_validate_adjacent_identical_links' ) ) {
		wp_ada_compliance_basic_validate_adjacent_identical_links( $content, $postinfo );
	}

	// validate empty th cells.
	if ( function_exists( 'wp_ada_compliance_basic_validate_empty_th' ) ) {
		wp_ada_compliance_basic_validate_empty_th( $content, $postinfo );
	}

	// check for links to non html content.
	if ( function_exists( 'wp_ada_compliance_basic_validate_link_to_non_html_content' ) ) {
		wp_ada_compliance_basic_validate_link_to_non_html_content( $content, $postinfo );
	}

	// validate skip nav links in theme files.
	if ( function_exists( 'wp_ada_compliance_basic_validate_skip_nav_links' ) ) {
		wp_ada_compliance_basic_validate_skip_nav_links( $content, $postinfo );
	}

	// validate missing landmarks.
	if ( function_exists( 'wp_ada_compliance_basic_validate_missing_landmarks' ) ) {
		wp_ada_compliance_basic_validate_missing_landmarks( $content, $postinfo );
	}

	// validate unlabeled landmarks.
	if ( function_exists( 'wp_ada_compliance_basic_validate_unlabeled_landmarks' ) ) {
		wp_ada_compliance_basic_validate_unlabeled_landmarks( $content, $postinfo );
	}

	// validate missing laguage attributes in theme files.
	if ( function_exists( 'wp_ada_compliance_basic_validate_missing_lang_attr' ) ) {
		wp_ada_compliance_basic_validate_missing_lang_attr( $content, $postinfo );
	}

	// carousels that require manual review.
	if ( function_exists( 'wp_ada_compliance_basic_validate_dynamic_carousel' ) ) {
		wp_ada_compliance_basic_validate_dynamic_carousel( $content, $postinfo );
	}

	// look for links without enough contrast between the text around them.
	if ( function_exists( 'wp_ada_compliance_basic_validate_link_color_contrast_failure' ) ) {
		wp_ada_compliance_basic_validate_link_color_contrast_failure( $content, $postinfo );
	}

	// check for accessibility help options.
	if ( function_exists( 'wp_ada_compliance_basic_validate_accessibility_help' ) ) {
		wp_ada_compliance_basic_validate_accessibility_help( $content, $postinfo );
	}
}


/**
 * Strip unwanted shortcodes from content before validating
 */
function wp_ada_compliance_basic_strip_shortcodes( $content ) {
	global $shortcode_tags;

	$shortcodes_array = array( 'embed', 'caption', 'audio', 'video', 'gallery' );

	if ( false === strpos( $content, '[', 0 ) ) {
		return $content;
	}

	if ( empty( $shortcode_tags ) || ! is_array( $shortcode_tags ) ) {
		return $content;
	}

	foreach ( $shortcode_tags as $key => $value ) {
		if ( ! in_array( $key, $shortcodes_array ) && strstr( $content, $key ) ) {
			// $pattern = "/\[(\/)*ezcol_1half_end(.*?)\]/";
			$pattern = '#\[(\[?)(' . $key . ')(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)#';
			$content = preg_replace( $pattern, '', $content );
		}
	}

	return $content;
}

/**
 * Add view report meta box
 */
function wp_ada_compliance_basic_report_meta_box() {
	$post_types = array( 'page', 'post' );
	foreach ( $post_types as $type ) {
		add_meta_box(
			'wp_ada_meta_box_id',
			'Web Accessibility',
			'wp_ada_compliance_basic_view_report',
			$type,
			'side',
			'high',
			array(
				'__block_editor_compatible_meta_box' => true,
			)
		);
	}
}

/**
 * View report
 */
function wp_ada_compliance_basic_view_report() {
	global $post;
	$enablewave = get_option( 'wp_ada_compliance_basic_enablewave', 'true' );
	if ( wp_ada_compliance_basic_reported_errors_check( $post->ID, $post->post_type ) ) {
		$nonce = wp_create_nonce( 'wp-ada-compliance-nonce' );
		echo '<p class="ada_compliance_report_link"><a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&_wpnonce=' . esc_attr( $nonce ) . '&view=1&errorid=' . esc_attr( $post->ID ) . '&type=' . esc_attr( $post->post_type ) . '&iframe=1&TB_iframe=true&width=900&height=550" class="thickbox btnwpada btnwpada-warning adareportlink">';
		esc_html_e( 'Accessibility Report', 'wp-ada-compliance-basic' );
		echo '</a></p>';
		if ( 'true' == $enablewave ) {
			echo '<p class="wp-ada-compliance-wave-link"><a href="http://wave.webaim.org/report#/' . esc_url( site_url() ) . '/?p=' . esc_attr( $post->ID ) . '"><i class="far fa-check-square" aria-hidden="true"></i>';
			esc_html_e( 'Evaluate with Wave', 'wp-ada-compliance-basic' );
			echo '</a>
      <a href="https://validator.w3.org/nu/?doc=' . esc_url( site_url() ) . '/?p=' . esc_attr( $post->ID ) . '"><i class="fas fa-code" aria-hidden="true"></i>';
			esc_html_e( 'Validate HTML', 'wp-ada-compliance' );
			echo '</a></p>';
		}
	} else {
		echo '<p class="ada_compliance_report_link_clear ada_compliance_report_link"><i class="fas fa-thumbs-up btnwpada" aria-hidden="true" title="' . esc_html__( 'No Issues', 'wp-ada-compliance-basic' ) . '"></i></p>';
		if ( 'true' == $enablewave ) {
			echo '<p class="wp-ada-compliance-wave-link"><a href="http://wave.webaim.org/report#/' . esc_url( site_url() ) . '/?p=' . esc_attr( $post->ID ) . '"><i class="far fa-check-square" aria-hidden="true"></i>';
			esc_html_e( 'Evaluate with Wave', 'wp-ada-compliance-basic' );
			echo '</a>
      <a href="https://validator.w3.org/nu/?doc=' . esc_url( site_url() ) . '/?p=' . esc_attr( $post->ID ) . '"><i class="fas fa-code" aria-hidden="true"></i>';
			esc_html_e( 'Validate HTML', 'wp-ada-compliance' );
			echo '</a></p>';
		}
	}
}
/**
 * Compare strings
 */
function wp_ada_compliance_basic_compare_strings( $string1, $string2 ) {
	// text to remove.
	$remove_text   = array();
	$remove_text[] = __( 'permalink of ', 'wp-ada-compliance' );
	$remove_text[] = __( 'permalink to ', 'wp-ada-compliance' );
	$remove_text[] = __( '&nbsp;', 'wp-ada-compliance' );

	$string1 = strtolower( $string1 );
	$string1 = str_ireplace( $remove_text, '', $string1 );
	$string1 = strip_tags( $string1 );
	$string1 = trim( $string1, " \t\n\r\0\x0B\xC2\xA0" );
	$string1 = html_entity_decode( $string1 );

	$string2 = strtolower( $string2 );
	$string2 = str_ireplace( $remove_text, '', $string2 );
	$string2 = strip_tags( $string2 );
	$string2 = trim( $string2, " \t\n\r\0\x0B\xC2\xA0" );
	$string2 = html_entity_decode( $string2 );

	if ( $string1 == $string2 ) {
		return 1;
	} else {
		return 0;
	}
}
/**
 * Start process
 */
function wp_ada_compliance_basic_start_import() {
	update_option( 'wp_ada_compliance_basic_import_inprocess', '1' );
}
/**
 * End Process
 */
function wp_ada_compliance_basic_end_import() {
	update_option( 'wp_ada_compliance_basic_import_inprocess', '0' );
}

/**
 * Check if image is hidden - returns true if visible
 **/
function wp_ada_compliance_check_basic_image_visibility( $image ) {

	if ( 'true' != $image->getAttribute( 'aria-hidden' )
	&& 'none' != $image->getAttribute( 'role' )
	&& 'presentation' != $image->getAttribute( 'role' )
	&& ! stristr( $image->getAttribute( 'style' ), 'display:none' )
	&& ! stristr( $image->getAttribute( 'style' ), 'display: none' )
	&& ! stristr( $image->getAttribute( 'style' ), 'visibility:hidden' )
	&& ! stristr( $image->getAttribute( 'style' ), 'visibility: hidden' )
	&& ! stristr( $image->getAttribute( 'class' ), 'role-presentation' )

	) {
		return 1;
	}

	return 0;
}
