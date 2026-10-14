<?php
/**
 * Functions related to renaming media files.
 * 
 * @since 4.5
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

/**
 * Function to check if a media file was renamed or not.
 * 
 * The GUID of a media file will contain the original filename. Comparing current
 * filename with the GUID can tell if the file was renamed or not.
 * 
 * @param $image_id (int) ID of the image to check.
 * @return (boolean) True if the media file was renamed. False otherwise.
 */
function iaffpro_is_attachment_renamed( $image_id ) {

	// Check if the ID belongs to an attachment.
	// Leaving this check behind as of now to make the process more efficient.
	/* if ( ! wp_attachment_is_image( $image_id ) ) {
		return false;
	} */

	// Get filename saved in GUID.
	$image_guid 			= get_the_guid( $image_id );
	$image_guid_extension 	= pathinfo( $image_guid );
	$image_guid_name		= basename( $image_guid, '.' . $image_guid_extension['extension'] );

	// Get image filename from attachment URL.
	$image_url			= wp_get_attachment_url( $image_id );
	$image_extension 	= pathinfo( $image_url );
	$image_name 		= basename( $image_url, '.' . $image_extension['extension'] );

	// If file name in GUID and attachment URL are same, then image was not renamed.
	if ( strcmp( $image_guid_name, $image_name ) === 0 ) {
		return false;
	}

	return true;
}