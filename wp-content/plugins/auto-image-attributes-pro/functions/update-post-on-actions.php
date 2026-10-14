<?php
/**
 * Functions to update post HTML while updating Media Library or posts.
 * 
 * @since 4.3
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

// Fired when an image is updated in the Media Library. Copy image attributes to post HTML.
add_action( 'attachment_fields_to_save', 'iaffpro_attachment_fields_to_save_update_image_attributes_in_post' );

// Save image caption added in the Gutenberg editor to the Media Library for new image uploads.
add_action( 'save_post', 'iaffpro_save_post_gutenberg_caption_to_media_library' );

// Fired when a post is published or updated. Used here to update image attributes on publish / update.
add_action( 'save_post', 'iaffpro_save_post_update_image_attributes', PHP_INT_MAX );

/**
 * Copy image attributes to post HTML while updating in Media Library.
 * 
 * @since 4.3
 * @since 4.9 Check using wp_attachment_is_image() is added.
 * 
 * @param $post (array) An array of post data (image data).
 */
function iaffpro_attachment_fields_to_save_update_image_attributes_in_post( $post ) {

	// Return if attachment is not an image.
	if( ! wp_attachment_is_image( $post['ID'] ) ) {
		return $post;
	}

	// Return if option is not enabled in the UI.
	$settings = iaff_get_settings();
    if ( ! isset( $settings['copy_attachment_to_post' ] ) ) {
        return $post;
    }

    // Prevent updating the Media Library so that attributes in the Media Library are not overwritten.
	add_filter( 'iaffpro_update_media_library', '__return_false' );

	/**
	 * Save $post in a transient to read $post['post_title'] in iaffpro_attachment_fields_to_save_override_attributes_with_media_library.
	 * 
	 * At this point when attachment_fields_to_save is fired, the new image title is not saved in the database yet. 
	 * While filtering iaffpro_image_attributes the new image title will not be available, but it is available in $post['post_title'].
	 */
	set_transient( 'iaffpro_update_image_attributes_in_post_new_post', $post );

    // Override image attributes generated with the ones in the Media Library.
	add_filter( 'iaffpro_image_attributes', 'iaffpro_attachment_fields_to_save_override_attributes_with_media_library', 10, 3 );

	// Get the object of the image form image ID.
	$image_object = get_post( $post['ID'] );

	if ( ! is_object( $image_object ) ) {
		return $post;
	}

	/**
	 * Action hook that is fired at the start of the Bulk Updater before updating any image.
	 * 
	 * @link https://imageattributespro.com/codex/iaff_before_bulk_updater/
	 */
	do_action( 'iaff_before_bulk_updater' );

	// Run the pro module. 
	iaffpro_auto_image_attributes_pro( $image_object, true );

	/**
	 * Action hook that is fired at the end of the Bulk Updater after updating all images.
	 * 
	 * @link https://imageattributespro.com/codex/iaff_after_bulk_updater/
	 */
	do_action( 'iaff_after_bulk_updater' );

	delete_transient( 'iaffpro_update_image_attributes_in_post_new_post' );

	return $post;
}

/**
 * Override image attributes generated as per image attribute settings in Advanced tab with the ones in the Media Library.
 * 
 * @since 4.3
 *
 * @param $attributes (array) Associative array of image attributes.
 * @param $image_id (int) ID of the current image.
 * @param $parent_post_id (int) ID of the post the image is inserted into. 0 for images not attached to a post.
 * 
 * @return $attributes (array) Associative array with the modified image attributes.
 */
function iaffpro_attachment_fields_to_save_override_attributes_with_media_library( $attributes, $image_id, $parent_post_id ) {

	// Retrieve the image object.
	$attachment = get_post( $image_id );
	
	if ( ! is_object( $attachment ) ) {
		return $attributes;
	}

	$post_title = $attachment->post_title;
	
	$post = get_transient( 'iaffpro_update_image_attributes_in_post_new_post' );
	
	if ( $post['ID'] == $image_id ) {
		$post_title = $post['post_title'];
	}

	$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

	$attributes['title'] = $post_title;
	$attributes['alt_text'] = $alt_text !== false ? $alt_text : $attributes['alt_text'];
	$attributes['caption'] = $attachment->post_excerpt;
	$attributes['description'] = $attachment->post_content;
	
	return $attributes;
}

/**
 * Save image caption added in the Gutenberg editor to the Media Library for new image uploads.
 * 
 * In the classic editor when a new image is uploaded, the caption typed in at the time of uploading is saved in the Media Library.
 * However, in Gutenberg, the caption added is only added to the post HTML and does not become part of the Media Library.
 * The following code copies the caption (if added) to the Media Library if the caption in the Media Library is empty.
 * 
 * @since 4.3
 * 
 * @param $post_id (integer) ID of the post revision.
 */
function iaffpro_save_post_gutenberg_caption_to_media_library( $post_id ) {

	// Do not update if updates in Media Library are disabled via filter.
	if ( ! apply_filters( 'iaffpro_update_media_library', true ) ) {
		return;
	}

	// Return if an older version of WordPress is used. Gutenberg and has_blocks() was introduced in 5.0.
	if ( ! function_exists( 'has_blocks' ) ) {
		return;
	}

	// post_id from save_post will be the ID of the revision. Finding parent post_id.
	if ( $post_parent_id = wp_get_post_parent_id( $post_id ) ) {
		$post_id = $post_parent_id;
	}

	$post = get_post( $post_id );

	// Check if the post contains Gutenberg blocks
	if ( ! has_blocks( $post->post_content ) ) {
		return;
	}

	$blocks = parse_blocks( $post->post_content );

	// Loop through each block and check for image blocks.
	foreach ( $blocks as $block ) {

		if ( $block['blockName'] !== 'core/image' ) {
			continue;
		}

		$image_id = isset( $block['attrs']['id'] ) ? $block['attrs']['id'] : 0;

		// Do not overwrite existing captions in the Media Library.
		$attachment = get_post( $image_id );
		if ( ! is_object( $attachment ) || ! empty( $attachment->post_excerpt ) ) {
			continue;
		}

		preg_match( '/<figcaption .+>(.+)<\/figcaption>/', $block['innerHTML'], $caption );

		// Add caption to Media Library.
		if ( ! empty( $caption[1] ) ) {
			$attachment->post_excerpt = $caption[1];

			wp_update_post( $attachment );
		}
	}
}

/**
 * Update image attributes as when a post is published or updated.
 * 
 * @since 4.3
 * 
 * @param $post_id (integer) ID of the post revision.
 */
function iaffpro_save_post_update_image_attributes( $post_id ) {

	if ( ! function_exists( 'iaff_get_settings' ) ) {
		return;
	}

	// Get Settings
	$settings = iaff_get_settings();

	// Return if option is not enabled in the UI.
    if ( ! isset( $settings['update_attributes_on_save_post' ] ) ) {
        return;
    }

	// post_id from save_post will be the ID of the revision. Finding parent post_id.
	if ( $post_parent_id = wp_get_post_parent_id( $post_id ) ) {
		$post_id = $post_parent_id;
	}

	/**
	 * iaffpro_update_attributes_in_post_by_post_id() will call save_post again, thus causing an infinite loop.
	 * To prevent this, unhook action.
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/save_post/#avoiding-infinite-loops
	 */
	remove_action( 'save_post', 'iaffpro_save_post_update_image_attributes', PHP_INT_MAX );

	/**
	 * Action hook that is fired at the start of the Bulk Updater before updating any image.
	 * 
	 * @link https://imageattributespro.com/codex/iaff_before_bulk_updater/
	 */
	do_action( 'iaff_before_bulk_updater' );

  	// Update image attributes.
	iaffpro_update_attributes_in_post_by_post_id( $post_id );

	/**
	 * Action hook that is fired at the end of the Bulk Updater after updating all images.
	 * 
	 * @link https://imageattributespro.com/codex/iaff_after_bulk_updater/
	 */
	do_action( 'iaff_after_bulk_updater' );
}