<?php
/**
 * Functions for compatibility with Divi Theme
 * 
 * @link https://www.elegantthemes.com/gallery/divi/
 *
 * @since 4.2
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) exit;

// Get current theme object and get the name of the parent theme if a parent theme exist.
$current_theme  = wp_get_theme();
$parent_theme   = $current_theme->parent() !== false ? $current_theme->parent()->get( 'Name' ) : '';

// Exit if Divi or a child theme of Divi is not active.
if ( ! ( $current_theme->get( 'Name' ) === 'Divi' || $parent_theme === 'Divi' ) ) {
    return;
}

// Update attributes of images in Divi's custom markup while updating the attributes of a post.
add_action( 'iaffpro_after_update_attributes_in_post', 'iaffpro_divi_update_attributes_in_post' );

/**
 * Update attributes in Divi's image markup.
 * 
 * This function is hooked on to iaffpro_after_update_attributes_in_post action in iaffpro_update_attributes_in_post_by_post_id().
 * 
 * @since 4.2
 * 
 * @param $post_id (integer) ID of the post that is being updated in iaffpro_update_attributes_in_post_by_post_id()
 */
function iaffpro_divi_update_attributes_in_post( $post_id ) {

    // Retrieve the post
	$post = get_post( $post_id );

	if ( $post === NULL ) {
		return;
	}

	/**
	 * Store $post_id in a transient to send to iaffpro_update_attributes_in_post_helper().
	 * $post_id is required to generate image attributes using iaffpro_generate_image_attributes() in iaffpro_update_attributes_in_post_helper().
	 */
	set_transient( 'iaffpro_current_post_id', $post_id );

	// Update images that uses proprietary Divi tag et_pb_image
	$updated_content = preg_replace_callback( '/\[et_pb_image[^\]]+/', 'iaffpro_divi_update_attributes_in_post_helper_et_pb_image' , $post->post_content );

	// Update images that uses proprietary Divi tag et_pb_blurb
	$updated_content = preg_replace_callback( '/\[et_pb_blurb[^\]]+/', 'iaffpro_divi_update_attributes_in_post_helper_et_pb_blurb' , $updated_content );

	delete_transient( 'iaffpro_current_post_id' );

	// Update post back into the database
	$updated_post = array(
		'ID'           	=> $post->ID,
		'post_content'	=> $updated_content,
	);

	// Update the post into the database
	wp_update_post( wp_slash( $updated_post ) );
    
    delete_transient( 'iaffpro_current_post_id' );
}

/**
 * Replace Divi's proprietary image tags with standard ones before sending for processing.
 * 
 * - Divi uses [et_pb_image tag instead of <img tag
 * - And title_text instead of title for image title.
 * 
 * Example: [et_pb_image src="../example.jpeg" alt="Alt" title_text="Title" _builder_version="4.20.2" _module_preset="default" global_colors_info="{}"
 * 
 * @since 4.2
 * 
 * @param $match (array) $match passed from preg_replace_callback() function.
 * @return $updated_content (string) Image markup with image attributes updated. Markup is reverted to Divi's proprietary format.
 */
function iaffpro_divi_update_attributes_in_post_helper_et_pb_image( $match ) {

    $match[0] = str_replace( '[et_pb_image', '<img', $match[0] );
    $match[0] = str_replace( 'title_text=', 'title=', $match[0] );

    $updated_content = iaffpro_update_attributes_in_post_helper( $match );

    $updated_content = str_replace( '<img', '[et_pb_image', $updated_content );
    $updated_content = str_replace( 'title=', 'title_text=', $updated_content );

    return $updated_content;
}

/**
 * Replace Divi's proprietary Blurb tags with standard image tags before sending for processing.
 * 
 * - Divi uses [et_pb_blurb tag instead of <img tag
 * - And image= instead of src= for image src 
 * - And title_text= instead of title= for image title. 
 * - Note: title= is the title of the blurb. What a mess!
 * 
 * Example: [et_pb_blurb title="Blurb Title" image="../example.jpg" _builder_version="4.27.4" _module_preset="default" theme_builder_area="post_content" alt="Image Alt" title_text="Image Title" hover_enabled="0" sticky_enabled="0"]
 * 
 * @since 4.7
 * 
 * @param $match (array) $match passed from preg_replace_callback() function.
 * @return $updated_content (string) Image markup with image attributes updated. Markup is reverted to Divi's proprietary format.
 */
function iaffpro_divi_update_attributes_in_post_helper_et_pb_blurb( $match ) {
	
	$match[0] = str_replace( '[et_pb_blurb', '<img', $match[0] );
	$match[0] = str_replace( 'image=', 'src=', $match[0] ); // image src
	$match[0] = str_replace( 'title=', '_IAP_TITLE_PH_=', $match[0] ); // Preserve blurb title
	$match[0] = str_replace( 'title_text=', 'title=', $match[0] ); // Image title
	$match[0] = str_replace( '<br>', '_IAP_BR_PH_', $match[0] ); // Preserve <br>
	
	$updated_content = iaffpro_update_attributes_in_post_helper( $match );

	$updated_content = str_replace( '<img', '[et_pb_blurb', $updated_content );
	$updated_content = str_replace( 'src=', 'image=', $updated_content );
	$updated_content = str_replace( 'title=', 'title_text=', $updated_content );
	$updated_content = str_replace( '_IAP_TITLE_PH_=', 'title=', $updated_content );
	$updated_content = str_replace( '_IAP_BR_PH_', '<br>', $updated_content );
	
	return $updated_content;
}