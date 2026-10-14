<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Check for empty anchor tag
 **/
function wp_ada_compliance_basic_validate_empty_anchor_tag( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'empty_anchor_tag', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$links = $dom->find( 'a' );
	foreach ( $links as $link ) {
		$svg    = $link->find( 'svg' );
		$svgalt = 1;
		if ( isset( $svg[0] ) ) {
			$meta = $svg[0]->find( 'metadata' );

			if ( isset( $meta[0] ) ) {
				$metatext = $meta[0]->innertext;
			} else {
				$metatext = '';
			}

			$svgalt = wp_ada_compliance_basic_check_svg_img_alt_text( $svg[0], $dom );
		}

		if ( ( '' == str_ireplace( array( ' ', '&nbsp;', '-', '_' ), '', trim( $link->plaintext ) ) || ( '' == $svgalt && ( trim( $metatext ) == trim( $link->plaintext ) ) ) )
		&& $link->hasAttribute( 'href' )
		&& '' == $link->getAttribute( 'aria-label' )
		&& '' == $link->getAttribute( 'title' )
		&& '' == wp_ada_compliance_basic_get_aria_values( $dom, $link, 'aria-labelledby' )
		&& '' == wp_ada_compliance_basic_get_aria_values( $dom, $link, 'aria-describedby' )
		|| '' == $link->innertext ) {

						// ADD CODE TO TRACK AUTO CORRECT.
			if ( 'elementor-image-box-img' == $link->parent->getAttribute( 'class' ) || 'elementor-icon-box-icon' == $link->parent->getAttribute( 'class' ) || 'elementor-icon-box-title' == $link->parent->getAttribute( 'class' ) || 'elementor-image-box-title' == $link->parent->getAttribute( 'class' ) ) {
				$link->setAttribute( 'data-class', 'elementor-image-or-icon-box' );
			}

			$atagcode = $link->outertext;
			$image    = $link->find( 'img' );

			if ( '' != $atagcode
				&& ! $link->hasAttribute( 'id' )
				&& ! $link->hasAttribute( 'name' )

			&& ( ! isset( $image[0] ) || '' == trim( $image[0]->getAttribute( 'alt' ) ) )

				) {

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'empty_anchor_tag', $atagcode ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'empty_anchor_tag', $wp_ada_compliance_basic_def['empty_anchor_tag']['StoredError'], $atagcode );
				}
			}
		}
	}
	return 1;
}


/**
 * Check svg image
 **/
function wp_ada_compliance_basic_check_svg_img( $svg, $dom ) {

	if ( is_array( $svg ) ) {

		if ( 0 == count( $svg ) ) {
			return 1;
		}

		$title = $svg[0]->find( 'title' );

		if ( ( 0 == count( $title ) || '' == $title[0]->innertext )
		&& '' == wp_ada_compliance_basic_get_aria_values( $dom, $svg[0], 'aria-labelledby' )
		&& '' == wp_ada_compliance_basic_get_aria_values( $dom, $svg[0], 'aria-describedby' ) ) {
			return 0;
		}
	} else {
		$title = $svg->find( 'title' );

		if ( ( 0 == count( $title ) || '' == $title[0]->innertext )
		&& '' == wp_ada_compliance_basic_get_aria_values( $dom, $svg, 'aria-labelledby' )
		&& '' == wp_ada_compliance_basic_get_aria_values( $dom, $svg, 'aria-describedby' ) ) {
			return 0;
		}
	}

	return 1;
}
