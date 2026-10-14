<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Check for accessibility help options
 */
function wp_ada_compliance_basic_validate_accessibility_help( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	// ignore check when scanning database only.
	if ( 'onsave' == $postinfo['scantype'] ) {
		return;
	}

	$dom = str_get_html( $content );

	// get options..
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned..
	if ( in_array( 'accessibility_help', $wp_ada_compliance_basic_scanoptions ) ) {
		return;
	}

	$links = $dom->find( 'a' );
	foreach ( $links as $link ) {
		if (
			isset( $link )
			&& ( stristr( $link->plaintext, 'accessible' )
				|| stristr( $link->plaintext, 'accessibility' )
				|| stristr( $link->plaintext, 'contact' )
				|| stristr( $link->getAttribute( 'aria-label' ), 'accessible' )
				|| stristr( $link->getAttribute( 'aria-label' ), 'accessibility' )
				|| stristr( $link->getAttribute( 'aria-label' ), 'contact' )
				|| stristr( $link->getAttribute( 'href' ), 'accessible' )
				|| stristr( $link->getAttribute( 'href' ), 'accessibility' )
				|| stristr( $link->getAttribute( 'href' ), 'contact' )
			)
		) {
			return;
		}
	}

	$code     = __( 'Missing accessibility help options or contact form.', 'wp-ada-compliance-basic' );
	$insertid = wp_ada_compliance_basic_error_check( $postinfo, 'accessibility_help', $code );
	if ( ! $insertid ) {
		$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'accessibility_help', $wp_ada_compliance_basic_def['accessibility_help']['StoredError'], $code );
	}
}
