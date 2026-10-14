<?php

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Look for links and focus styles where visual focus indication has been removed.
 */
function wp_ada_compliance_basic_validate_visual_focus_removed( $content, $postinfo ) {
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'visual_focus_removed', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	if ( 'css' != $postinfo['type'] ) {

		$dom = str_get_html( $content );

		foreach ( $dom->find( 'a,input,button,textarea,select,iframe,area,details' ) as $element ) {
			if (
			preg_match( '/outline:\s?((thin|\dpx)\s?|(dotted)\s?|(#000|black|rgb\(0,0,0\))\s?){3}/i', $element->getAttribute( 'style' ) )
			|| preg_match( '/outline:\s?(0|none)/', $element->getAttribute( 'style' ) )
			|| preg_match( '/border:\s?((medium|thick|\dpx)\s?|(solid)\s?|(#000|black|rgb\(0,0,0\))\s?){3}/', $element->getAttribute( 'style' ) )
			|| ( preg_match( '/outline-width:\s?(thin|\dpx)/i', $element->getAttribute( 'style' ) )
			&& preg_match( '/outline-style:\s?(dotted)/i', $element->getAttribute( 'style' ) )
			&& preg_match( '/outline-color:\s?(#000|black|rgb\(0,0,0\))/i', $element->getAttribute( 'style' ) )
			)
			|| ( preg_match( '/border-width:\s?(medium|thick|\dpx)/i', $element->getAttribute( 'style' ) )
			&& preg_match( '/border-style:\s?(solid)/i', $element->getAttribute( 'style' ) )
			&& preg_match( '/border-color:\s?(#000|black|rgb\(0,0,0\))/i', $element->getAttribute( 'style' ) )
			)
			|| stristr( $element->getAttribute( 'onfocus' ), 'this.blur' )
			) {

				// save error.
				$visual_focus_removed_errorcode = wp_ada_compliance_basic_simple_dom_remove_child( $element );

				if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'visual_focus_removed', $visual_focus_removed_errorcode ) ) {

					$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'visual_focus_removed', $wp_ada_compliance_basic_def['visual_focus_removed']['StoredError'], $visual_focus_removed_errorcode );
				}
			}
		}
	}

	$types = array( 'a:link', 'a:visited', ':focus', 'a', 'a[', ' a,', 'input', 'button', 'textarea', 'select', 'iframe', 'area', 'details', 'a:focus', 'input:focus', 'button:focus', 'textarea:focus', 'select:focus', 'iframe:focus', 'area:focus', 'details:focus' );

	// // parse and scan style tag content in post files.
	if ( 'css' != $postinfo['type'] && stristr( $content, '<style' ) ) {
		// check links in content for style tags.

		$dom = str_get_html( $content );

		foreach ( $dom->find( 'style' ) as $style ) {
			$css_array = wp_ada_compliance_basic_parce_style_content_for_visual_focus_removed( $style->innertext );

			foreach ( $types as $type ) {
				wp_ada_compliance_basic_scan_css_content_for_visual_focus_removed_violation( $css_array, $postinfo, $type );
			}
		}
	}
	// parse and scan css file content.
	if ( 'css' == $postinfo['type'] ) {
		$css_array = wp_ada_compliance_basic_parce_style_content_for_visual_focus_removed( $content );

		if ( count( $css_array ) > 0 ) {
			foreach ( $types as $type ) {
				wp_ada_compliance_basic_scan_css_content_for_visual_focus_removed_violation( $css_array, $postinfo, $type );
			}
		}
	}
}


/**
 * Parse css file to make it easy to search
 **/
function wp_ada_compliance_basic_parce_style_content_for_visual_focus_removed( $css ) {
	$css = strip_tags( $css );
	$css = preg_replace( '%/\*(?:(?!\*/).)*\*/%s', ' ', $css );

	$css_array = array(); // master array to hold all values.
	$element   = explode( '}', $css );

	foreach ( $element as $element ) {
		$elementtemp = explode( '{', $element );

		// get the name of the CSS element.
		$a_name = explode( '{', $element );

		$name = $a_name[0];

		// get all the key:value pair styles.
		$a_styles = explode( ';', $element );
		// remove element name from first property element.
		$a_styles[0] = str_replace( $name . '{', '', $a_styles[0] );
		// loop through each style and split apart the key from the value.
		$count = count( $a_styles );
		for ( $a = 0;$a < $count;$a++ ) {
			if ( '' != $a_styles[ $a ] ) {
				$a_styles[ $a ] = str_ireplace( 'https://', '//', $a_styles[ $a ] );
				$a_styles[ $a ] = str_ireplace( 'http://', '//', $a_styles[ $a ] );
				$a_key_value    = explode( ':', $a_styles[ $a ] );
				// build the master css array.
				if ( array_key_exists( 1, $a_key_value ) ) {
					$css_array[ trim( $name ) ][ trim( strtolower( $a_key_value[0] ) ) ] = trim( $a_key_value[1] );
				}
			}
		}
	}

	return $css_array;
}

/**
 * Scan the content from a css file or style tag inside a post
 **/
function wp_ada_compliance_basic_scan_css_content_for_visual_focus_removed_violation( $css_array, $postinfo, $type ) {
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	foreach ( $css_array as $element => $rules ) {
		$elementtemp = explode( '{', $element );

		$errorfound   = 0;
		$outline      = wp_ada_compliance_basic_findKey( $rules, 'outline' );
		$border       = wp_ada_compliance_basic_findKey( $rules, 'border' );
		$borderwidth  = wp_ada_compliance_basic_findKey( $rules, 'border-width' );
		$borderstyle  = wp_ada_compliance_basic_findKey( $rules, 'border-style' );
		$bordercolor  = wp_ada_compliance_basic_findKey( $rules, 'border-color' );
		$outlinewidth = wp_ada_compliance_basic_findKey( $rules, 'outline-width' );
		$outlinestyle = wp_ada_compliance_basic_findKey( $rules, 'outline-style' );
		$outlinecolor = wp_ada_compliance_basic_findKey( $rules, 'outline-color' );

		if ( strstr( $type, ':focus' ) ) {
			if ( preg_match( '/\s?(0|none)/', $outline ) ) {
				$errorfound = 1;

			}
		} elseif ( ( ( 'a' == $element || 'a' == substr( trim( $elementtemp[0] ), -2 ) || stristr( $element, 'a,' ) || stristr( $element, 'a[' ) || stristr( $element, 'a.' ) || stristr( $element, 'a#' ) ) && 'a' == $type )
		|| ( 'a' != $type && stristr( $element, $type ) ) ) {
			if ( preg_match( '/\s?((medium|thick|\dpx)\s?|(solid)\s?|(#000|black|rgb\(0,0,0\))\s?){3}/', $border )
			|| ( preg_match( '/\s?(medium|thick|\dpx)/i', $borderwidth )
			&& preg_match( '/\s?(solid)/i', $borderstyle )
			&& preg_match( '/\s?(#000|black|rgb\(0,0,0\))/i', $bordercolor )
			)
			|| ( preg_match( '/\s?((thin|\dpx)\s?|(dotted)\s?|(#000|black|rgb\(0,0,0\))\s?){3}/i', $outline )
			|| preg_match( '/\s?(0|none)/', $outline )
			|| ( preg_match( '/\s?(thin|\dpx)/i', $outlinewidth )
			&& preg_match( '/\s?(dotted)/i', $outlinestyle )
			&& preg_match( '/\s?(#000|black|rgb\(0,0,0\))/i', $outlinecolor ) )
			)
			) {
				$errorfound = 1;
			}
		}

		if ( 1 == $errorfound ) {

				$visual_focus_removed_errorcode = $element . '{';
			foreach ( $rules as $key => $value ) {
				$visual_focus_removed_errorcode .= $key . ': ' . $value . '; ';
			}
				$visual_focus_removed_errorcode .= '}';

			// save error.
			$insertid = wp_ada_compliance_basic_error_check( $postinfo, 'visual_focus_removed', $visual_focus_removed_errorcode );
			if ( ! $insertid ) {

				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'visual_focus_removed', $wp_ada_compliance_basic_def['visual_focus_removed']['StoredError'], $visual_focus_removed_errorcode );
			}
		}
	}
}
/**
 * Search rule array for matching value
 **/
function wp_ada_compliance_basic_findKey( $rules, $key ) {
	foreach ( $rules as $k => $value ) {
		if ( $k == $key ) {
			return $value;
		}
		if ( is_array( $value ) ) {
			$find = wp_ada_compliance_basic_findKey( $value, $key );
			if ( $find ) {
				return $find;
			}
		}
	}
	return '';
}
