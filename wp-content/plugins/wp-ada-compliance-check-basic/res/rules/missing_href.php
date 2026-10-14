<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Validate links without href but that include event handlers
 **/
function wp_ada_compliance_basic_validate_missing_href( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'missing_href', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$elements = $dom->find( 'a' );

	foreach ( $elements as $element ) {

		// ignore.
		if ( isset( $element ) ) {
			$founderror = 0;
			// click events.
			if ( ( '' == $element->getAttribute( 'href' ) && ! stristr( $element->getAttribute( 'role' ), 'link' ) ) &&
			( $element->getAttribute( 'onclick' )
			|| $element->getAttribute( 'ondblclick' )
			|| $element->getAttribute( 'onmousedown' )
			|| $element->getAttribute( 'onmouseup' )
			|| $element->getAttribute( 'onkeypress' )
			|| $element->getAttribute( 'onkeydown' )
			|| $element->getAttribute( 'onkeyup' )
			|| $element->getAttribute( 'onmouseover' )
			|| $element->getAttribute( 'onmouseout' )
			|| $element->getAttribute( 'onmousemove' )
			|| $element->getAttribute( 'onfocus' )
			|| $element->getAttribute( 'onblur' ) ) ) {

				$code = $element->outertext;

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'missing_href', $code ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'missing_href', $wp_ada_compliance_basic_def['missing_href']['StoredError'], $code );
				}
			}
		}
	}
	return 1;
}
