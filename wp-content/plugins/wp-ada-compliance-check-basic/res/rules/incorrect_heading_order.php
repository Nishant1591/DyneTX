<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Validate page structure, look for headings that are not in order
 */
function wp_ada_compliance_basic_validate_incorrect_heading_order( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// save simple dom.
	$content = $dom->save();

	if ( '' == $content ) {
		return;
	}

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions      = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );
	$wp_ada_compliance_basic_starting_h_level = get_option( 'wp_ada_compliance_basic_starting_h_level', 'h2' );

	// check if being scanned.
	if ( in_array( 'incorrect_heading_order', $wp_ada_compliance_basic_scanoptions ) ) {
		return;
	}

	// when scanning in editors check this.
	if ( 'onsave' == $postinfo['scantype'] ) {

		$errorcode = '';

		// check using page structure.
		$headings = $dom->find( 'h1,[role=heading][aria-level=1],h2,[role=heading][aria-level=2],h3,[role=heading][aria-level=3],h4,[role=heading][aria-level=4],h5,[role=heading][aria-level=5],h6,[role=heading][aria-level=6]' );
		$previous = '';
		foreach ( $headings as $heading ) {

			if ( 'h1' == $wp_ada_compliance_basic_starting_h_level ) {
				if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '1' == $heading->getAttribute( 'aria-level' ) )
					|| 'h1' == strtolower( $heading->tag ) ) && '' != $previous ) {
					$errorcode .= __( 'more than one h1; ', 'wp-ada-compliance-basic' );
					$errorcode .= $heading->outertext;
				}

				if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '2' == $heading->getAttribute( 'aria-level' ) )
				|| 'h2' == strtolower( $heading->tag ) ) && 'h1' != $previous && 'h2' != $previous && 'h3' != $previous && 'h4' != $previous && 'h5' != $previous && 'h6' != $previous ) {
					$errorcode .= __( 'h2 without h1; ', 'wp-ada-compliance-basic' );
					$errorcode .= $heading->outertext;
				}
			} elseif ( 'h2' == $wp_ada_compliance_basic_starting_h_level ) {
				if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '2' == $heading->getAttribute( 'aria-level' ) )
				|| 'h2' == strtolower( $heading->tag ) ) && 'h1' != $previous && 'h2' != $previous && 'h3' != $previous && 'h4' != $previous && 'h5' != $previous && 'h6' != $previous && '' != $previous ) {
					$errorcode .= __( 'h2 before h1; ', 'wp-ada-compliance-basic' );
					$errorcode .= $heading->outertext;
				}
			}

			if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '3' == $heading->getAttribute( 'aria-level' ) )
			|| 'h3' == strtolower( $heading->tag ) ) && 'h2' != $previous && 'h3' != $previous && 'h4' != $previous && 'h5' != $previous && 'h6' != $previous ) {
				$errorcode .= __( 'h3 before h2; ', 'wp-ada-compliance-basic' );
				$errorcode .= $heading->outertext;
			}

			if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '4' == $heading->getAttribute( 'aria-level' ) )
			|| 'h4' == strtolower( $heading->tag ) ) && 'h3' != $previous && 'h4' != $previous && 'h5' != $previous && 'h6' != $previous ) {
				$errorcode .= __( 'h4 before h3; ', 'wp-ada-compliance-basic' );
				$errorcode .= $heading->outertext;
			}

			if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '5' == $heading->getAttribute( 'aria-level' ) )
			|| 'h5' == strtolower( $heading->tag ) ) && 'h4' != $previous && 'h5' != $previous && 'h6' != $previous ) {
				$errorcode .= __( 'h5 before h4; ', 'wp-ada-compliance-basic' );
				$errorcode .= $heading->outertext;
			}

			if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '6' == $heading->getAttribute( 'aria-level' ) )
			|| 'h6' == strtolower( $heading->tag ) ) && 'h5' != $previous && 'h6' != $previous ) {
				$errorcode .= __( 'h6 before h5; ', 'wp-ada-compliance-basic' );
				$errorcode .= $heading->outertext;
			}

			if ( 'heading' == $heading->getAttribute( 'role' ) ) {
				$previous = 'h' . trim( $heading->getAttribute( 'aria-level' ) );
			} else {
				$previous = strtolower( $heading->tag );
			}
		}

		if ( '' != $errorcode ) {
			$errorcode = 'Issues: ' . $errorcode;

			// save error.
			if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'incorrect_heading_order', $errorcode ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'incorrect_heading_order', $wp_ada_compliance_basic_def['incorrect_heading_order']['StoredError'], $errorcode );
			}
		}
	} else { // when scanning the full content do this.
		$errorcode = '';

		$h1 = count( $dom->find( 'h1,[role=heading][aria-level=1]' ) );
		if ( 0 == $h1 ) {
			$errorcode .= __( 'no first level heading h1; ', 'wp-ada-compliance-basic' );
		} elseif ( $h1 > 1 ) {
			$errorcode .= __( 'more than one h1; ', 'wp-ada-compliance-basic' );
		}

		$h1s          = $dom->find( 'h1,[role=heading][aria-level=1]' );
		$frontpage_id = get_option( 'page_on_front' );
		foreach ( $h1s as $h1value ) {

			if ( ( '' != trim( wp_ada_compliance_basic_replaceSpecialCharacters( $postinfo['title'] ) ) && '' != trim( wp_ada_compliance_basic_replaceSpecialCharacters( $h1value->plaintext ) ) )
			&& ! stristr( trim( wp_ada_compliance_basic_replaceSpecialCharacters( $h1value->plaintext ) ), trim( wp_ada_compliance_basic_replaceSpecialCharacters( $postinfo['title'] ) ) )
			&& ! stristr( trim( wp_ada_compliance_basic_replaceSpecialCharacters( $postinfo['title'] ) ), trim( wp_ada_compliance_basic_replaceSpecialCharacters( $h1value->plaintext ) ) )
			&& ! stristr( trim( $h1value->plaintext ), trim( $postinfo['title'] ) )
			&& ! stristr( trim( $postinfo['title'] ), trim( $h1value->plaintext ) )
			&& ! strstr( $errorcode, __( 'h1 not page title; ', 'wp-ada-compliance-basic' ) )
			&& $postinfo['postid'] != $frontpage_id
			) {
				$errorcode .= __( 'h1 not page title; ', 'wp-ada-compliance-basic' );
			}

			if ( strstr( $errorcode, __( 'h1 not page title; ', 'wp-ada-compliance-basic' ) ) || strstr( $errorcode, __( 'more than one h1; ', 'wp-ada-compliance-basic' ) ) ) {
				$errorcode .= $h1value->outertext;
			}
		}

		// check that heading 1 is found inside the main content area.
		$mains = $dom->find( 'main,[role=main]' );
		foreach ( $mains as $main ) {
			$headings = $main->find( 'h1,[role=heading][aria-level=1]' );
			if ( count( $headings ) < 1 ) {
				$errorcode .= __( 'h1 not found in the main content area; ', 'wp-ada-compliance-basic' );
			}
		}

		// check using page structure.
		$headings = $dom->find( 'h1,[role=heading][aria-level=1],h2,[role=heading][aria-level=2],h3,[role=heading][aria-level=3],h4,[role=heading][aria-level=4],h5,[role=heading][aria-level=5],h6,[role=heading][aria-level=6]' );
		$previous = '';
		foreach ( $headings as $heading ) {

			if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '1' == $heading->getAttribute( 'aria-level' ) )
			|| 'h1' == strtolower( $heading->tag ) ) && '' != $previous ) {
				$errorcode .= __( 'more than one h1; ', 'wp-ada-compliance-basic' );
				$errorcode .= $heading->outertext;
			}

			if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '2' == $heading->getAttribute( 'aria-level' ) )
			|| 'h2' == strtolower( $heading->tag ) ) && 'h1' != $previous && 'h2' != $previous && 'h3' != $previous && 'h4' != $previous && 'h5' != $previous && 'h6' != $previous ) {
				$errorcode .= __( 'h2 before h1; ', 'wp-ada-compliance-basic' );
				$errorcode .= $heading->outertext;
			}

			if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '3' == $heading->getAttribute( 'aria-level' ) )
			|| 'h3' == strtolower( $heading->tag ) ) && 'h2' != $previous && 'h3' != $previous && 'h4' != $previous && 'h5' != $previous && 'h6' != $previous ) {
				$errorcode .= __( 'h3 before h2; ', 'wp-ada-compliance-basic' );
				$errorcode .= $heading->outertext;
			}

			if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '4' == $heading->getAttribute( 'aria-level' ) )
			|| 'h4' == strtolower( $heading->tag ) ) && 'h3' != $previous && 'h4' != $previous && 'h5' != $previous && 'h6' != $previous ) {
				$errorcode .= __( 'h4 before h3; ', 'wp-ada-compliance-basic' );
				$errorcode .= $heading->outertext;
			}

			if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '5' == $heading->getAttribute( 'aria-level' ) )
			|| 'h5' == strtolower( $heading->tag ) ) && 'h4' != $previous && 'h5' != $previous && 'h6' != $previous ) {
				$errorcode .= __( 'h5 before h4; ', 'wp-ada-compliance-basic' );
				$errorcode .= $heading->outertext;
			}

			if ( ( ( 'heading' == $heading->getAttribute( 'role' ) && '6' == $heading->getAttribute( 'aria-level' ) )
			|| 'h6' == strtolower( $heading->tag ) ) && 'h5' != $previous && 'h6' != $previous ) {
				$errorcode .= __( 'h6 before h5; ', 'wp-ada-compliance-basic' );
				$errorcode .= $heading->outertext;
			}

			if ( 'heading' == $heading->getAttribute( 'role' ) ) {
				$previous = 'h' . trim( $heading->getAttribute( 'aria-level' ) );
			} else {
				$previous = strtolower( $heading->tag );
			}
		}

		if ( '' != $errorcode ) {
			$errorcode = 'Issues: ' . $errorcode;
			// save error.
			if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'incorrect_heading_order', $errorcode ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'incorrect_heading_order', $wp_ada_compliance_basic_def['incorrect_heading_order']['StoredError'], $errorcode );
			}
		}
		$errorcode = '';

	}
	return;
}
/**
 * Replace special Characters
 */
function wp_ada_compliance_basic_replaceSpecialCharacters( $value ) {
	$value = trim( $value );

	$value = str_ireplace( '   ', ' ', $value );
	$value = str_ireplace( '  ', ' ', $value );
	$value = str_ireplace( '‘', "'", $value );
	$value = str_ireplace( '&amp;', '&', $value );
	$value = str_ireplace( '‘', "'", $value );
	$value = str_ireplace( '’', "'", $value );
	$value = str_ireplace( '”', '"', $value );
	$value = str_ireplace( '“', '"', $value );
	$value = str_ireplace( '&ldquo;', '"', $value );
	$value = str_ireplace( '&rdquo;', '"', $value );
	$value = str_ireplace( '&rsquo;', '"', $value );
	$value = str_ireplace( '&ndash;', '"', $value );
	$value = str_ireplace( '–', '-', $value );
	$value = str_ireplace( '…', '...', $value );
	$value = str_ireplace( '&oacute;', '', $value );
	$value = str_ireplace( '&eacute;', '', $value );
	$value = str_ireplace( '&aacute;', '', $value );
	$value = str_ireplace( '&middot;', '', $value );
	$value = str_ireplace( '&uacute;', '', $value );
	$value = str_ireplace( '&uuml;', '', $value );
	$value = str_ireplace( '&ograve;', '', $value );
	$value = str_ireplace( '&egrave;', '', $value );
	$value = str_ireplace( '&iacute;', '', $value );
	$value = str_ireplace( '&ccedil;', '', $value );
	$value = str_ireplace( '&agrave;', '', $value );

	$value = preg_replace( '/[^a-zA-Z ]/u', '', $value );

	// echo $value;
	return $value;
}
