<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Validate missing laguage attributes in theme files
 */
function wp_ada_compliance_basic_validate_missing_lang_attr( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	if ( 'onsave' == $postinfo['scantype'] ) {
		return;
	}

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'missing_lang_attr', $wp_ada_compliance_basic_scanoptions ) ) {
		return;
	}

	$dom = str_get_html( $content );

	$html = $dom->find( 'html' );

	foreach ( $html as $htmlcode ) {
		if ( '' != $htmlcode->getAttribute( 'lang' ) || '' != $htmlcode->getAttribute( 'xml:lang' ) ) {
			return;
		}

		$code = substr( $htmlcode->outertext, 0, strpos( $htmlcode->outertext, '>', 0 ) + 1 );
		$code = str_replace( 'data-wp-ada-scanner="true"', '', $code );

		// save error.
		if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'missing_lang_attr', $code ) ) {
			$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'missing_lang_attr', $wp_ada_compliance_basic_def['missing_lang_attr']['StoredError'], $code );
		}
	}
}
