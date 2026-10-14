<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Check for empty button tag
 **/
function wp_ada_compliance_basic_validate_empty_button_tag( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'empty_button_tag', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$buttons = $dom->find( 'button' );
	foreach ( $buttons as $button ) {
		$svgalt = 1;
		$image  = $button->find( 'img' );
		$input  = $button->find( 'input' );
		$svg    = $button->find( 'svg' );
		$i      = $button->find( 'i' );
		if ( isset( $svg[0] ) ) {
			$meta = $svg[0]->find( 'metadata' );

			if ( isset( $meta[0] ) ) {
				$metatext = $meta[0]->innertext;
			} else {
				$metatext = '';
			}

			$svgalt = wp_ada_compliance_basic_check_svg_img_alt_text( $svg[0], $dom );
		}

		if ( ( '' == str_ireplace( array( ' ', '&nbsp;', '-', '_' ), '', trim( $button->plaintext ) ) || ( '' == $svgalt && trim( $metatext ) == trim( $button->plaintext ) ) )
		&& '' == $button->getAttribute( 'aria-label' )
		&& '' == $button->getAttribute( 'title' )
		&& '' == wp_ada_compliance_basic_get_aria_values( $dom, $button, 'aria-labelledby' )
		&& '' == wp_ada_compliance_basic_get_aria_values( $dom, $button, 'aria-describedby' )
		) {

			$errorcode = $button->outertext;

			if ( '' != $errorcode
			&& ( ! isset( $image[0] ) || '' == trim( $image[0]->getAttribute( 'alt' ) ) )
				&& ( ! isset( $svg[0] ) || '' == $svgalt )
			&& ( ! isset( $input[0] ) || '' == trim( $input[0]->getAttribute( 'value' ) ) )
				&& ( ! isset( $i[0] ) || ( '' == trim( $i[0]->getAttribute( 'title' ) ) && '' == trim( $i[0]->getAttribute( 'aria-label' ) ) ) ) ) {

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'empty_button_tag', $errorcode ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'empty_button_tag', $wp_ada_compliance_basic_def['empty_button_tag']['StoredError'], $errorcode );
				}

				// }
			}
		}
	}
	return 1;
}
