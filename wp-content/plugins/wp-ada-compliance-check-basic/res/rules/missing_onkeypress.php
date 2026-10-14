<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Validate elments with onclick but not onkeypress
 */
function wp_ada_compliance_basic_validate_missing_onkeypress( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'missing_onkeypress', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$elements = $dom->find( '*' );

	foreach ( $elements as $element ) {

		// ignore.
		if ( isset( $element ) ) {
			$founderror = 0;
			// click events.
			if ( ( $element->getAttribute( 'ondblclick' )
			|| $element->getAttribute( 'onmousedown' ) || $element->getAttribute( 'onmouseup' ) )
			&& ! $element->getAttribute( 'onkeypress' ) && ! $element->getAttribute( 'onkeydown' ) && ! $element->getAttribute( 'onkeyup' ) ) {
				$founderror = 1;
			}

			// focus and blur events.
			if ( ( $element->getAttribute( 'onmouseover' ) || $element->getAttribute( 'onmouseout' ) || $element->getAttribute( 'onmousemove' ) )
			&& ! $element->getAttribute( 'onfocus' ) && ! $element->getAttribute( 'onblur' ) ) {
				$founderror = 1;
			}

			if ( 1 == $founderror ) {

				$code = $element->outertext;

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'missing_onkeypress', $code ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'missing_onkeypress', $wp_ada_compliance_basic_def['missing_onkeypress']['StoredError'], $code );
				}
			}
		}
	}
	return 1;
}
