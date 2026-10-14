<?php
/**
 * SECURITY FUNCTIONS
 **/

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Validate deep scan
 */
function wp_ada_compliance_basic_validate_deep_scan( $value ) {
	$accepted = array( 'basic', 'deep' );

	if ( in_array( $value, $accepted ) ) {
		return wp_ada_compliance_basic_sanitize_text_or_array_field( $value );
	}

	return 'basic';
}

/**
 * Validate post types
 **/
function wp_ada_compliance_basic_validate_posttypes( $values ) {
	global $wpdb;
	$post_type_list = array();
	$results        = $wpdb->get_results( 'SELECT distinct(post_type) FROM ' . $wpdb->prefix . 'posts', ARRAY_A );
	if ( $results ) {
		foreach ( $results as $row ) {
			if ( ! in_array( $row['post_type'], $post_type_list ) ) {
				$post_type_list[] = $row['post_type'];
			}
		}
	}
	if ( is_array( $values ) || '' == $values ) {
		foreach ( $values as $key => $value ) {
			if ( ! in_array( $value, $post_type_list ) ) {
				unset( $values[ $key ] );
			}
		}
		return wp_ada_compliance_basic_sanitize_text_or_array_field( $values );
	}
	return array( 'page', 'post' );
}

/**
 * Validate scan rules
 */
function wp_ada_compliance_basic_validate_scan_rules( $values ) {
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$allowed = array();

	if ( is_array( $wp_ada_compliance_basic_def ) ) {
		foreach ( $wp_ada_compliance_basic_def as $rows => $row ) {
			if ( ! in_array( $rows, $allowed ) ) {
				$allowed[] = $rows;
			}
		}
	}

	if ( is_array( $values ) || '' == $values ) {
		foreach ( $values as $key => $value ) {
			if ( ! in_array( $value, $allowed ) ) {
				unset( $values[ $key ] );
			}
		}
		return wp_ada_compliance_basic_sanitize_text_or_array_field( $values );
	}
	return $allowed;
}

/**
 * Validate errors per page
 */
function wp_ada_compliance_basic_validate_errors_per_page( $value ) {

	$value = (int) $value;

	if ( 0 == $value ) {
		return 15;
	} else {
		return $value;
	}
}

/**
 * Validate settings users
 **/
function wp_ada_compliance_basic_validate_settingsusers( $value ) {
	if ( in_array( $value, array( 'edit_pages', 'manage_options' ) ) ) {
		return wp_ada_compliance_basic_sanitize_text_or_array_field( $value );
	} else {
		return 'manage_options';
	}
}

/**
 * Validate language code
 */
function wp_ada_compliance_basic_validate_language_code( $value ) {
	if ( preg_match( '/^[a-z]{2}(-[A-Z]{2})?$/i', $value ) ) {
		return wp_ada_compliance_basic_sanitize_text_or_array_field( $value );
	} else {
		return 'en';
	}
}

/**
 * Validate background color
 */
function wp_ada_compliance_basic_validate_background_color( $value ) {
	if ( preg_match( '/^#([a-f0-9]{6}|[a-f0-9]{3})\b$/i', $value ) ) {
		return wp_ada_compliance_basic_sanitize_text_or_array_field( $value );
	} else {
		return '#ffffff';
	}
}

/**
 * Validate foreground color
 */
function wp_ada_compliance_basic_validate_foreground_color( $value ) {
	if ( preg_match( '/^#([a-f0-9]{6}|[a-f0-9]{3})\b$/i', $value ) ) {
		return wp_ada_compliance_basic_sanitize_text_or_array_field( $value );
	} else {
		return '#000000';
	}
}

/**
 * Validate starting H level
 */
function wp_ada_compliance_basic_validate_starting_h_level( $value ) {
	if ( in_array( $value, array( 'h1', 'h2' ) ) ) {
		return wp_ada_compliance_basic_sanitize_text_or_array_field( $value );
	} else {
		return 'h2';
	}
}

/**
 * Validate true false values DEFAULT to true
 */
function wp_ada_compliance_basic_validate_false_default_true( $value ) {
	if ( in_array( $value, array( 'true', 'false' ) ) ) {
		return wp_ada_compliance_basic_sanitize_text_or_array_field( $value );
	} else {
		return 'true';
	}
}

/**
 * Validate true false values DEFAULT to false
 */
function wp_ada_compliance_basic_validate_true_default_false( $value ) {
	if ( in_array( $value, array( 'true', 'false' ) ) ) {
		return wp_ada_compliance_basic_sanitize_text_or_array_field( $value );
	} else {
		return 'false';
	}
}

/**
 * Sanitize array
 */
function wp_ada_compliance_basic_sanitize_text_or_array_field( $array_or_string ) {
	if ( is_string( $array_or_string ) ) {
		$array_or_string = sanitize_text_field( $array_or_string );
	} elseif ( is_array( $array_or_string ) ) {
		foreach ( $array_or_string as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = wp_ada_compliance_basic_sanitize_text_or_array_field( $value );
			} else {
				$value = sanitize_text_field( $value );
			}
		}
	}

	return $array_or_string;
}

/**
 * Validate input
 */
function wp_ada_compliance_basic_form_values() {
	global $wpdb;
	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();
	$error = '';

	if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'wp-ada-compliance-nonce' ) ) {
		die();
	}

	foreach ( $_POST as $key => $value ) {

		if ( isset( $_POST[ $key ] ) ) {
			$_POST[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		}

		// error email.
		if ( 'email' == $key && ! filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
			$error = __( 'Email address is invalid', 'wp-ada-compliance' );
		}

		// error types.
		if ( 'comments' == $key && ! preg_match( "/^([[:alnum:]]|-|[[:space:]]|[[:punct:]]|')+$/D", $value ) && '' != $value ) {
			$error = __( 'Comments contain invalid text.', 'wp-ada-compliance' );
		} elseif ( 'error' == $key && ! array_key_exists( $value, $wp_ada_compliance_basic_def ) && '' != $value ) {
			$_POST['error'] = '';
		}

		// check title search.
		if ( 'searchtitle' == $key && ! preg_match( "/^([[:alnum:]]|-|[[:space:]]|[[:punct:]]|')+$/D", $value ) && '' != $value ) {
			$_POST['searchtitle'] = '';
		}

		// post types.
		if ( 'type' == $key ) {
			$accepted = array( '' );
			$results  = $wpdb->get_results( 'SELECT distinct(type) FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic', ARRAY_A );
			foreach ( $results as $row ) {
				$accepted[] = $row['type'];
			}
			if ( ! in_array( $value, $accepted ) && '' != $value ) {
				$_POST['type'] = '';
			}
		}
	}

	foreach ( $_GET as $key => $value ) {

		if ( isset( $_GET[ $key ] ) ) {
			$_GET[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
		}

		// error types.
		if ( 'errorw' == $key && ! array_key_exists( $value, $wp_ada_compliance_basic_def ) && '' != $value ) {
			$_GET['errorw'] = '';
		} elseif ( 'error' == $key && ! array_key_exists( $value, $wp_ada_compliance_basic_def ) && '' != $value ) {
			$_GET['error'] = '';
		}

		// check title search.
		if ( 'searchtitle' == $key && ! preg_match( "/^([[:alnum:]]|-|[[:space:]]|[[:punct:]]|')+$/D", $value ) && '' != $value ) {
			$_GET['searchtitle'] = '';
		}

		// post types.
		if ( 'type' == $key ) {
			$accepted = array( '' );

			$results = $wpdb->get_results( 'SELECT distinct(type) FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic', ARRAY_A );
			foreach ( $results as $row ) {
				$accepted[] = $row['type'];
			}
			if ( ! in_array( $value, $accepted ) && '' != $value ) {
				$_GET['type'] = '';
			}
		}

		// ignore rule.
		if ( 'wpada_ignore_rule' == $key ) {
			if ( ! array_key_exists( $value, $wp_ada_compliance_basic_def ) ) {
				$_GET['wpada_ignore_rule'] = '';
			}
		}
	} // end get loop.

	return $error;
}
