<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Validate redundant title attributes
 */
function wp_ada_compliance_basic_validate_redundant_title_tag( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned..
	if ( in_array( 'redundant_title_tag', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	// redundant title text without images.
	$links      = $dom->find( 'a' );
	$titlearray = array();
	foreach ( $links as $link ) {
		if ( '' != $link->getAttribute( 'title' ) ) {
						$titlearray[] = __( 'permalink of ', 'wp-ada-compliance-basic' );
			$titlearray[]             = __( 'permalink to ', 'wp-ada-compliance-basic' );
			if ( isset( $link ) && strtolower( trim( $link->plaintext ) ) == strtolower( trim( str_ireplace( $titlearray, '', $link->getAttribute( 'title' ) ) ) ) || strtolower( trim( str_ireplace( $titlearray, '', $link->getAttribute( 'title' ) ) ) ) === trim( strtolower( $link->getAttribute( 'aria-label' ) ) ) ) {
				$redeidanttitletag = $link->outertext;

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'redundant_title_tag', $redeidanttitletag ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'redundant_title_tag', $wp_ada_compliance_basic_def['redundant_title_tag']['StoredError'], $redeidanttitletag );
				}
			}
		}
	}

	// redundant title text on links wrapping an image.
	$links = $dom->find( 'a' );
	foreach ( $links as $link ) {
		if ( '' != $link->getAttribute( 'title' ) ) {
			$images = $link->find( 'img,svg' );
			foreach ( $images as $image ) {

				if ( 'svg' == $image->tag ) {
					$alt = wp_ada_compliance_basic_check_svg_img_alt_text( $image, $dom );
				} else {
					$alt = $image->getAttribute( 'alt' );
				}

				if ( '' != $alt ) {

					if ( isset( $link ) && isset( $image )
					&& strtolower( trim( $link->getAttribute( 'title' ) ) ) == strtolower( trim( $alt ) )
					) {

						$redeidanttitletag = $link->outertext;

						// save error.
						if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'redundant_title_tag', $redeidanttitletag ) ) {
							$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'redundant_title_tag', $wp_ada_compliance_basic_def['redundant_title_tag']['StoredError'], $redeidanttitletag );
						}
					}
				}
			}
		}
	}

	return 1;
}
