<?php
/**
 * FUNCTIONS
 **/

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Validate audio or video tags missing track tags
 **/
function wp_ada_compliance_basic_validate_av_tags_missing_track( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'av_tags_missing_track', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	// check iframe tags.
	$iframes = $dom->find( 'iframe' );
	foreach ( $iframes as $iframe ) {
		$videocode = $iframe->outertext;
		if ( isset( $iframe ) && (

			strstr( $videocode, '.mp4' )
			|| stristr( $videocode, '.m4a' )
			|| stristr( $videocode, '.ogv' )
			|| stristr( $videocode, '.mp3' )
			|| stristr( $videocode, '.webm' )
			|| stristr( $videocode, '.flv' )
			|| strstr( $videocode, '.vtt' )

			|| strstr( $videocode, '.vob' )
			|| strstr( $videocode, '.ogg' )
			|| strstr( $videocode, '.ogv' )
			|| strstr( $videocode, '.wmv' )
			|| strstr( $videocode, '.avi' )
			|| strstr( $videocode, '.m4v' )
			|| strstr( $videocode, '.mov' )
			|| strstr( $videocode, '.swf' )
			|| strstr( $videocode, '.mpeg' )
			|| strstr( $videocode, '.asf' )
			|| strstr( $videocode, '.wav' )
			|| strstr( $videocode, '.wma' )
			|| strstr( $videocode, '.mid' )
			|| strstr( $videocode, '.midi' )
			|| strstr( $videocode, '.au' )
			|| strstr( $videocode, '.aiff' )
			|| strstr( $videocode, '.qt' )

			) ) {
			// save error.
			if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'av_tags_missing_track', $videocode ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'av_tags_missing_track', $wp_ada_compliance_basic_def['av_tags_missing_track']['StoredError'], $videocode );
			}
		}
	}

	// check video tag.
	$videos = $dom->find( 'video' );
	foreach ( $videos as $video ) {
		$videocode = $video->outertext;
		if ( isset( $video ) && ! strstr( $videocode, '<track' ) ) {
			// save error.
			if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'av_tags_missing_track', $videocode ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'av_tags_missing_track', $wp_ada_compliance_basic_def['av_tags_missing_track']['StoredError'], $videocode );
			}
		}
	}

	// validate audio tags.
	$audios = $dom->find( 'audio' );
	foreach ( $audios as $audio ) {
		$audiocode = $audio->outertext;
		if ( isset( $audio ) && ! strstr( $audiocode, '<track' ) ) {

			// save error.
			if ( ! $insertid = wp_ada_compliance_basic_error_check( $postinfo, 'av_tags_missing_track', $audiocode ) ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'av_tags_missing_track', $wp_ada_compliance_basic_def['av_tags_missing_track']['StoredError'], $audiocode );
			}
		}
	}// end audio validate.
	return 1;
}
