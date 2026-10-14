<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Check missing page titles
 */
function wp_ada_compliance_basic_validate_missing_title( $content, $postinfo ) {
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	if ( 'widget' == $postinfo['type'] ) {
		return 1;
	}

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'missing_title', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	if ( '' == $postinfo['title']
				|| stristr( $postinfo['title'], __( 'untitled document', 'wp-ada-compliance-basic' ) )
	|| stristr( $postinfo['title'], __( 'enter the title of your html document here,', 'wp-ada-compliance-basic' ) )
		|| stristr( $postinfo['title'], __( 'no title', 'wp-ada-compliance-basic' ) )
		|| stristr( $postinfo['title'], __( 'untitled page', 'wp-ada-compliance-basic' ) )
		|| strtolower( $postinfo['title'] ) == __( 'untitled', 'wp-ada-compliance' )
		|| stristr( $postinfo['title'], __( '.html', 'wp-ada-compliance-basic' ) )
		|| stristr( $postinfo['title'], __( 'New Page', 'wp-ada-compliance-basic' ) )
	) {

		$missing_title_errorcode = '<title>' . $postinfo['title'] . '</title>';
		// save error.
		if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'missing_title', $missing_title_errorcode ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'missing_title', $wp_ada_compliance_basic_def['missing_title']['StoredError'], $missing_title_errorcode );
		}
	}
}
