<?php
/**
 * ERROR FUNCTIONS
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Check if error exists
 **/
function wp_ada_compliance_basic_error_check( $postinfo, $errortype, $objectvalue ) {
	global $wpdb;

		$scantype  = sanitize_text_field( $postinfo['scantype'] );
	$postid        = (int) $postinfo['postid'];
	$title         = sanitize_text_field( $postinfo['title'] );
	$type          = sanitize_text_field( $postinfo['type'] );
		$errortype = sanitize_text_field( $errortype );

	// set flag for onsave scans.
	if ( 'onsave' == $scantype ) {
		$onsave = 1;
	} else {
		$onsave = 0;
	}

	$ignre = wp_ada_compliance_basic_get_ignore_value( $errortype, $onsave, $type, $objectvalue );

	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT postid, ignre FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where type = %s and postid = %d and errorcode = %s and object = %s ', $type, $postid, $errortype, $objectvalue ), ARRAY_A );

	if ( $results ) {
		foreach ( $results as $row ) {

			// if being ignored, don't overwrite value.
			if ( 1 == $row['ignre'] ) {
				$ignre = 1;
			}

			// mark theme errors.
			$themeerror = wp_ada_compliance_basic_theme_error_probability_check( $objectvalue, $errortype );
			wp_ada_compliance_basic_mark_theme_errors( $objectvalue, $themeerror, $type );

			// set recordcheck to 1.
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'wp_ada_compliance_basic SET recordcheck = %d, onsave = %d, ignre = %d  WHERE postid = %d and errorcode = %s and object = %s and type = %s', 1, $onsave, $ignre, $postid, $errortype, $objectvalue, $type ) );

			return 1;
		}
	}
}
/**
 * Check if theme error
 **/
function wp_ada_compliance_basic_theme_error_probability_check( $objectvalue, $errorcode ) {
	global $wpdb;
	$probability = '';
	$results     = $wpdb->get_results( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where object = %s and errorcode = %s', $objectvalue, $errorcode ), ARRAY_A );
	if ( $wpdb->num_rows > 10 ) {
		$probability = 100;
	} elseif ( $wpdb->num_rows > 6 && $wpdb->num_rows < 11 ) {
		$probability = 75;
	} elseif ( $wpdb->num_rows > 3 && $wpdb->num_rows < 7 ) {
		$probability = 33;
	} elseif ( $wpdb->num_rows > 1 && $wpdb->num_rows < 4 ) {
		$probability = 10;
	}

	return $probability;
}

/**
 * Format error location message
 */
function wp_ada_compliance_basic_format_error_location( $ignre, $errorcode, $type, $errorid, $themeerror, $tags = 1 ) {
	global $wpdb;
	$source  = '';
	$details = '';

	$marketing = __( 'There is a HIGH probability that this issue will be found somewhere external to the editor content of this file. Theme error tool tips help you quickly identify the location of errors found in widget content, custom fields, excerpts and other areas outside of the actual page content. Upgrade to the full version to unlock this feature.', 'wp-ada-compliance-basic' );

	// certain error types are always theme errors.
	$error_types = array( 'skip_nav_links', 'missing_lang_attr', 'multiple_ways_failure', 'missing_landmarks', 'unlabeled_landmarks' );

	// certain error types are always theme errors.
	if ( in_array( $errorcode, $error_types ) ) {
		$source = __( '** theme error', 'wp-ada-compliance-basic' );
	}

	// if location notes are not set yet show probability notes.
	if ( '' == $source ) {
		if ( 10 == $themeerror ) {
			$source = __( '** theme error', 'wp-ada-compliance-basic' );

		} elseif ( 33 == $themeerror ) {
			$source = __( '** theme error', 'wp-ada-compliance-basic' );

		} elseif ( $themeerror > 74 && 'term' != $type && 'theme' != $type ) {
			$source = __( '** theme error', 'wp-ada-compliance-basic' );

		} elseif ( 100 == $themeerror ) {
			$source = __( '** theme error', 'wp-ada-compliance-basic' );

		}
	}

	if ( '' != $source ) {
		if ( 1 == $tags ) {
			$message = '<br /><div class="wp-ada-compliance-location-other wp-ada-compliance-location" ><span class="wp-ada-source-text">' . $source . '</span> <div class="wp-ada-screen-reader-text">' . $details . '<span class="adaIgnored">' . $marketing . '</span></div></div>';
		} else {
			$message = $source;
		}

		return $message;
	}
}

/**
 * Mark all theme errors
 **/
function wp_ada_compliance_basic_mark_theme_errors( $objectvalue, $probability, $type ) {
	global $wpdb;

	$wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'wp_ada_compliance_basic set themeerror = %d where object  = %s and type = %s', $probability, $objectvalue, $type ) );
}
/**
 * Check if post has errors
 **/
function wp_ada_compliance_basic_reported_errors_check( $postid, $type ) {
	global $wpdb;

	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT postid FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where type = %s and postid = %d and ignre != 1', $type, $postid ), ARRAY_A );

	if ( $wpdb->num_rows > 0 ) {
		return 1;
	}

	return 0;
}

/**
 * Insert error
 */
function wp_ada_compliance_basic_insert_error( $postinfo, $errortype, $error, $objectvalue ) {
	global $wpdb;
	$ignre      = 0;
	$themeerror = '';

	$errortype = sanitize_text_field( $errortype );
	$error     = sanitize_text_field( $error );
	$scantype  = sanitize_text_field( $postinfo['scantype'] );
	$postid    = (int) $postinfo['postid'];
	$title     = sanitize_text_field( $postinfo['title'] );
	$type      = sanitize_text_field( $postinfo['type'] );

		// mark theme errors.
	$themeerror = wp_ada_compliance_basic_theme_error_probability_check( $objectvalue, $errortype );
	wp_ada_compliance_basic_mark_theme_errors( $objectvalue, $themeerror, $type );

	if ( isset( $postinfo['taxonomy'] ) ) {
		$taxonomy = sanitize_text_field( $postinfo['taxonomy'] );
	} else {
		$taxonomy = '';
	}
	if ( isset( $postinfo['externalsrc'] ) ) {
		$externalsrc = sanitize_text_field( $postinfo['externalsrc'] );
	} else {
		$externalsrc = '';
	}

	if ( isset( $postinfo['examplecode'] ) && '' !== $postinfo['examplecode'] ) {
		$examplecode = strip_tags( $postinfo['examplecode'], '<div><img>' );
	} else {
		$examplecode = '';
	}

	// set flag for onsave scans.
	if ( 'onsave' == $scantype ) {
		$onsave = 1;
	} else {
		$onsave = 0;
	}

		$ignre = wp_ada_compliance_basic_get_ignore_value( $errortype, $onsave, $type, $objectvalue );

	$timezone = get_option( 'timezone_string' );
	if ( '' == $timezone ) {
		$timezone = 'America/Chicago';
	}
	date_default_timezone_set( $timezone );
	$errordate = gmdate( 'Y-m-d H:i.s', time() + 120 );
	$userid    = sanitize_text_field( get_current_user_id() );
	if ( '' == $userid ) {
		$userid = 'autoscan';
	}

	$wpdb->query( $wpdb->prepare( 'INSERT INTO ' . $wpdb->prefix . 'wp_ada_compliance_basic (postid, object, errorcode, posttitle, type, taxonomy, date, activeuser, ignre, recordcheck, scantype, onsave, externalsrc, examplecode) values(%d,  %s, %s, %s, %s, %s, %s, %s, %d, %d, %s, %s, %s, %s)', $postid, $objectvalue, $errortype, $title, $type, $taxonomy, $errordate, $userid, $ignre, 1, $scantype, $onsave, $externalsrc, $examplecode ) );
	return $wpdb->insert_id;
}

/**
 * Set ignore value
 **/
function wp_ada_compliance_basic_get_ignore_value( $errortype, $onsave, $type, $objectvalue ) {

	$ignre = 0;

	if ( 'img_alt_invalid' == $errortype
	&& ( stristr( $objectvalue, __( 'graphic of', 'wp-ada-compliance-basic' ) )
	|| stristr( $objectvalue, __( 'image of', 'wp-ada-compliance-basic' ) )
	|| stristr( $objectvalue, __( 'photo of', 'wp-ada-compliance-basic' ) ) ) ) {
		$ignre = 2;
	}

	if ( 'dynamic_carousel' == $errortype ) {
		$ignre = 2;
	}
	if ( 'accessibility_help' == $errortype ) {
		$ignre = 2;
	}
	if ( 'tab_order_modified' == $errortype ) {
		$ignre = 2;
	}
	if ( 'empty_icon' == $errortype ) {
		$ignre = 2;
	}
	if ( 'missing_landmarks' == $errortype ) {
		$ignre = 2;
	}
	if ( 'link_color_contrast_failure' == $errortype ) {
		$ignre = 2;
	}
	if ( 'missing_lang_attr' == $errortype ) {
		$ignre = 2;
	}
	if ( 'unlabeled_landmarks' == $errortype ) {
		$ignre = 2;
	}
	if ( 'skip_nav_links' == $errortype ) {
		$ignre = 2;
	}
	if ( 'missing_th' == $errortype && stristr( $objectvalue, 'role-presentation' ) ) {
		$ignre = 2;
	}
	if ( 'redundant_title_tag' == $errortype ) {
		$ignre = 2;
	}
	if ( 'redundant_alt_text' == $errortype ) {
		$ignre = 2;
	}
	if ( 'absolute_fontsize' == $errortype ) {
		$ignre = 2;
	}
	if ( 'text_justified' == $errortype ) {
		$ignre = 2;
	}
	if ( 'empty_heading_tag' == $errortype ) {
		$ignre = 2;
	}
	if ( 'empty_anchor_tag' == $errortype && ! stristr( $objectvalue, '<img' ) && ! stristr( $objectvalue, 'fa-' ) && ! stristr( $objectvalue, '<input' ) && ! stristr( $objectvalue, '<button' ) ) {
		$ignre = 2;
	}
	if ( 'empty_anchor_tag' == $errortype && stristr( $objectvalue, 'elementor-image-or-icon-box' ) ) {
		$ignre = 2;
	}
	if ( 'adjacent_identical_links' == $errortype && stristr( $objectvalue, 'elementor-image-or-icon-box' ) ) {
		$ignre = 2;
	}
	if ( 'new_window_tag' == $errortype ) {
		$ignre = 2;
	}
	if ( stristr( $objectvalue, 'window.open' ) && 'new_window_tag' == $errortype ) {
		$ignre = 0;
	}
	if ( 'av_tag_with_autoplay' == $errortype ) {
		$ignre = 2;
	}
	if ( 'img_linked_to_self' == $errortype ) {
		$ignre = 2;
	}
	if ( 'iframe_missing_title' == $errortype && wp_ada_compliance_basic_check_iframe_for_filtered_src_url( '', $objectvalue ) ) {
		$ignre = 2;
	}
	if ( 'emulating_links' == $errortype ) {
		$ignre = 2;
	}
	if ( 'visual_focus_removed' == $errortype ) {
		$ignre = 2;
	}
	if ( 'missing_onkeypress' == $errortype ) {
		$ignre = 2;
	}
	if ( 'unlinked_anchors' == $errortype && preg_match( '/<a.*?<\/a>(*SKIP)(*F)|[\w-]+@([\w-]+\.)+[\w-]+/i', $objectvalue ) ) {
		$ignre = 2;
	}
	if ( 'link_to_non_html_content' == $errortype ) {
		$ignre = 2;
	}
	if ( 'link_to_in_page_content' == $errortype ) {
		$ignre = 2;
	}
	if ( 'visual_focus_removed' == $errortype && strstr( $objectvalue, 'style=' ) ) {
		$ignre = 2;
	}
	// if ( 'elementor_toc' == $errortype ) {
	// $ignre = 2;
	// }
	if ( 'elementor_toggles' == $errortype ) {
		$ignre = 2;
	}

	return $ignre;
}
/**
 * Ignore error with jquery
 **/
function wp_ada_compliance_basic_jquery_ignore_error( $id, $direction ) {
	global $wpdb;

	if ( ! current_user_can( 'edit_pages' ) ) {
		return 1;
	}

	$wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'wp_ada_compliance_basic set ignre = %d where id  = %d ', $direction, $id ) );
}
/**
 * Ignore error
 */
function wp_ada_compliance_basic_ignore_error( $id ) {
	global $wpdb;

	if ( ! current_user_can( 'edit_pages' ) ) {
		return 1;
	}

	// cancel ignore.
	if ( isset( $_REQUEST['canxignore'] ) && isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ) ) ) {
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'wp_ada_compliance_basic set ignre = %d where id  = %d ', 0, $id ) );
		$_SESSION['my_ada_important_notices'] = __( 'The selected error is no longer being ignored.', 'wp-ada-compliance-basic' );
	} else {
		// ignore just this id.
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'wp_ada_compliance_basic set ignre = %d where id  = %d ', 1, $id ) );

		$_SESSION['my_ada_important_notices'] = __( 'The selected error is now being ignored.', 'wp-ada-compliance-basic' );
	}
}
/**
 * Check ignore status
 **/
function wp_ada_compliance_basic_ignore_check( $errortype, $postid, $objectvalue, $type ) {
	global $wpdb;
	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where type= %s and postid = %d and errorcode = %s and object = %s and ignre = %d', $type, $postid, $errortype, $objectvalue, 1 ), ARRAY_A );

	if ( $results ) {
		return 1;
	}
}

/**
 * Check if font awesome link can be corrected
 **/
function wp_ada_compliance_basic_check_if_font_awesome_link_can_be_corrected( $content ) {

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( $content );

	$links = $dom->getElementsByTagName( 'a' );

	foreach ( $links as $link ) {

		$elements = $link->getElementsByTagName( '*' );
		foreach ( $elements as $i ) {
			if ( isset( $i ) && '' == strip_tags( trim( $link->nodeValue ) ) ) {
				if ( stristr( $i->getAttribute( 'class' ), 'fa-' ) ) {
					return 1;
				}
			}
		}
	}

	return 0;
}


/**
 * Retrieve aria value and return.
 **/
function wp_ada_compliance_basic_get_aria_values( $dom, $element, $field ) {
	$label = '';
	if ( 'aria-labelledby' == $field && '' != $element->getAttribute( 'aria-labelledby' ) ) {
		$ariaids = explode( ' ', $element->getAttribute( 'aria-labelledby' ) );

		foreach ( $ariaids as $ariaid ) {
			if ( isset( $dom->getElementById( $ariaid )->plaintext ) && '' != trim( $dom->getElementById( $ariaid )->plaintext ) ) {
				$label .= trim( $dom->getElementById( $ariaid )->plaintext ) . ' ';
			}
		}

		return trim( $label );
	}

	if ( 'aria-describedby' == $field && '' != $element->getAttribute( 'aria-describedby' ) ) {
		$ariaids = explode( ' ', $element->getAttribute( 'aria-describedby' ) );
		foreach ( $ariaids as $ariaid ) {
			if ( isset( $dom->getElementById( $ariaid )->plaintext ) && '' != trim( $dom->getElementById( $ariaid )->plaintext ) ) {
				$label .= trim( $dom->getElementById( $ariaid )->plaintext ) . ' ';
			}
		}

		return trim( $label );
	}

	return;
}

/**
 * Get error list for affected post
 **/
function wp_ada_compliance_basic_get_error_list_for_post( $postid, $posttype ) {
	global $wpdb;

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$errornotices = '';

	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT distinct errorcode FROM ' . $wpdb->prefix . 'wp_ada_compliance_basic where postid = %d and type = %s and scantype = %s and ignre != %d', $postid, $posttype, 'onsave', '1' ), ARRAY_A );

	foreach ( $results as $row ) {
		$errorcode         = $row['errorcode'];
			$errornotices .= '<p>';
			$errornotices .= $wp_ada_compliance_basic_def[ $errorcode ]['DisplayError'];
		if ( '' != $wp_ada_compliance_basic_def[ $errorcode ]['Reference'] ) {
			$errornotices .= ' <a href="' . $wp_ada_compliance_basic_def[ $errorcode ]['ReferenceURL'] . '" target="_blank" class="adaNewWindowInfo">' . $wp_ada_compliance_basic_def[ $errorcode ]['Reference'] . '<i class="fas fa-external-link-alt" aria-hidden="true"><span class="wp_ada_hidden">' . __( 'opens in a new window', 'wp-ada-compliance-basic' ) . '</span></i></a>';
		}
		$nonce             = wp_create_nonce( 'wp-ada-compliance-nonce' );
			$errornotices .= '<a href="' . esc_url( get_site_url() ) . '/wp-admin/admin.php?page=ada_compliance/compliancereportbasic.php&_wpnonce=' . esc_attr( $nonce ) . '&view=1&errorid=' . esc_attr( $postid ) . '&type=' . esc_attr( $posttype ) . '&iframe=1&TB_iframe=true&width=900&height=550" class="thickbox adaNewWindowInfo adaErrorText adareportlink" target="_blank"><i class="fas fa-eye" aria-hidden="true"></i>';
			$errornotices .= __( 'View Accessibility Report for Help Options', 'wp-ada-compliance-basic' );
			$errornotices .= '</a>';
			$errornotices .= '</p>';
	}
	return $errornotices;
}
