<?php
/**
 * FUNCTIONS
 * **/

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * Check video with autoplay
 **/
function wp_ada_compliance_basic_validate_av_tag_with_autoplay( $content, $postinfo ) {

	$wp_ada_compliance_basic_def = wp_ada_compliance_basic_def();

	$dom = str_get_html( $content );

	// get options.
	$wp_ada_compliance_basic_scanoptions = get_option( 'wp_ada_compliance_basic_ignore_scan_rules', array() );

	// check if being scanned.
	if ( in_array( 'av_tag_with_autoplay', $wp_ada_compliance_basic_scanoptions ) ) {
		return 1;
	}

	$videos = $dom->find( 'video' );
	foreach ( $videos as $video ) {
		$videocode = $video->outertext;
		if ( isset( $video ) && ( stristr( $videocode, 'autoplay' ) && ! stristr( $videocode, 'muted' ) ) ) {
			// save error.
			$insertid = wp_ada_compliance_basic_error_check( $postinfo, 'av_tag_with_autoplay', $videocode );
			if ( ! $insertid ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'av_tag_with_autoplay', $wp_ada_compliance_basic_def['av_tag_with_autoplay']['StoredError'], $videocode );
			}
		}
	}
	// check audio tags for autoplay.
	$audios = $dom->find( 'audio' );
	foreach ( $audios as $audio ) {
		$audiocode = $audio->outertext;
		if ( isset( $audio ) && ( strstr( $audiocode, 'autoplay' ) && ! strstr( $audiocode, 'muted' ) ) ) {
			// save error.
			$insertid = wp_ada_compliance_basic_error_check( $postinfo, 'av_tag_with_autoplay', $audiocode );
			if ( ! $insertid ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'av_tag_with_autoplay', $wp_ada_compliance_basic_def['av_tag_with_autoplay']['StoredError'], $audiocode );
			}
		}
	}
	// Check object tags for auto play.
	$objects = $dom->find( 'object' );
	foreach ( $objects as $object ) {
		$nodes = $object->children;
		foreach ( $nodes as $node ) {
			if ( 'param' == $node->tag ) {

				if ( ( 'autoplay' == strtolower( $node->getAttribute( 'name' ) ) && 'true' == strtolower( $node->getAttribute( 'value' ) ) ) || ( 'flashvars' == strtolower( $node->getAttribute( 'name' ) )
				&& stristr( $node->getAttribute( 'value' ), 'autoPlay=true' ) ) ) {

					$objectcode = $object->outertext;

					// save error.
					$insertid = wp_ada_compliance_basic_error_check( $postinfo, 'av_tag_with_autoplay', $objectcode );
					if ( ! $insertid ) {
						$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'av_tag_with_autoplay', $wp_ada_compliance_basic_def['av_tag_with_autoplay']['StoredError'], $objectcode );
					}
				}
			}
		}
	}

	// check embed tags for autostart.
	$embeds = $dom->find( 'embed' );
	foreach ( $embeds as $embed ) {
		$embedcode = $embed->outertext;
		if ( isset( $embed ) && 'true' == $embed->getAttribute( 'autostart' ) ) {
			// save error.
			$insertid = wp_ada_compliance_basic_error_check( $postinfo, 'av_tag_with_autoplay', $embedcode );
			if ( ! $insertid ) {
				$insertid = wp_ada_compliance_basic_insert_error( $postinfo, 'av_tag_with_autoplay', $wp_ada_compliance_basic_def['av_tag_with_autoplay']['StoredError'], $embedcode );
			}
		}
	}

	return 1;
}
