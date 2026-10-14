<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Validate redundent link text
 */
function wp_ada_compliance_basic_validate_redundant_anchor_text( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'redundant_anchor_text', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$links = $dom->find( 'a' );

	// define site url.
	$siteurl                = esc_url_raw( get_site_url() );
	$replacement_patterns   = array();
	$replacement_patterns[] = '|' . $siteurl . '|i';
	$replacement_patterns[] = '|' . preg_replace( '#^[^\/]*#', '', preg_replace( '#((https://)|http://)*#i', '', $siteurl ) ) . '|i';
	$replacement_patterns[] = '|https://|i';
	$replacement_patterns[] = '|http://|i';

	$links           = $dom->find( 'a' );
	$count           = 1;
	$linktext        = array();
	$linkcode        = array();
	$linkdestination = array();

	foreach ( $links as $link ) {

		if ( '-1' != $link->getAttribute( 'tabindex' ) && '' == $link->getAttribute( 'aria-hidden' ) ) {

			$arialabelledby  = wp_ada_compliance_basic_get_aria_values( $dom, $link, 'aria-labelledby' );
			$ariadescribedby = wp_ada_compliance_basic_get_aria_values( $dom, $link, 'aria-describedby' );

			$img = $link->find( 'img' );
			$svg = $link->find( 'svg' );

			if ( '' != $link->getAttribute( 'aria-label' ) ) {// aria label on lnks.
				$linktext[ $count ] = $link->getAttribute( 'aria-label' );
			} elseif ( '' != $link->getAttribute( 'title' ) ) {// title on links.
				$linktext[ $count ] = $link->getAttribute( 'title' );
			} elseif ( '' != $ariadescribedby ) {// aria - described by.
				$linktext[ $count ] = $ariadescribedby;
			} elseif ( '' != $arialabelledby ) {// aria - labelled by.
				$linktext[ $count ] = $arialabelledby;
			} elseif ( isset( $img[0] ) && '' == $link->plaintext ) {// images.
				if ( '' != $img[0]->getAttribute( 'alt' ) ) {
					$linktext[ $count ] = $img[0]->getAttribute( 'alt' );
				}
			} elseif ( isset( $svg[0] ) ) {// svg.
				$linktext[ $count ] = trim( wp_ada_compliance_basic_check_svg_img_alt_text( $svg[0], $dom ) );
			} else {
				$temptext = '';
				// title and link text.
				if ( '' != $link->getAttribute( 'title' ) ) {
					$temptext .= ' ' . $link->getAttribute( 'title' );
				}
				// alt text from image.
				if ( isset( $img[0] ) && '' != $img[0]->getAttribute( 'alt' ) ) {
					$temptext .= ' ' . $img[0]->getAttribute( 'alt' );
				}
				if ( isset( $svg[0] ) ) {

					$temptext          .= ' ' . trim( wp_ada_compliance_basic_check_svg_img_alt_text( $svg[0], $dom ) );
					$linktext[ $count ] = $temptext;
				} else {
					$linktext[ $count ] = $link->plaintext . $temptext;
				}
			}

			$linkcode[ $count ]        = $link->outertext;
			$linkdestination[ $count ] = preg_replace( $replacement_patterns, '', $link->getAttribute( 'href' ) );

			$excludedlinks[] = '';
			$excludedlinks[] = 'x';
			$excludedlinks[] = __( 'close', 'wp-ada-compliance' );

			$matchfound = 0;

			foreach ( $linktext as $key => $value ) {
				if ( array_key_exists( $key - 1, $linkcode ) && array_key_exists( $key, $linkcode )
					&& array_key_exists( $key - 1, $linktext ) && array_key_exists( $key, $linktext )
					&& trim( strtolower( $linktext[ $key ] ) ) == trim( strtolower( $linktext[ $key - 1 ] ) )
					&& $linkdestination[ $key ] != $linkdestination[ $key - 1 ]
					) {
					if ( ! in_array( trim( strtolower( $value ) ), $excludedlinks ) ) {

						$atagcode   = $linkcode[ $key - 1 ] . ' ... ' . $linkcode[ $key ];
						$matchfound = 1;

					}
				}
			}

			// display error.
			if ( 1 == $matchfound ) {

				// save error.
				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'redundant_anchor_text', $atagcode ) ) {
					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'redundant_anchor_text', $wp_ada_compliance_basic_def['redundant_anchor_text']['StoredError'], $atagcode );
				}
			}
			++$count;

		}
	}
	return 1;
}
