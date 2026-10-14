<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Check for links to non html content
 */
function wp_ada_compliance_basic_validate_link_to_non_html_content( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'link_to_non_html_content', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$types       = array();
	$types[]     = 'PDF/.pdf';
	$types[]     = 'WORD/.doc';
	$types[]     = 'WORD/.dot';
	$types[]     = 'EXCEL/.xls';
	$types[]     = 'RTF/.rtf';
	$types[]     = 'TEXT/.txt';
	$types[]     = 'CSV/.csv';
	$types[]     = 'ODT/.odt';
	$types[]     = 'RSS/.rss';
	$types[]     = 'XML/.xml';
	$types[]     = 'POWERPOINT/.ppt';
	$types[]     = 'IMAGE/.jpg';
	$types[]     = 'IMAGE/.png';
	$types[]     = 'IMAGE/.gif';
	$types[]     = 'IMAGE/.svg';
	$types[]     = 'AUDIO/.mp3';
	$types[]     = 'VIDEO/.avi';
	$types[]     = 'VIDEO/.mov';
	$types[]     = 'VIDEO/.wmv';
	$types[]     = 'VIDEO/.flv';
	$types[]     = 'VIDEO/.mp4';
	$types[]     = 'VIDEO/.mkv';
	$types[]     = 'VIDEO/.avchd';
		$types[] = 'EMAIL/mailto:';
	$types[]     = 'PHONE/tel:';

	$links = $dom->find( 'a' );
	foreach ( $links as $link ) {
		foreach ( $types as $key => $value ) {

			$parts = explode( '/', $value );

			$imagealtiscompliant = 0;
			// check embeded image for compliance.
			$nodes = $link->children();
			foreach ( $nodes as $node ) {

				if ( 'img' == $node->tag || 'i' == $node->tag ) {
					if ( stristr( $node->getAttribute( 'alt' ), $parts[0] )
						|| stristr( $node->getAttribute( 'title' ), $parts[0] )
						|| stristr( $node->getAttribute( 'aria-label' ), $parts[0] )
						) {
						$imagealtiscompliant = 1;
					}
				}
			}
			$file_extension = '.' . pathinfo( $link->getAttribute( 'href' ), PATHINFO_EXTENSION );
			if ( ( $file_extension == $parts[1]
			or ( stristr( $link->getAttribute( 'href' ), $parts[1] ) && ( stristr( $link->getAttribute( 'href' ), 'tel:' ) || stristr( $link->getAttribute( 'href' ), 'mailto:' ) ) ) )
			&& ! stristr( $link->plaintext, $parts[0] )
				&& ! stristr( $link->getAttribute( 'title' ), $parts[0] )
				&& ! stristr( $link->getAttribute( 'aria-label' ), $parts[0] )
				&& 0 == $imagealtiscompliant
				) {

				$code = $link->outertext;

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'link_to_non_html_content', $code ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'link_to_non_html_content', $wp_ada_compliance_basic_def['link_to_non_html_content']['StoredError'], $code );
				}
			}
		}
	}
	return 1;
}
