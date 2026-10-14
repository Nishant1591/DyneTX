<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Look for tags with onclick used to emulate links
 */
function wp_ada_compliance_basic_validate_emulating_links( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'emulating_links', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$elements = $dom->find( '*' );

	foreach ( $elements as $element ) {
		if ( ( $element->getAttribute( 'onclick' ) || $element->getAttribute( 'ondblclick' )
		|| $element->getAttribute( 'onmousedown' ) || $element->getAttribute( 'onmouseup' )
		|| $element->getAttribute( 'onkeypress' ) || $element->getAttribute( 'onkeydown' )
		|| $element->getAttribute( 'onkeyup' ) || $element->getAttribute( 'onmouseover' )
		|| $element->getAttribute( 'onmouseout' ) || $element->getAttribute( 'onmousemove' )
		|| $element->getAttribute( 'onfocus' ) || $element->getAttribute( 'onblur' ) )
			&& 'a' != $element->tag
			&& 'button' != $element->tag
			&& 'input' != $element->tag
			&& 'select' != $element->tag
			&& 'textarea' != $element->tag
			&& 'area' != $element->tag
			&& 'datalist' != $element->tag
			&& 'output' != $element->tag
			&& ( ! $element->getAttribute( 'role' )
			|| ! $element->hasAttribute( 'tabindex' ) )
			) {

			$ahtagcode = $element->outertext;

			// save error.
			if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'emulating_links', $ahtagcode ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'emulating_links', $wp_ada_compliance_basic_def['emulating_links']['StoredError'], $ahtagcode );
			}
		}
	}

	return 1;
}
