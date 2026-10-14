<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Check for anchor tags linking images to them self
 */
function wp_ada_compliance_basic_validate_img_linked_to_self( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );
	$remove_images_linked_to_self        = 'false';
	$report_filtered_errors              = 'true';

	// check if being scanned.
	if ( in_array( 'img_linked_to_self', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$links = $dom->find( 'a' );
	foreach ( $links as $link ) {

		$images = $link->find( 'img' );
		if ( isset( $images ) ) {
			foreach ( $images as $image ) {
				$base_src = basename( $image->getAttribute( 'src' ) );
			}
			if ( isset( $base_src ) ) {

				$alttext = '';
				if ( '' != $link->getAttribute( 'title' ) ) {
					$alttext = $link->getAttribute( 'title' );
				}
				$alttext .= $image->getAttribute( 'alt' );
				if ( basename( $link->getAttribute( 'href' ) ) == $base_src && ! wp_ada_compliance_basic_check_if_alt_text_includes_exclusion( $alttext ) ) {

					$atagcode = $link->outertext;

					// save error.
					if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'img_linked_to_self', $atagcode ) ) {
						$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'img_linked_to_self', $wp_ada_compliance_basic_def['img_linked_to_self']['StoredError'], $atagcode );
					}
				}
			}
		}
	}
	return 1;
}
/**
 * Exclude images that include
 **/
function wp_ada_compliance_basic_check_if_alt_text_includes_exclusion( $alt ) {
	$excluded_alt[] = __( 'view larger image', 'wp-ada-compliance-basic' );
	$excluded_alt[] = __( 'view a larger image', 'wp-ada-compliance-basic' );
	foreach ( $excluded_alt as $value ) {
		if ( stristr( $alt, $value ) ) {
			return 1;
		}
	}
	return 0;
}
