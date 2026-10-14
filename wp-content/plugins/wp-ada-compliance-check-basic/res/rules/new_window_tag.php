<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Target new window
 **/
function wp_ada_compliance_basic_validate_new_window_tag( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'new_window_tag', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$links = $dom->find( 'a' );
	foreach ( $links as $link ) {
		$imagealtiscompliant = 0;
		// check embeded image for compliance.
		$nodes = $link->children();
		foreach ( $nodes as $node ) {
			if ( 'img' == $node->tag || 'i' == $node->tag ) {
				if ( stristr( $node->getAttribute( 'title' ), 'new window' )
					|| stristr( $node->getAttribute( 'alt' ), 'new window' )
					|| stristr( $node->getAttribute( 'aria-label' ), 'new window' )
					|| stristr( $node->getAttribute( 'title' ), 'new tab' )
					|| ( stristr( $node->getAttribute( 'alt' ), 'new window' )
					|| stristr( $node->getAttribute( 'aria-label' ), 'new tab' ) ) ) {
					$imagealtiscompliant = 1;
				}
			}
		}

		if ( isset( $link )
			&& ( '_blank' == $link->getAttribute( 'target' ) || stristr( $link->getAttribute( 'onclick' ), 'window.open' ) || stristr( $link->getAttribute( 'onclick' ), 'openwindow' ) )
			&& ! stristr( $link->plaintext, 'new window' )
			&& ! stristr( $link->plaintext, 'new tab' )
			&& ! stristr( $link->getAttribute( 'title' ), 'new tab' )
		&& ! stristr( $link->getAttribute( 'aria-label' ), 'new tab' )
			&& ! stristr( $link->getAttribute( 'title' ), 'new window' )
		&& ! stristr( $link->getAttribute( 'aria-label' ), 'new window' )
			&& 0 == $imagealtiscompliant ) {

			$newwindowtag = $link->outertext;

			// save error.
			if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'new_window_tag', $newwindowtag ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'new_window_tag', $wp_ada_compliance_basic_def['new_window_tag']['StoredError'], $newwindowtag );
			}
		}
	}
	return 1;
}
